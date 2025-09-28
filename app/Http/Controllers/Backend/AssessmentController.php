<?php

// app/Http/Controllers/Backend/AssessmentController.php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ServiceUnit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssessmentController extends Controller
{
    // index: ตารางรายการ
    public function index(Request $req)
    {
        $query = Assessment::with('serviceUnit')->latest();
        if ($fy = $req->get('fiscalYear')) {
            $query->where('fiscalYear', $fy);
        }

        if ($lv = $req->get('level')) {
            $query->where('level', $lv);
        }

        $rows = $query->paginate(12);
        return view('backend.assessment.index', compact('rows'));
    }

    // create: Step 1 (คัดกรองระดับจาก decision tree)
    public function create()
    {
        $years = [date('Y') + 543, date('Y') + 542];

        // สมมติผู้ใช้ผูก service_unit_id ไว้ในตาราง users
        $user        = auth()->user();
        $serviceUnit = $user->serviceUnit ?? null;

        // กรณีผู้ใช้ยังไม่ถูกผูกกับหน่วยบริการ ให้ส่ง list ไปให้เลือก
        $serviceUnits = null;
        if (!$serviceUnit) {
            $serviceUnits = \App\Models\ServiceUnit::orderBy('unitName')->get(['id', 'unitName']);
        }

        return view('backend.assessment.create_step1', compact('years', 'serviceUnit', 'serviceUnits'));
    }

    // store: รับผลการคัดกรองระดับ
    public function store(Request $req)
    {
        $data = $req->validate([
            'service_unit_id' => ['required', 'exists:service_units,id'],
            'fiscalYear'      => ['required', 'string', 'size:4'],
            'level'           => ['required', Rule::in(['basic', 'medium', 'advanced'])],
        ]);
        $assessment = Assessment::updateOrCreate(
            [
                'service_unit_id' => $data['service_unit_id'],
                'fiscalYear'      => $data['fiscalYear'],
                'round'           => $data['round'],
            ],
            [
                'level'  => $data['level'],
                'status' => 'draft',
            ]
        );
        return redirect()->route('backend.assessment.fill', $assessment)->with('success', 'สร้างรอบประเมินแล้ว');
    }

    // show: สรุปผล/ดาวน์โหลด (ภายหลัง)
    public function show(Assessment $assessment)
    {
        $assessment->load('serviceUnit');
        return view('backend.assessment.show', compact('assessment'));
    }

    public function edit(Assessment $assessment)
    {
        // แก้ไขระดับ (กรณีคัดกรองใหม่)
        return view('backend.assessment.edit_level', compact('assessment'));
    }

    public function update(Request $req, Assessment $assessment)
    {
        $req->validate(['level' => ['required', Rule::in(['basic', 'medium', 'advanced'])]]);
        $assessment->update(['level' => $req->level]);
        return redirect()->route('backend.assessment.fill', $assessment)->with('success', 'อัปเดตระดับเรียบร้อย');
    }
}
