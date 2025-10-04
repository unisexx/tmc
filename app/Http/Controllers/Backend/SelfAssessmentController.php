<?php

// app/Http/Controllers/Backend/SelfAssessmentController.php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentComponent;
use App\Models\AssessmentForm;
use App\Models\AssessmentLevel;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentSection;
use App\Models\AssessmentSuggestion;
use App\Models\ServiceUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SelfAssessmentController extends Controller
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

    public function create(Request $req)
    {
        /* รับ level_code จากผล “พิจารณาสถานะหน่วยบริการ” ที่เสร็จแล้ว (basic|intermediate|advanced)*/
        $levelCode = $req->input('level_code'); // ควรมาจากขั้นตอน step1 ที่ล็อกแล้ว
        abort_unless(in_array($levelCode, ['basic', 'medium', 'advanced']), 403);

        $year  = (int) ($req->year ?? fiscalYearCE());
        $round = (int) ($req->round ?? fiscalRound());

        // กันซ้ำ
        $exists = AssessmentForm::where([
            'service_unit_id' => session('current_service_unit_id'),
            'assess_year'     => $year, 'assess_round' => $round,
        ])->exists();
        if ($exists) {
            return redirect()->route('backend.self.index')->with('warning', 'มีฟอร์มปี/รอบนี้แล้ว');
        }

        $level          = AssessmentLevel::whereCode($levelCode)->firstOrFail();
        $components     = AssessmentComponent::orderBy('no')->get();
        $sectionsByComp = AssessmentSection::where('assessment_level_id', $level->id)
            ->orderBy('assessment_component_id')->orderBy('ordering')->get()
            ->groupBy('assessment_component_id');

        $questionsBySection = AssessmentQuestion::where('assessment_level_id', $level->id)
            ->where('is_active', true)->orderBy('assessment_section_id')->orderBy('ordering')->get()
            ->groupBy('assessment_section_id');

        return view('backend.self.create', compact(
            'level', 'components', 'sectionsByComp', 'questionsBySection', 'year', 'round'
        ));
    }

    public function store(Request $req)
    {
        $req->validate([
            'level_code'   => 'required|in:basic,intermediate,advanced',
            'assess_year'  => 'required|integer',
            'assess_round' => 'required|integer',
        ]);

        return DB::transaction(function () use ($req) {
            $form = AssessmentForm::create([
                'service_unit_id' => session('current_service_unit_id'),
                'assess_year'     => $req->assess_year,
                'assess_round'    => $req->assess_round,
                'level_code'      => $req->level_code,
                'status'          => 'draft',
            ]);

            // บันทึกคำตอบเบื้องต้น (ถ้ามี)
            foreach (($req->input('answers') ?? []) as $qid => $payload) {
                $ans = new AssessmentAnswer([
                    'assessment_question_id' => $qid,
                    'answer_bool'            => isset($payload['bool']) ? (bool) $payload['bool'] : null,
                    'answer_text'            => $payload['text'] ?? null,
                ]);
                if (isset($payload['file']) && $payload['file'] instanceof \Illuminate\Http\UploadedFile) {
                    $ans->attachment_path = $payload['file']->store("self-assess/{$form->id}", 'public');
                }
                $form->answers()->save($ans);
            }

            // ข้อเสนอ/แผนพัฒนา
            foreach (($req->input('suggestions') ?? []) as $i => $text) {
                if (!$text) {
                    continue;
                }

                $sg = new AssessmentSuggestion(['text' => $text]);
                if ($req->file("suggestion_files.$i")) {
                    $sg->attachment_path = $req->file("suggestion_files.$i")->store("self-assess/{$form->id}", 'public');
                }
                $form->suggestions()->save($sg);
            }

            return redirect()->route('backend.self.edit', $form->id)->with('success', 'สร้างแบบประเมินแล้ว');
        });
    }

    public function edit($id)
    {
        $form = AssessmentForm::with(['answers', 'suggestions'])->mine()->findOrFail($id);
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

        return view('backend.self.edit', compact(
            'form', 'level', 'components', 'sectionsByComp', 'questionsBySection', 'answerMap'
        ));
    }

    public function update(Request $req, $id)
    {
        $form = AssessmentForm::mine()->findOrFail($id);
        abort_if($form->status !== 'draft', 403);

        return DB::transaction(function () use ($req, $form) {
            foreach (($req->input('answers') ?? []) as $qid => $payload) {
                $ans              = $form->answers()->firstOrNew(['assessment_question_id' => $qid]);
                $ans->answer_bool = array_key_exists('bool', $payload) ? (bool) $payload['bool'] : $ans->answer_bool;
                $ans->answer_text = $payload['text'] ?? $ans->answer_text;
                if (isset($payload['file']) && $payload['file'] instanceof \Illuminate\Http\UploadedFile) {
                    $ans->attachment_path = $payload['file']->store("self-assess/{$form->id}", 'public');
                }
                $ans->save();
            }

            // suggestions
            $form->suggestions()->delete();
            foreach (($req->input('suggestions') ?? []) as $i => $text) {
                if (!$text) {
                    continue;
                }

                $sg = new \App\Models\AssessmentSuggestion(['text' => $text]);
                if ($req->file("suggestion_files.$i")) {
                    $sg->attachment_path = $req->file("suggestion_files.$i")->store("self-assess/{$form->id}", 'public');
                }
                $form->suggestions()->save($sg);
            }

            return back()->with('success', 'บันทึกแล้ว');
        });
    }

    public function submit($id)
    {
        $form = AssessmentForm::mine()->findOrFail($id);
        abort_if($form->status !== 'draft', 403);
        $form->update(['status' => 'submitted', 'submitted_at' => now()]);
        return redirect()->route('backend.self.show', $form->id)->with('success', 'ส่งแบบประเมินแล้ว');
    }

    public function show($id)
    {
        $form = AssessmentForm::with(['answers.question.component', 'suggestions'])->findOrFail($id);
        // สรุป “มี/ไม่มี” เป็นคุณสมบัติที่มี และช่องว่าง (gap) ตามองค์ประกอบ
        $byComp = $form->answers->groupBy(fn($a) => $a->question->component->no)->map(function ($items) {
            return [
                'have' => $items->filter(fn($a) => $a->answer_bool === true)->pluck('question.text')->values(),
                'gap'  => $items->filter(fn($a) => $a->answer_bool === false)->pluck('question.text')->values(),
            ];
        });
        return view('backend.self.show', compact('form', 'byComp'));
    }

    // Review flow (สคร./ส่วนกลาง)
    public function reviewForm($id)
    {
        $form = AssessmentForm::findOrFail($id);
        abort_unless(in_array($form->status, ['submitted', 'reviewing']), 403);
        return view('backend.self.review', compact('form'));
    }

    public function review(Request $req, $id)
    {
        $form = AssessmentForm::findOrFail($id);
        abort_unless(in_array($form->status, ['submitted', 'reviewing']), 403);
        $form->update([
            'status'      => 'reviewing',
            'reviewer_id' => Auth::id(),
            'review_note' => $req->input('review_note'),
            'reviewed_at' => now(),
        ]);
        return back()->with('success', 'บันทึกข้อเสนอแนะแล้ว');
    }

    public function approve($id)
    {
        $form = AssessmentForm::findOrFail($id);
        abort_unless(in_array($form->status, ['submitted', 'reviewing']), 403);
        $form->update(['status' => 'approved', 'reviewer_id' => Auth::id(), 'reviewed_at' => now()]);
        return redirect()->route('backend.self.show', $form->id)->with('success', 'อนุมัติผลประเมินแล้ว');
    }

    public function reject(Request $req, $id)
    {
        $form = AssessmentForm::findOrFail($id);
        abort_unless(in_array($form->status, ['submitted', 'reviewing']), 403);

        $form->update([
            'status'      => 'rejected',
            'reviewer_id' => Auth::id(),
            'review_note' => $req->input('review_note'),
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('backend.self.show', $form->id)
            ->with('warning', 'ส่งกลับให้แก้ไข');
    }

    public function destroy($id)
    {
        $form = AssessmentForm::mine()->where('status', 'draft')->findOrFail($id);
        $form->delete();
        return redirect()->route('backend.self.index')->with('success', 'ลบแล้ว');
    }
}
