<?php

// app/Http/Controllers/Backend/SelfAssessmentComponentController.php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AssessmentComponent;
use App\Models\AssessmentForm;
use App\Models\AssessmentLevel;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentSection;
use App\Models\AssessmentServiceUnitLevel;
use App\Models\AssessmentSuggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SelfAssessmentComponentController extends Controller
{
    public function create(int $suLevelId)
    {
        // 1) อ้างอิง Step1 + ตรวจสิทธิ์
        $suLevel      = AssessmentServiceUnitLevel::with('serviceUnit')->findOrFail($suLevelId);
        $activeUnitId = (int) session('current_service_unit_id');
        abort_unless($activeUnitId && $suLevel->service_unit_id === $activeUnitId, 403);

        // 2) ปี/รอบ/ระดับจาก Step1
        $year               = (int) $suLevel->assess_year;
        $round              = (int) $suLevel->assess_round;
        $levelCodeFromStep1 = $suLevel->level; // basic|medium|advanced
        abort_unless(in_array($levelCodeFromStep1, ['basic', 'medium', 'advanced'], true), 403);

        // 2.1) ล็อกสิทธิ์แก้ไขตามสถานะของ parent (แก้ได้เฉพาะ null|returned)
        $locked = in_array($suLevel->approval_status, ['pending', 'reviewing', 'approved', 'rejected'], true);
        if ($locked) {
            return redirect()
                ->route('backend.self-assessment-service-unit-level.show', $suLevel->id)
                ->with('warning', 'รายการนี้ถูกส่ง/กำลังตรวจ/เสร็จสิ้น ไม่สามารถแก้ไขได้');
        }

        // 3) หา form เดิมถ้ามี (พรีฟิลได้)
        $form = AssessmentForm::with(['answers', 'suggestions', 'serviceUnit'])
            ->where('service_unit_id', $suLevel->service_unit_id)
            ->where('assess_year', $year)
            ->where('assess_round', $round)
            ->first();

        // 3.x) ถ้ามีฟอร์มเดิม แต่ระดับจาก Step1 เปลี่ยน -> รีเซ็ตฟอร์ม (อนุญาตเฉพาะกรณีแก้ไขได้)
        if ($form && $form->level_code !== $levelCodeFromStep1) {
            DB::transaction(function () use ($form, $levelCodeFromStep1) {
                // ลบคำตอบ + ไฟล์แนบ
                $form->answers()->get()->each(function ($ans) {
                    if ($ans->attachment_path) {
                        Storage::disk('public')->delete($ans->attachment_path);
                    }
                    $ans->delete();
                });

                // ลบข้อเสนอ/แผน + ไฟล์แนบ
                $form->suggestions()->get()->each(function ($sg) {
                    if ($sg->attachment_path) {
                        Storage::disk('public')->delete($sg->attachment_path);
                    }
                    $sg->delete();
                });

                // เซ็ตระดับใหม่
                $form->level_code = $levelCodeFromStep1;
                $form->save();
            });

                                                     // เคลียร์ตัวแปรพรีฟิล
            $form->load(['answers', 'suggestions']); // ตอนนี้จะว่าง
        }

        // สร้างแผนที่คำตอบ (หลังรีเซ็ตแล้วจะว่าง)
        $answerMap = $form ? $form->answers->keyBy('assessment_question_id') : collect();

        // 4) เลือก level ที่ใช้โหลดคำถาม
        $levelCode = $form ? $form->level_code : $levelCodeFromStep1;
        $level     = AssessmentLevel::where('code', $levelCode)->firstOrFail();

        // 5) โหลดชุดคำถามตามระดับ (6 องค์ประกอบของระดับปัจจุบัน)
        $components = AssessmentComponent::orderBy('no')->get();

        $sectionsByComp = AssessmentSection::where('assessment_level_id', $level->id)
            ->orderBy('assessment_component_id')
            ->orderBy('ordering')
            ->get()
            ->groupBy('assessment_component_id');

        $questionsBySection = AssessmentQuestion::where('assessment_level_id', $level->id)
            ->where('is_active', true)
            ->orderBy('assessment_section_id')
            ->orderBy('ordering')
            ->get()
            ->groupBy('assessment_section_id');

        // 6) สรุปหัวกระดาน
        $summary = [
            'unit_name'         => optional($suLevel->serviceUnit)->org_name ?? '-',
            'level'             => $levelCode,
            'level_code'        => $levelCode,
            'level_text'        => $suLevel->level_text,
            'level_badge_class' => $suLevel->level_badge_class,
            'fiscal_year'       => $year,
            'fiscal_year_th'    => $year + 543,
            'round'             => $round,
        ];

        // 7) ส่งไป view
        return view('backend.self.create', compact(
            'suLevel', 'form', 'level', 'components',
            'sectionsByComp', 'questionsBySection',
            'answerMap', 'year', 'round', 'summary'
        ));
    }

    public function save(Request $req, int $suLevelId)
    {
        $suLevel      = AssessmentServiceUnitLevel::with('serviceUnit')->findOrFail($suLevelId);
        $activeUnitId = (int) session('current_service_unit_id');
        abort_unless($activeUnitId && $suLevel->service_unit_id === $activeUnitId, 403);

        $year  = (int) $suLevel->assess_year;
        $round = (int) $suLevel->assess_round;
        $level = $suLevel->level; // basic|medium|advanced
        abort_unless(in_array($level, ['basic', 'medium', 'advanced'], true), 403);

        // ล็อกสิทธิ์แก้ไขตาม parent
        $locked = in_array($suLevel->approval_status, ['pending', 'reviewing', 'approved', 'rejected'], true);
        if ($locked) {
            flash_notify('รายการนี้ถูกส่ง/กำลังตรวจ/เสร็จสิ้น ไม่สามารถแก้ไขได้', 'warning');
            return back();
        }

        $action = $req->input('__action', 'save'); // save | submit

        $req->validate([
            'answers'            => ['nullable', 'array'],
            'answers.*.bool'     => ['nullable', Rule::in(['0', '1', 0, 1, true, false])],
            'answers.*.text'     => ['nullable', 'string', 'max:2000'],
            'answers.*.file'     => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg', 'max:10240'],
            'suggestions'        => ['nullable', 'array'],
            'suggestions.*.id'   => ['nullable', 'integer'],
            'suggestions.*.text' => ['nullable', 'string', 'max:5000'],
            'suggestions.*.file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg', 'max:10240'],
        ]);

        return DB::transaction(function () use ($req, $suLevel, $year, $round, $level, $action) {

            // upsert แบบฟอร์ม (ไม่มี status ของตัวเองแล้ว)
            $form = AssessmentForm::firstOrCreate(
                [
                    'service_unit_id' => $suLevel->service_unit_id,
                    'assess_year'     => $year,
                    'assess_round'    => $round,
                ],
                ['level_code' => $level]
            );

            // sync คำตอบ
            foreach ((array) $req->input('answers', []) as $qid => $payload) {
                if (!ctype_digit((string) $qid)) {
                    continue;
                }

                $ans = $form->answers()->firstOrNew(['assessment_question_id' => (int) $qid]);

                $bool = null;
                if (array_key_exists('bool', $payload)) {
                    $v    = $payload['bool'];
                    $bool = in_array($v, ['1', 1, true], true) ? true : (in_array($v, ['0', 0, false], true) ? false : null);
                }
                $ans->answer_bool = $bool;
                $ans->answer_text = $payload['text'] ?? null;

                if ($file = $req->file("answers.$qid.file")) {
                    if ($ans->attachment_path) {
                        Storage::disk('public')->delete($ans->attachment_path);
                    }
                    $ans->attachment_path = $file->store("self-assess/{$form->id}", 'public');
                }
                $ans->save();
            }

            // sync ข้อเสนอ/แผน
            $keep = [];
            foreach ((array) $req->input('suggestions', []) as $i => $row) {
                $id   = data_get($row, 'id');
                $text = trim((string) data_get($row, 'text', ''));
                $file = $req->file("suggestions.$i.file");

                if ($id) {
                    $sg = $form->suggestions()->whereKey($id)->first();
                    if (!$sg) {
                        continue;
                    }

                    if ($text !== '') {
                        $sg->text = $text;
                    }

                    if ($file) {
                        if ($sg->attachment_path) {
                            Storage::disk('public')->delete($sg->attachment_path);
                        }
                        $sg->attachment_path = $file->store("self-assess/{$form->id}", 'public');
                    }
                    $sg->save();
                    $keep[] = $sg->id;
                } else {
                    if ($text === '' && !$file) {
                        continue;
                    }

                    $sg = new AssessmentSuggestion(['text' => $text !== '' ? $text : null]);
                    if ($file) {
                        $sg->attachment_path = $file->store("self-assess/{$form->id}", 'public');
                    }

                    $form->suggestions()->save($sg);
                    $keep[] = $sg->id;
                }
            }

            $form->suggestions()
                ->when(count($keep) > 0, fn($q) => $q->whereNotIn('id', $keep))
                ->get()
                ->each(function ($sg) {
                    if ($sg->attachment_path) {
                        Storage::disk('public')->delete($sg->attachment_path);
                    }
                    $sg->delete();
                });

            // ถ้ากดส่ง ให้ตรวจครบถ้วนก่อน แล้วอัปเดตสถานะที่ parent
            if ($action === 'submit') {
                $levelId = optional(AssessmentLevel::where('code', $form->level_code)->first())->id;
                $totalQ  = AssessmentQuestion::where('assessment_level_id', $levelId)
                    ->where('is_active', true)->count();

                $answeredQ = $form->answers()
                    ->where(function ($q) {
                        $q->whereNotNull('answer_bool')
                            ->orWhereNotNull('answer_text');
                    })->count();

                if ($totalQ > 0 && $answeredQ < $totalQ) {
                    flash_notify("ยังทำแบบประเมินไม่ครบ $answeredQ/$totalQ ข้อ กรุณาตรวจสอบ", 'danger');
                    return back()->withInput();
                }

                // อัปเดตสถานะที่ parent
                $suLevel->update([
                    'status'          => 'completed',
                    'approval_status' => 'pending',
                    'submitted_by'    => auth()->id(),
                    'submitted_at'    => now(),
                ]);

                // TODO: แจ้งเตือนผู้กำกับฯ ตามสิทธิ์ของระบบ (Notification/Queue/Email)
                flash_notify('ส่งแบบประเมินให้ สคร./สสจ. แล้ว', 'success');
                return redirect()->route('backend.self-assessment-service-unit-level.index');
            }

            flash_notify('บันทึกแบบร่างแล้ว', 'success');
            return back();
        });
    }
}
