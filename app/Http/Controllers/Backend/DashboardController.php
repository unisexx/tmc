<?php
// app/Http/Controllers/Backend/DashboardController.php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AssessmentServiceConfig;
use App\Models\HealthRegion;
use App\Models\ServiceUnit;
use App\Models\StHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * เดิมเรียก /backend/dashboard -> ใช้งานได้ต่อ โดยชี้ไป overview()
     */
    public function index(Request $request)
    {
        return $this->overview($request);
    }

    // public function overview(Request $request)
    // {
    //     $serviceUnitId = $request->get('service_unit_id');

    //     // ถ้ามีการเลือกหน่วยบริการ → เด้งไปหน้ารายหน่วยทันที
    //     if ($serviceUnitId) {
    //         $queryString = http_build_query($request->query());
    //         return redirect("/backend/dashboard/unit" . ($queryString ? "?{$queryString}" : ""));
    //     }

    //     $filterYear  = (int) $request->input('year', fiscalYearCE());
    //     $filterRound = (int) $request->input('round', fiscalRound());

    //     $regionId      = $request->get('region');
    //     $provinceCode  = $request->get('province_code');
    //     $serviceUnitId = $request->get('service_unit_id');
    //     $levelLabel    = $request->get('level');
    //     $affiliation   = $request->get('affiliation');

    //     $levelMap  = ['พื้นฐาน' => 'basic', 'กลาง' => 'intermediate', 'สูง' => 'advanced'];
    //     $levelCode = $levelLabel ? ($levelMap[$levelLabel] ?? null) : null;

    //     // ---------- provinces for filter ----------
    //     $provinces = DB::table('province')
    //         ->when($regionId, fn($q) => $q->where('health_region_id', $regionId))
    //         ->orderBy('title')
    //         ->get(['code', 'title']);

    //     // ---------- latest form / asul by year-round ----------
    //     $latestForm = $this->buildLatestFormSubquery($filterYear, $filterRound);
    //     $latestAsul = $this->buildLatestAsulSubquery($filterYear, $filterRound);

    //     // ---------- base with joins ----------
    //     $applyGeoFilters = fn($q) => $this->applyGeoFilters($q, $regionId, $provinceCode, $serviceUnitId);

    //     $unitsBase = DB::table('service_units AS su')
    //         ->leftJoin('province AS p', 'p.code', '=', 'su.org_province_code')
    //         ->leftJoin('district AS d', 'd.code', '=', 'su.org_district_code')
    //         ->leftJoin('subdistrict AS s', 's.code', '=', 'su.org_subdistrict_code')
    //         ->leftJoinSub($latestForm, 'lf', fn($j) => $j->on('lf.service_unit_id', '=', 'su.id'))
    //         ->leftJoin('assessment_forms AS af', 'af.id', '=', 'lf.latest_id')
    //         ->leftJoinSub($latestAsul, 'la', fn($j) => $j->on('la.service_unit_id', '=', 'su.id'))
    //         ->leftJoin('assessment_service_unit_levels AS asul', 'asul.id', '=', 'la.latest_id');

    //     $unitsBase = $applyGeoFilters($unitsBase)
    //         ->when($levelCode, fn($q) => $q->where('af.level_code', $levelCode))
    //         ->when($affiliation, fn($q) => $q->where('su.org_affiliation', $affiliation));

    //     $units = (clone $unitsBase)->select([
    //         'su.id', 'su.org_name', 'su.org_affiliation', 'su.org_lat', 'su.org_lng',
    //         'su.org_province_code', 'su.org_district_code', 'su.org_subdistrict_code',
    //         'p.title AS province_title', 'd.title AS district_title', 's.title AS subdistrict_title',
    //         'p.health_region_id', 'af.level_code', 'asul.approval_status',
    //     ])->get();

    //     $summary = [
    //         'basic'        => (clone $unitsBase)->where('af.level_code', 'basic')->count(),
    //         'intermediate' => (clone $unitsBase)->whereIn('af.level_code', ['intermediate', 'medium'])->count(),
    //         'advanced'     => (clone $unitsBase)->where('af.level_code', 'advanced')->count(),
    //     ];
    //     $notAssessed = (clone $unitsBase)->whereNull('af.level_code')->count();

    //     $status = [
    //         'pending'   => [
    //             'count' => (clone $unitsBase)->where('asul.approval_status', 'pending')->count(),
    //             'units' => (clone $unitsBase)->where('asul.approval_status', 'pending')->orderBy('su.org_name')->limit(6)->get(['su.org_name']),
    //         ],
    //         'reviewing' => [
    //             'count' => (clone $unitsBase)->where('asul.approval_status', 'reviewing')->count(),
    //             'units' => (clone $unitsBase)->where('asul.approval_status', 'reviewing')->orderBy('su.org_name')->limit(6)->get(['su.org_name']),
    //         ],
    //         'approved'  => [
    //             'count' => (clone $unitsBase)->where('asul.approval_status', 'approved')->count(),
    //             'units' => (clone $unitsBase)->where('asul.approval_status', 'approved')->orderBy('su.org_name')->limit(6)->get(['su.org_name']),
    //         ],
    //     ];

    //     $gaps = DB::table('assessment_answers AS ans')
    //         ->joinSub($latestForm, 'lf', 'lf.latest_id', '=', 'ans.assessment_form_id')
    //         ->join('assessment_forms AS af', 'af.id', '=', 'lf.latest_id')
    //         ->join('service_units AS su', 'su.id', '=', 'lf.service_unit_id')
    //         ->leftJoin('province AS p', 'p.code', '=', 'su.org_province_code');

    //     $gaps = $applyGeoFilters($gaps)
    //         ->when($levelCode, fn($q) => $q->where('af.level_code', $levelCode))
    //         ->when($affiliation, fn($q) => $q->where('su.org_affiliation', $affiliation))
    //         ->where('ans.answer_bool', 0)
    //         ->groupBy('su.id', 'su.org_name', 'af.level_code')
    //         ->select('su.org_name', 'af.level_code', DB::raw('COUNT(*) AS gap_count'))
    //         ->orderByDesc('gap_count')
    //         ->get();

    //     $gapChart = [
    //         'labels' => $gaps->pluck('org_name')->take(15)->values(),
    //         'data'   => $gaps->pluck('gap_count')->take(15)->values(),
    //     ];

    //     $overallChart = [
    //         'labels'   => ['พื้นฐาน', 'กลาง', 'สูง'],
    //         'datasets' => [['data' => [$summary['basic'], $summary['intermediate'], $summary['advanced']]]],
    //     ];
    //     $levelChart = $overallChart;

    //     $componentAgg = DB::table('assessment_answers AS ans')
    //         ->joinSub($latestForm, 'lf', 'lf.latest_id', '=', 'ans.assessment_form_id')
    //         ->join('assessment_forms AS af', 'af.id', '=', 'lf.latest_id')
    //         ->join('service_units AS su', 'su.id', '=', 'lf.service_unit_id')
    //         ->leftJoin('province AS p', 'p.code', '=', 'su.org_province_code');

    //     $componentAgg = $applyGeoFilters($componentAgg)
    //         ->when($levelCode, fn($q) => $q->where('af.level_code', $levelCode))
    //         ->when($affiliation, fn($q) => $q->where('su.org_affiliation', $affiliation))
    //         ->groupBy('ans.assessment_question_id')
    //         ->selectRaw('ans.assessment_question_id, SUM(ans.answer_bool=1) AS haves, SUM(ans.answer_bool=0) AS gaps')
    //         ->get();

    //     $componentChart = [
    //         'labels'   => $componentAgg->pluck('assessment_question_id'),
    //         'datasets' => [
    //             ['label' => 'มี', 'data' => $componentAgg->pluck('haves')],
    //             ['label' => 'ไม่มี (GAP)', 'data' => $componentAgg->pluck('gaps')],
    //         ],
    //     ];

    //     $boolAgg = DB::table('assessment_answers AS ans')
    //         ->joinSub($latestForm, 'lf', 'lf.latest_id', '=', 'ans.assessment_form_id')
    //         ->join('assessment_forms AS af', 'af.id', '=', 'lf.latest_id')
    //         ->join('service_units AS su', 'su.id', '=', 'lf.service_unit_id')
    //         ->leftJoin('province AS p', 'p.code', '=', 'su.org_province_code');

    //     $boolAgg = $applyGeoFilters($boolAgg)
    //         ->when($levelCode, fn($q) => $q->where('af.level_code', $levelCode))
    //         ->when($affiliation, fn($q) => $q->where('su.org_affiliation', $affiliation))
    //         ->selectRaw('SUM(ans.answer_bool=1) AS haves, SUM(ans.answer_bool=0) AS gaps')
    //         ->first();

    //     $boolChart = [
    //         'labels'   => ['มี', 'ไม่มี (GAP)'],
    //         'datasets' => [['data' => [(int) ($boolAgg->haves ?? 0), (int) ($boolAgg->gaps ?? 0)]]],
    //     ];

    //     $regionTable = DB::table('province AS p')
    //         ->leftJoin('service_units AS su', 'su.org_province_code', '=', 'p.code')
    //         ->leftJoinSub($latestForm, 'lf', fn($j) => $j->on('lf.service_unit_id', '=', 'su.id'))
    //         ->leftJoin('assessment_forms AS af', 'af.id', '=', 'lf.latest_id')
    //         ->when($affiliation, fn($q) => $q->where('su.org_affiliation', $affiliation))
    //         ->when($provinceCode, fn($q) => $q->where('p.code', $provinceCode))
    //         ->when($serviceUnitId, fn($q) => $q->where('su.id', $serviceUnitId))
    //         ->groupBy('p.health_region_id')
    //         ->selectRaw('
    //             p.health_region_id,
    //             SUM(CASE WHEN su.id IS NOT NULL AND af.level_code = "basic" THEN 1 ELSE 0 END) AS basic,
    //             SUM(CASE WHEN su.id IS NOT NULL AND af.level_code IN ("intermediate","medium") THEN 1 ELSE 0 END) AS medium,
    //             SUM(CASE WHEN su.id IS NOT NULL AND af.level_code = "advanced" THEN 1 ELSE 0 END) AS advanced,
    //             SUM(CASE WHEN su.id IS NOT NULL AND af.level_code IS NULL THEN 1 ELSE 0 END) AS unassessed,
    //             SUM(CASE WHEN su.id IS NOT NULL THEN 1 ELSE 0 END) AS total
    //         ')
    //         ->get()
    //         ->keyBy('health_region_id');

    //     $provinceTable = DB::table('province AS p')
    //         ->leftJoin('service_units AS su', 'su.org_province_code', '=', 'p.code')
    //         ->leftJoinSub($latestForm, 'lf', fn($j) => $j->on('lf.service_unit_id', '=', 'su.id'))
    //         ->leftJoin('assessment_forms AS af', 'af.id', '=', 'lf.latest_id')
    //         ->when($regionId, fn($q) => $q->where('p.health_region_id', $regionId))
    //         ->when($affiliation, fn($q) => $q->where('su.org_affiliation', $affiliation))
    //         ->when($provinceCode, fn($q) => $q->where('p.code', $provinceCode))
    //         ->when($serviceUnitId, fn($q) => $q->where('su.id', $serviceUnitId))
    //         ->groupBy('p.code', 'p.title')
    //         ->orderBy('p.title')
    //         ->selectRaw('
    //             p.code, p.title,
    //             SUM(CASE WHEN su.id IS NOT NULL AND af.level_code = "basic" THEN 1 ELSE 0 END) AS basic,
    //             SUM(CASE WHEN su.id IS NOT NULL AND af.level_code IN ("intermediate","medium") THEN 1 ELSE 0 END) AS medium,
    //             SUM(CASE WHEN su.id IS NOT NULL AND af.level_code = "advanced" THEN 1 ELSE 0 END) AS advanced,
    //             SUM(CASE WHEN su.id IS NOT NULL AND af.level_code IS NULL THEN 1 ELSE 0 END) AS unassessed,
    //             SUM(CASE WHEN su.id IS NOT NULL THEN 1 ELSE 0 END) AS total
    //         ')
    //         ->get();

    //     $gapByLevelRaw = DB::table('assessment_answers AS ans')
    //         ->joinSub($latestForm, 'lf', 'lf.latest_id', '=', 'ans.assessment_form_id')
    //         ->join('assessment_forms AS af', 'af.id', '=', 'lf.latest_id')
    //         ->join('service_units AS su', 'su.id', '=', 'lf.service_unit_id')
    //         ->leftJoin('province AS p', 'p.code', '=', 'su.org_province_code')
    //         ->leftJoin('assessment_questions AS q', 'q.id', '=', 'ans.assessment_question_id');

    //     $gapByLevelRaw = $applyGeoFilters($gapByLevelRaw)
    //         ->when($levelCode, fn($q2) => $q2->where('af.level_code', $levelCode))
    //         ->when($affiliation, fn($q2) => $q2->where('su.org_affiliation', $affiliation))
    //         ->where('ans.answer_bool', 0)
    //         ->groupBy('af.level_code', 'ans.assessment_question_id', 'q.text', 'q.code')
    //         ->selectRaw('
    //             af.level_code,
    //             ans.assessment_question_id,
    //             COALESCE(q.text, q.code, CONCAT("คำถาม #", ans.assessment_question_id)) AS gap_label,
    //             COUNT(DISTINCT su.id) AS unit_count
    //         ')
    //         ->get();

    //     $gapBasic        = $gapByLevelRaw->where('level_code', 'basic')->sortByDesc('unit_count')->values();
    //     $gapIntermediate = $gapByLevelRaw->whereIn('level_code', ['intermediate', 'medium'])->sortByDesc('unit_count')->values();
    //     $gapAdvanced     = $gapByLevelRaw->where('level_code', 'advanced')->sortByDesc('unit_count')->values();

    //     $regions      = HealthRegion::query()->orderBy('id')->get(['id', 'code', 'title', 'short_title']);
    //     $levels       = array_keys($levelMap);
    //     $affiliations = [
    //         'สำนักงานปลัดกระทรวงสาธารณสุข', 'กรมควบคุมโรค', 'กรมการแพทย์', 'กรมสุขภาพจิต', 'สภากาชาดไทย',
    //         'สำนักการแพทย์ กรุงเทพมหานคร', 'กระทรวงอุดมศึกษา วิทยาศาสตร์ วิจัยและนวัตกรรม', 'กระทรวงกลาโหม',
    //         'องค์กรปกครองส่วนท้องถิ่น', 'องค์การมหาชน', 'เอกชน', 'อื่น ๆ',
    //     ];

    //     return view('backend.dashboard.index', compact(
    //         'regions', 'levels', 'affiliations',
    //         'regionId', 'provinceCode', 'serviceUnitId',
    //         'levelLabel', 'affiliation',
    //         'filterYear', 'filterRound',
    //         'provinces',
    //         'units', 'summary', 'status',
    //         'gaps', 'gapChart', 'overallChart', 'levelChart', 'componentChart', 'boolChart',
    //         'regionTable', 'provinceTable',
    //         'notAssessed',
    //         'gapBasic', 'gapIntermediate', 'gapAdvanced'
    //     ));
    // }

    public function overview(Request $request)
    {
        if ($serviceUnitId = $request->get('service_unit_id')) {
            $queryString = http_build_query($request->query());
            return redirect("/backend/dashboard/unit" . ($queryString ? "?{$queryString}" : ""));
        }

        // ===== ฟิลเตอร์หลัก (มีค่า default ถ้าไม่ส่งมา) =====
        $filterYear         = (int) ($request->input('year') ?: fiscalYearCE());
        $filterRound        = (int) ($request->input('round') ?: fiscalRound());
        $filterRegion       = $request->filled('region') ? (int) $request->input('region') : null;
        $filterProvinceCode = $request->input('province_code');

        $mapLevel = fn($v) => match (strtolower((string) $v)) {
            'พื้นฐาน', 'ระดับพื้นฐาน', 'basic' => 'basic',
            'กลาง', 'ระดับกลาง', 'medium'      => 'medium',
            'สูง', 'ระดับสูง', 'advanced'      => 'advanced',
            default => null,
        };
        $prefer = ['advanced', 'medium', 'basic'];

        $serviceUnits = ServiceUnit::query()
            ->with([
                'assessmentLevels' => function ($q) use ($filterYear, $filterRound) {
                    $q->when($filterYear, fn($qq) => $qq->where('assess_year', $filterYear))
                        ->when($filterRound, fn($qq) => $qq->where('assess_round', $filterRound))
                        ->where('approval_status', 'approved')
                        ->select('id', 'service_unit_id', 'assess_year', 'assess_round', 'level', 'approval_status');
                },
                'province:code,title,health_region_id',
                'district:code,title',
                'subdistrict:code,title',
            ])
            ->when($filterProvinceCode, fn($q) => $q->where('org_province_code', $filterProvinceCode))
            ->when($filterRegion, fn($q) => $q->whereHas('province', fn($qq) => $qq->where('health_region_id', $filterRegion)))
            ->get([
                'id', 'org_name', 'org_address', 'org_tel', 'org_email',
                'org_province_code', 'org_district_code', 'org_subdistrict_code',
                'org_lat', 'org_lng',
            ]);

        // เลือกระดับที่สูงสุดและ asul_id ต่อหน่วย
        $bestByUnit = $serviceUnits->mapWithKeys(function ($su) use ($mapLevel, $prefer) {
            $approved = collect($su->assessmentLevels)
                ->map(fn($a) => ['id' => $a->id, 'key' => $mapLevel($a->level)])
                ->filter(fn($x) => $x['key']);

            foreach ($prefer as $k) {
                $row = $approved->firstWhere('key', $k);
                if ($row) {
                    // ['id'=>asul_id,'key'=>basic|medium|advanced]
                    return [$su->id => $row];
                }
            }
            return [$su->id => ['id' => null, 'key' => null]];
        });

        // บริการพื้นฐานตามระดับ
        $servicesByLevel = StHealthService::query()
            ->active()
            ->orderBy('ordering')
            ->get(['id', 'name', 'level_code'])
            ->groupBy('level_code'); // basic|medium|advanced => collection

        // ค่าปิด/เปิดเฉพาะหน่วย
        $asulIds = $bestByUnit->pluck('id')->filter()->values();
        $configs = AssessmentServiceConfig::query()
            ->whereIn('assessment_service_unit_level_id', $asulIds)
            ->get(['assessment_service_unit_level_id', 'st_health_service_id', 'is_enabled'])
            ->groupBy('assessment_service_unit_level_id');

        // รายชื่อบริการหลัง apply config
        $servicesByUnit = $bestByUnit->map(function ($best) use ($servicesByLevel, $configs) {
            if (!$best['key']) {
                // ไม่มีอนุมัติ
                return collect();
            }
            $base = collect($servicesByLevel->get($best['key']) ?? []);
            $map  = collect($configs->get($best['id']) ?? [])
                ->keyBy('st_health_service_id')
                ->map->is_enabled;

            return $base->filter(function ($svc) use ($map) {
                // ไม่มี config => แสดง
                return $map->has($svc->id) ? (bool) $map->get($svc->id) : true;
            })->values()->pluck('name');
        });

        // โครงระดับที่อนุมัติ ใช้สำหรับสีหมุด
        $approvedByUnit = $serviceUnits->mapWithKeys(function ($su) use ($mapLevel) {
            $levels = collect($su->assessmentLevels)
                ->map(fn($a) => $mapLevel($a->level))
                ->filter()->unique()->values();
            return [$su->id => ['levels' => $levels]];
        });

        // ===== คำนวณ GAP (answer_bool = 0) ต่อระดับ =====
        $gapBasic        = $this->aggregateGapsByLevel('basic', $filterYear, $filterRound, $filterRegion, $filterProvinceCode);
        $gapIntermediate = $this->aggregateGapsByLevel('medium', $filterYear, $filterRound, $filterRegion, $filterProvinceCode);
        $gapAdvanced     = $this->aggregateGapsByLevel('advanced', $filterYear, $filterRound, $filterRegion, $filterProvinceCode);

        return view('backend.dashboard.index', compact(
            'serviceUnits',
            'approvedByUnit',
            'bestByUnit',
            'servicesByUnit',
            // ฟิลเตอร์ (เผื่อใช้ใน view อื่น)
            'filterYear', 'filterRound', 'filterRegion', 'filterProvinceCode',
            // GAP
            'gapBasic', 'gapIntermediate', 'gapAdvanced',
        ));
    }

    /**
     * รวม GAP (answer_bool = 0) ต่อ "คำถาม" และนับจำนวนหน่วยบริการที่มี GAP ข้อนั้น
     * - จำกัดปี/รอบ
     * - จำกัดระดับ (assessment_forms.level_code)
     * - เฉพาะเรคคอร์ดที่อนุมัติแล้ว (assessment_service_unit_levels.approval_status = 'approved')
     * - เคารพตัวกรองเขต/จังหวัด
     */
    private function aggregateGapsByLevel(string $level, int $year, int $round, $regionId = null, $provinceCode = null)
    {
        $answersTbl   = 'assessment_answers';
        $formsTbl     = 'assessment_forms';
        $questionsTbl = 'assessment_questions';
        $levelsTbl    = 'assessment_service_unit_levels';
        $suTbl        = 'service_units';
        $provTbl      = 'province'; // ปรับชื่อ/คอลัมน์ให้ตรงสคีมาของคุณหากต่างกัน

        $q = \DB::table("$answersTbl as aa")
            ->join("$formsTbl as f", 'f.id', '=', 'aa.assessment_form_id')
            ->join("$questionsTbl as q", 'q.id', '=', 'aa.assessment_question_id')
            ->join("$levelsTbl as lvl", function ($j) {
                $j->on('lvl.service_unit_id', '=', 'f.service_unit_id')
                    ->on('lvl.assess_year', '=', 'f.assess_year')
                    ->on('lvl.assess_round', '=', 'f.assess_round');
            })
            ->join("$suTbl as su", 'su.id', '=', 'f.service_unit_id')
            ->leftJoin("$provTbl as p", 'p.code', '=', 'su.org_province_code')
        // เงื่อนไขหลัก
            ->where('f.assess_year', $year)
            ->where('f.assess_round', $round)
            ->where('f.level_code', $level)
            ->where('lvl.approval_status', 'approved')
            ->where('aa.answer_bool', 0);

        // กรองตาม เขต/จังหวัด ถ้ามี
        if (!empty($regionId)) {
            $q->where('p.health_region_id', $regionId);
        }
        if (!empty($provinceCode)) {
            $q->where('su.org_province_code', $provinceCode);
        }

        return $q->selectRaw('q.id as question_id, q.text as gap_label, COUNT(DISTINCT f.service_unit_id) as unit_count')
            ->groupBy('q.id', 'q.text')
            ->orderByDesc('unit_count')
            ->orderBy('q.id')
            ->get();
    }

    public function gapUnits(Request $request)
    {
        $request->validate([
            'level'         => 'required|in:basic,medium,advanced',
            'qid'           => 'required|integer',
            'year'          => 'required|integer',
            'round'         => 'required|integer',
            'region'        => 'nullable|integer',
            'province_code' => 'nullable',
        ]);

        $level        = $request->string('level');
        $questionId   = (int) $request->input('qid');
        $year         = (int) $request->input('year');
        $round        = (int) $request->input('round');
        $regionId     = $request->input('region');
        $provinceCode = $request->input('province_code');

        // === Hard-code table names ===
        $answersTbl   = 'assessment_answers';
        $formsTbl     = 'assessment_forms';
        $levelsTbl    = 'assessment_service_unit_levels';
        $questionsTbl = 'assessment_questions';
        $suTbl        = 'service_units';
        $provTbl      = 'province';

        $q = \DB::table("$answersTbl as aa")
            ->join("$formsTbl as f", 'f.id', '=', 'aa.assessment_form_id')
            ->join("$questionsTbl as q", 'q.id', '=', 'aa.assessment_question_id')
            ->join("$suTbl as su", 'su.id', '=', 'f.service_unit_id')
            ->leftJoin("$provTbl as p", 'p.code', '=', 'su.org_province_code')
            ->join("$levelsTbl as lvl", function ($j) {
                $j->on('lvl.service_unit_id', '=', 'f.service_unit_id')
                    ->on('lvl.assess_year', '=', 'f.assess_year')
                    ->on('lvl.assess_round', '=', 'f.assess_round');
            })
            ->where('aa.assessment_question_id', $questionId)
            ->where('aa.answer_bool', 0)
            ->where('f.assess_year', $year)
            ->where('f.assess_round', $round)
            ->where('f.level_code', $level)
            ->where('lvl.approval_status', 'approved');

        // ฟิลเตอร์เสริม (ถ้ามี)
        if (!empty($regionId)) {
            $q->where('p.health_region_id', $regionId);
        }
        if (!empty($provinceCode)) {
            $q->where('su.org_province_code', $provinceCode);
        }

        // DISTINCT service_unit + province name
        $rows = $q->distinct()
            ->orderBy('su.org_province_code')
            ->orderBy('su.org_name')
            ->get([
                'su.id as service_unit_id',
                'su.org_name as name',
                'su.org_province_code as province_code',
                \DB::raw("COALESCE(p.title, '-') as province"),
            ]);

        return response()->json([
            'ok'    => true,
            'count' => $rows->count(),
            'rows'  => $rows,
        ]);
    }

    /**
     * Dashboard รายหน่วยบริการ => view('backend.dashboard.unit')
     */
    public function unit(Request $request)
    {
        $serviceUnitId = (int) $request->query('service_unit_id');

        // ถ้าไม่มีค่า service_unit_id → redirect กลับ overview()
        if (!$serviceUnitId) {
            $queryString = http_build_query($request->query());
            return redirect("/backend/dashboard" . ($queryString ? "?{$queryString}" : ""));
        }

        // ---------- ปีงบประมาณและรอบ ----------
        $filterYear  = (int) $request->input('year', fiscalYearCE());
        $filterRound = (int) $request->input('round', fiscalRound());

        // ---------- latest form / asul ----------
        // หมายเหตุ: สร้าง subquery ไว้หากต้องใช้ต่อยอดในอนาคต
        $latestForm = $this->buildLatestFormSubquery($filterYear, $filterRound);
        $latestAsul = $this->buildLatestAsulSubquery($filterYear, $filterRound);

        // ---------- ข้อมูลหน่วย ----------
        // ดึงความสัมพันธ์เฉพาะปี/รอบที่เลือก และจำกัด 1 เรคคอร์ดล่าสุด
        $unit = ServiceUnit::query()
            ->with([
                'province:code,title',
                'district:code,title',
                'subdistrict:code,title',
                'assessmentForms'   => fn($q)   => $q
                    ->where('assess_year', $filterYear)
                    ->where('assess_round', $filterRound)
                    ->latest('id')->limit(1),
                'serviceUnitLevels' => fn($q) => $q
                    ->where('assess_year', $filterYear)
                    ->where('assess_round', $filterRound)
                    ->latest('id')->limit(1),
            ])
            ->findOrFail($serviceUnitId);

        // หมายเหตุสำคัญ:
        // เดิมเคย setAttribute เช่น province_title, level, approval_status, asul_id, form_id
        // ตอนนี้ “ย้ายไปคำนวณใน Blade” ทั้งหมด เพื่อให้ Controller บางและทดสอบง่ายขึ้น

        return view('backend.dashboard.unit', compact(
            'serviceUnitId',
            'filterYear',
            'filterRound',
            'unit',
        ));
    }

    // ====================== shared helpers ======================

    private function buildLatestFormSubquery(int $year, int $round)
    {
        return DB::table('assessment_forms')
            ->when($year, fn($q) => $q->where('assess_year', $year))
            ->when($round, fn($q) => $q->where('assess_round', $round))
            ->select('service_unit_id', DB::raw('MAX(id) AS latest_id'))
            ->groupBy('service_unit_id');
    }

    private function buildLatestAsulSubquery(int $year, int $round)
    {
        return DB::table('assessment_service_unit_levels')
            ->when($year, fn($q) => $q->where('assess_year', $year))
            ->when($round, fn($q) => $q->where('assess_round', $round))
            ->select('service_unit_id', DB::raw('MAX(id) AS latest_id'))
            ->groupBy('service_unit_id');
    }

    private function applyGeoFilters($q, $regionId, $provinceCode, $serviceUnitId)
    {
        return $q
            ->when($regionId, fn($qq) => $qq->where('p.health_region_id', $regionId))
            ->when($provinceCode, fn($qq) => $qq->where('su.org_province_code', $provinceCode))
            ->when($serviceUnitId, fn($qq) => $qq->where('su.id', $serviceUnitId));
    }
}
