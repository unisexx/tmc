@extends('layouts.main')

@section('title', 'แดชบอร์ดหน่วยบริการสุขภาพผู้เดินทาง')
@section('breadcrumb-item', 'Backend')
@section('breadcrumb-item-active', 'แดชบอร์ด')

@section('content')
    {{-- ฟอร์มค้นหา: ปีงบประมาณ · รอบ · สคร. · จังหวัด · หน่วยบริการ --}}
    @include('backend.dashboard._filter')


    {{-- Card จำนวนแต่ละระดับ --}}
    @php
        $total = array_sum($summary) + $notAssessed;
        $pct = fn($v) => $total > 0 ? number_format(($v / $total) * 100, 1) . '%' : '0%';

        $levelBg = config('assessment.level_badge_class');
        $levelText = config('assessment.level_badge_text_color');
    @endphp

    <div class="row g-3 mb-3">
        {{-- ระดับพื้นฐาน --}}
        <div class="col-lg-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avtar bg-{{ $levelBg['basic'] }}">
                                <i class="ph-duotone ph-hospital f-24 text-{{ $levelText['basic'] }}"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="mb-1">ระดับพื้นฐาน</p>
                            <div class="d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">{{ number_format($summary['basic']) }}</h4>
                                <span class="fw-medium text-{{ $levelText['basic'] }}">{{ $pct($summary['basic']) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ระดับกลาง --}}
        <div class="col-lg-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avtar bg-{{ $levelBg['medium'] }}">
                                <i class="ph-duotone ph-hospital f-24 text-{{ $levelText['medium'] }}"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="mb-1">ระดับกลาง</p>
                            <div class="d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">{{ number_format($summary['intermediate']) }}</h4>
                                <span class="fw-medium text-{{ $levelText['medium'] }}">{{ $pct($summary['intermediate']) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ระดับสูง --}}
        <div class="col-lg-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avtar bg-{{ $levelBg['advanced'] }}">
                                <i class="ph-duotone ph-hospital f-24 text-{{ $levelText['advanced'] }}"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="mb-1">ระดับสูง</p>
                            <div class="d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">{{ number_format($summary['advanced']) }}</h4>
                                <span class="fw-medium text-{{ $levelText['advanced'] }}">{{ $pct($summary['advanced']) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ยังไม่ได้ประเมิน --}}
        <div class="col-lg-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avtar bg-gray-100">
                                <i class="ph-duotone ph-hospital f-24 text-gray-900"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="mb-1">ยังไม่ได้ประเมิน</p>
                            <div class="d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">{{ number_format($notAssessed) }}</h4>
                                <span class="fw-medium text-gray-900">{{ $pct($notAssessed) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    {{-- MAP แผนที่ --}}
    <div class="row g-3 mb-3">
        {{-- MAP --}}
        <div class="col-lg-8 col-md-7">
            <div class="card h-100">
                <div class="card-header">
                    <i class="ph-duotone ph-map-pin-area"></i> แผนที่หน่วยบริการ
                </div>
                <div class="card-body p-0">
                    <div id="map" style="height:560px;"></div>
                </div>
            </div>
        </div>

        {{-- รายการหน่วยบริการ (ใช้สี badge จาก config) --}}
        <div class="col-lg-4 col-md-5">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <i class="ph-duotone ph-hospital"></i> รายการหน่วยบริการ
                    </div>
                    <span class="badge bg-light text-dark">{{ number_format($units->count()) }}</span>
                </div>
                <div class="card-body p-0">
                    <div id="suList" class="list-group list-group-flush" style="max-height:560px; overflow:auto;">
                        @forelse ($units as $u)
                            @php
                                $hasCoord = data_get($u, 'org_lat') && data_get($u, 'org_lng');

                                // รับค่าระดับจากหลายแหล่ง แล้ว map เป็น key ของ config
                                $raw = data_get($u, 'level_code') ?? (data_get($u, 'level') ?? data_get($u, 'level_text'));
                                $code = match (true) {
                                    in_array($raw, ['basic', 'พื้นฐาน'], true) => 'basic',
                                    in_array($raw, ['medium', 'intermediate', 'กลาง'], true) => 'medium',
                                    in_array($raw, ['advanced', 'สูง'], true) => 'advanced',
                                    default => 'unassessed', // fallback ใช้จาก config
                                };

                                // โหลดค่าจาก config
                                $cfgText = config('assessment.level_text');
                                $cfgBg = config('assessment.level_badge_class');
                                $cfgFg = config('assessment.level_badge_text_color');
                                $cfgBorder = config('assessment.level_badge_border_class');

                                // สร้างคลาสและข้อความสำหรับ badge
                                $badgeLabel = $cfgText[$code] ?? '-';
                                $badgeBgCls = 'bg-' . ($cfgBg[$code] ?? 'gray-100');
                                $badgeFgCls = 'text-' . ($cfgFg[$code] ?? 'gray-900');
                                $badgeBorder = $cfgBorder[$code] ?? '';
                            @endphp

                            <button type="button" class="list-group-item list-group-item-action d-flex gap-2 align-items-start {{ $hasCoord ? '' : 'disabled' }}" data-id="{{ data_get($u, 'id') }}" data-lat="{{ data_get($u, 'org_lat') }}" data-lng="{{ data_get($u, 'org_lng') }}">
                                <span class="badge rounded-pill align-self-start" style="width:10px;height:10px;background:#6c757d"></span>
                                <div class="flex-fill text-start">
                                    <div class="fw-semibold text-truncate" title="{{ data_get($u, 'org_name') }}">
                                        {{ data_get($u, 'org_name') }}
                                        <span class="badge ms-1 px-2 py-1 rounded-pill {{ $badgeBgCls }} {{ $badgeFgCls }} {{ $badgeBorder }}">
                                            {{ $badgeLabel }}
                                        </span>
                                    </div>
                                    <div class="small text-muted text-truncate" title="{{ data_get($u, 'province_title', '-') . ' · ' . data_get($u, 'district_title', '-') . ' · ' . data_get($u, 'subdistrict_title', '-') }}">
                                        {{ data_get($u, 'province_title', '-') }} · {{ data_get($u, 'district_title', '-') }} · {{ data_get($u, 'subdistrict_title', '-') }}
                                    </div>
                                </div>
                            </button>
                        @empty
                            <div class="list-group-item text-muted text-center">ไม่พบข้อมูล</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    </div>


    <div class="row g-3 mb-3">
        {{-- GAP: ระดับพื้นฐาน --}}
        <div class="col-lg-4">
            <div class="card h-100 position-relative">
                <span class="position-absolute top-0 bottom-0 start-0 bg-{{ $levelBg['basic'] }}" style="width:6px; border-top-left-radius:0.5rem; border-bottom-left-radius:0.5rem;"></span>
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <span class="text-{{ $levelText['basic'] }}"><i class="ph-duotone ph-chart-bar"></i> GAP ระดับพื้นฐาน</span>
                    <span class="badge bg-{{ $levelBg['basic'] }} text-{{ $levelText['basic'] }}">{{ number_format($gapBasic->count()) }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th style="width:60%">รายการ GAP</th>
                                    <th class="text-end" style="width:40%">จำนวนหน่วยบริการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($gapBasic as $row)
                                    <tr>
                                        <td class="text-wrap">{{ $row->gap_label }}</td>
                                        <td class="text-end">{{ number_format($row->unit_count) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">ไม่พบข้อมูล</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- GAP: ระดับกลาง --}}
        <div class="col-lg-4">
            <div class="card h-100 position-relative">
                <span class="position-absolute top-0 bottom-0 start-0 bg-{{ $levelBg['medium'] }}" style="width:6px; border-top-left-radius:0.5rem; border-bottom-left-radius:0.5rem;"></span>
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <span class="text-{{ $levelText['medium'] }}"><i class="ph-duotone ph-chart-bar"></i> GAP ระดับกลาง</span>
                    <span class="badge bg-{{ $levelBg['medium'] }} text-{{ $levelText['medium'] }}">{{ number_format($gapIntermediate->count()) }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th style="width:60%">รายการ GAP</th>
                                    <th class="text-end" style="width:40%">จำนวนหน่วยบริการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($gapIntermediate as $row)
                                    <tr>
                                        <td class="text-wrap">{{ $row->gap_label }}</td>
                                        <td class="text-end">{{ number_format($row->unit_count) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">ไม่พบข้อมูล</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- GAP: ระดับสูง --}}
        <div class="col-lg-4">
            <div class="card h-100 position-relative">
                <span class="position-absolute top-0 bottom-0 start-0 bg-{{ $levelBg['advanced'] }}" style="width:6px; border-top-left-radius:0.5rem; border-bottom-left-radius:0.5rem;"></span>
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <span class="text-{{ $levelText['advanced'] }}"><i class="ph-duotone ph-chart-bar"></i> GAP ระดับสูง</span>
                    <span class="badge bg-{{ $levelBg['advanced'] }} text-{{ $levelText['advanced'] }}">{{ number_format($gapAdvanced->count()) }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th style="width:60%">รายการ GAP</th>
                                    <th class="text-end" style="width:40%">จำนวนหน่วยบริการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($gapAdvanced as $row)
                                    <tr>
                                        <td class="text-wrap">{{ $row->gap_label }}</td>
                                        <td class="text-end">{{ number_format($row->unit_count) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">ไม่พบข้อมูล</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- รอการพิจารณา, อยู่ระหว่างการพิจารณา, อนุมัติแล้ว --}}
    @php
        $labels = config('assessment.approval_text');
        $bgMap = config('assessment.approval_badge_class');
        $fgMap = config('assessment.approval_badge_text_color');
    @endphp

    <div class="row g-3 mb-3">
        @foreach ($labels as $key => $label)
            @php
                $bg = $bgMap[$key] ?? 'gray-100';
                $fg = $fgMap[$key] ?? 'gray-900';
                $count = data_get($status, "$key.count", 0);
                $unitsInStatus = data_get($status, "$key.units", collect()); // ชื่อใหม่ เลี่ยงชน $units หลัก
            @endphp

            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center py-2 bg-{{ $bg }}">
                        <span class="fw-semibold text-{{ $fg }}">
                            <i class="ti ti-building-community me-1 text-{{ $fg }}"></i>{{ $label }}
                        </span>
                        <span class="badge border text-{{ $fg }} border-{{ $fg }} bg-white">
                            {{ number_format($count) }}
                        </span>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:6%">#</th>
                                        <th>ชื่อหน่วยบริการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($unitsInStatus as $i => $u)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td class="text-truncate" title="{{ $u->org_name }}">{{ $u->org_name }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center text-muted">ไม่มีข้อมูล</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- รายงานภาพรวม (ตาราง) — ตามเขต + กราฟ ApexCharts แนวตั้ง แยกระดับ --}}
    <div class="card mt-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span>รายงานภาพรวม — ตามเขต</span>
        </div>

        <div class="card-body">
            <div id="region-summary-chart" style="height: 420px;"></div>
        </div>

        <div class="card-body table-responsive pt-0">
            <table id="region-summary-table" class="table table-sm table-striped align-middle mb-0">
                <thead>
                    <tr class="text-center">
                        <th>เขต</th>
                        <th class="text-end">พื้นฐาน</th>
                        <th class="text-end">กลาง</th>
                        <th class="text-end">สูง</th>
                        <th class="text-end">ยังไม่ได้ประเมิน</th>
                        <th class="text-end">รวม</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($regions as $r)
                        @php
                            $basic = (int) data_get($regionTable, "$r->id.basic", 0);
                            $medium = (int) data_get($regionTable, "$r->id.medium", 0);
                            $advanced = (int) data_get($regionTable, "$r->id.advanced", 0);
                            $unassessed = (int) data_get($regionTable, "$r->id.unassessed", 0);
                            $total = $basic + $medium + $advanced + $unassessed;
                        @endphp
                        <tr>
                            <td>{{ $r->short_title }}</td>
                            <td class="text-end">{{ number_format($basic) }}</td>
                            <td class="text-end">{{ number_format($medium) }}</td>
                            <td class="text-end">{{ number_format($advanced) }}</td>
                            <td class="text-end">{{ number_format($unassessed) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- รายงานภาพรวม (ตาราง) — ตามจังหวัด + กราฟแนวนอน --}}
    <div class="card mt-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span>รายงานภาพรวม — ตามจังหวัด</span>
        </div>

        <div class="card-body">
            <div id="province-summary-chart" style="height: 500px;"></div>
        </div>

        <div class="card-body table-responsive pt-0">
            <table id="province-summary-table" class="table table-sm table-striped align-middle mb-0">
                <thead>
                    <tr class="text-center">
                        <th>จังหวัด</th>
                        <th class="text-end">พื้นฐาน</th>
                        <th class="text-end">กลาง</th>
                        <th class="text-end">สูง</th>
                        <th class="text-end">ยังไม่ได้ประเมิน</th>
                        <th class="text-end">รวม</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($provinceTable as $p)
                        <tr>
                            <td>{{ $p->title }}</td>
                            <td class="text-end">{{ number_format($p->basic) }}</td>
                            <td class="text-end">{{ number_format($p->medium) }}</td>
                            <td class="text-end">{{ number_format($p->advanced) }}</td>
                            <td class="text-end">{{ number_format($p->unassessed) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($p->total) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">ไม่พบข้อมูล</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection


@push('js')
    {{-- รายงานภาพรวม — ตามเขต --}}
    <script>
        (function() {
            const tbl = document.getElementById('region-summary-table');
            if (!tbl) return;

            const regions = [];
            const basic = [],
                medium = [],
                advanced = [],
                unassessed = [];
            tbl.querySelectorAll('tbody tr').forEach(tr => {
                const t = tr.querySelectorAll('td');
                if (t.length >= 6) {
                    regions.push(t[0].textContent.trim());
                    basic.push(parseInt(t[1].textContent.replace(/[, ]/g, '')) || 0);
                    medium.push(parseInt(t[2].textContent.replace(/[, ]/g, '')) || 0);
                    advanced.push(parseInt(t[3].textContent.replace(/[, ]/g, '')) || 0);
                    unassessed.push(parseInt(t[4].textContent.replace(/[, ]/g, '')) || 0);
                }
            });
            if (!regions.length) return;

            // โหลดสีพื้นจาก config
            const cfgBg = @json(config('assessment.level_badge_class'));

            const colors = [
                mapColor(cfgBg.basic),
                mapColor(cfgBg.medium),
                mapColor(cfgBg.advanced),
                mapColor(cfgBg.unassessed)
            ];

            function mapColor(cls) {
                // map Light Able utility class → hex ระดับ -400
                const map = {
                    'pink-100': '#f472b6', // pink-400
                    'yellow-100': '#facc15', // yellow-400
                    'green-100': '#4ade80', // green-400
                    'gray-100': '#9ca3af' // gray-400
                };
                return map[cls] || '#9ca3af';
            }



            const el = document.getElementById('region-summary-chart');
            const maxY = Math.max(...basic, ...medium, ...advanced, ...unassessed, 0);

            const options = {
                chart: {
                    type: 'bar',
                    height: 420,
                    stacked: true,
                    toolbar: {
                        show: false
                    },
                    fontFamily: 'inherit'
                },
                series: [{
                        name: 'พื้นฐาน',
                        data: basic
                    },
                    {
                        name: 'กลาง',
                        data: medium
                    },
                    {
                        name: 'สูง',
                        data: advanced
                    },
                    {
                        name: 'ยังไม่ได้ประเมิน',
                        data: unassessed
                    }
                ],
                xaxis: {
                    categories: regions,
                    labels: {
                        style: {
                            fontSize: '12px'
                        }
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 6,
                        dataLabels: {
                            total: {
                                enabled: true,
                                style: {
                                    fontSize: '12px',
                                    fontWeight: 600
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: (v) => v.toLocaleString()
                    }
                },
                colors: colors,
                grid: {
                    strokeDashArray: 3,
                    padding: {
                        left: 10,
                        right: 10
                    }
                },
                yaxis: {
                    labels: {
                        formatter: (v) => v.toFixed(0),
                        style: {
                            fontSize: '12px'
                        }
                    },
                    tickAmount: maxY < 5 ? maxY : undefined,
                    forceNiceScale: true,
                    min: 0,
                    title: {
                        text: 'จำนวนหน่วยบริการ',
                        style: {
                            fontSize: '13px',
                            fontWeight: 500
                        }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'center',
                    markers: {
                        radius: 4
                    }
                }
            };

            const chart = new ApexCharts(el, options);
            chart.render();
        })();
    </script>
@endpush

@push('js')
    {{-- รายงานภาพรวม — ตามจังหวัด --}}
    <script>
        (function() {
            const tbl = document.getElementById('province-summary-table');
            if (!tbl) return;

            const provinces = [];
            const basic = [],
                medium = [],
                advanced = [],
                unassessed = [],
                totals = [];

            tbl.querySelectorAll('tbody tr').forEach(tr => {
                const t = tr.querySelectorAll('td');
                if (t.length >= 6) {
                    provinces.push(t[0].textContent.trim());

                    const b = parseInt(t[1].textContent.replace(/[, ]/g, '')) || 0;
                    const m = parseInt(t[2].textContent.replace(/[, ]/g, '')) || 0;
                    const a = parseInt(t[3].textContent.replace(/[, ]/g, '')) || 0;
                    const u = parseInt(t[4].textContent.replace(/[, ]/g, '')) || 0;

                    basic.push(b);
                    medium.push(m);
                    advanced.push(a);
                    unassessed.push(u);
                    totals.push(b + m + a + u);
                }
            });
            if (!provinces.length) return;

            const cfgBg = @json(config('assessment.level_badge_class'));

            function mapColor(cls) {
                const map = {
                    'pink-100': '#f472b6', // basic
                    'yellow-100': '#facc15', // medium
                    'green-100': '#4ade80', // advanced
                    'gray-100': '#9ca3af' // unassessed
                };
                return map[cls] || '#9ca3af';
            }
            const colors = [
                mapColor(cfgBg.basic),
                mapColor(cfgBg.medium),
                mapColor(cfgBg.advanced),
                mapColor(cfgBg.unassessed)
            ];

            const el = document.getElementById('province-summary-chart');
            const maxX = Math.max(...totals, 0);

            const options = {
                chart: {
                    type: 'bar',
                    height: Math.max(360, provinces.length * 26),
                    stacked: true,
                    toolbar: {
                        show: false
                    },
                    fontFamily: 'inherit'
                },
                series: [{
                        name: 'พื้นฐาน',
                        data: basic
                    },
                    {
                        name: 'กลาง',
                        data: medium
                    },
                    {
                        name: 'สูง',
                        data: advanced
                    },
                    {
                        name: 'ยังไม่ได้ประเมิน',
                        data: unassessed
                    }
                ],
                plotOptions: {
                    bar: {
                        horizontal: true,
                        barHeight: '70%',
                        borderRadius: 6,
                        dataLabels: {
                            total: {
                                enabled: true,
                                style: {
                                    fontSize: '12px',
                                    fontWeight: 600
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                xaxis: {
                    categories: provinces,
                    labels: {
                        formatter: (v) => Number(v).toFixed(0),
                        style: {
                            fontSize: '12px'
                        }
                    },
                    title: {
                        text: 'จำนวนหน่วยบริการ',
                        style: {
                            fontSize: '13px',
                            fontWeight: 500
                        }
                    },
                    tickAmount: maxX < 5 ? maxX : undefined,
                    min: 0
                },
                yaxis: {
                    labels: {
                        style: {
                            fontSize: '12px'
                        }
                    }
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: (v) => v.toLocaleString()
                    }
                },
                colors: colors,
                grid: {
                    strokeDashArray: 3,
                    padding: {
                        left: 10,
                        right: 10
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'center',
                    markers: {
                        radius: 4
                    }
                }
            };

            const chart = new ApexCharts(el, options);
            chart.render();
        })();
    </script>
@endpush

@push('js')
    {{-- แผนที่ พร้อมปักหมุด --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const map = L.map('map').setView([13.736717, 100.523186], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);
            setTimeout(() => map.invalidateSize(), 0);

            const markers = new Map();
            const bounds = L.latLngBounds();

            // สีตามระดับ (hex)
            const levelColors = {
                basic: '#e91e63',
                medium: '#fbc02d',
                intermediate: '#fbc02d',
                advanced: '#4caf50',
                default: '#9e9e9e'
            };

            function createColoredIcon(hex) {
                const svg = `
      <svg xmlns="http://www.w3.org/2000/svg" width="28" height="38" viewBox="0 0 28 38">
        <path d="M14 1C9 1 4 5.6 4 11.6 4 21.8 14 37 14 37s10-15.2 10-25.4C24 5.6 19 1 14 1z" fill="${hex}"/>
        <circle cx="14" cy="11.6" r="3.6" fill="#fff"/>
      </svg>`;
                return L.icon({
                    iconUrl: 'data:image/svg+xml;utf8,' + encodeURIComponent(svg),
                    iconSize: [28, 38],
                    iconAnchor: [14, 37],
                    popupAnchor: [0, -38]
                });
            }

            @foreach ($units as $u)
                @if ($u->org_lat && $u->org_lng)
                    {
                        const lat = {{ $u->org_lat }};
                        const lng = {{ $u->org_lng }};
                        const level = "{{ $u->level_code ?? 'default' }}";
                        const color = levelColors[level] || levelColors.default;
                        const icon = createColoredIcon(color);

                        const m = L.marker([lat, lng], {
                                icon
                            })
                            .addTo(map)
                            .bindPopup(`<strong>{{ addslashes($u->org_name) }}</strong><br>{{ addslashes($u->province_title ?? '') }}`, {
                                autoPan: false
                            });

                        markers.set({{ $u->id }}, m);
                        bounds.extend([lat, lng]);
                    }
                @endif
            @endforeach

            if (bounds.isValid()) map.fitBounds(bounds.pad(0.2));

            const suList = document.getElementById('suList');
            if (!suList) return;
            suList.addEventListener('click', e => {
                const item = e.target.closest('.list-group-item');
                if (!item || item.classList.contains('disabled')) return;

                const id = Number(item.dataset.id);
                const lat = Number(item.dataset.lat);
                const lng = Number(item.dataset.lng);
                const m = markers.get(id);
                if (!m || Number.isNaN(lat) || Number.isNaN(lng)) return;

                suList.querySelectorAll('.list-group-item').forEach(el => el.classList.remove('active'));
                item.classList.add('active');

                map.flyTo([lat, lng], 16, {
                    animate: true
                });
                map.once('moveend', () => m.openPopup());
            });
        });
    </script>
@endpush
