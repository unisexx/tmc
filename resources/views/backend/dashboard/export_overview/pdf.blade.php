{{-- resources/views/backend/dashboard/export_overview/pdf.blade.php --}}
@extends('layouts.pdf')

@section('title', 'รายงานสรุปภาพรวมหน่วยบริการสุขภาพผู้เดินทาง ปีงบประมาณ ' . ($filterYear + 543) . ' รอบที่ ' . $filterRound)

@section('content')

    @php
        use App\Models\HealthRegion;
        use App\Models\Province;
        use App\Models\AssessmentServiceUnitLevel;

        /*
        ==========================================================================================
        GLOBAL PREP
        ==========================================================================================
        */

        // ====== filter text บรรทัดใต้หัวรายงาน ======
        $regionName = null;
        $provinceName = null;

        if (!empty($filterRegion)) {
            $regionName = optional(HealthRegion::find($filterRegion))->title ?? "เขตสุขภาพที่ {$filterRegion}";
        }

        if (!empty($filterProvinceCode)) {
            $provinceName = optional(Province::where('code', $filterProvinceCode)->first())->title ?? '-';
        }

        $filterText = '';
        if ($regionName && $provinceName) {
            $filterText = "{$regionName} / จังหวัด{$provinceName}";
        } elseif ($regionName) {
            $filterText = $regionName;
        } elseif ($provinceName) {
            $filterText = "จังหวัด{$provinceName}";
        } else {
            $filterText = 'ทุกเขตสุขภาพ / ทุกจังหวัด';
        }

        // ====== ระดับบริการ (key → label ภาษาไทย) ใช้ซ้ำหลาย section ======
        $LEVEL_TEXTS_GLOBAL = [
            'advanced' => 'ระดับสูง',
            'medium' => 'ระดับกลาง',
            'basic' => 'ระดับพื้นฐาน',
            'unassessed' => 'ยังไม่ได้ประเมิน',
        ];

        // ========== Helper สำหรับเลือก "ระดับสูงสุดที่อนุมัติแล้ว" ==========
        $mapLevelHelper = function ($v) {
            $s = strtolower((string) $v);
            return match ($s) {
                'พื้นฐาน', 'ระดับพื้นฐาน', 'basic' => 'basic',
                'กลาง', 'ระดับกลาง', 'medium' => 'medium',
                'สูง', 'ระดับสูง', 'advanced' => 'advanced',
                default => null,
            };
        };
        $preferLevelsDesc = ['advanced', 'medium', 'basic']; // best → worst

        // ====== เตรียมข้อมูล group ต่อระดับ (SECTION 1) ======
        $groupedUnits = [
            'advanced' => [],
            'medium' => [],
            'basic' => [],
            'unassessed' => [],
        ];

        foreach ($serviceUnits as $su) {
            $bestInfo = $bestByUnit[$su->id] ?? null;
            $keyLevel = $bestInfo['key'] ?? 'unassessed';
            $groupedUnits[$keyLevel][] = [
                'name' => $su->org_name ?? '-',
                'province' => optional($su->province)->title ?? '-',
            ];
        }

        // ====== รายการหน่วยบริการ + พิกัด (SECTION 2) ======
        $facilityRowsForPdf = collect($serviceUnits)
            ->map(function ($su) use ($approvedByUnit, $preferLevelsDesc, $LEVEL_TEXTS_GLOBAL) {
                $lvCol = collect(data_get($approvedByUnit, "{$su->id}.levels", collect()));
                $picked = 'unassessed';
                foreach ($preferLevelsDesc as $candidate) {
                    if ($lvCol->contains($candidate)) {
                        $picked = $candidate;
                        break;
                    }
                }

                return [
                    'name' => $su->org_name ?? '-',
                    'province' => optional($su->province)->title ?? '-',
                    'lat' => $su->org_lat ?: '',
                    'lon' => $su->org_lng ?: '',
                    'phone' => $su->org_tel ?: '',
                    'levelText' => $LEVEL_TEXTS_GLOBAL[$picked] ?? $picked,
                ];
            })
            ->sortBy('province')
            ->values();

        /*
        ==========================================================================================
        SECTION 3 PREP - GAP
        ==========================================================================================
        */
        $GAP_LEVEL_ORDER = ['basic', 'medium', 'advanced'];
        $GAP_LEVEL_LABELS = [
            'basic' => 'ระดับพื้นฐาน',
            'medium' => 'ระดับกลาง',
            'advanced' => 'ระดับสูง',
        ];

        /*
        ==========================================================================================
        SECTION 4 PREP - สถานะการประเมิน/อนุมัติ
        ==========================================================================================
        */
        $displayOrderStatus = config('tmc.approval_display_order', ['pending', 'reviewing', 'returned', 'approved', 'rejected', 'no_form']);
        $statusLabels = config('tmc.approval_text', []);
        $statusBgHex = config('tmc.approval_card_bg', []);
        $statusFgClass = config('tmc.approval_card_fg', []);
        $dbStatusesAllowed = ['pending', 'reviewing', 'returned', 'approved', 'rejected'];

        $unitIds = $serviceUnits->pluck('id')->all();
        $latestAll = collect();
        if (!empty($unitIds)) {
            $latestAll = AssessmentServiceUnitLevel::select('id', 'service_unit_id', 'assess_year', 'assess_round', 'approval_status')->whereIn('service_unit_id', $unitIds)->where('assess_year', $filterYear)->where('assess_round', $filterRound)->whereNull('deleted_at')->orderByDesc('id')->get()->groupBy('service_unit_id')->map->first();
        }

        $statusBuckets = [];
        foreach ($displayOrderStatus as $key) {
            $statusBuckets[$key] = [
                'count' => 0,
                'units' => collect(),
            ];
        }

        foreach ($serviceUnits as $su) {
            $record = $latestAll->get($su->id);

            if (!$record) {
                $bucketKey = 'no_form';
            } else {
                $st = $record->approval_status;
                if ($st === 'pending' || $st === null || $st === '') {
                    $bucketKey = 'pending';
                } elseif (in_array($st, $dbStatusesAllowed, true)) {
                    $bucketKey = $st;
                } else {
                    continue;
                }
            }

            $statusBuckets[$bucketKey]['count']++;
            $statusBuckets[$bucketKey]['units']->push(
                (object) [
                    'org_name' => $su->org_name ?? ($su->name ?? '—'),
                    'province' => optional($su->province)->title ?? '-',
                ],
            );
        }

        foreach ($statusBuckets as $key => $bucket) {
            $statusBuckets[$key]['units'] = $bucket['units']->sortBy('org_name', SORT_NATURAL | SORT_FLAG_CASE)->values();
        }

        /*
        ==========================================================================================
        SECTION 5 PREP - ระดับตามเขตสุขภาพ (สคร.)
        ==========================================================================================
        */
        $showRegionSection = empty($filterRegion);

        if ($showRegionSection) {
            $REGION_NAMES = HealthRegion::query()
                ->orderBy('id')
                ->get(['id', 'title', 'short_title'])
                ->mapWithKeys(function ($r) {
                    $nm = $r->short_title ?: $r->title ?: 'สคร.' . $r->id;
                    return [(int) $r->id => $nm];
                });

            $regionRowsMap = [];

            foreach ($serviceUnits as $su) {
                $rid = (int) data_get($su, 'province.health_region_id', 0);
                if ($rid === 0) {
                    continue;
                }

                if (!isset($regionRowsMap[$rid])) {
                    $regionRowsMap[$rid] = [
                        'region_id' => $rid,
                        'region_name' => $REGION_NAMES->get($rid, 'สคร.' . $rid),
                        'basic' => 0,
                        'medium' => 0,
                        'advanced' => 0,
                        'unassessed' => 0,
                    ];
                }

                $approvedLevels = collect(data_get($su, 'assessmentLevels', []))->map(fn($a) => $mapLevelHelper(data_get($a, 'level')))->filter()->unique()->values();

                $pickedLevel = 'unassessed';
                foreach ($preferLevelsDesc as $candidate) {
                    if ($approvedLevels->contains($candidate)) {
                        $pickedLevel = $candidate;
                        break;
                    }
                }

                $regionRowsMap[$rid][$pickedLevel] = (int) $regionRowsMap[$rid][$pickedLevel] + 1;
            }

            foreach ($REGION_NAMES as $rid => $rname) {
                if (!isset($regionRowsMap[$rid])) {
                    $regionRowsMap[$rid] = [
                        'region_id' => (int) $rid,
                        'region_name' => $rname,
                        'basic' => 0,
                        'medium' => 0,
                        'advanced' => 0,
                        'unassessed' => 0,
                    ];
                }
            }

            $orderLevelsRegion = ['unassessed', 'basic', 'medium', 'advanced'];

            $regionRows = collect($regionRowsMap)
                ->values()
                ->map(function ($r) use ($orderLevelsRegion) {
                    $sum = 0;
                    foreach ($orderLevelsRegion as $lv) {
                        $sum += (int) ($r[$lv] ?? 0);
                    }
                    $r['_total'] = $sum;
                    return $r;
                })
                ->sortBy('region_id')
                ->values();

            $regionColTotals = [];
            foreach ($orderLevelsRegion as $lv) {
                $regionColTotals[$lv] = (int) collect($regionRows)->sum($lv);
            }
            $regionGrandTotal = (int) collect($regionRows)->sum('_total');
        }

        /*
        ==========================================================================================
        SECTION 6 PREP - ระดับตามจังหวัด
        ==========================================================================================
        */
        $PROVINCE_NAMES = Province::query()
            ->when(isset($filterRegion) && $filterRegion, fn($q) => $q->where('health_region_id', (int) $filterRegion))
            ->orderBy('code')
            ->get(['code', 'title'])
            ->mapWithKeys(fn($p) => [(int) $p->code => $p->title]);

        $provinceRowsMap = [];
        $orderLevelsProvince = ['unassessed', 'basic', 'medium', 'advanced'];

        foreach ($serviceUnits as $su) {
            $pcode = (int) data_get($su, 'org_province_code', 0);
            if ($pcode === 0 || !$PROVINCE_NAMES->has($pcode)) {
                continue;
            }

            if (!isset($provinceRowsMap[$pcode])) {
                $provinceRowsMap[$pcode] = [
                    'province_code' => $pcode,
                    'province_name' => $PROVINCE_NAMES->get($pcode),
                    'basic' => 0,
                    'medium' => 0,
                    'advanced' => 0,
                    'unassessed' => 0,
                ];
            }

            $approvedLevels = collect(data_get($su, 'assessmentLevels', []))->map(fn($a) => $mapLevelHelper(data_get($a, 'level')))->filter()->unique()->values();

            $pickedLevel = 'unassessed';
            foreach ($preferLevelsDesc as $candidate) {
                if ($approvedLevels->contains($candidate)) {
                    $pickedLevel = $candidate;
                    break;
                }
            }

            $provinceRowsMap[$pcode][$pickedLevel] = (int) $provinceRowsMap[$pcode][$pickedLevel] + 1;
        }

        foreach ($PROVINCE_NAMES as $code => $title) {
            if (!isset($provinceRowsMap[$code])) {
                $provinceRowsMap[$code] = [
                    'province_code' => (int) $code,
                    'province_name' => $title,
                    'basic' => 0,
                    'medium' => 0,
                    'advanced' => 0,
                    'unassessed' => 0,
                ];
            }
        }

        $provinceRows = collect($provinceRowsMap)
            ->values()
            ->map(function ($r) use ($orderLevelsProvince) {
                $sum = 0;
                foreach ($orderLevelsProvince as $lv) {
                    $sum += (int) ($r[$lv] ?? 0);
                }
                $r['_total'] = $sum;
                return $r;
            })
            ->sortBy('province_code')
            ->values();

        $provinceColTotals = [];
        foreach ($orderLevelsProvince as $lv) {
            $provinceColTotals[$lv] = (int) collect($provinceRows)->sum($lv);
        }
        $provinceGrandTotal = (int) collect($provinceRows)->sum('_total');

        // สีหัวตารางมาตรฐานราชการ (เทาอ่อนตัวเข้ม)
        $theadBg = '#e5e5e5';
        $theadTextColor = '#000';
    @endphp


    {{-- ====================================================================================== --}}
    {{-- SECTION 1. ภาพรวมหน่วยบริการตามระดับศักยภาพ --}}
    {{-- ====================================================================================== --}}
    <div style="text-align:center; margin-bottom:1rem; page-break-inside: avoid;">
        <div style="font-size:1.1rem; font-weight:bold;">
            รายงานสรุปภาพรวมหน่วยบริการสุขภาพผู้เดินทาง
        </div>
        <div style="font-size:0.9rem;">
            ปีงบประมาณ {{ $filterYear + 543 }} (รอบที่ {{ $filterRound }})
        </div>
        <div style="font-size:0.8rem; color:#555; margin-top:0.25rem;">
            ขอบเขตข้อมูล: {{ $filterText }}
        </div>
    </div>

    <div style="font-size:0.8rem; margin-bottom:0.4rem;">
        ตารางนี้แสดงจำนวนและรายชื่อหน่วยบริการสุขภาพผู้เดินทาง แยกตามระดับศักยภาพที่ได้รับการอนุมัติ
        (ระดับสูง &gt; ระดับกลาง &gt; ระดับพื้นฐาน; ถ้าไม่ผ่านการอนุมัติแสดงเป็น "ยังไม่ได้ประเมิน")
    </div>

    @php
        $LEVEL_ORDER_DISPLAY = ['advanced', 'medium', 'basic', 'unassessed'];
    @endphp

    <table width="100%" border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse; font-size:0.75rem;">
        <thead>
            <tr style="background:{{ $theadBg }}; color:{{ $theadTextColor }}; font-weight:bold;">
                <th style="width:20%; text-align:center;">ระดับหน่วยบริการ</th>
                <th style="width:10%; text-align:center;">จำนวน (แห่ง)</th>
                <th style="width:70%; text-align:left;">รายชื่อหน่วยบริการ (จังหวัด)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($LEVEL_ORDER_DISPLAY as $lvKey)
                @php
                    $rowsLv = $groupedUnits[$lvKey] ?? [];
                    $countLv = count($rowsLv);
                @endphp
                <tr>
                    <td style="vertical-align:top; font-weight:bold;">
                        {{ $LEVEL_TEXTS_GLOBAL[$lvKey] ?? $lvKey }}
                    </td>
                    <td style="vertical-align:top; text-align:center;">
                        {{ $countLv }}
                    </td>
                    <td style="vertical-align:top;">
                        @if ($countLv > 0)
                            <ul style="margin:0; padding-left:1.2rem; line-height:1.4;">
                                @foreach ($rowsLv as $r)
                                    <li>{{ $r['name'] }} ({{ $r['province'] }})</li>
                                @endforeach
                            </ul>
                        @else
                            <span style="color:#888;">ไม่มีหน่วยบริการในระดับนี้</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>


    {{-- ====================================================================================== --}}
    {{-- SECTION 2. รายการหน่วยบริการและพิกัดตำแหน่ง --}}
    {{-- ====================================================================================== --}}

    <div style="page-break-before: always;"></div>

    <div style="font-size:0.9rem; font-weight:bold; margin-bottom:0.4rem;">
        2. รายการหน่วยบริการและพิกัดตำแหน่ง
    </div>

    <div style="font-size:0.75rem; margin-bottom:0.4rem;">
        ตารางนี้ใช้เพื่ออ้างอิงตำแหน่งทางภูมิศาสตร์ของหน่วยบริการ (ละติจูด/ลองจิจูด) รวมถึงข้อมูลติดต่อเบื้องต้น
        เพื่อประกอบการทำแผนที่และการติดตามหน่วยบริการในพื้นที่
    </div>

    <table width="100%" border="1" cellspacing="0" cellpadding="5" style="border-collapse:collapse; font-size:0.7rem;">
        <thead>
            <tr style="background:{{ $theadBg }}; color:{{ $theadTextColor }}; font-weight:bold;">
                <th style="width:2rem; text-align:center;">#</th>
                <th style="width:28%; text-align:left;">หน่วยบริการ</th>
                <th style="width:18%; text-align:left;">จังหวัด</th>
                <th style="width:14%; text-align:left;">ระดับ</th>
                <th style="width:18%; text-align:left;">โทรศัพท์</th>
                <th style="width:20%; text-align:center;">พิกัด (lat, lon)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($facilityRowsForPdf as $idx => $frow)
                <tr>
                    <td style="text-align:center; vertical-align:top;">{{ $idx + 1 }}</td>
                    <td style="vertical-align:top;">{{ $frow['name'] }}</td>
                    <td style="vertical-align:top;">{{ $frow['province'] }}</td>
                    <td style="vertical-align:top;">{{ $frow['levelText'] }}</td>
                    <td style="vertical-align:top;">{{ $frow['phone'] }}</td>
                    <td style="text-align:center; vertical-align:top;">
                        @if ($frow['lat'] && $frow['lon'])
                            {{ $frow['lat'] }}, {{ $frow['lon'] }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>


    {{-- ====================================================================================== --}}
    {{-- SECTION 3. ช่องว่าง (GAP) ที่พบในการประเมิน --}}
    {{-- ====================================================================================== --}}

    <div style="page-break-before: always;"></div>

    <div style="font-size:0.9rem; font-weight:bold; margin-bottom:0.4rem;">
        3. ช่องว่าง (GAP) ที่พบในการประเมิน
    </div>

    <div style="font-size:0.75rem; margin-bottom:0.4rem;">
        ช่องว่าง (GAP) หมายถึงรายการเงื่อนไข/มาตรฐานที่หน่วยบริการยังไม่ผ่าน (ตอบ "ยังไม่พร้อม / ไม่มี")
        โดยจัดกลุ่มตามระดับบริการที่คาดหวัง (พื้นฐาน / กลาง / สูง) และแสดงรายชื่อหน่วยบริการที่ยังมีช่องว่างนั้น
    </div>

    @foreach ($GAP_LEVEL_ORDER as $lvKey)
        @php
            $levelBlock = $gapUnitsByLevel[$lvKey] ?? [];
        @endphp

        <div style="margin-bottom:1rem; page-break-inside: avoid;">
            <div style="font-size:0.8rem; font-weight:bold; margin-bottom:0.4rem; background:#f8f9fa; border:1px solid #ddd; padding:.5rem .75rem;">
                ระดับเป้าหมาย: {{ $GAP_LEVEL_LABELS[$lvKey] ?? $lvKey }}
            </div>

            @if (empty($levelBlock))
                <div style="font-size:0.75rem; color:#666; margin-left:.5rem;">
                    ไม่พบช่องว่างในระดับนี้
                </div>
            @else
                @foreach ($levelBlock as $questionId => $gapInfo)
                    @php
                        $gapLabel = $gapInfo['gap_label'] ?? '-';
                        $unitList = $gapInfo['units'] ?? collect();
                    @endphp

                    <table width="100%" border="1" cellspacing="0" cellpadding="5" style="border-collapse:collapse; font-size:0.7rem; margin-bottom:.75rem;">
                        <thead>
                            <tr>
                                <th colspan="3" style="text-align:left; background:#eef4ff; font-weight:bold;">
                                    {{ $gapLabel }}
                                </th>
                            </tr>
                            <tr style="background:{{ $theadBg }}; color:{{ $theadTextColor }}; font-weight:bold;">
                                <th style="width:2rem; text-align:center;">#</th>
                                <th style="width:65%; text-align:left;">หน่วยบริการ</th>
                                <th style="width:33%; text-align:left;">จังหวัด</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($unitList->isEmpty())
                                <tr>
                                    <td colspan="3" style="text-align:center; color:#777;">
                                        ไม่พบบันทึกหน่วยบริการที่มี GAP นี้
                                    </td>
                                </tr>
                            @else
                                @foreach ($unitList as $idx => $u)
                                    <tr>
                                        <td style="text-align:center; vertical-align:top;">{{ $idx + 1 }}</td>
                                        <td style="vertical-align:top;">{{ $u->name }}</td>
                                        <td style="vertical-align:top;">{{ $u->province }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                @endforeach
            @endif
        </div>
    @endforeach


    {{-- ====================================================================================== --}}
    {{-- SECTION 4. สถานะการประเมิน/อนุมัติของหน่วยบริการ --}}
    {{-- ====================================================================================== --}}

    <div style="page-break-before: always;"></div>

    <div style="font-size:0.9rem; font-weight:bold; margin-bottom:0.4rem;">
        4. สถานะการประเมิน/อนุมัติของหน่วยบริการ
    </div>

    <div style="font-size:0.75rem; margin-bottom:0.4rem;">
        ส่วนนี้แสดงความคืบหน้าการพิจารณารับรองหน่วยบริการในรอบปีงบประมาณที่เลือก
        เช่น อยู่ระหว่างตรวจสอบ, ขอแก้ไข/ส่งกลับ, อนุมัติแล้ว หรือยังไม่ได้ยื่นประเมิน
    </div>

    @php
        // ------------------------------------------------------------------
        // จัดลำดับใหม่: เอา approved ไว้ก่อนเสมอ จากนั้นตามลำดับปกติ
        // ------------------------------------------------------------------
        $approvalFirstOrder = collect($displayOrderStatus)->reject(fn($st) => $st === 'approved')->prepend('approved')->values();
    @endphp

    @foreach ($approvalFirstOrder as $key)
        @php
            $label = $statusLabels[$key] ?? $key;
            $bgHex = $statusBgHex[$key] ?? '#999';
            $count = data_get($statusBuckets, "$key.count", 0);
            $unitsList = data_get($statusBuckets, "$key.units", collect());
        @endphp

        <table width="100%" border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse; font-size:0.7rem; margin-bottom:1rem;">
            <thead>
                {{-- แถวหัวหมวดสถานะ (แทนสีพื้นด้วยจุดสี) --}}
                <tr>
                    <th colspan="3" style="text-align:left; font-weight:bold; background:#fff; color:#000;">
                        <span style="
                            display:inline-block;
                            width:10px;
                            height:10px;
                            border-radius:50%;
                            background:{{ $bgHex }};
                            margin-right:6px;
                            vertical-align:middle;
                        "></span>
                        {{ $label }}
                        <span style="font-weight:normal;">
                            ({{ number_format($count) }} หน่วยบริการ)
                        </span>
                    </th>
                </tr>

                {{-- แถวหัวคอลัมน์ข้อมูล --}}
                <tr style="background:{{ $theadBg }}; color:{{ $theadTextColor }}; font-weight:bold;">
                    <th style="width:2rem; text-align:center;">#</th>
                    <th style="width:68%; text-align:left;">ชื่อหน่วยบริการ</th>
                    <th style="width:30%; text-align:left;">จังหวัด</th>
                </tr>
            </thead>

            <tbody>
                @if ($unitsList->isEmpty())
                    <tr>
                        <td colspan="3" style="text-align:center; color:#777;">
                            ไม่มีข้อมูล
                        </td>
                    </tr>
                @else
                    @foreach ($unitsList as $idx => $u)
                        <tr>
                            <td style="text-align:center; vertical-align:top;">{{ $idx + 1 }}</td>
                            <td style="vertical-align:top;">{{ $u->org_name }}</td>
                            <td style="vertical-align:top;">{{ $u->province }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    @endforeach





    {{-- ====================================================================================== --}}
    {{-- SECTION 5. สรุประดับของหน่วยบริการตามเขตสุขภาพ (สคร.) --}}
    {{-- ====================================================================================== --}}

    @if ($showRegionSection)
        <div style="page-break-before: always;"></div>

        <div style="font-size:0.9rem; font-weight:bold; margin-bottom:0.4rem;">
            5. สรุประดับของหน่วยบริการตามเขตสุขภาพ (สคร.)
        </div>

        <div style="font-size:0.75rem; margin-bottom:0.4rem;">
            ตารางนี้แสดงจำนวนหน่วยบริการ (แห่ง) จำแนกตามระดับศักยภาพสูงสุดที่ได้รับการอนุมัติ
            ในแต่ละเขตสุขภาพ (สคร.) และผลรวมในประเทศ
        </div>

        <table width="100%" border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse; font-size:0.7rem; margin-bottom:1rem;">
            <thead>
                <tr style="background:{{ $theadBg }}; color:{{ $theadTextColor }}; font-weight:bold;">
                    <th style="width:18%; text-align:left;">สคร.</th>
                    @foreach ($orderLevelsRegion as $lv)
                        <th style="text-align:right;">
                            {{ $LEVEL_TEXTS_GLOBAL[$lv] ?? ucfirst($lv) }}
                        </th>
                    @endforeach
                    <th style="width:10%; text-align:right;">รวม</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($regionRows as $r)
                    @php $rowTotal = (int) ($r['_total'] ?? 0); @endphp
                    <tr>
                        <td style="text-align:left; vertical-align:top;">
                            {{ $r['region_name'] }}
                        </td>

                        @foreach ($orderLevelsRegion as $lv)
                            <td style="text-align:right; vertical-align:top;">
                                {{ number_format((int) ($r[$lv] ?? 0)) }}
                            </td>
                        @endforeach

                        <td style="text-align:right; vertical-align:top; font-weight:bold;">
                            {{ number_format($rowTotal) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($orderLevelsRegion) + 2 }}" style="text-align:center; color:#777;">
                            ไม่มีข้อมูล
                        </td>
                    </tr>
                @endforelse
            </tbody>

            <tfoot>
                <tr style="background:#f8f9fa; font-weight:bold;">
                    <td style="text-align:left;">รวมทั้งหมด</td>

                    @foreach ($orderLevelsRegion as $lv)
                        <td style="text-align:right;">
                            {{ number_format($regionColTotals[$lv] ?? 0) }}
                        </td>
                    @endforeach

                    <td style="text-align:right;">
                        {{ number_format($regionGrandTotal) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    @endif


    {{-- ====================================================================================== --}}
    {{-- SECTION 6. สรุประดับของหน่วยบริการตามจังหวัด --}}
    {{-- ====================================================================================== --}}

    <div style="page-break-before: always;"></div>

    <div style="font-size:0.9rem; font-weight:bold; margin-bottom:0.4rem;">
        6. สรุประดับของหน่วยบริการตามจังหวัด
    </div>

    <div style="font-size:0.75rem; margin-bottom:0.4rem;">
        ตารางนี้แสดงจำนวนหน่วยบริการ (แห่ง) จำแนกตามระดับศักยภาพสูงสุดที่ได้รับการอนุมัติ
        รายจังหวัดภายใต้ขอบเขตการค้นหาปัจจุบัน
        @if (!empty($filterRegion))
            (เขตสุขภาพที่ {{ $filterRegion }})
        @endif
    </div>

    <table width="100%" border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse; font-size:0.7rem; margin-bottom:1rem;">
        <thead>
            <tr style="background:{{ $theadBg }}; color:{{ $theadTextColor }}; font-weight:bold;">
                <th style="min-width:160px; text-align:left;">จังหวัด</th>
                @foreach ($orderLevelsProvince as $lv)
                    <th style="text-align:right;">
                        {{ $LEVEL_TEXTS_GLOBAL[$lv] ?? ucfirst($lv) }}
                    </th>
                @endforeach
                <th style="width:10%; text-align:right;">รวม</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($provinceRows as $row)
                @php $rowTotal = (int) ($row['_total'] ?? 0); @endphp
                <tr>
                    <td style="text-align:left; vertical-align:top;">
                        {{ $row['province_name'] }}
                    </td>

                    @foreach ($orderLevelsProvince as $lv)
                        <td style="text-align:right; vertical-align:top;">
                            {{ number_format((int) ($row[$lv] ?? 0)) }}
                        </td>
                    @endforeach

                    <td style="text-align:right; vertical-align:top; font-weight:bold;">
                        {{ number_format($rowTotal) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($orderLevelsProvince) + 2 }}" style="text-align:center; color:#777;">
                        ไม่มีข้อมูล
                    </td>
                </tr>
            @endforelse
        </tbody>

        <tfoot>
            <tr style="background:#f8f9fa; font-weight:bold;">
                <td style="text-align:left;">รวมทั้งหมด</td>

                @foreach ($orderLevelsProvince as $lv)
                    <td style="text-align:right;">
                        {{ number_format($provinceColTotals[$lv] ?? 0) }}
                    </td>
                @endforeach

                <td style="text-align:right;">
                    {{ number_format($provinceGrandTotal) }}
                </td>
            </tr>
        </tfoot>
    </table>

@endsection
