<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AssessmentStep1;
use App\Models\ServiceUnit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AssessmentController extends Controller
{
    public function __construct()
    {
        // ใส่ middleware/permission ได้ตามสิทธิ์ของระบบ
        // $this->middleware('permission:assessment.view', ['only' => ['index','show']]);
        // $this->middleware('permission:assessment.create', ['only' => ['create_step1','store_step1']]);
        // $this->middleware('permission:assessment.approve', ['only' => ['approveForm','approve']]);
    }

    /* =========================================================
    | 1) INDEX : ตารางรายการ + ค้นหา/กรอง
    | พารามิเตอร์รองรับ: ?year=2025&round=1&level=basic&status=completed&approval=approved&q=ชื่อหน่วย
    ==========================================================*/
    public function index(Request $req)
    {
        $q = AssessmentStep1::with(['serviceUnit', 'user', 'approver'])->latest('id');

        // คำค้นชื่อหน่วยบริการ
        if ($kw = trim($req->get('q', ''))) {
            $q->whereHas('serviceUnit', function ($s) use ($kw) {
                $s->where('unitName', 'like', "%{$kw}%");
            });
        }

        // กรองปี/รอบ
        if ($year = $req->get('year')) {
            $q->where('assess_year', $this->normalizeYearToCE($year));
        }
        if ($round = $req->get('round')) {
            $q->where('assess_round', (int) $round);
        }

        // กรองระดับ/สถานะ/การอนุมัติ
        if ($lv = $req->get('level')) {
            $q->where('level', $lv);
        }
        if ($st = $req->get('status')) {
            $q->where('status', $st);
        }
        if ($ap = $req->get('approval')) {
            $q->where('approval_status', $ap);
        }

        $rows = $q->paginate(15)->appends($req->query());

        return view('backend.assessment.index', compact('rows'));
    }

    /* =========================================================
    | 2) CREATE (STEP 1) : แบบทำทีละข้อ
    | - เตรียมปี (แสดงเป็น พ.ศ. ในฟอร์ม) และหน่วยบริการของผู้ใช้
    ==========================================================*/
    public function create_step1()
    {
        return view('backend.assessment.step1.create');
    }

    /* =========================================================
    | 3) STORE (STEP 1) : รับผลการคัดกรองระดับจากฟอร์มทีละข้อ
    | - รับ year (พ.ศ./ค.ศ.) + round(1/2) + service_unit_id + level
    | - อัปเดต/สร้างเรคอร์ดตาม Unique (unit+year+round)
    ==========================================================*/
    public function store_step1(Request $req)
    {
        // 1) หา service_unit_id จากผู้ใช้ที่ล็อกอิน (ไม่รับจากฟอร์ม)
        $serviceUnitId = $this->currentServiceUnitId();
        if (!$serviceUnitId) {
            return back()->withErrors(['service_unit_id' => 'บัญชีผู้ใช้นี้ยังไม่ได้ผูกกับหน่วยบริการ'])
                ->withInput();
        }

        // 2) Validate ข้อมูล
        $data = $req->validate([
            'assess_year'  => ['nullable', 'integer'],
            'assess_round' => ['nullable', 'integer', Rule::in([1, 2])],
            'fiscalYear'   => ['nullable', 'string', 'max:4'],
            'round'        => ['nullable', 'integer', Rule::in([1, 2])],

            // ตัวเลือกแต่ละข้อ (บางข้ออาจไม่ถูกถามตามเส้นทาง จึงเป็น nullable)
            'q1'           => ['required', Rule::in(['have', 'none'])],
            'q2'           => ['nullable', Rule::in(['tm', 'other'])],
            'q31'          => ['nullable', Rule::in(['yes', 'no'])],
            'q32'          => ['nullable', Rule::in(['yes', 'no'])],
            'q4'           => ['nullable', Rule::in(['can', 'cannot'])],
        ], [
            'q1.required' => 'กรุณาเลือกข้อ 1',
        ]);

        // 3) ปี/รอบ
        $yearCE  = $data['assess_year'] ?? $this->normalizeYearToCE($data['fiscalYear'] ?? null) ?? (int) date('Y');
        $roundNo = $data['assess_round'] ?? ($data['round'] ?? 1);

        // 4) คำนวณ level จากคำตอบที่ส่งมา
        $computedLevel = $this->computeLevel($data['q1'] ?? null, $data['q2'] ?? null, $data['q31'] ?? null, $data['q32'] ?? null, $data['q4'] ?? null);
        if (!$computedLevel) {
            return back()->withErrors(['level' => 'กรุณาตอบแบบประเมินให้ครบตามเงื่อนไขเพื่อสรุประดับ'])
                ->withInput();
        }

        // 5) บันทึกหรืออัปเดต (กันซ้ำด้วย service_unit_id + ปี + รอบ)
        $row = AssessmentStep1::updateOrCreate(
            [
                'service_unit_id' => (int) $serviceUnitId,
                'assess_year'     => (int) $yearCE,
                'assess_round'    => (int) $roundNo,
            ],
            [
                'user_id'         => Auth::id(),
                'status'          => 'completed',
                'last_question'   => 'done',

                // เก็บคำตอบทีละข้อ
                'q1'              => $data['q1'] ?? null,
                'q2'              => $data['q2'] ?? null,
                'q31'             => $data['q31'] ?? null,
                'q32'             => $data['q32'] ?? null,
                'q4'              => $data['q4'] ?? null,

                // ผลสรุป
                'level'           => $computedLevel,
                'decided_at'      => now(),

                // meta & audit
                'approval_status' => 'pending',
                'created_by'      => Auth::id(),
                'updated_by'      => Auth::id(),
                'submitted_by'    => Auth::id(),
                'submitted_at'    => now(),
                'ip_address'      => $req->ip(),
                'user_agent'      => substr((string) $req->header('User-Agent'), 0, 255),
            ]
        );

        return redirect()->route('backend.assessment.index')->with('success', 'บันทึกผลคัดกรองขั้นที่ 1 สำเร็จ');
    }

    /* =========================================================
    | 5) EDIT/UPDATE : (กรณีอนุญาตให้แก้ไข level หรือรอบ/ปี)
    |   - ถ้าไม่ต้องการให้แก้ level หลังส่งแล้ว ให้ล็อกใน Policy/Validation
    ==========================================================*/
    public function edit($id)
    {
        // โหลดความสัมพันธ์ที่ใช้แสดงผลได้ตามต้องการ
        $row = AssessmentStep1::with(['serviceUnit', 'user', 'approver'])->findOrFail($id);

        // หน้า edit ใช้พาร์เชียลเดียวกับ create (_form) โดยให้ mode=edit และส่ง $row
        return view('backend.assessment.step1.edit', compact('row'));
    }

    // ===== UPDATE : บันทึกการแก้ไขรอบประเมิน (ขั้นที่ 1)
    public function update(Request $req, $id)
    {
        $row = AssessmentStep1::findOrFail($id);

        // Validate
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
        ], [
            'q1.required' => 'กรุณาเลือกข้อ 1',
        ]);

        // ปี/รอบ (ถ้าไม่ส่งมาจะคงของเดิม)
        $yearCE  = $data['assess_year'] ?? $this->normalizeYearToCE($data['fiscalYear'] ?? null) ?? $row->assess_year;
        $roundNo = $data['assess_round'] ?? ($data['round'] ?? $row->assess_round);

        // คำนวณ level ใหม่จากคำตอบ
        $computedLevel = $this->computeLevel($data['q1'] ?? null, $data['q2'] ?? null, $data['q31'] ?? null, $data['q32'] ?? null, $data['q4'] ?? null);
        if (!$computedLevel) {
            return back()->withErrors(['level' => 'กรุณาตอบแบบประเมินให้ครบตามเงื่อนไขเพื่อสรุประดับ'])
                ->withInput();
        }

        // อัปเดตข้อมูล
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
        ])->save();

        return redirect()->route('backend.assessment.index')->with('success', 'อัปเดตรอบประเมินขั้นที่ 1 สำเร็จ');
    }

    /* =========================================================
    | 6) APPROVE : ฟอร์ม/การอนุมัติ
    ==========================================================*/
    public function approveForm($id)
    {
        $row = AssessmentStep1::with(['serviceUnit', 'user'])->findOrFail($id);
        return view('backend.assessment.approve', compact('row'));
    }

    public function approve(Request $req, $id)
    {
        $req->validate([
            'approval_status' => ['required', Rule::in(['approved', 'rejected'])],
            'approval_remark' => ['nullable', 'string', 'max:1000'],
        ]);

        $row                  = AssessmentStep1::findOrFail($id);
        $row->approval_status = $req->approval_status;
        $row->approval_remark = $req->approval_remark;
        $row->approved_by     = Auth::id();
        $row->approved_at     = now();
        $row->save();

        flash_notify('บันทึกผลการอนุมัติเรียบร้อยแล้ว', 'success');
        return redirect()->route('backend.assessment.show', $row->id);
    }

    /* =========================================================
    | 7) DELETE
    ==========================================================*/
    public function destroy($id)
    {
        $row = AssessmentStep1::findOrFail($id);
        $row->delete();
        flash_notify('ลบรายการสำเร็จ', 'success');
        return redirect()->route('backend.assessment.index');
    }

    /* =========================================================
    | Helper: แปลงปี พ.ศ./สตริง → ค.ศ.
    | - รับค่าเป็นสตริงหรืออินท์ก็ได้
    | - ถ้ามากกว่า 2400 ถือว่าเป็น พ.ศ. แล้วลบ 543
    ==========================================================*/
    private function normalizeYearToCE($year): ?int
    {
        if (empty($year)) {
            return null;
        }

        $y = (int) $year;
        return $y > 2400 ? $y - 543 : $y;
    }

    /**
     * คำนวณ level จากคำตอบทีละข้อ ตามกติกา:
     * Q1: none -> basic
     * Q1: have + Q2: tm
     *    - Q31: no -> basic
     *    - Q31: yes + Q4: can -> advanced
     *    - Q31: yes + Q4: cannot -> medium
     * Q1: have + Q2: other
     *    - Q32: yes -> medium
     *    - Q32: no  -> basic
     * ถ้ายังตอบไม่ครบเส้นทาง -> คืน null
     */
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
                    if ($q4 === 'can') {
                        return 'advanced';
                    }

                    if ($q4 === 'cannot') {
                        return 'medium';
                    }

                }
                return null; // ยังไม่เลือก q31 หรือ q4
            }

            if ($q2 === 'other') {
                if ($q32 === 'yes') {
                    return 'medium';
                }

                if ($q32 === 'no') {
                    return 'basic';
                }

                return null; // ยังไม่เลือก q32
            }
            return null; // ยังไม่เลือก q2
        }

        return null; // ยังไม่เลือก q1
    }

/** ดึง service_unit_id จากผู้ใช้ที่ล็อกอิน */
    private function currentServiceUnitId(): ?int
    {
        $user = Auth::user();
        return optional($user->serviceUnit)->id ?? $user->service_unit_id ?? null;
    }

}
