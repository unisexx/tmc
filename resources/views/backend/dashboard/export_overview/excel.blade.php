{{-- resources/views/backend/dashboard/export_overview/excel.blade.php --}}
@php
    use App\Models\HealthRegion;
    use App\Models\Province;
    use App\Models\AssessmentServiceUnitLevel;

    /*
เตรียมข้อมูลเหมือน pdf.blade.php
*/

    // ===== filter text =====
    $regionName = null;
    $provinceName = null;

    if (!empty($filterRegion)) {
        $regionName = optional(HealthRegion::find($filterRegion))->title ?? "เขตสุขภาพที่ {$filterRegion}";
    }
    if (!empty($filterProvinceCode)) {
        $provinceName = optional(Province::where('code', $filterProvinceCode)->first())->title ?? '-';
    }

    if ($regionName && $provinceName) {
        $filterText = "{$regionName} / จังหวัด{$provinceName}";
    } elseif ($regionName) {
        $filterText = $regionName;
    } elseif ($provinceName) {
        $filterText = "จังหวัด{$provinceName}";
    } else {
        $filterText = 'ทุกเขตสุขภาพ / ทุกจังหวัด';
    }

    // ===== label ระดับบริการ =====
    $LEVEL_TEXTS_GLOBAL = [
        'advanced' => 'ระดับสูง',
        'medium' => 'ระดับกลาง',
        'basic' => 'ระดับพื้นฐาน',
        'unassessed' => 'ยังไม่ได้ประเมิน',
    ];

    // helper เลือกระดับสูงสุด
    $mapLevelHelper = function ($v) {
        $s = strtolower((string) $v);
        return match ($s) {
            'พื้นฐาน', 'ระดับพื้นฐาน', 'basic' => 'basic',
            'กลาง', 'ระดับกลาง', 'medium' => 'medium',
            'สูง', 'ระดับสูง', 'advanced' => 'advanced',
            default => null,
        };
    };
    $preferLevelsDesc = ['advanced', 'medium', 'basic'];

    // ===== SECTION 1 data =====
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

    $LEVEL_ORDER_DISPLAY = ['advanced', 'medium', 'basic', 'unassessed'];

    // ===== SECTION 2 data =====
    $facilityRowsForExcel = collect($serviceUnits)
        ->map(function ($su) use ($approvedByUnit, $preferLevelsDesc, $LEVEL_TEXTS_GLOBAL) {
            $lvCol = collect(data_get($approvedByUnit, "{$su->id}.levels", collect()));
            $picked = 'unassessed';
            foreach ($preferLevelsDesc as $c) {
                if ($lvCol->contains($c)) {
                    $picked = $c;
                    break;
                }
            }
            return [
                'name' => $su->org_name ?? '-',
                'province' => optional($su->province)->title ?? '-',
                'levelText' => $LEVEL_TEXTS_GLOBAL[$picked] ?? $picked,
                'phone' => $su->org_tel ?: '',
                'lat' => $su->org_lat ?: '',
                'lon' => $su->org_lng ?: '',
            ];
        })
        ->sortBy('province')
        ->values();

    // ===== SECTION 3 data (GAP) =====
    $GAP_LEVEL_ORDER = $GAP_LEVEL_ORDER ?? ['basic', 'medium', 'advanced'];
    $GAP_LEVEL_LABELS = [
        'basic' => 'ระดับพื้นฐาน',
        'medium' => 'ระดับกลาง',
        'advanced' => 'ระดับสูง',
    ];

    // ===== SECTION 4 data (สถานะ) =====
    $displayOrderStatus = $displayOrderStatus ?? ['pending', 'reviewing', 'returned', 'approved', 'rejected', 'no_form'];
    $statusLabels = $statusLabels ?? [];
    $statusBgHex = $statusBgHex ?? [];
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

    // approved มาก่อน
    $approvalFirstOrder = collect($displayOrderStatus)->reject(fn($st) => $st === 'approved')->prepend('approved')->values();

    // ===== SECTION 5 data (สรุปตามเขตสุขภาพ) =====
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
    } else {
        $orderLevelsRegion = $orderLevelsRegion ?? ['unassessed', 'basic', 'medium', 'advanced'];
        $regionRows = $regionRows ?? collect();
        $regionColTotals = $regionColTotals ?? [];
        $regionGrandTotal = $regionGrandTotal ?? 0;
    }

    // ===== SECTION 6 data (สรุปตามจังหวัด) =====
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
@endphp


