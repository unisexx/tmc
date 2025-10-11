<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AssessmentForm;
use App\Models\AssessmentServiceUnitLevel;
use App\Models\AssessmentSuggestion;
use App\Models\ServiceUnit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SelfAssessmentServiceUnitLevelController extends Controller
{
    public function __construct()
    {
        // $this->middleware('permission:assessment.view', ['only' => ['index','show']]);
        // $this->middleware('permission:assessment.create', ['only' => ['create','store']]);
        // $this->middleware('permission:assessment.approve', ['only' => ['approveForm','approve']]);
    }

    /* =========================================================
    | 1) INDEX
    ==========================================================*/
    public function index(Request $req)
    {
        $unitId = $this->activeServiceUnitId();

        if (!$unitId) {
            // เหมือนตัวอย่าง edit(): แจ้งเตือนและย้อนกลับ
            flash_notify('กรุณาเลือกหน่วยบริการจากเมนูด้านบน', 'warning');

            // เผื่อกรณีไม่มี referer ให้กลับไป dashboard
            return url()->previous()
                ? redirect()->back()
                : redirect()->route('backend.dashboard');
        }

        $q = AssessmentServiceUnitLevel::with([
            'serviceUnit:id,org_name,org_province_code,org_district_code,org_subdistrict_code,org_postcode',
            'serviceUnit.province:code,title',
            'serviceUnit.district:code,title',
            'serviceUnit.subdistrict:code,title',
            'user:id,name',
            'approver:id,name',
        ])
            ->where('service_unit_id', $unitId)
            ->latest('id');

        if ($kw = trim($req->get('q', ''))) {
            $q->where(function ($qq) use ($kw) {
                $qq->whereHas('serviceUnit', fn($s) => $s->where('org_name', 'like', "%{$kw}%"))
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$kw}%"));
            });
        }

        if ($year = $req->get('year')) {$q->where('assess_year', $this->normalizeYearToCE($year));}
        if ($round = $req->get('round')) {$q->where('assess_round', (int) $round);}
        if ($lv = $req->get('level')) {$q->where('level', $lv);}
        if ($st = $req->get('status')) {$q->where('status', $st);}
        if ($ap = $req->get('approval')) {$q->where('approval_status', $ap);}

        $rows = $q->paginate(20)->appends($req->query());

        return view('backend.self_assessment_service_unit_level.index', compact('rows'));
    }

    /* =========================================================
    | 2) CREATE (STEP 1)
    ==========================================================*/
    public function create()
    {
        $unitId = $this->activeServiceUnitId();
        if (!$unitId) {
            return redirect()->route('backend.assessment.index')
                ->withErrors(['service_unit_id' => 'กรุณาเลือกหน่วยบริการจากเมนูด้านบน']);
        }

        /* ⬇️ คำนวณ "รอบปัจจุบัน" */
        $yearCE  = $this->currentFiscalYearCE(); // ปีงบประมาณ (ค.ศ.)
        $roundNo = $this->currentAssessRound();  // รอบที่ 1 หรือ 2

        // ⬇️ กันซ้ำ: ถ้ามีแบบประเมินปี/รอบปัจจุบันอยู่แล้ว ไม่ให้สร้างใหม่
        $existing = AssessmentServiceUnitLevel::where('service_unit_id', $unitId)
            ->where('assess_year', $yearCE)
            ->where('assess_round', $roundNo)
            ->latest('id')
            ->first();

        if ($existing) {
            // แจ้งเตือนแบบ sweetalert แล้วพากลับไปหน้าแก้ไข/สรุป ที่มีอยู่เดิม
            $yearBE = $yearCE + 543;
            flash_confirm(
                "หน่วยบริการนี้ได้ทำแบบประเมิน ปีงบ {$yearBE} รอบที่ {$roundNo} แล้ว",
                'warning',
                ['subText' => 'ท่านยังสามารถกดแก้ไขรายการประเมินนั้นได้ จนกว่าจะกดส่งให้ สคร./สสจ. ตรวจสอบ']
            );
            return url()->previous()
                ? redirect()->back()
                : redirect()->route('backend.self-assessment-service-unit-level.index');

            // จะพากลับไปหน้าแก้ไขขั้นที่ 1 ที่มีอยู่ หรือจะไปหน้า Component ก็ได้
            // return redirect()->route('backend.self-assessment-service-unit-level.edit', $existing->id);
            // หรือถ้าต้องการพาไป Step2 เลย ให้ใช้:
            // return redirect()->route('backend.self-assessment-component.create', $existing->id);
        }

        $serviceUnit = ServiceUnit::find($unitId);
        return view('backend.self_assessment_service_unit_level.create', compact('serviceUnit'));
    }

    /* =========================================================
    | 3) STORE (STEP 1)
    |   - บันทึกเป็น draft ที่ parent
    |   - การ "ส่ง" จะทำที่ Step2 (ComponentController::save -> submit)
    ==========================================================*/
    public function store(Request $req)
    {
        $serviceUnitId = $this->activeServiceUnitId();
        if (!$serviceUnitId) {
            return back()->withErrors(['service_unit_id' => 'กรุณาเลือกหน่วยบริการก่อนบันทึก'])->withInput();
        }

        $data = $req->validate([
            'assess_year'  => ['nullable', 'integer'],
            'assess_round' => ['nullable', 'integer', Rule::in([1, 2])],
            'fiscalYear'   => ['nullable', 'string', 'max:4'],
            'round'        => ['nullable', 'integer', Rule::in([1, 2])],
            'q1'           => ['required', Rule::in(['have', 'none'])],
            'q2'           => ['nullable', Rule::in(['tm', 'other'])],
            'q31'          => ['nullable', Rule::in(['yes', 'no'])],
            'q32'          => ['nullable', Rule::in(['yes', 'no'])],
            'q4'           => ['nullable', Rule::in(['can', 'cannot'])],
        ], ['q1.required' => 'กรุณาเลือกข้อ 1']);

        $yearCE  = $data['assess_year'] ?? $this->normalizeYearToCE($data['fiscalYear'] ?? null) ?? (int) date('Y');
        $roundNo = $data['assess_round'] ?? ($data['round'] ?? 1);

        $computedLevel = $this->computeLevel($data['q1'] ?? null, $data['q2'] ?? null, $data['q31'] ?? null, $data['q32'] ?? null, $data['q4'] ?? null);
        if (!$computedLevel) {
            return back()->withErrors(['level' => 'กรุณาตอบแบบประเมินให้ครบตามเงื่อนไขเพื่อสรุประดับ'])->withInput();
        }

        $record = AssessmentServiceUnitLevel::updateOrCreate(
            ['service_unit_id' => $serviceUnitId, 'assess_year' => (int) $yearCE, 'assess_round' => (int) $roundNo],
            [
                'user_id'         => Auth::id(),
                'status'          => 'draft', // ⬅ เปลี่ยนเป็น draft
                'last_question'   => 'done',
                'q1'              => $data['q1'] ?? null,
                'q2'              => $data['q2'] ?? null,
                'q31'             => $data['q31'] ?? null,
                'q32'             => $data['q32'] ?? null,
                'q4'              => $data['q4'] ?? null,
                'level'           => $computedLevel,
                'decided_at'      => now(),
                'approval_status' => null, // ⬅ ยังไม่ส่ง
                'created_by'      => Auth::id(),
                'updated_by'      => Auth::id(),
                'submitted_by'    => null, // ⬅ ยังไม่ส่ง
                'submitted_at'    => null, // ⬅ ยังไม่ส่ง
                'ip_address'      => $req->ip(),
                'user_agent'      => substr((string) $req->header('User-Agent'), 0, 255),
            ]
        );

        return redirect()
            ->route('backend.self-assessment-component.create', $record->id)
            ->with('success', 'บันทึกผลคัดกรองขั้นที่ 1 สำเร็จ กรุณาประเมิน 6 องค์ประกอบ');
    }

    /* =========================================================
    | 5) EDIT/UPDATE (STEP 1)
    |   - กันแก้ไขเมื่อ parent เคยส่งแล้ว/กำลังตรวจ/ปิดจบ
    ==========================================================*/
    public function edit($id)
    {
        $row = AssessmentServiceUnitLevel::with(['serviceUnit', 'user', 'approver'])->findOrFail($id);
        return view('backend.self_assessment_service_unit_level.edit', compact('row'));
    }

    public function update(Request $req, $id)
    {
        $row = AssessmentServiceUnitLevel::findOrFail($id);

        // ⬇ กันแก้ไขเมื่อถูกส่งหรืออยู่ในสถานะที่ล็อกแล้ว
        if (in_array($row->approval_status, ['pending', 'reviewing', 'approved', 'rejected'], true)) {
            return back()->with('warning', 'รายการนี้ถูกส่งหรืออยู่ระหว่างการพิจารณาแล้ว ไม่สามารถแก้ไขขั้นที่ 1 ได้');
        }

        $data = $req->validate([
            'assess_year'  => ['nullable', 'integer'],
            'assess_round' => ['nullable', 'integer', Rule::in([1, 2])],
            'fiscalYear'   => ['nullable', 'string', 'max:4'],
            'round'        => ['nullable', 'integer', Rule::in([1, 2])],
            'q1'           => ['required', Rule::in(['have', 'none'])],
            'q2'           => ['nullable', Rule::in(['tm', 'other'])],
            'q31'          => ['nullable', Rule::in(['yes', 'no'])],
            'q32'          => ['nullable', Rule::in(['yes', 'no'])],
            'q4'           => ['nullable', Rule::in(['can', 'cannot'])],
        ], ['q1.required' => 'กรุณาเลือกข้อ 1']);

        $yearCE  = $data['assess_year'] ?? $this->normalizeYearToCE($data['fiscalYear'] ?? null) ?? $row->assess_year;
        $roundNo = $data['assess_round'] ?? ($data['round'] ?? $row->assess_round);

        $computedLevel = $this->computeLevel($data['q1'] ?? null, $data['q2'] ?? null, $data['q31'] ?? null, $data['q32'] ?? null, $data['q4'] ?? null);
        if (!$computedLevel) {
            return back()->withErrors(['level' => 'กรุณาตอบแบบประเมินให้ครบตามเงื่อนไขเพื่อสรุประดับ'])->withInput();
        }

        $row->fill([
            'assess_year'  => (int) $yearCE,
            'assess_round' => (int) $roundNo,
            'q1'           => $data['q1'] ?? null,
            'q2'           => $data['q2'] ?? null,
            'q31'          => $data['q31'] ?? null,
            'q32'          => $data['q32'] ?? null,
            'q4'           => $data['q4'] ?? null,
            'level'        => $computedLevel,
            'decided_at'   => now(),
            'updated_by'   => Auth::id(),
            // สถานะยังคง draft/returned จนกว่าจะกดส่งใน Step2
        ])->save();

        return redirect()
            ->route('backend.self-assessment-component.create', $row->id)
            ->with('success', 'อัปเดตรอบประเมินขั้นที่ 1 สำเร็จ กรุณาประเมิน 6 องค์ประกอบ');
    }

    /* =========================================================
    | 7) DELETE
    ==========================================================*/
    public function destroy($id)
    {
        $row = AssessmentServiceUnitLevel::findOrFail($id);
        $row->delete();
        flash_notify('ลบรายการสำเร็จ', 'success');
        return redirect()->route('backend.self-assessment-service_unit_level.index');
    }

    /* =========================================================
    | Helpers
    ==========================================================*/
    private function normalizeYearToCE($year): ?int
    {
        if (empty($year)) {
            return null;
        }
        $y = (int) $year;
        return $y > 2400 ? $y - 543 : $y;
    }

    private function computeLevel(?string $q1, ?string $q2, ?string $q31, ?string $q32, ?string $q4): ?string
    {
        if ($q1 === 'none') {
            return 'basic';
        }
        if ($q1 === 'have') {
            if ($q2 === 'tm') {
                if ($q31 === 'no') {
                    return 'basic';
                }

                if ($q31 === 'yes') {
                    return $q4 === 'can' ? 'advanced' : ($q4 === 'cannot' ? 'medium' : null);
                }
                return null;
            }
            if ($q2 === 'other') {
                return $q32 === 'yes' ? 'medium' : ($q32 === 'no' ? 'basic' : null);
            }
            return null;
        }
        return null;
    }

    private function activeServiceUnitId(): ?int
    {
        $user      = Auth::user();
        $sessionId = (int) session('current_service_unit_id');

        // ถ้ามี session แล้ว
        if ($sessionId) {
            // แอดมิน: ใช้หน่วยจาก session ได้ทุกหน่วย (ถ้ามีอยู่จริง)
            if ($user->isAdmin()) {
                return ServiceUnit::whereKey($sessionId)->exists() ? $sessionId : null;
            }

            // ผู้ใช้ทั่วไป: ต้องเป็นหน่วยของตนเอง
            if ($user->serviceUnits()->where('service_units.id', $sessionId)->exists()) {
                return $sessionId;
            }
        }

        // ยังไม่มี session หรือใช้ไม่ได้
        if ($user->isAdmin()) {
            // สำหรับแอดมิน: ไม่ auto ผูกหน่วยให้ เลือกจาก select ด้านบนก่อน
            return null;
        }

        // ผู้ใช้ทั่วไป: ลองหน่วย primary → หน่วยแรก → ไม่พบ
        $primary = $user->serviceUnits()->wherePivot('is_primary', 1)->value('service_units.id');
        if ($primary) {
            return (int) $primary;
        }

        $only = $user->serviceUnits()->limit(2)->pluck('service_units.id');
        if ($only->count() === 1) {
            return (int) $only->first();
        }

        return null;
    }

    public function show($id)
    {
        $unitId = $this->activeServiceUnitId();
        if (!$unitId) {
            return redirect()->route('backend.assessment.index')
                ->withErrors(['service_unit_id' => 'กรุณาเลือกหน่วยบริการจากเมนูด้านบน']);
        }

        $row = AssessmentServiceUnitLevel::with(['serviceUnit', 'user', 'approver'])
            ->where('id', $id)->where('service_unit_id', $unitId)->firstOrFail();

        $yearBE = $row->assess_year ? $row->assess_year + 543 : null;

        $form = AssessmentForm::with([
            'answers' => fn($q) => $q->whereHas('question.component'),
            'answers.question.component',
            'suggestions',
        ])
            ->where('service_unit_id', $unitId)
            ->where('assess_year', $row->assess_year)
            ->where('assess_round', $row->assess_round)
            ->first();

        $components = [];
        if ($form) {
            foreach ($form->answers as $ans) {
                $q = $ans->question;
                if (!$q) {
                    continue;
                }

                $cmp = $q->component;
                if (!$cmp) {
                    continue;
                }

                $key = (int) $cmp->no;
                $components[$key] ??= ['name' => $cmp->name, 'has' => [], 'gaps' => []];

                $label = ($q->code ? "{$q->code}) " : '') . $q->text;
                $isYes = $ans->answer_bool === true;

                if ($isYes) {
                    $components[$key]['has'][] = $label;
                } else {
                    $components[$key]['gaps'][] = $label;
                }
            }
            ksort($components);
        }

        return view('backend.self_assessment_service_unit_level.show',
            compact('row', 'yearBE', 'form', 'components'));
    }

    public function exportPdf($id)
    {
        $unitId = $this->activeServiceUnitId();
        if (!$unitId) {
            return redirect()->route('backend.assessment.index')
                ->withErrors(['service_unit_id' => 'กรุณาเลือกหน่วยบริการจากเมนูด้านบน']);
        }

        $row = AssessmentServiceUnitLevel::with(['serviceUnit', 'user', 'approver'])
            ->where('id', $id)->where('service_unit_id', $unitId)->firstOrFail();

        $yearBE = $row->assess_year ? $row->assess_year + 543 : null;

        $form = AssessmentForm::with([
            'answers' => fn($q) => $q->whereHas('question.component'),
            'answers.question.component',
            'suggestions',
        ])
            ->where('service_unit_id', $unitId)
            ->where('assess_year', $row->assess_year)
            ->where('assess_round', $row->assess_round)
            ->first();

        $components = [];
        if ($form) {
            foreach ($form->answers as $ans) {
                $q = $ans->question;
                if (!$q) {
                    continue;
                }

                $cmp = $q->component;
                if (!$cmp) {
                    continue;
                }

                $key = (int) $cmp->no;
                $components[$key] ??= ['name' => $cmp->name, 'has' => [], 'gaps' => []];

                $label = ($q->code ? "{$q->code}) " : '') . $q->text;
                $isYes = $ans->answer_bool === true;

                if ($isYes) {
                    $components[$key]['has'][] = $label;
                } else {
                    $components[$key]['gaps'][] = $label;
                }

            }
            ksort($components);
        }

        $pdf = Pdf::loadView(
            'backend.self_assessment_service_unit_level.pdf',
            compact('row', 'yearBE', 'form', 'components')
        )
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'Sarabun'); // สำคัญ! บอก dompdf ให้ใช้ฟอนต์ไทยของเรา

        return $pdf->stream("self-assessment-{$row->id}.pdf");
    }

    public function downloadAttachment($id)
    {
        $sg = AssessmentSuggestion::findOrFail($id);

        if (empty($sg->attachment_path) || !Storage::disk('public')->exists($sg->attachment_path)) {
            abort(404, 'ไม่พบไฟล์แนบ');
        }

        // ✅ ให้ดาวน์โหลดหรือแสดงใน browser ก็ได้
        return response()->file(Storage::disk('public')->path($sg->attachment_path));
        // หรือใช้ download() ถ้าต้องการให้ browser บังคับโหลด
        // return Storage::disk('public')->download($sg->attachment_path);
    }

    private function canAccessUnitId(int $unitId): bool
    {
        $user = Auth::user();

        // แอดมิน: เข้าถึงได้ทุกหน่วย (ต้องมีอยู่จริง)
        if ($user->isAdmin()) {
            return ServiceUnit::whereKey($unitId)->exists();
        }

        // ผู้ใช้ทั่วไป: ต้องเป็นหน่วยที่ตนเองสังกัด
        return $user->serviceUnits()->where('service_units.id', $unitId)->exists();
    }

    /* =========================================================
    | Helpers (เติม 2 ฟังก์ชันนี้ในส่วน Helpers)
    ==========================================================*/
    private function currentFiscalYearCE(): int
    {
        // ปีงบประมาณไทย: ต.ค.–ก.ย. (เดือน 10–12 นับเป็นปีถัดไปแบบ ค.ศ.)
        $y = (int) date('Y');
        $m = (int) date('n');
        return $m >= 10 ? $y + 1 : $y;
    }

    private function currentAssessRound(): int
    {
        // นิยามรอบตัวอย่าง:
        // รอบที่ 1 = ต.ค.–มี.ค. | รอบที่ 2 = เม.ย.–ก.ย.
        $m = (int) date('n');
        return ($m >= 10 || $m <= 3) ? 1 : 2;
    }

}
