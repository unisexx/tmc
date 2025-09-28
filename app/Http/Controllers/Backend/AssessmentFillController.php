<?php

// app/Http/Controllers/Backend/AssessmentFillController.php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentComponent;
use App\Models\AssessmentItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssessmentFillController extends Controller
{
    public function index(Assessment $assessment)
    {
        // ดึง item ตามระดับ แยกกลุ่ม 6 องค์ประกอบ
        $components = AssessmentComponent::with(['items' => fn($q) => $q->where('forLevel', $assessment->level)->orderBy('code')])->get();

        // Map คำตอบเดิม
        $answers = $assessment->answers()->get()->keyBy('assessment_item_id');

        return view('backend.assessment.fill', compact('assessment', 'components', 'answers'));
    }

    public function store(Request $req, Assessment $assessment)
    {
        // รับค่าจาก form เป็น array answers[itemId][value|remark|file]
        $answers = $req->get('answers', []);

        foreach ($answers as $itemId => $payload) {
            $data = [
                'assessment_id'      => $assessment->id,
                'assessment_item_id' => $itemId,
                'value'              => $payload['value'] ?? null,
                'remark'             => $payload['remark'] ?? null,
            ];

            // save file (optional)
            if ($req->hasFile("answers.$itemId.file")) {
                $path             = $req->file("answers.$itemId.file")->store('evidence', 'public');
                $data['filePath'] = $path;
            }

            AssessmentAnswer::updateOrCreate(
                ['assessment_id' => $assessment->id, 'assessment_item_id' => $itemId],
                $data
            );
        }

        return back()->with('success', 'บันทึกแบบร่างแล้ว');
    }

    public function submit(Request $req, Assessment $assessment)
    {
        $assessment->update(['status' => 'submitted', 'submittedAt' => now()]);
        return redirect()->route('backend.assessment.index')->with('success', 'ส่งแบบประเมินเรียบร้อย');
    }
}