{{-- ======================== --}}
{{-- SECTION 1 HEADER BLOCK (MERGE) --}}
{{-- ======================== --}}
<table>
    <tr>
        <td colspan="6" style="font-weight:bold; font-size:14pt;">
            รายงานสรุปภาพรวมหน่วยบริการสุขภาพผู้เดินทาง
        </td>
    </tr>
    <tr>
        <td colspan="6" style="font-size:11pt;">
            ปีงบประมาณ {{ $filterYear + 543 }} (รอบที่ {{ $filterRound }})
        </td>
    </tr>
    <tr>
        <td colspan="6" style="font-size:10pt;">
            ขอบเขตข้อมูล: {{ $filterText }}
        </td>
    </tr>
    <tr>
        <td colspan="6" style="font-weight:bold; font-size:12pt;">
            1. ภาพรวมหน่วยบริการตามระดับศักยภาพ
        </td>
    </tr>
    <tr>
        <td colspan="6" style="font-size:10pt;">
            จำนวนและรายชื่อหน่วยบริการสุขภาพผู้เดินทาง แยกตามระดับศักยภาพที่ได้รับการอนุมัติ
            (ระดับสูง > ระดับกลาง > ระดับพื้นฐาน; ถ้าไม่ผ่านการอนุมัติแสดงเป็น "ยังไม่ได้ประเมิน")
        </td>
    </tr>
</table>

<table border="1">
    <thead style="background:#e5e5e5;">
        <tr>
            <th style="width:20%;">ระดับหน่วยบริการ</th>
            <th style="width:10%;">จำนวน (แห่ง)</th>
            <th style="width:70%;">รายชื่อหน่วยบริการ (จังหวัด)</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($LEVEL_ORDER_DISPLAY as $lvKey)
            @php
                $rowsLv = $groupedUnits[$lvKey] ?? [];
                $countLv = count($rowsLv);
            @endphp
            <tr>
                {{-- จัดชิดบนแนวตั้ง --}}
                <td style="vertical-align: top;"><strong>{{ $LEVEL_TEXTS_GLOBAL[$lvKey] ?? $lvKey }}</strong></td>
                <td style="text-align:center; vertical-align: top;">{{ $countLv }}</td>
                <td style="vertical-align: top;">
                    @if ($countLv > 0)
                        @foreach ($rowsLv as $r)
                            • {{ $r['name'] }} ({{ $r['province'] }})@if (!$loop->last)
                                <br>
                            @endif
                        @endforeach
                    @else
                        ไม่มีหน่วยบริการในระดับนี้
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>


{{-- ======================== --}}
{{-- SECTION 2 HEADER BLOCK (MERGE) --}}
{{-- ======================== --}}
<table style="margin-top:20px;">
    <tr>
        <td colspan="6" style="font-weight:bold; font-size:12pt;">
            2. รายการหน่วยบริการและพิกัดตำแหน่ง
        </td>
    </tr>
    <tr>
        <td colspan="6" style="font-size:10pt;">
            ใช้อ้างอิงตำแหน่งภูมิศาสตร์ของหน่วยบริการ (ละติจูด/ลองจิจูด)
            รวมถึงข้อมูลติดต่อเบื้องต้น สำหรับการทำแผนที่และติดตามหน่วยบริการในพื้นที่
        </td>
    </tr>
</table>

