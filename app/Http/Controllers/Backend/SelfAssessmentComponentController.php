<?php

// app/Http/Controllers/Backend/SelfAssessmentComponentController.php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentComponent;
use App\Models\AssessmentForm;
use App\Models\AssessmentLevel;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentSection;
use App\Models\AssessmentServiceUnitLevel;
use App\Models\AssessmentSuggestion;
use App\Models\ServiceUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SelfAssessmentComponentController extends Controller
{
    // ใน SelfAssessmentController@index
    public function index(Request $req)
    {
        $forms = AssessmentForm::query()
            ->mine()
            ->when($req->filled('year'), fn($q) => $q->where('assess_year', $req->year))
            ->when($req->filled('round'), fn($q) => $q->where('assess_round', $req->round))
            ->when($req->filled('status'), fn($q) => $q->where('status', $req->status))
            ->orderByDesc('assess_year')
            ->orderByDesc('assess_round')
            ->paginate(15)
            ->withQueryString();

        return view('backend.self.index', compact('forms'));
    }

    // public function create(Request $req)
    // {
    //     /* รับ level_code จากผล “พิจารณาสถานะหน่วยบริการ” ที่เสร็จแล้ว (basic|intermediate|advanced)*/
    //     $levelCode = $req->input('level_code'); // ควรมาจากขั้นตอน step1 ที่ล็อกแล้ว
    //     abort_unless(in_array($levelCode, ['basic', 'medium', 'advanced']), 403);

    //     $year  = (int) ($req->year ?? fiscalYearCE());
    //     $round = (int) ($req->round ?? fiscalRound());

    //     // กันซ้ำ
    //     $exists = AssessmentForm::where([
    //         'service_unit_id' => session('current_service_unit_id'),
    //         'assess_year'     => $year, 'assess_round' => $round,
    //     ])->exists();
    //     if ($exists) {
    //         return redirect()->route('backend.self.index')->with('warning', 'มีฟอร์มปี/รอบนี้แล้ว');
    //     }

    //     $level          = AssessmentLevel::whereCode($levelCode)->firstOrFail();
    //     $components     = AssessmentComponent::orderBy('no')->get();
    //     $sectionsByComp = AssessmentSection::where('assessment_level_id', $level->id)
    //         ->orderBy('assessment_component_id')->orderBy('ordering')->get()
    //         ->groupBy('assessment_component_id');

    //     $questionsBySection = AssessmentQuestion::where('assessment_level_id', $level->id)
    //         ->where('is_active', true)->orderBy('assessment_section_id')->orderBy('ordering')->get()
    //         ->groupBy('assessment_section_id');

    //     return view('backend.self.create', compact(
    //         'level', 'components', 'sectionsByComp', 'questionsBySection', 'year', 'round'
    //     ));
    // }

    // app/Http/Controllers/Backend/SelfAssessmentComponentController.php

    public function create(int $suLevelId)
    {
        // 1) โหลดผล step1 ที่ล็อกแล้ว + หน่วยบริการ
        $suLevel = AssessmentServiceUnitLevel::with('serviceUnit')->findOrFail($suLevelId);

        // 2) ตรวจสิทธิ์หน่วยที่กำลังใช้งาน
        $activeUnitId = (int) session('current_service_unit_id');
        abort_unless($activeUnitId && $suLevel->service_unit_id === $activeUnitId, 403);

        // 3) ดึงปี/รอบ/ระดับจาก step1 โดยตรง
        $year      = (int) $suLevel->assess_year;
        $round     = (int) $suLevel->assess_round;
        $levelCode = $suLevel->level; // basic|medium|advanced
        abort_unless(in_array($levelCode, ['basic', 'medium', 'advanced'], true), 403);

        // 4) กันซ้ำฟอร์มปี/รอบนี้ของหน่วยเดียวกัน
        $exists = AssessmentForm::where([
            'service_unit_id' => $suLevel->service_unit_id,
            'assess_year'     => $year,
            'assess_round'    => $round,
        ])->exists();

        if ($exists) {
            return redirect()->route('backend.self.index')
                ->with('warning', 'มีฟอร์มปี/รอบนี้แล้ว');
        }

        // 5) เตรียมชุดคำถามตามระดับ
        $level = AssessmentLevel::where('code', $levelCode)->firstOrFail();

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

        $summary = [
            'unit_name'         => optional($suLevel->serviceUnit)->org_name ?? '-',
            'level_code'        => $levelCode,
            'level_text'        => $suLevel->level_text,        // <- จาก accessor
            'level_badge_class' => $suLevel->level_badge_class, // <- จาก accessor
            'fiscal_year'       => $year,
            'round'             => $round,
        ];

        // 7) ส่งไปหน้า create
        return view('backend.self.create', compact(
            'suLevel', 'level', 'components', 'sectionsByComp', 'questionsBySection',
            'year', 'round', 'summary'
        ));
    }

    public function store(Request $req)
    {
        // 1) ตรวจอินพุตให้ตรงกับฟอร์ม
        $validated = $req->validate([
            'level_code'         => ['required', Rule::in(['basic', 'medium', 'advanced'])],
            'assess_year'        => ['required', 'integer'],
            'assess_round'       => ['required', 'integer', Rule::in([1, 2])],

            // answers: โครงสร้าง answers[<qid>][bool|text]
            'answers'            => ['nullable', 'array'],
            'answers.*.bool'     => ['nullable', Rule::in(['0', '1', 0, 1, true, false])],
            'answers.*.text'     => ['nullable', 'string', 'max:2000'],
            'answers.*.file'     => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg', 'max:10240'],

            // suggestions: โครงสร้าง suggestions[i][text|file]
            'suggestions'        => ['nullable', 'array'],
            'suggestions.*.id'   => ['nullable', 'integer'],
            'suggestions.*.text' => ['nullable', 'string', 'max:5000'],
            'suggestions.*.file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg', 'max:10240'],
        ]);

        $serviceUnitId = (int) session('current_service_unit_id');
        abort_unless($serviceUnitId > 0, 403);

        // 2) กันซ้ำฟอร์มปี/รอบเดียวกันของหน่วยเดียวกัน
        $dup = AssessmentForm::where([
            'service_unit_id' => $serviceUnitId,
            'assess_year'     => $validated['assess_year'],
            'assess_round'    => $validated['assess_round'],
        ])->exists();
        if ($dup) {
            return back()->withInput()->with('warning', 'มีฟอร์มปี/รอบนี้แล้ว');
        }

        // 3) สร้างและบันทึกทุกอย่างในทรานแซกชัน
        return DB::transaction(function () use ($req, $validated, $serviceUnitId) {

            $form = AssessmentForm::create([
                'service_unit_id' => $serviceUnitId,
                'assess_year'     => (int) $validated['assess_year'],
                'assess_round'    => (int) $validated['assess_round'],
                'level_code'      => $validated['level_code'], // basic|medium|advanced
                'status'          => 'draft',
            ]);

            // 3.1) เซฟคำตอบ
            foreach ((array) $req->input('answers', []) as $qid => $payload) {
                // ป้องกันคีย์แปลก
                if (!ctype_digit((string) $qid)) {
                    continue;
                }

                $answerBool = null;
                if (array_key_exists('bool', $payload)) {
                    // แปลงให้ได้ true/false ชัดเจน
                    $v          = $payload['bool'];
                    $answerBool = filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($answerBool === null) {
                        // กรณีรับค่า '0' ให้เป็น false
                        $answerBool = in_array($v, ['0', 0], true) ? false : null;
                    }
                }

                $ans = new AssessmentAnswer([
                    'assessment_question_id' => (int) $qid,
                    'answer_bool'            => $answerBool,
                    'answer_text'            => $payload['text'] ?? null,
                ]);

                // รองรับไฟล์แนบของคำถามถ้ามีชื่อ input ตามฟอร์ม: answers[qid][file]
                if ($file = $req->file("answers.$qid.file")) {
                    $ans->attachment_path = $file->store("self-assess/{$form->id}", 'public');
                }

                $form->answers()->save($ans);
            }

            // 3.2) เซฟข้อเสนอ/แผน
            foreach ((array) $req->input('suggestions', []) as $i => $row) {
                $text = trim((string) data_get($row, 'text', ''));
                $file = $req->file("suggestions.$i.file");

                if ($text === '' && !$file) {
                    continue;
                }

                $sg = new AssessmentSuggestion([
                    'text' => $text !== '' ? $text : null,
                ]);

                if ($file) {
                    $sg->attachment_path = $file->store("self-assess/{$form->id}", 'public');
                }

                // หากมาจากการแก้ไขและมี id เดิม ส่ง id มาจะอัปเดตได้ภายหลัง
                $form->suggestions()->save($sg);
            }

            return redirect()
                ->route('backend.self.edit', $form->id)
                ->with('success', 'สร้างแบบประเมินแล้ว');
        });
    }

    public function edit($id)
    {
        $form = AssessmentForm::with(['answers', 'suggestions', 'serviceUnit'])
            ->mine()->findOrFail($id);
        abort_if($form->status !== 'draft', 403);

        $level      = AssessmentLevel::whereCode($form->level_code)->firstOrFail();
        $components = AssessmentComponent::orderBy('no')->get();

        $sectionsByComp = AssessmentSection::where('assessment_level_id', $level->id)
            ->orderBy('assessment_component_id')->orderBy('ordering')->get()
            ->groupBy('assessment_component_id');

        $questionsBySection = AssessmentQuestion::where('assessment_level_id', $level->id)
            ->where('is_active', true)->orderBy('assessment_section_id')->orderBy('ordering')->get()
            ->groupBy('assessment_section_id');

        $answerMap = $form->answers->keyBy('assessment_question_id');

        $summary = [
            'unit_name'         => optional($form->serviceUnit)->org_name ?? '-',
            'level_code'        => $form->level_code,
            'level_text'        => $form->level_text,        // accessor จาก AssessmentForm
            'level_badge_class' => $form->level_badge_class, // accessor จาก AssessmentForm
            'fiscal_year'       => $form->assess_year,
            'round'             => $form->assess_round,
        ];

        return view('backend.self.edit', compact(
            'form', 'level', 'components', 'sectionsByComp',
            'questionsBySection', 'answerMap', 'summary'
        ));
    }

    public function update(Request $req, $id)
    {
        $form = AssessmentForm::with(['answers', 'suggestions'])->mine()->findOrFail($id);
        abort_if($form->status !== 'draft', 403);

        // validate เฉพาะ payload ที่แก้ไข
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

        return DB::transaction(function () use ($req, $form) {

            // 1) upsert คำตอบ
            foreach ((array) $req->input('answers', []) as $qid => $payload) {
                if (!ctype_digit((string) $qid)) {
                    continue;
                }

                // normalize bool
                $answerBool = null;
                if (array_key_exists('bool', $payload)) {
                    $v          = $payload['bool'];
                    $answerBool = filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($answerBool === null) {
                        $answerBool = in_array($v, ['0', 0], true) ? false : null;
                    }
                }

                $ans              = $form->answers()->firstOrNew(['assessment_question_id' => (int) $qid]);
                $ans->answer_bool = $answerBool;
                $ans->answer_text = $payload['text'] ?? null;

                if ($file = $req->file("answers.$qid.file")) {
                    // ลบไฟล์เดิมถ้ามี
                    if ($ans->attachment_path) {
                        Storage::disk('public')->delete($ans->attachment_path);
                    }
                    $ans->attachment_path = $file->store("self-assess/{$form->id}", 'public');
                }

                $ans->save();
            }

            // 2) sync ข้อเสนอ/แผน (update/create + ลบรายการที่ถูกเอาออก)
            $incoming = (array) $req->input('suggestions', []);
            $keepIds  = [];

            foreach ($incoming as $i => $row) {
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
                    $keepIds[] = $sg->id;
                } else {
                    if ($text === '' && !$file) {
                        continue;
                    }

                    $sg = new AssessmentSuggestion(['text' => $text !== '' ? $text : null]);
                    if ($file) {
                        $sg->attachment_path = $file->store("self-assess/{$form->id}", 'public');
                    }
                    $form->suggestions()->save($sg);
                    $keepIds[] = $sg->id;
                }
            }

            // ลบรายการที่หายไป
            $form->suggestions()
                ->when(count($keepIds) > 0, fn($q) => $q->whereNotIn('id', $keepIds))
                ->when(count($keepIds) === 0, fn($q) => $q) // ลบหมดเมื่อไม่มี keepIds
                ->get()
                ->each(function ($sg) {
                    if ($sg->attachment_path) {
                        Storage::disk('public')->delete($sg->attachment_path);
                    }
                    $sg->delete();
                });

            return back()->with('success', 'บันทึกแล้ว');
        });
    }

    // public function submit($id)
    // {
    //     $form = AssessmentForm::mine()->findOrFail($id);
    //     abort_if($form->status !== 'draft', 403);
    //     $form->update(['status' => 'submitted', 'submitted_at' => now()]);
    //     return redirect()->route('backend.self.show', $form->id)->with('success', 'ส่งแบบประเมินแล้ว');
    // }

    // public function show($id)
    // {
    //     $form = AssessmentForm::with(['answers.question.component', 'suggestions'])->findOrFail($id);
    //     // สรุป “มี/ไม่มี” เป็นคุณสมบัติที่มี และช่องว่าง (gap) ตามองค์ประกอบ
    //     $byComp = $form->answers->groupBy(fn($a) => $a->question->component->no)->map(function ($items) {
    //         return [
    //             'have' => $items->filter(fn($a) => $a->answer_bool === true)->pluck('question.text')->values(),
    //             'gap'  => $items->filter(fn($a) => $a->answer_bool === false)->pluck('question.text')->values(),
    //         ];
    //     });
    //     return view('backend.self.show', compact('form', 'byComp'));
    // }

    // // Review flow (สคร./ส่วนกลาง)
    // public function reviewForm($id)
    // {
    //     $form = AssessmentForm::findOrFail($id);
    //     abort_unless(in_array($form->status, ['submitted', 'reviewing']), 403);
    //     return view('backend.self.review', compact('form'));
    // }

    // public function review(Request $req, $id)
    // {
    //     $form = AssessmentForm::findOrFail($id);
    //     abort_unless(in_array($form->status, ['submitted', 'reviewing']), 403);
    //     $form->update([
    //         'status'      => 'reviewing',
    //         'reviewer_id' => Auth::id(),
    //         'review_note' => $req->input('review_note'),
    //         'reviewed_at' => now(),
    //     ]);
    //     return back()->with('success', 'บันทึกข้อเสนอแนะแล้ว');
    // }

    // public function approve($id)
    // {
    //     $form = AssessmentForm::findOrFail($id);
    //     abort_unless(in_array($form->status, ['submitted', 'reviewing']), 403);
    //     $form->update(['status' => 'approved', 'reviewer_id' => Auth::id(), 'reviewed_at' => now()]);
    //     return redirect()->route('backend.self.show', $form->id)->with('success', 'อนุมัติผลประเมินแล้ว');
    // }

    // public function reject(Request $req, $id)
    // {
    //     $form = AssessmentForm::findOrFail($id);
    //     abort_unless(in_array($form->status, ['submitted', 'reviewing']), 403);

    //     $form->update([
    //         'status'      => 'rejected',
    //         'reviewer_id' => Auth::id(),
    //         'review_note' => $req->input('review_note'),
    //         'reviewed_at' => now(),
    //     ]);

    //     return redirect()
    //         ->route('backend.self.show', $form->id)
    //         ->with('warning', 'ส่งกลับให้แก้ไข');
    // }

    // public function destroy($id)
    // {
    //     $form = AssessmentForm::mine()->where('status', 'draft')->findOrFail($id);
    //     $form->delete();
    //     return redirect()->route('backend.self.index')->with('success', 'ลบแล้ว');
    // }
}
