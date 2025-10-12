<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\HealthRegion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ปีงบ / รอบ
        $filterYear  = (int) $request->input('year', fiscalYearCE());
        $filterRound = (int) $request->input('round', fiscalRound());

                                                     // พื้นที่/ระดับ/สังกัด
        $regionId    = $request->get('region');      // อิง health_regions.id
        $levelLabel  = $request->get('level');       // พื้นฐาน|กลาง|สูง
        $affiliation = $request->get('affiliation'); // สังกัด
        $levelMap    = ['พื้นฐาน' => 'basic', 'กลาง' => 'intermediate', 'สูง' => 'advanced'];
        $levelCode   = $levelLabel ? ($levelMap[$levelLabel] ?? null) : null;

        // จังหวัดสำหรับตัวกรอง
        $provinces = DB::table('province')
            ->when($regionId, fn($q) => $q->where('health_region_id', $regionId))
            ->orderBy('title')
            ->get(['code', 'title']);

        // ฟอร์มล่าสุดของแต่ละหน่วย ภายใต้ปี/รอบ
        $latestForm = DB::table('assessment_forms')
            ->when($filterYear, fn($q) => $q->where('assess_year', $filterYear))
            ->when($filterRound, fn($q) => $q->where('assess_round', $filterRound))
            ->select('service_unit_id', DB::raw('MAX(id) AS latest_id'))
            ->groupBy('service_unit_id');

        // ระดับ/อนุมัติล่าสุดของแต่ละหน่วย ภายใต้ปี/รอบ
        $latestAsul = DB::table('assessment_service_unit_levels')
            ->when($filterYear, fn($q) => $q->where('assess_year', $filterYear))
            ->when($filterRound, fn($q) => $q->where('assess_round', $filterRound))
            ->select('service_unit_id', DB::raw('MAX(id) AS latest_id'))
            ->groupBy('service_unit_id');

        // ฐาน query หลัก
        $unitsBase = DB::table('service_units AS su')
            ->leftJoin('province AS p', 'p.code', '=', 'su.org_province_code')
            ->leftJoin('district AS d', 'd.code', '=', 'su.org_district_code')
            ->leftJoin('subdistrict AS s', 's.code', '=', 'su.org_subdistrict_code')
            ->leftJoinSub($latestForm, 'lf', fn($j) => $j->on('lf.service_unit_id', '=', 'su.id'))
            ->leftJoin('assessment_forms AS af', 'af.id', '=', 'lf.latest_id')
            ->leftJoinSub($latestAsul, 'la', fn($j) => $j->on('la.service_unit_id', '=', 'su.id'))
            ->leftJoin('assessment_service_unit_levels AS asul', 'asul.id', '=', 'la.latest_id')
            ->when($regionId, fn($q) => $q->where('p.health_region_id', $regionId))
            ->when($levelCode, fn($q) => $q->where('af.level_code', $levelCode))
            ->when($affiliation, fn($q) => $q->where('su.org_affiliation', $affiliation));

        $units = (clone $unitsBase)->select([
            'su.id',
            'su.org_name',
            'su.org_affiliation',
            'su.org_lat',
            'su.org_lng',
            'su.org_province_code',
            'su.org_district_code',
            'su.org_subdistrict_code',
            'p.title AS province_title',
            'd.title AS district_title',
            's.title AS subdistrict_title',
            'p.health_region_id',
            'af.level_code',
            'asul.approval_status',
        ])->get();

        $summary = [
            'basic'        => (clone $unitsBase)->where('af.level_code', 'basic')->count(),
            'intermediate' => (clone $unitsBase)->where('af.level_code', 'intermediate')->count(),
            'advanced'     => (clone $unitsBase)->where('af.level_code', 'advanced')->count(),
        ];

        // ยังไม่ได้ประเมิน
        $notAssessed = (clone $unitsBase)->whereNull('af.level_code')->count();

        $status = [
            'pending'   => [
                'count' => (clone $unitsBase)->where('asul.approval_status', 'pending')->count(),
                'units' => (clone $unitsBase)->where('asul.approval_status', 'pending')->orderBy('su.org_name')->limit(6)->get(['su.org_name']),
            ],
            'reviewing' => [
                'count' => (clone $unitsBase)->where('asul.approval_status', 'reviewing')->count(),
                'units' => (clone $unitsBase)->where('asul.approval_status', 'reviewing')->orderBy('su.org_name')->limit(6)->get(['su.org_name']),
            ],
            'approved'  => [
                'count' => (clone $unitsBase)->where('asul.approval_status', 'approved')->count(),
                'units' => (clone $unitsBase)->where('asul.approval_status', 'approved')->orderBy('su.org_name')->limit(6)->get(['su.org_name']),
            ],
        ];

        $gaps = DB::table('assessment_answers AS ans')
            ->joinSub($latestForm, 'lf', 'lf.latest_id', '=', 'ans.assessment_form_id')
            ->join('assessment_forms AS af', 'af.id', '=', 'lf.latest_id')
            ->join('service_units AS su', 'su.id', '=', 'lf.service_unit_id')
            ->leftJoin('province AS p', 'p.code', '=', 'su.org_province_code')
            ->when($regionId, fn($q) => $q->where('p.health_region_id', $regionId))
            ->when($levelCode, fn($q) => $q->where('af.level_code', $levelCode))
            ->when($affiliation, fn($q) => $q->where('su.org_affiliation', $affiliation))
            ->where('ans.answer_bool', 0)
            ->groupBy('su.id', 'su.org_name', 'af.level_code')
            ->select('su.org_name', 'af.level_code', DB::raw('COUNT(*) AS gap_count'))
            ->orderByDesc('gap_count')
            ->get();

        $gapChart = [
            'labels' => $gaps->pluck('org_name')->take(15)->values(),
            'data'   => $gaps->pluck('gap_count')->take(15)->values(),
        ];

        $overallChart = [
            'labels'   => ['พื้นฐาน', 'กลาง', 'สูง'],
            'datasets' => [['data' => [$summary['basic'], $summary['intermediate'], $summary['advanced']]]],
        ];
        $levelChart = $overallChart;

        $componentAgg = DB::table('assessment_answers AS ans')
            ->joinSub($latestForm, 'lf', 'lf.latest_id', '=', 'ans.assessment_form_id')
            ->join('assessment_forms AS af', 'af.id', '=', 'lf.latest_id')
            ->join('service_units AS su', 'su.id', '=', 'lf.service_unit_id')
            ->leftJoin('province AS p', 'p.code', '=', 'su.org_province_code')
            ->when($regionId, fn($q) => $q->where('p.health_region_id', $regionId))
            ->when($levelCode, fn($q) => $q->where('af.level_code', $levelCode))
            ->when($affiliation, fn($q) => $q->where('su.org_affiliation', $affiliation))
            ->groupBy('ans.assessment_question_id')
            ->selectRaw('ans.assessment_question_id, SUM(ans.answer_bool=1) AS haves, SUM(ans.answer_bool=0) AS gaps')
            ->get();

        $componentChart = [
            'labels'   => $componentAgg->pluck('assessment_question_id'),
            'datasets' => [
                ['label' => 'มี', 'data' => $componentAgg->pluck('haves')],
                ['label' => 'ไม่มี (GAP)', 'data' => $componentAgg->pluck('gaps')],
            ],
        ];

        $boolAgg = DB::table('assessment_answers AS ans')
            ->joinSub($latestForm, 'lf', 'lf.latest_id', '=', 'ans.assessment_form_id')
            ->join('assessment_forms AS af', 'af.id', '=', 'lf.latest_id')
            ->join('service_units AS su', 'su.id', '=', 'lf.service_unit_id')
            ->leftJoin('province AS p', 'p.code', '=', 'su.org_province_code')
            ->when($regionId, fn($q) => $q->where('p.health_region_id', $regionId))
            ->when($levelCode, fn($q) => $q->where('af.level_code', $levelCode))
            ->when($affiliation, fn($q) => $q->where('su.org_affiliation', $affiliation))
            ->selectRaw('SUM(ans.answer_bool=1) AS haves, SUM(ans.answer_bool=0) AS gaps')
            ->first();

        $boolChart = [
            'labels'   => ['มี', 'ไม่มี (GAP)'],
            'datasets' => [['data' => [(int) ($boolAgg->haves ?? 0), (int) ($boolAgg->gaps ?? 0)]]],
        ];

        $regionTable = DB::table('province AS p')
            ->leftJoin('service_units AS su', 'su.org_province_code', '=', 'p.code')
            ->leftJoinSub($latestForm, 'lf', fn($j) => $j->on('lf.service_unit_id', '=', 'su.id'))
            ->leftJoin('assessment_forms AS af', 'af.id', '=', 'lf.latest_id')
            ->when($levelCode, fn($q) => $q->where('af.level_code', $levelCode))
            ->when($affiliation, fn($q) => $q->where('su.org_affiliation', $affiliation))
            ->groupBy('p.health_region_id')
            ->select('p.health_region_id', DB::raw('COUNT(su.id) AS total'))
            ->get()->keyBy('health_region_id');

        $provinceTable = DB::table('province AS p')
            ->leftJoin('service_units AS su', 'su.org_province_code', '=', 'p.code')
            ->leftJoinSub($latestForm, 'lf', fn($j) => $j->on('lf.service_unit_id', '=', 'su.id'))
            ->leftJoin('assessment_forms AS af', 'af.id', '=', 'lf.latest_id')
            ->when($regionId, fn($q) => $q->where('p.health_region_id', $regionId))
            ->when($levelCode, fn($q) => $q->where('af.level_code', $levelCode))
            ->when($affiliation, fn($q) => $q->where('su.org_affiliation', $affiliation))
            ->groupBy('p.code', 'p.title')
            ->orderBy('p.title')
            ->select('p.code', 'p.title', DB::raw('COUNT(su.id) AS total'))
            ->get();

        // ดึง สคร. จากฐานข้อมูล
        $regions = HealthRegion::query()
            ->orderBy('id')
            ->get(['id', 'code', 'title', 'short_title']);
        $levels       = array_keys($levelMap);
        $affiliations = [
            'สำนักงานปลัดกระทรวงสาธารณสุข', 'กรมควบคุมโรค', 'กรมการแพทย์', 'กรมสุขภาพจิต', 'สภากาชาดไทย',
            'สำนักการแพทย์ กรุงเทพมหานคร', 'กระทรวงอุดมศึกษา วิทยาศาสตร์ วิจัยและนวัตกรรม', 'กระทรวงกลาโหม',
            'องค์กรปกครองส่วนท้องถิ่น', 'องค์การมหาชน', 'เอกชน', 'อื่น ๆ',
        ];

        return view('backend.dashboard.index', compact(
            'regions', 'levels', 'affiliations',
            'regionId', 'levelLabel', 'affiliation',
            'filterYear', 'filterRound',
            'provinces',
            'units', 'summary', 'status',
            'gaps', 'gapChart', 'overallChart', 'levelChart', 'componentChart', 'boolChart',
            'regionTable', 'provinceTable',
            'notAssessed'
        ));
    }

}
