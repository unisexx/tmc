<?php
// app/Http/Controllers/Backend/DashboardController.php

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
     */
    public function index(Request $request)
    {
        return $this->overview($request);
    }

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

        return view('backend.dashboard.overview', compact(
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

    public function export(Request $request)
    {
        $format = $request->get('format', 'excel');
        $year   = $request->get('year', fiscalYearCE());
        $round  = $request->get('round', fiscalRound());

        // ดึงข้อมูลเหมือนหน้า dashboard
        $serviceUnits = ServiceUnit::with(['province', 'assessmentLevels'])
            ->when($request->filled('region'), fn($q) => $q->whereHas('province', fn($p) => $p->where('health_region_id', $request->region)))
            ->get();

        $data = [
            'serviceUnits' => $serviceUnits,
            'filterYear'   => $year,
            'filterRound'  => $round,
        ];

        if ($format === 'excel') {
            return Excel::download(
                new \App\Exports\DashboardExport($data),
                'สรุปภาพรวมปี_' . ($year + 543) . '_รอบที่_' . $round . '.xlsx'
            );
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('backend.dashboard.export_pdf', $data)->setPaper('a4', 'portrait');
            return $pdf->download('สรุปภาพรวมปี_' . ($year + 543) . '_รอบที่_' . $round . '.pdf');
        }

        abort(400, 'Invalid format');
    }
}
