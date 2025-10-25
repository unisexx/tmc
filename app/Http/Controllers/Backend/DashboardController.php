<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AssessmentServiceConfig;
use App\Models\ServiceUnit;
use App\Models\StHealthService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    /**
     * เดิมเรียก /backend/dashboard -> ใช้งานได้ต่อ โดยชี้ไป overview()
     * เพิ่มเงื่อนไข: ถ้ามี service_unit_id ให้ไปหน้า unit()
     */
    public function index(Request $request)
    {
        // ตรวจสอบว่ามีการส่งค่า service_unit_id มาจากฟอร์มหรือไม่
        $serviceUnitId = $request->input('service_unit_id');

        if (!empty($serviceUnitId)) {
            // แนบ query string อื่น ๆ ที่อาจส่งมาจากฟอร์ม เช่น year, round
            $query = http_build_query($request->query());

            // redirect ไปหน้า unit โดยแนบพารามิเตอร์เดิมทั้งหมด
            return redirect("/backend/dashboard/unit" . ($query ? "?{$query}" : ""));
        }

        // ถ้าไม่มี service_unit_id → ไปหน้า overview ตามปกติ
        return $this->overview($request);
    }

    /**
     * หน้า Dashboard ภาพรวม
     */
    public function overview(Request $request)
    {
        $data = $this->getOverviewData($request);
        return view('backend.dashboard.overview', $data);
    }

    /**
     * หน้า Dashboard รายหน่วยบริการ
     */
    public function unit(Request $request)
    {
        $data = $this->getUnitData($request);

        return view('backend.dashboard.unit', $data);
    }

    /**
     * =====[ OVERVIEW DATA ]====================================================
     * รวม query ทั้งหมดจาก overview() เพื่อให้ export ใช้ร่วมได้
     */
    private function getOverviewData(Request $request): array
    {
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

        // หา level ที่ "ดีที่สุด" ของแต่ละหน่วย (advanced > medium > basic)
        $bestByUnit = $serviceUnits->mapWithKeys(function ($su) use ($mapLevel, $prefer) {
            $approved = collect($su->assessmentLevels)
                ->map(fn($a) => ['id' => $a->id, 'key' => $mapLevel($a->level)])
                ->filter(fn($x) => $x['key']);
            foreach ($prefer as $k) {
                $row = $approved->firstWhere('key', $k);
                if ($row) {
                    return [$su->id => $row];
                }
            }
            return [$su->id => ['id' => null, 'key' => null]];
        });

        // รายการบริการ (บริการสุขภาพผู้เดินทาง) ที่เปิดใช้ในระดับนั้น ๆ
        $servicesByLevel = StHealthService::query()
            ->active()
            ->orderBy('ordering')
            ->get(['id', 'name', 'level_code'])
            ->groupBy('level_code');

        $asulIds = $bestByUnit->pluck('id')->filter()->values();

        $configs = AssessmentServiceConfig::query()
            ->whereIn('assessment_service_unit_level_id', $asulIds)
            ->get(['assessment_service_unit_level_id', 'st_health_service_id', 'is_enabled'])
            ->groupBy('assessment_service_unit_level_id');

        $servicesByUnit = $bestByUnit->map(function ($best) use ($servicesByLevel, $configs) {
            if (!$best['key']) {
                return collect();
            }
            $base = collect($servicesByLevel->get($best['key']) ?? []);
            $map  = collect($configs->get($best['id']) ?? [])->keyBy('st_health_service_id')->map->is_enabled;

            return $base->filter(fn($svc) => $map->has($svc->id) ? (bool) $map->get($svc->id) : true)
                ->values()
                ->pluck('name');
        });

        // ระบุว่าหน่วยบริการนี้เคยได้ level ไหนบ้าง (basic/medium/advanced)
        $approvedByUnit = $serviceUnits->mapWithKeys(function ($su) use ($mapLevel) {
            $levels = collect($su->assessmentLevels)
                ->map(fn($a) => $mapLevel($a->level))
                ->filter()
                ->unique()
                ->values();
            return [$su->id => ['levels' => $levels]];
        });

        // GAP summary (รวมนับจำนวนหน่วยที่ขาดในแต่ละข้อ)
        $gapBasic        = $this->aggregateGapsByLevel('basic', $filterYear, $filterRound, $filterRegion, $filterProvinceCode);
        $gapIntermediate = $this->aggregateGapsByLevel('medium', $filterYear, $filterRound, $filterRegion, $filterProvinceCode);
        $gapAdvanced     = $this->aggregateGapsByLevel('advanced', $filterYear, $filterRound, $filterRegion, $filterProvinceCode);

        // เตรียมรายชื่อหน่วยบริการภายใต้ GAP แต่ละข้อ (เอาไว้ใช้ใน PDF)
        $gapUnitsByLevel = [
            'basic'    => [],
            'medium'   => [],
            'advanced' => [],
        ];

        foreach (['basic', 'medium', 'advanced'] as $lvCode) {
            $gapList = match ($lvCode) {
                'basic'    => $gapBasic,
                'medium'   => $gapIntermediate,
                'advanced' => $gapAdvanced,
            };

            foreach ($gapList as $gapRow) {
                $questionId = (int) $gapRow->question_id;

                $q = DB::table('assessment_answers as aa')
                    ->join('assessment_forms as f', 'f.id', '=', 'aa.assessment_form_id')
                    ->join('assessment_questions as q', 'q.id', '=', 'aa.assessment_question_id')
                    ->join('service_units as su', 'su.id', '=', 'f.service_unit_id')
                    ->leftJoin('province as p', 'p.code', '=', 'su.org_province_code')
                    ->join('assessment_service_unit_levels as lvl', function ($j) {
                        $j->on('lvl.service_unit_id', '=', 'f.service_unit_id')
                            ->on('lvl.assess_year', '=', 'f.assess_year')
                            ->on('lvl.assess_round', '=', 'f.assess_round');
                    })
                    ->where('aa.assessment_question_id', $questionId)
                    ->where('aa.answer_bool', 0)
                    ->where('f.assess_year', $filterYear)
                    ->where('f.assess_round', $filterRound)
                    ->where('f.level_code', $lvCode)
                    ->where('lvl.approval_status', 'approved');

                if (!empty($filterRegion)) {
                    $q->where('p.health_region_id', $filterRegion);
                }
                if (!empty($filterProvinceCode)) {
                    $q->where('su.org_province_code', $filterProvinceCode);
                }

                $rows = $q->distinct()
                    ->orderBy('su.org_province_code')
                    ->orderBy('su.org_name')
                    ->get([
                        'su.id as service_unit_id',
                        'su.org_name as name',
                        'su.org_province_code as province_code',
                        DB::raw("COALESCE(p.title, '-') as province"),
                    ]);

                $gapUnitsByLevel[$lvCode][$questionId] = [
                    'gap_label' => $gapRow->gap_label,
                    'units'     => $rows,
                ];
            }
        }

        return compact(
            'serviceUnits',
            'approvedByUnit',
            'bestByUnit',
            'servicesByUnit',
            'filterYear',
            'filterRound',
            'filterRegion',
            'filterProvinceCode',
            'gapBasic',
            'gapIntermediate',
            'gapAdvanced',
            'gapUnitsByLevel',
        );
    }

    /**
     * =====[ UNIT DATA ]========================================================
     * รวม logic เดิมของ unit() ให้ทั้งหน้า UI และ exportUnit ใช้ร่วมกัน
     *
     * return array:
     *  - serviceUnitId
     *  - filterYear
     *  - filterRound
     *  - unit (ServiceUnit model + relations จำกัดตามปี/รอบ)
     */
    private function getUnitData(Request $request): array
    {
        $serviceUnitId = (int) $request->query('service_unit_id');

        // ปีงบประมาณและรอบ
        $filterYear  = (int) $request->input('year', fiscalYearCE());
        $filterRound = (int) $request->input('round', fiscalRound());

        // subquery เตรียมไว้ (อาจใช้ขยายต่อ เช่น join ค่า latest id)
        $latestForm = $this->buildLatestFormSubquery($filterYear, $filterRound);
        $latestAsul = $this->buildLatestAsulSubquery($filterYear, $filterRound);

        // ดึงข้อมูลหน่วยบริการ + relation ที่จำกัดปี/รอบ
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

        return [
            'serviceUnitId' => $serviceUnitId,
            'filterYear'    => $filterYear,
            'filterRound'   => $filterRound,
            'unit'          => $unit,
            // เก็บไว้เผื่อ view export อยากรู้
            'latestForm'    => $latestForm,
            'latestAsul'    => $latestAsul,
        ];
    }

    /**
     * รวม GAP (answer_bool = 0) ต่อ "คำถาม" และนับจำนวนหน่วยบริการที่มี GAP ข้อนั้น
     * - จำกัดปี/รอบ
     * - จำกัดระดับ
     * - เฉพาะเรคคอร์ดที่อนุมัติแล้ว
     * - เคารพตัวกรองเขต/จังหวัด
     */
    private function aggregateGapsByLevel(string $level, int $year, int $round, $regionId = null, $provinceCode = null)
    {
        $answersTbl   = 'assessment_answers';
        $formsTbl     = 'assessment_forms';
        $questionsTbl = 'assessment_questions';
        $levelsTbl    = 'assessment_service_unit_levels';
        $suTbl        = 'service_units';
        $provTbl      = 'province';

        $q = DB::table("$answersTbl as aa")
            ->join("$formsTbl as f", 'f.id', '=', 'aa.assessment_form_id')
            ->join("$questionsTbl as q", 'q.id', '=', 'aa.assessment_question_id')
            ->join("$levelsTbl as lvl", function ($j) {
                $j->on('lvl.service_unit_id', '=', 'f.service_unit_id')
                    ->on('lvl.assess_year', '=', 'f.assess_year')
                    ->on('lvl.assess_round', '=', 'f.assess_round');
            })
            ->join("$suTbl as su", 'su.id', '=', 'f.service_unit_id')
            ->leftJoin("$provTbl as p", 'p.code', '=', 'su.org_province_code')
            ->where('f.assess_year', $year)
            ->where('f.assess_round', $round)
            ->where('f.level_code', $level)
            ->where('lvl.approval_status', 'approved')
            ->where('aa.answer_bool', 0);

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

    /**
     * AJAX: รายชื่อหน่วยบริการที่มี GAP ข้อนั้น
     */
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

        $answersTbl   = 'assessment_answers';
        $formsTbl     = 'assessment_forms';
        $levelsTbl    = 'assessment_service_unit_levels';
        $questionsTbl = 'assessment_questions';
        $suTbl        = 'service_units';
        $provTbl      = 'province';

        $q = DB::table("$answersTbl as aa")
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

        if (!empty($regionId)) {
            $q->where('p.health_region_id', $regionId);
        }
        if (!empty($provinceCode)) {
            $q->where('su.org_province_code', $provinceCode);
        }

        $rows = $q->distinct()
            ->orderBy('su.org_province_code')
            ->orderBy('su.org_name')
            ->get([
                'su.id as service_unit_id',
                'su.org_name as name',
                'su.org_province_code as province_code',
                DB::raw("COALESCE(p.title, '-') as province"),
            ]);

        return response()->json([
            'ok'    => true,
            'count' => $rows->count(),
            'rows'  => $rows,
        ]);
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

    /**
     * ส่งออกผลรวมจากหน้า Overview (Excel/PDF)
     */
    public function exportOverview(Request $request)
    {
        $format = $request->get('format', 'excel');

        // ดึงข้อมูลชุดเดียวกับ overview()
        $data = $this->getOverviewData($request);

        if ($format === 'excel') {
            return Excel::download(
                new \App\Exports\DashboardExport($data),
                'สรุปภาพรวมปี_' . ($data['filterYear'] + 543) . '_รอบที่_' . $data['filterRound'] . '.xlsx'
            );
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('backend.dashboard.export_overview.pdf', $data)
                ->setPaper('a4', 'portrait');

            $filename = 'สรุปภาพรวมปี_' . ($data['filterYear'] + 543) . '_รอบที่_' . $data['filterRound'] . '.pdf';

            // stream = inline preview
            return $pdf->stream($filename);
        }

        abort(400, 'Invalid format');
    }

    /**
     * ส่งออกข้อมูลรายหน่วยบริการเดียว (unit)
     * - ใช้ getUnitData() เหมือนหน้า UI เพื่อไม่ duplicate query
     * - format=excel -> download .xlsx
     * - format=pdf   -> stream(เปิดแท็บใหม่)
     *
     * view ที่ต้องเตรียม:
     *   resources/views/backend/dashboard/export_unit/excel.blade.php
     *   resources/views/backend/dashboard/export_unit/pdf.blade.php
     *
     * และสร้าง Export class:
     *   \App\Exports\UnitDashboardExport
     */
    public function exportUnit(Request $request)
    {
        // ต้องมี service_unit_id เสมอ
        if (!$request->query('service_unit_id')) {
            abort(400, 'Missing service_unit_id');
        }

        $format = $request->get('format', 'excel');

        // ดึงข้อมูลแบบเดียวกับหน้า unit()
        $data = $this->getUnitData($request);

        $serviceUnitId = $data['serviceUnitId'];
        $filterYear    = $data['filterYear'];
        $filterRound   = $data['filterRound'];

        if ($format === 'excel') {
            return Excel::download(
                new \App\Exports\UnitDashboardExport($data),
                'รายงานหน่วย_' . $serviceUnitId . '_ปี_' . ($filterYear + 543) . '_รอบที่_' . $filterRound . '.xlsx'
            );
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('backend.dashboard.export_unit.pdf', $data)
                ->setPaper('a4', 'portrait');

            $filename = 'รายงานหน่วย_' . $serviceUnitId . '_ปี_' . ($filterYear + 543) . '_รอบที่_' . $filterRound . '.pdf';

            return $pdf->stream($filename);
        }

        abort(400, 'Invalid format');
    }
}