<table border="1">
    <thead style="background:#e5e5e5;">
        <tr>
            <th style="width:3%;">#</th>
            <th style="width:30%;">หน่วยบริการ</th>
            <th style="width:18%;">จังหวัด</th>
            <th style="width:14%;">ระดับ</th>
            <th style="width:18%;">โทรศัพท์</th>
            <th style="width:20%;">พิกัด (lat, lon)</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($facilityRowsForExcel as $idx => $f)
            <tr>
                <td style="text-align:center;">{{ $idx + 1 }}</td>
                <td>{{ $f['name'] }}</td>
                <td>{{ $f['province'] }}</td>
                <td>{{ $f['levelText'] }}</td>
                <td>{{ $f['phone'] }}</td>
                <td style="text-align:center;">
                    @if ($f['lat'] && $f['lon'])
                        {{ $f['lat'] }}, {{ $f['lon'] }}
                    @else
                        -
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>


{{-- ======================== --}}
{{-- SECTION 3 HEADER BLOCK (MERGE) --}}
{{-- ======================== --}}
<table style="margin-top:20px;">
    <tr>
        <td colspan="6" style="font-weight:bold; font-size:12pt;">
            3. ช่องว่าง (GAP) ที่พบในการประเมิน
        </td>
    </tr>
    <tr>
        <td colspan="6" style="font-size:10pt;">
            GAP = รายการเงื่อนไข/มาตรฐานที่หน่วยบริการยังไม่ผ่าน (ตอบ "ยังไม่พร้อม / ไม่มี")
            จัดกลุ่มตามระดับบริการที่คาดหวัง และระบุหน่วยบริการที่ยังมีช่องว่างนั้น
        </td>
    </tr>
</table>

