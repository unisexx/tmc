<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AssessmentForm;
use App\Models\AssessmentServiceUnitLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AssessmentReviewController extends Controller
{
    /**
     * แสดงรายการแบบประเมินทั้งหมดที่หน่วยบริการส่งมา
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = AssessmentServiceUnitLevel::query()
            ->with([
                'serviceUnit:id,org_name,org_province_code,org_district_code,org_subdistrict_code,org_postcode',
                'serviceUnit.province:code,title',
                'serviceUnit.district:code,title',
                'serviceUnit.subdistrict:code,title',
                'approver:id,name',
            ])
        // โชว์เฉพาะรายการที่ส่งตรวจสอบแล้ว
            ->whereNotNull('submitted_at')

        // ===== SCOPE ตามสิทธิ์ =====
            ->when(!$user?->isAdmin(), function ($q) use ($user) {
                // สร้างชุดจังหวัดที่อนุญาตให้เห็น
                $allowedProvCodes = collect();

                // P = สสจ. → จังหวัดเดียวกับที่กำกับ
                if ($user?->hasPurpose('P') && !empty($user->reg_supervise_province_code)) {
                    $allowedProvCodes->push($user->reg_supervise_province_code);
                }

                // R = สคร. → ทุกจังหวัดในเขตสุขภาพที่กำกับ
                if ($user?->hasPurpose('R') && !empty($user->reg_supervise_region_id)) {
                    // หมายเหตุ: ชื่อตารางตามสกรีนช็อตคือ `province`
                    $regionProvCodes = DB::table('province')
                        ->where('health_region_id', $user->reg_supervise_region_id)
                        ->pluck('code');
                    $allowedProvCodes = $allowedProvCodes->merge($regionProvCodes);
                }

                $allowedProvCodes = $allowedProvCodes->unique()->values();

                // ถ้ามีอย่างน้อย 1 จังหวัด กรองตามนั้น
                if ($allowedProvCodes->isNotEmpty()) {
                    $q->whereHas('serviceUnit', fn($s) =>
                        $s->whereIn('org_province_code', $allowedProvCodes)
                    );
                }
                // ถ้าไม่มีสิทธิ์ผูกจังหวัด/เขตเลย ปล่อยโดยไม่กรอง (หรือจะบังคับไม่เห็นอะไรเลยก็ได้)
                // -> อยาก “ไม่เห็นอะไรเลย” ให้แทนด้วย:
                // else { $q->whereRaw('1=0'); }
            })

        /* ===== ฟิลเตอร์จากแถบค้นหา =====*/
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))              // สถานะฟอร์ม
            ->when($request->filled('approval'), fn($q) => $q->where('approval_status', $request->approval)) // สถานะตรวจสอบ
            ->when($request->filled('level'), fn($q) => $q->where('level', $request->level))
            ->when($request->filled('year'), fn($q) => $q->where('assess_year', $request->year))
            ->when($request->filled('round'), fn($q) => $q->where('assess_round', $request->round))
            ->when($request->filled('keyword'), function ($q) use ($request) {
                $kw = '%' . $request->keyword . '%';
                $q->whereHas('serviceUnit', fn($s) => $s->where('org_name', 'like', $kw));
            })
            ->latest('submitted_at');

        return view('backend.review_assessment.index', [
            'items' => $query->paginate(15)->withQueryString(),
        ]);
    }

    /**
     * แสดงรายละเอียดแบบประเมินรายรายการ (สำหรับตรวจสอบ)
     */
    public function show($id)
    {
        // ดึงข้อมูลระดับแบบประเมิน
        $row = AssessmentServiceUnitLevel::with(['serviceUnit', 'user', 'approver'])
            ->findOrFail($id);

        $yearBE = $row->assess_year ? $row->assess_year + 543 : null;

        // ดึงแบบฟอร์มและคำตอบที่เกี่ยวข้อง
        $form = AssessmentForm::with([
            'answers' => fn($q) => $q->whereHas('question.component'),
            'answers.question.component',
            'suggestions',
        ])
            ->where('service_unit_id', $row->service_unit_id)
            ->where('assess_year', $row->assess_year)
            ->where('assess_round', $row->assess_round)
            ->first();

        // สร้างชุดข้อมูลองค์ประกอบ (components)
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

                $key = (int) ($cmp->no ?? 0);
                $components[$key] ??= [
                    'name' => $cmp->name ?? "องค์ประกอบที่ {$key}",
                    'has'  => [],
                    'gaps' => [],
                ];

                $label = ($q->code ? "{$q->code}) " : '') . $q->text;
                $isYes = (bool) $ans->answer_bool;

                $components[$key][$isYes ? 'has' : 'gaps'][] = $label;
            }
            ksort($components);
        }

        return view('backend.review_assessment.show', compact('row', 'yearBE', 'form', 'components'));
    }

    /**
     * อัปเดตสถานะการตรวจสอบ (review / return / approve / reject)
     */
    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['review', 'return', 'approve', 'reject'])],
            'remark' => ['nullable', 'string', 'max:5000'],
        ]);

        // ถ้าเป็นการส่งกลับหรือไม่อนุมัติ ต้องกรอกหมายเหตุ
        if (in_array($data['action'], ['return', 'reject']) && empty($data['remark'])) {
            return back()
                ->withInput()
                ->withErrors(['remark' => 'กรุณากรอกหมายเหตุเมื่อทำการส่งกลับหรือไม่อนุมัติ']);
        }

        $level = AssessmentServiceUnitLevel::findOrFail($id);

        $map = [
            'review'  => 'reviewing',
            'return'  => 'returned',
            'approve' => 'approved',
            'reject'  => 'rejected',
        ];
        $level->approval_status = $map[$data['action']];
        $level->approval_remark = $data['remark'] ?? null;

        if (in_array($level->approval_status, ['approved', 'rejected'])) {
            $level->approved_by = Auth::id();
            $level->approved_at = now();
        }

        $level->save();

        flash_notify('อัปเดตสถานะเรียบร้อย', 'success');
        return back();
    }

    /**
     * ลบแบบประเมิน (ใช้เฉพาะสำหรับ admin หรือทดสอบระบบ)
     */
    public function destroy($id)
    {
        $level = AssessmentServiceUnitLevel::findOrFail($id);
        $level->delete();

        flash_notify('ลบข้อมูลเรียบร้อยแล้ว', 'success');
        return redirect()->route('backend.assessment_review.index');
    }
}