@foreach ($GAP_LEVEL_ORDER as $lvKey)
    @php
        $levelBlock = $gapUnitsByLevel[$lvKey] ?? [];
    @endphp

    <table>
        <tr>
            <td colspan="6" style="font-weight:bold; font-size:10pt; padding-top:5px;">
                ระดับเป้าหมาย: {{ $GAP_LEVEL_LABELS[$lvKey] ?? $lvKey }}
            </td>
        </tr>
    </table>

    @if (empty($levelBlock))
        <table>
            <tr>
                <td colspan="6" style="font-size:10pt;">ไม่พบช่องว่างในระดับนี้</td>
            </tr>
        </table>
    @else
        @foreach ($levelBlock as $questionId => $gapInfo)
            @php
                $gapLabel = $gapInfo['gap_label'] ?? '-';
                $unitList = $gapInfo['units'] ?? collect();
            @endphp

            <table border="1" style="margin-bottom:10px;">
                <thead style="background:#eef4ff;">
                    <tr>
                        <th colspan="3" style="text-align:left;">
                            {{ $gapLabel }}
                        </th>
                    </tr>
                    <tr style="background:#e5e5e5;">
                        <th style="width:3%;">#</th>
                        <th style="width:65%;">หน่วยบริการ</th>
                        <th style="width:32%;">จังหวัด</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($unitList->isEmpty())
                        <tr>
                            <td colspan="3" style="text-align:center;">ไม่พบบันทึกหน่วยบริการที่มี GAP นี้</td>
                        </tr>
                    @else
                        @foreach ($unitList as $idx => $u)
                            <tr>
                                <td style="text-align:center;">{{ $idx + 1 }}</td>
                                <td>{{ $u->name }}</td>
                                <td>{{ $u->province }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        @endforeach
    @endif
@endforeach


{{-- ======================== --}}
{{-- SECTION 4 HEADER BLOCK (MERGE) --}}
{{-- ======================== --}}
<table style="margin-top:20px;">
    <tr>
        <td colspan="6" style="font-weight:bold; font-size:12pt;">
            4. สถานะการประเมิน/อนุมัติของหน่วยบริการ
        </td>
    </tr>
    <tr>
        <td colspan="6" style="font-size:10pt;">
            แสดงความคืบหน้าการพิจารณารับรองหน่วยบริการในรอบปีงบประมาณที่เลือก
            เช่น อยู่ระหว่างตรวจสอบ, ขอแก้ไข/ส่งกลับ, อนุมัติแล้ว หรือยังไม่ได้ยื่นประเมิน
        </td>
    </tr>
</table>

@foreach ($approvalFirstOrder as $key)
    @php
        $label = $statusLabels[$key] ?? $key;
        $count = data_get($statusBuckets, "$key.count", 0);
        $unitsList = data_get($statusBuckets, "$key.units", collect());
    @endphp

    <table border="1" style="margin-bottom:15px;">
        <thead style="background:#e5e5e5;">
            <tr>
                <th colspan="3" style="text-align:left;">
                    {{ $label }}
                    ({{ number_format($count) }} หน่วยบริการ)
                </th>
            </tr>
            <tr>
                <th style="width:3%;">#</th>
                <th style="width:67%;">ชื่อหน่วยบริการ</th>
                <th style="width:30%;">จังหวัด</th>
            </tr>
        </thead>
        <tbody>
            @if ($unitsList->isEmpty())
                <tr>
                    <td colspan="3" style="text-align:center;">ไม่มีข้อมูล</td>
                </tr>
            @else
                @foreach ($unitsList as $idx => $u)
                    <tr>
                        <td style="text-align:center;">{{ $idx + 1 }}</td>
                        <td>{{ $u->org_name }}</td>
                        <td>{{ $u->province }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
@endforeach


{{-- ======================== --}}
{{-- SECTION 5 HEADER BLOCK (MERGE) --}}
{{-- ======================== --}}
@if ($showRegionSection)
    <table style="margin-top:20px;">
        <tr>
            <td colspan="6" style="font-weight:bold; font-size:12pt;">
                5. สรุประดับของหน่วยบริการตามเขตสุขภาพ (สคร.)
            </td>
        </tr>
        <tr>
            <td colspan="6" style="font-size:10pt;">
                จำนวนหน่วยบริการ (แห่ง) จำแนกตามระดับศักยภาพสูงสุดที่ได้รับการอนุมัติ
                ในแต่ละเขตสุขภาพ (สคร.) และผลรวมในประเทศ
            </td>
        </tr>
    </table>

    <table border="1" style="margin-bottom:15px;">
        <thead style="background:#e5e5e5;">
            <tr>
                <th style="width:18%;">สคร.</th>
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
                    <td>{{ $r['region_name'] }}</td>

                    @foreach ($orderLevelsRegion as $lv)
                        <td style="text-align:right;">
                            {{ number_format((int) ($r[$lv] ?? 0)) }}
                        </td>
                    @endforeach

                    <td style="text-align:right; font-weight:bold;">
                        {{ number_format($rowTotal) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($orderLevelsRegion) + 2 }}" style="text-align:center;">
                        ไม่มีข้อมูล
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td><strong>รวมทั้งหมด</strong></td>

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


{{-- ======================== --}}
{{-- SECTION 6 HEADER BLOCK (MERGE) --}}
{{-- ======================== --}}
<table style="margin-top:20px;">
    <tr>
        <td colspan="6" style="font-weight:bold; font-size:12pt;">
            6. สรุประดับของหน่วยบริการตามจังหวัด
        </td>
    </tr>
    <tr>
        <td colspan="6" style="font-size:10pt;">
            จำนวนหน่วยบริการ (แห่ง) จำแนกตามระดับศักยภาพสูงสุดที่ได้รับการอนุมัติ
            รายจังหวัดภายใต้ขอบเขตการค้นหาปัจจุบัน
            @if (!empty($filterRegion))
                (เขตสุขภาพที่ {{ $filterRegion }})
            @endif
        </td>
    </tr>
</table>

<table border="1" style="margin-bottom:15px;">
    <thead style="background:#e5e5e5;">
        <tr>
            <th style="min-width:160px;">จังหวัด</th>
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
                <td>{{ $row['province_name'] }}</td>

                @foreach ($orderLevelsProvince as $lv)
                    <td style="text-align:right;">
                        {{ number_format((int) ($row[$lv] ?? 0)) }}
                    </td>
                @endforeach

                <td style="text-align:right; font-weight:bold;">
                    {{ number_format($rowTotal) }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($orderLevelsProvince) + 2 }}" style="text-align:center;">
                    ไม่มีข้อมูล
                </td>
            </tr>
        @endforelse
    </tbody>

    <tfoot>
        <tr>
            <td><strong>รวมทั้งหมด</strong></td>

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
