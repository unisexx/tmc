{{-- resources/views/backend/dashboard/index.blade.php --}}
@extends('layouts.main')

@section('title', 'แดชบอร์ดหน่วยบริการสุขภาพผู้เดินทาง')
@section('breadcrumb-item', 'Backend')
@section('breadcrumb-item-active', 'แดชบอร์ด')

@section('content')
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin>
        <link rel="stylesheet" href="https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/leaflet.fullscreen.css">
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin></script>
        <script src="https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/Leaflet.fullscreen.min.js"></script>
        <script src="https://unpkg.com/leaflet-simple-map-screenshoter"></script>
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    @endpush

    @include('backend.dashboard._filter')

    {{-- ปุ่มส่งออกข้อมูล --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <div class="d-flex align-items-center text-muted">
                <i class="ti ti-download me-2 fs-5 text-primary"></i>
                <span class="fw-semibold">ส่งออกข้อมูล</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('backend.dashboard.export', array_merge(request()->all(), ['format' => 'excel'])) }}" class="btn btn-outline-success btn-sm px-3 d-flex align-items-center" data-bs-toggle="tooltip" data-bs-title="ดาวน์โหลดรายงานเป็น Excel">
                    <i class="ti ti-file-spreadsheet me-1"></i> Excel
                </a>

                <a href="{{ route('backend.dashboard.export', array_merge(request()->all(), ['format' => 'pdf'])) }}" class="btn btn-outline-danger btn-sm px-3 d-flex align-items-center" data-bs-toggle="tooltip" data-bs-title="ดาวน์โหลดรายงานเป็น PDF">
                    <i class="ti ti-file-text me-1"></i> PDF
                </a>
            </div>
        </div>
    </div>




    @php
        $mapLevel = fn($v) => match (strtolower((string) $v)) {
            'พื้นฐาน', 'ระดับพื้นฐาน', 'basic' => 'basic',
            'กลาง', 'ระดับกลาง', 'medium' => 'medium',
            'สูง', 'ระดับสูง', 'advanced' => 'advanced',
            default => null,
        };

        // ✅ เรียงลำดับตาม province_code ก่อน
        $sortedUnits = $serviceUnits->sortBy(fn($su) => (int) $su->org_province_code);

        // สร้างแผนที่หน่วย -> { name, province, province_code, levels(approved) }
        $approvedByUnit = $sortedUnits->mapWithKeys(function ($su) use ($mapLevel) {
            $approvedLevels = collect($su->assessmentLevels)->filter(fn($a) => strtolower((string) data_get($a, 'approval_status')) === 'approved')->map(fn($a) => $mapLevel(data_get($a, 'level')))->filter();

            return [
                $su->id => [
                    'name' => $su->org_name,
                    'province' => data_get($su, 'province.title', '-'),
                    'province_code' => (int) data_get($su, 'org_province_code'),
                    'levels' => $approvedLevels->values(),
                ],
            ];
        });

        // ชุดข้อมูลรายแถว
        $rowsBasic = $approvedByUnit->filter(fn($v) => $v['levels']->contains('basic'))->sortBy('province_code')->values();
        $rowsMedium = $approvedByUnit->filter(fn($v) => $v['levels']->contains('medium'))->sortBy('province_code')->values();
        $rowsAdvanced = $approvedByUnit->filter(fn($v) => $v['levels']->contains('advanced'))->sortBy('province_code')->values();

        // ยังไม่ได้ประเมิน = ไม่มีเรคคอร์ดปี–รอบ หรือไม่มี approved level ใน 3 กลุ่ม
        $rowsNotAssessed = $sortedUnits
            ->filter(function ($su) use ($mapLevel) {
                $rows = collect($su->assessmentLevels);
                if ($rows->isEmpty()) {
                    return true;
                }
                return $rows->every(function ($a) use ($mapLevel) {
                    $approved = strtolower((string) data_get($a, 'approval_status')) === 'approved';
                    $lvl = $mapLevel(data_get($a, 'level'));
                    return !($approved && in_array($lvl, ['basic', 'medium', 'advanced'], true));
                });
            })
            ->map(
                fn($su) => [
                    'name' => $su->org_name,
                    'province' => data_get($su, 'province.title', '-'),
                    'province_code' => (int) data_get($su, 'org_province_code'),
                ],
            )
            ->sortBy('province_code')
            ->values();

        // ตัวเลขสรุป
        $summary = ['basic' => $rowsBasic->count(), 'medium' => $rowsMedium->count(), 'advanced' => $rowsAdvanced->count()];
        $notAssessed = $rowsNotAssessed->count();
        $total = array_sum($summary) + $notAssessed;
        $pct = fn($v) => $total > 0 ? number_format(($v / $total) * 100, 1) . '%' : '0%';

        $levelBg = config('tmc.level_badge_class');
        $levelText = config('tmc.level_badge_text_color');
    @endphp

    {{-- --------------------------------------------------------------------------------------------------------------------------------

    ███████╗██╗   ██╗███╗   ███╗███╗   ███╗ █████╗ ██████╗ ██╗   ██╗     ██████╗ █████╗ ██████╗ ██████╗
    ██╔════╝██║   ██║████╗ ████║████╗ ████║██╔══██╗██╔══██╗╚██╗ ██╔╝    ██╔════╝██╔══██╗██╔══██╗██╔══██╗
    ███████╗██║   ██║██╔████╔██║██╔████╔██║███████║██████╔╝ ╚████╔╝     ██║     ███████║██████╔╝██║  ██║
    ╚════██║██║   ██║██║╚██╔╝██║██║╚██╔╝██║██╔══██║██╔══██╗  ╚██╔╝      ██║     ██╔══██║██╔══██╗██║  ██║
    ███████║╚██████╔╝██║ ╚═╝ ██║██║ ╚═╝ ██║██║  ██║██║  ██║   ██║       ╚██████╗██║  ██║██║  ██║██████╔╝
    ╚══════╝ ╚═════╝ ╚═╝     ╚═╝╚═╝     ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝   ╚═╝        ╚═════╝╚═╝  ╚═╝╚═╝  ╚═╝╚═════╝

        SECTION 1: SUMMARY CARDS (การ์ดสรุประดับ + ยังไม่ได้ประเมิน)
        - การ์ด 4 ใบ (พื้นฐาน/กลาง/สูง/ยังไม่ได้ประเมิน)
        - เปิด modal รายชื่อเมื่อคลิกการ์ด
    -------------------------------------------------------------------------------------------------------------------------------- --}}

    <div class="row g-3 mb-3">
        {{-- การ์ดพื้นฐาน --}}
        <div class="col-lg-3 col-md-6">
            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#modal-basic">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avtar {{ $levelBg['basic'] }}">
                                    <i class="ph-duotone ph-hospital f-24 {{ $levelText['basic'] }}"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="fw-semibold mb-1">ระดับพื้นฐาน</p>
                                <div class="d-flex align-items-center justify-content-between">
                                    <h4 class="mb-0">{{ number_format($summary['basic']) }}</h4>
                                    <span class="fw-medium {{ $levelText['basic'] }}">{{ $pct($summary['basic']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        {{-- การ์ดกลาง --}}
        <div class="col-lg-3 col-md-6">
            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#modal-medium">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avtar {{ $levelBg['medium'] }}">
                                    <i class="ph-duotone ph-hospital f-24 {{ $levelText['medium'] }}"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="fw-semibold mb-1">ระดับกลาง</p>
                                <div class="d-flex align-items-center justify-content-between">
                                    <h4 class="mb-0">{{ number_format($summary['medium']) }}</h4>
                                    <span class="fw-medium {{ $levelText['medium'] }}">{{ $pct($summary['medium']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        {{-- การ์ดสูง --}}
        <div class="col-lg-3 col-md-6">
            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#modal-advanced">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avtar {{ $levelBg['advanced'] }}">
                                    <i class="ph-duotone ph-hospital f-24 {{ $levelText['advanced'] }}"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="fw-semibold mb-1">ระดับสูง</p>
                                <div class="d-flex align-items-center justify-content-between">
                                    <h4 class="mb-0">{{ number_format($summary['advanced']) }}</h4>
                                    <span class="fw-medium {{ $levelText['advanced'] }}">{{ $pct($summary['advanced']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        {{-- การ์ดยังไม่ได้ประเมิน --}}
        <div class="col-lg-3 col-md-6">
            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#modal-unassessed">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avtar {{ $levelBg['unassessed'] }}">
                                    <i class="ph-duotone ph-hospital f-24 {{ $levelText['unassessed'] }}"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="fw-semibold mb-1">ยังไม่ได้ประเมิน</p>
                                <div class="d-flex align-items-center justify-content-between">
                                    <h4 class="mb-0">{{ number_format($notAssessed) }}</h4>
                                    <span class="fw-medium {{ $levelText['unassessed'] }}">{{ $pct($notAssessed) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- ==== Modal: ตารางชื่อหน่วย + จังหวัด (มีเลขลำดับ) ==== --}}
    <div class="modal fade" id="modal-basic" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">หน่วยบริการระดับพื้นฐาน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    @if ($rowsBasic->isEmpty())
                        <div class="p-3 text-muted">ไม่มีข้อมูล</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:5%">#</th>
                                        <th style="width:65%">ชื่อหน่วยบริการ</th>
                                        <th>จังหวัด</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rowsBasic as $i => $r)
                                        <tr>
                                            <td class="text-center">{{ $i + 1 }}</td>
                                            <td>{{ $r['name'] }}</td>
                                            <td>{{ $r['province'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-medium" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">หน่วยบริการระดับกลาง</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    @if ($rowsMedium->isEmpty())
                        <div class="p-3 text-muted">ไม่มีข้อมูล</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:5%">#</th>
                                        <th style="width:65%">ชื่อหน่วยบริการ</th>
                                        <th>จังหวัด</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rowsMedium as $i => $r)
                                        <tr>
                                            <td class="text-center">{{ $i + 1 }}</td>
                                            <td>{{ $r['name'] }}</td>
                                            <td>{{ $r['province'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-advanced" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">หน่วยบริการระดับสูง</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    @if ($rowsAdvanced->isEmpty())
                        <div class="p-3 text-muted">ไม่มีข้อมูล</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:5%">#</th>
                                        <th style="width:65%">ชื่อหน่วยบริการ</th>
                                        <th>จังหวัด</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rowsAdvanced as $i => $r)
                                        <tr>
                                            <td class="text-center">{{ $i + 1 }}</td>
                                            <td>{{ $r['name'] }}</td>
                                            <td>{{ $r['province'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-unassessed" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">หน่วยบริการยังไม่ได้ประเมิน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    @if ($rowsNotAssessed->isEmpty())
                        <div class="p-3 text-muted">ไม่มีข้อมูล</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:5%">#</th>
                                        <th style="width:65%">ชื่อหน่วยบริการ</th>
                                        <th>จังหวัด</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rowsNotAssessed as $i => $r)
                                        <tr>
                                            <td class="text-center">{{ $i + 1 }}</td>
                                            <td>{{ $r['name'] }}</td>
                                            <td>{{ $r['province'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- --------------------------------------------------------------------------------------------------------------------------------
        ███╗   ███╗ █████╗ ██████╗
        ████╗ ████║██╔══██╗██╔══██╗
        ██╔████╔██║███████║██████╔╝
        ██║╚██╔╝██║██╔══██║██╔═══╝
        ██║ ╚═╝ ██║██║  ██║██║
        ╚═╝     ╚═╝╚═╝  ╚═╝╚═╝

        SECTION 2: MAP (แผนที่หน่วยบริการ + CSS/JS)
        - เตรียมข้อมูล facilities
        - แสดงแผนที่ Leaflet + ป้ายชื่อ + popup
        - แผงรายชื่อ (list panel) + ค้นหา + fly/pan/zoom
    -------------------------------------------------------------------------------------------------------------------------------- --}}

    @php
        $LEVEL_TEXTS = ['basic' => 'พื้นฐาน', 'medium' => 'กลาง', 'advanced' => 'สูง', 'unassessed' => 'ยังไม่ได้ประเมิน'];

        $prefer = ['advanced', 'medium', 'basic'];
        $pickLevel = function ($unitId) use ($approvedByUnit, $prefer) {
            $lvCol = collect(data_get($approvedByUnit, "{$unitId}.levels", collect()));
            foreach ($prefer as $k) {
                if ($lvCol->contains($k)) {
                    return $k;
                }
            }
            return 'unassessed';
        };

        $facilities = $serviceUnits
            ->map(function ($su) use ($pickLevel, $LEVEL_TEXTS, $bestByUnit, $servicesByUnit) {
                $key = $pickLevel($su->id);
                $approved = data_get($bestByUnit, "{$su->id}.key") !== null;
                $svcList = collect($servicesByUnit->get($su->id) ?? collect())
                    ->values()
                    ->all();

                return [
                    'id' => (int) $su->id,
                    'name' => (string) $su->org_name,
                    'address' => (string) $su->org_address,
                    'provinceCode' => (string) $su->org_province_code,
                    'districtCode' => (string) $su->org_district_code,
                    'subdistrictCode' => (string) $su->org_subdistrict_code,
                    'province' => (string) optional($su->province)->title,
                    'district' => (string) optional($su->district)->title,
                    'subdistrict' => (string) optional($su->subdistrict)->title,
                    'lat' => $su->org_lat !== null ? (float) $su->org_lat : null,
                    'lon' => $su->org_lng !== null ? (float) $su->org_lng : null,
                    'phone' => (string) $su->org_tel,
                    'email' => (string) ($su->org_email ?? ''),
                    'levelKey' => $key,
                    'levelText' => $LEVEL_TEXTS[$key] ?? 'ยังไม่ได้ประเมิน',
                    'approved' => (bool) $approved,
                    'services' => $svcList, // array ของชื่อบริการที่ผ่านเงื่อนไข
                ];
            })
            ->values()
            ->all();
    @endphp

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="fw-semibold text-dark"><i class="ph-duotone ph-map-pin"></i> <span class="ms-1">แผนที่หน่วยบริการสุขภาพผู้เดินทาง</span></div>
        </div>
        <div class="card-body p-0">
            <div id="tmc-map" style="height:560px;"></div>
        </div>
    </div>

    @push('styles')
        <style>
            /* กล่องเมนู Export */
            .tmc-export {
                background: #fff;
                border-radius: .5rem;
                box-shadow: 0 10px 30px rgba(0, 0, 0, .12);
                overflow: hidden;
                font-size: .9rem;
                line-height: 1.1;
                text-align: left;
                /* ✅ จัดข้อความชิดซ้าย */
                min-width: 180px;
                /* ปรับความกว้างพอดี */
            }

            /* ปุ่มแต่ละรายการ */
            .tmc-export .btn {
                display: flex;
                /* ✅ ใช้ flex เพื่อจัด icon กับข้อความสวย ๆ */
                align-items: center;
                gap: .5rem;
                padding: .45rem .75rem;
                color: #333;
                text-decoration: none;
                white-space: nowrap;
                text-align: left;
                /* ✅ เนื้อหาในปุ่มชิดซ้าย */
                background: transparent;
            }

            .tmc-export .btn:hover {
                background: #f8f9fa;
            }

            .tmc-export .btn i {
                width: 1.1rem;
                text-align: center;
                flex-shrink: 0;
            }


            /* ปุ่ม trigger แบบ “สามขีด” – สีดำ และอยู่กึ่งกลางพอดี */
            .leaflet-control.tmc-export-toggle a {
                position: relative;
                display: block;
                width: 30px;
                height: 30px;
                background: #fff;
                border-radius: 4px;
                box-shadow: 0 1px 2px rgba(0, 0, 0, .08);
            }

            /* เส้นสามขีด สีดำ */
            .leaflet-control.tmc-export-toggle a::before {
                content: "";
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 16px;
                height: 2px;
                background: #000;
                /* ✅ สีดำกลาง */
                border-radius: 2px;
                box-shadow: 0 -5px 0 #000, 0 5px 0 #000;
                /* ✅ เส้นบน/ล่างสีดำ */
            }

            /* เว้นระยะให้พอดีกับปุ่มอื่นทางซ้าย */
            .leaflet-top.leaflet-left .tmc-export-toggle {
                margin-top: .25rem;
            }
        </style>
    @endpush


    @push('styles')
        {{-- ----------------------------------------------------------------------------------------------------------------------------
    MAP: STYLES (CSS เฉพาะของแผนที่/ป้าย/แผงรายชื่อ/ขนาด popup)
    ---------------------------------------------------------------------------------------------------------------------------- --}}
        <style>
            #tmc-map {
                height: 560px;
            }

            /* ===== ป้ายชื่อหมุด ===== */
            .flabel {
                --c: #0d6efd;
                position: relative;
                display: inline-flex;
                align-items: center;
                gap: .5rem;
                background: color-mix(in srgb, var(--c)18%, white);
                border: 1px solid color-mix(in srgb, var(--c)45%, transparent);
                color: #0b2e13;
                padding: .35rem .75rem;
                border-radius: 999px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, .12);
                white-space: nowrap;
                font-size: .9rem;
                line-height: 1;
            }

            .flabel:before {
                content: "";
                position: absolute;
                left: -10px;
                top: 50%;
                transform: translateY(-50%);
                border-top: 7px solid transparent;
                border-bottom: 7px solid transparent;
                border-right: 10px solid color-mix(in srgb, var(--c)45%, transparent);
            }

            .flabel .dot {
                width: .66rem;
                height: .66rem;
                border-radius: 4px;
                background: var(--c);
                box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .08);
            }

            .flabel .name {
                font-weight: 600;
            }

            /* ===== แผงรายชื่อหน่วยบริการ ===== */
            .facility-label {
                transition: opacity .2s ease;
            }

            .opacity-0 {
                opacity: 0;
            }

            .tmc-list {
                width: 320px;
                max-width: 70vw;
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, .12);
                overflow: hidden;
            }

            .tmc-list .hdr {
                display: flex;
                align-items: center;
                gap: .5rem;
                padding: .6rem .75rem;
                border-bottom: 1px solid #e9ecef;
                font-weight: 600;
            }

            .tmc-list .hdr .toggle {
                margin-left: auto;
                cursor: pointer;
                font-size: .95rem;
                border: 0;
                background: transparent;
            }

            .tmc-list .srch {
                padding: .5rem .75rem;
                border-bottom: 1px solid #f1f3f5;
            }

            .tmc-list input[type="search"] {
                width: 100%;
                padding: .45rem .6rem;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                font-size: .9rem;
            }

            .tmc-list .body {
                max-height: 360px;
                overflow: auto;
            }

            .tmc-item {
                display: flex;
                gap: .5rem;
                align-items: flex-start;
                padding: .55rem .75rem;
                border-bottom: 1px dashed #f1f3f5;
                cursor: pointer;
                border-left: 4px solid transparent;
            }

            .tmc-item:last-child {
                border-bottom: 0;
            }

            .tmc-item .dot {
                width: .66rem;
                height: .66rem;
                border-radius: 50%;
                margin-top: .25rem;
                flex: 0 0 auto;
            }

            .tmc-item .tit {
                font-size: .92rem;
                font-weight: 600;
                line-height: 1.1;
            }

            .tmc-item .sub {
                font-size: .78rem;
                color: #6c757d;
            }

            .tmc-empty {
                padding: .75rem .75rem;
                color: #6c757d;
                font-size: .9rem;
            }

            .tmc-item.active {
                background: #fffbea;
                box-shadow: inset 0 0 0 1px #ffe08a;
                border-left-color: var(--c);
            }

            .tmc-list.min .srch,
            .tmc-list.min .body {
                display: none;
            }

            /* ===== ปรับขนาดตัวหนังสือใน popup ===== */
            .tmc-pop {
                font-size: 1rem;
                line-height: 1.45;
                color: #212529;
            }

            .tmc-pop h6 {
                font-size: 1.15rem;
                font-weight: 700;
                color: #0d6efd;
            }

            .tmc-pop p {
                margin-bottom: .5rem;
                font-size: 1rem;
            }

            .tmc-pop ul {
                margin: 0 0 .5rem 1rem;
            }

            .tmc-pop li {
                font-size: 1rem;
                line-height: 1.4;
            }

            .tmc-pop .fw-semibold {
                font-size: 1.05rem;
            }

            .tmc-pop small {
                font-size: .9rem;
            }

            /* ✅ ส่วนหัวชื่อหน่วยบริการใน popup */
            .tmc-pop .lv-head {
                display: flex;
                align-items: center;
                /* <<< ปรับเป็น center เพื่อให้จุดสีอยู่กลางบรรทัดเดียวกับชื่อ */
                gap: .5rem;
                line-height: 1.2;
                margin-bottom: .5rem;
            }

            /* ✅ จุดสีสถานะระดับ */
            .tmc-pop .lv-dot {
                width: .8rem;
                height: .8rem;
                border-radius: 50%;
                box-shadow: 0 0 0 2px #fff, 0 0 4px rgba(0, 0, 0, .2);
                flex-shrink: 0;
            }

            /* ชื่อหน่วยบริการ */
            .tmc-pop .lv-name {
                font-weight: 600;
                color: #0d6efd;
                font-size: 1.05rem;
            }

            .tmc-pop .lv-meta {
                font-weight: 400;
                color: #6c757d;
                font-size: .9rem;
            }
        </style>
    @endpush


    @push('scripts')
        {{-- ----------------------------------------------------------------------------------------------------------------------------
    MAP: SCRIPTS (สร้างแผนที่, หมุด, ป้าย, popup, แผงรายชื่อ, การเลื่อนกล้อง, และ Export เมนู)
    ---------------------------------------------------------------------------------------------------------------------------- --}}
        @php
            // ดึงสีจาก config พร้อม fallback ถ้าคีย์ไหนหายไป
            $LEVEL_COLORS = array_replace(
                [
                    'basic' => '#FF4560', // ชมพู
                    'medium' => '#FEB019', // ส้ม
                    'advanced' => '#00E396', // เขียว
                    'unassessed' => '#A8A8A8', // เทา
                ],
                (array) config('tmc.level_colors'),
            );
        @endphp
        <script>
            (function() {
                const facilities = @json($facilities);

                // ✅ ใช้สีจาก config/tmc.php
                const LEVEL_COLORS = @json($LEVEL_COLORS);
                const DEFAULT_COLOR = LEVEL_COLORS.unassessed || '#A8A8A8';

                // ====== สร้างแผนที่ ======
                const map = L.map('tmc-map', {
                    zoomControl: true,
                    fullscreenControl: true,
                    fullscreenControlOptions: {
                        position: 'topleft'
                    },
                    zoomSnap: 0.25,
                    zoomDelta: 0.5,
                    wheelDebounceTime: 40,
                    wheelPxPerZoomLevel: 100,
                    inertia: true,
                    inertiaDeceleration: 3000,
                    easeLinearity: 0.2
                }).setView([13.736717, 100.523186], 6);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);

                const markers = [],
                    labels = [],
                    markerById = new Map(),
                    elByKey = new Map();
                let activeKey = null,
                    activeEl = null;

                facilities.forEach(f => {
                    if (f.lat == null || f.lon == null) return;

                    const c = LEVEL_COLORS[f.levelKey] || DEFAULT_COLOR;
                    const key = String(f.id ?? `${f.lat},${f.lon}`);

                    const mk = L.marker([f.lat, f.lon], {
                        icon: coloredPinClassic(c) // ใช้หมุดสีตามระดับ
                    }).addTo(map);
                    mk._tmcKey = key;

                    const contactLine = [f.phone, f.email].filter(Boolean).join(' / ');
                    const isBkk = String(f.provinceCode) === '10';
                    const districtLabel = isBkk ? 'เขต' : 'อำเภอ';
                    const subdistrictLabel = isBkk ? 'แขวง' : 'ตำบล';

                    const levelDisplay = (f.levelKey === 'unassessed') ?
                        'ยังไม่ได้ประเมิน' :
                        ('ระดับ' + String(f.levelText || '-'));

                    const svcHtml = (f.approved && Array.isArray(f.services) && f.services.length) ?
                        `<div class="mt-2">
                            <div class="fw-semibold mb-1">บริการ</div>
                            <ul class="mb-2 ps-3">${f.services.map(s=>`<li class="small">${esc(s)}</li>`).join('')}</ul>
                       </div>` :
                        '';

                    mk.bindPopup(`
                        <div class="tmc-pop" style="min-width:260px;">

                        <div class="lv-head">
                            <span class="lv-dot" style="background:${c}"></span>
                            <span class="lv-name">${esc(f.name)}</span>
                        </div>

                        ${f.address
                            ? `<p class="mb-1"><span class="text-muted">ที่อยู่:</span> ${esc(f.address)}</p>`
                            : ''}

                        <p class="mb-2">
                            <span class="text-muted">จังหวัด:</span> ${esc(f.province || '-')}
                            <span class="ms-2 text-muted">${districtLabel}:</span> ${esc(f.district || '-')}
                            <span class="ms-2 text-muted">${subdistrictLabel}:</span> ${esc(f.subdistrict || '-')}
                        </p>

                        ${svcHtml}

                        ${contactLine
                            ? `<p class="text-muted small mt-2 mb-2"><strong>ติดต่อ:</strong> ${esc(contactLine)}</p>`
                            : ''}

                        <button type="button" class="btn btn-primary btn-sm w-100" disabled>
                            <i class="ph-duotone ph-paper-plane-tilt me-1"></i> ส่งข้อความ
                        </button>
                        </div>
                    `, {
                        maxWidth: 480,
                        autoPan: true
                    });


                    mk.on('popupopen', () => {
                        setActiveByKey(key, true);
                        ensureVisibleWithPanel(mk);
                        panToOffset(mk.getLatLng(), L.point(-100, 200));
                    });

                    markers.push(mk);
                    markerById.set(key, mk);

                    const lbl = L.marker([f.lat, f.lon], {
                        interactive: true,
                        icon: L.divIcon({
                            className: 'facility-label',
                            html: `<div class="flabel" style="--c:${c}">
                                <span class="dot"></span>
                                <span class="name">${esc(f.name)}</span>
                               </div>`,
                            iconSize: null,
                            iconAnchor: [-6, 18]
                        }),
                        zIndexOffset: 1000
                    }).addTo(map);

                    lbl.on('click', () => mk.openPopup());
                    labels.push(lbl);
                });

                if (markers.length) map.fitBounds(L.featureGroup(markers).getBounds().pad(0.2));

                const LABEL_MIN_ZOOM = 9;

                function updateLabels() {
                    const show = map.getZoom() >= LABEL_MIN_ZOOM;
                    labels.forEach(l => l.getElement()?.classList.toggle('opacity-0', !show));
                }
                map.whenReady(updateLabels);
                map.on('zoomend', updateLabels);

                function flyPanZoom(mk) {
                    if (!mk) return;
                    const latlng = mk.getLatLng(),
                        cur = map.getCenter(),
                        distM = map.distance(cur, latlng);

                    let z;
                    if (distM > 600000) z = 8;
                    else if (distM > 200000) z = 10;
                    else if (distM > 50000) z = 12;
                    else z = Math.max(map.getZoom(), 14);

                    const needZoom = Math.abs(map.getZoom() - z) > 0.6;
                    const done = () => mk.openPopup();
                    const offsetPx = L.point(-100, 200);
                    const target = (() => {
                        const pt = map.project(latlng, z).subtract(offsetPx);
                        return map.unproject(pt, z);
                    })();

                    if (distM > 1500 || needZoom) {
                        map.flyTo(target, z, {
                            animate: true,
                            duration: 3,
                            easeLinearity: 0.1
                        });
                        map.once('moveend', done);
                    } else {
                        map.panTo(target, {
                            animate: true,
                            duration: 1.2,
                            easeLinearity: 0.2
                        });
                        map.once('moveend', done);
                    }
                }

                function ensureVisibleWithPanel(mk) {
                    const panel = document.querySelector('.tmc-list');
                    if (!panel) return;
                    const panelHidden = getComputedStyle(panel).display === 'none' || panel.classList.contains('min');
                    const panelWidth = panelHidden ? 0 : panel.offsetWidth;
                    if (panelWidth <= 0) return;

                    const margin = 24,
                        halfNeed = panelWidth / 2 + margin;

                    const size = map.getSize(),
                        pt = map.latLngToContainerPoint(mk.getLatLng());

                    const rightSpace = size.x - pt.x;
                    if (rightSpace < halfNeed) {
                        const dx = (halfNeed - rightSpace);
                        map.panBy([-dx, 0], {
                            animate: true,
                            duration: 0.6
                        });
                    }
                }

                function panToOffset(latlng, offsetPx) {
                    const z = map.getZoom();
                    const pt = map.project(latlng, z).subtract(offsetPx);
                    const ll = map.unproject(pt, z);
                    map.panTo(ll, {
                        animate: true,
                        duration: 0.6,
                        easeLinearity: 0.2
                    });
                }

                const listCtrl = L.control({
                    position: 'topright'
                });
                listCtrl.onAdd = function() {
                    const wrap = L.DomUtil.create('div', 'tmc-list');
                    wrap.innerHTML = `<div class="hdr">
                        <span>หน่วยบริการทั้งหมด</span>
                        <button class="toggle" type="button" title="ย่อ/ขยาย">▣</button>
                    </div>
                    <div class="srch"><input type="search" placeholder="ค้นหาชื่อ/จังหวัด"></div>
                    <div class="body"></div>`;

                    L.DomEvent.disableClickPropagation(wrap);
                    L.DomEvent.disableScrollPropagation(wrap);

                    const body = wrap.querySelector('.body'),
                        input = wrap.querySelector('input[type="search"]'),
                        toggleBtn = wrap.querySelector('.toggle');

                    L.DomEvent.disableClickPropagation(input);
                    L.DomEvent.disableScrollPropagation(input);

                    [
                        'keydown', 'keypress', 'keyup', 'input', 'click',
                        'mousedown', 'dblclick', 'touchstart', 'pointerdown',
                        'wheel', 'contextmenu'
                    ].forEach(evt =>
                        L.DomEvent.on(input, evt, e => e.stopPropagation())
                    );

                    input.addEventListener('focus', () => map.keyboard && map.keyboard.disable());
                    input.addEventListener('blur', () => map.keyboard && map.keyboard.enable());
                    input.setAttribute('autocomplete', 'off');
                    input.setAttribute('spellcheck', 'false');
                    input.setAttribute('autocapitalize', 'off');

                    const rowsData = facilities
                        .filter(f => f.lat != null && f.lon != null)
                        .map(f => {
                            const c = LEVEL_COLORS[f.levelKey] || DEFAULT_COLOR;
                            const key = String(f.id ?? `${f.lat},${f.lon}`);
                            const lvlText = f.levelKey === 'unassessed' ?
                                'ยังไม่ได้ประเมิน' :
                                `ระดับ${String(f.levelText||'-')}`;

                            return {
                                key,
                                name: f.name,
                                province: f.province || '-',
                                levelText: lvlText,
                                color: c
                            };
                        });

                    function buildRow(row) {
                        const el = document.createElement('div');
                        el.className = 'tmc-item';
                        el.dataset.key = row.key;
                        el.style.setProperty('--c', row.color);
                        el.innerHTML = `
                        <span class="dot" style="background:${row.color}"></span>
                        <div>
                            <div class="tit">${esc(row.name)}</div>
                            <div class="sub">${esc(row.province)} • ${esc(row.levelText)}</div>
                        </div>`;
                        el.addEventListener('click', () => {
                            setActive(el, true);
                            const mk = markerById.get(row.key);
                            flyPanZoom(mk);
                        });
                        return el;
                    }

                    function render(q = '') {
                        const s = q.trim().toLowerCase();
                        body.innerHTML = '';
                        elByKey.clear();

                        const list = s ?
                            rowsData.filter(r =>
                                (r.name + ' ' + r.province + ' ' + r.levelText)
                                .toLowerCase()
                                .includes(s)
                            ) :
                            rowsData;

                        if (!list.length) {
                            const emp = document.createElement('div');
                            emp.className = 'tmc-empty';
                            emp.textContent = 'ไม่พบบันทึก';
                            body.appendChild(emp);
                        } else {
                            list.forEach(r => {
                                const el = buildRow(r);
                                elByKey.set(r.key, el);
                                body.appendChild(el);
                            });
                        }

                        if (activeKey) setActiveByKey(activeKey, false);
                    }

                    input.addEventListener('input', e => render(e.target.value));

                    toggleBtn.addEventListener('click', () => {
                        wrap.classList.toggle('min');
                        if (activeKey) {
                            const mk = markerById.get(activeKey);
                            if (mk) ensureVisibleWithPanel(mk);
                        }
                    });

                    render();
                    return wrap;
                };
                listCtrl.addTo(map);

                function setActive(el, scrollIntoView) {
                    if (activeEl) activeEl.classList.remove('active');
                    activeEl = el || null;
                    if (activeEl) {
                        activeKey = activeEl.dataset.key;
                        activeEl.classList.add('active');
                        if (scrollIntoView) {
                            activeEl.scrollIntoView({
                                block: 'nearest',
                                inline: 'nearest',
                                behavior: 'smooth'
                            });
                        }
                    }
                }

                function setActiveByKey(key, scrollIntoView) {
                    activeKey = key;
                    const el = elByKey.get(key);
                    if (el) {
                        setActive(el, scrollIntoView);
                        return;
                    }

                    const panel = document.querySelector('.tmc-list'),
                        input = panel?.querySelector('input[type="search"]');

                    if (input && input.value) {
                        input.value = '';
                        input.dispatchEvent(new Event('input'));
                    }

                    const el2 = elByKey.get(key);
                    if (el2) setActive(el2, scrollIntoView);
                }

                function coloredPin(c) {
                    return L.icon({
                        iconUrl: 'data:image/svg+xml;utf8,' + encodeURIComponent(`
<svg xmlns="http://www.w3.org/2000/svg" width="28" height="38" viewBox="0 0 28 38" fill="none">
    <path d="M14 1C9 1 4 5.6 4 11.6 4 21.8 14 37 14 37s10-15.2 10-25.4C24 5.6 19 1 14 1z" fill="${c}"/>
    <circle cx="14" cy="11.6" r="3.6" fill="#fff"/>
</svg>`),
                        iconSize: [28, 38],
                        iconAnchor: [14, 37],
                        popupAnchor: [0, -38]
                    });
                }

                function coloredPinClassic(c) {
                    return L.icon({
                        iconUrl: 'data:image/svg+xml;utf8,' + encodeURIComponent(`
<svg xmlns="http://www.w3.org/2000/svg" width="28" height="38" viewBox="0 0 28 38" fill="none">
    <path d="M14 1C9 1 4 5.6 4 11.6C4 21.8 14 37 14 37C14 37 24 21.8 24 11.6C24 5.6 19 1 14 1Z"
          fill="${c}" stroke="rgba(0,0,0,0.3)" stroke-width="1"/>
    <circle cx="14" cy="11.6" r="4.2" fill="#fff" />
    <circle cx="14" cy="11.6" r="2.2" fill="rgba(0,0,0,0.08)"/>
</svg>`),
                        iconSize: [28, 38],
                        iconAnchor: [14, 37],
                        popupAnchor: [0, -30],
                        className: 'pin-classic'
                    });
                }

                function esc(s) {
                    return String(s ?? '').replace(/[&<>\"']/g, m => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;'
                    } [m]));
                }

                // ====== EXPORT MENU (PNG / GeoJSON / CSV) =====================================================
                const screenshoter = (typeof L.simpleMapScreenshoter === 'function') ?
                    L.simpleMapScreenshoter({
                        hidden: true
                    }).addTo(map) :
                    null;

                function downloadBlob(blob, filename) {
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = filename;
                    a.style.display = 'none';
                    document.body.appendChild(a);
                    a.click();
                    setTimeout(() => {
                        URL.revokeObjectURL(url);
                        a.remove();
                    }, 0);
                }

                function downloadText(text, filename, mime = 'text/plain;charset=utf-8') {
                    const blob = new Blob([text], {
                        type: mime
                    });
                    downloadBlob(blob, filename);
                }

                function facilitiesToGeoJSON() {
                    const feats = (Array.isArray(facilities) ? facilities : [])
                        .filter(f => f.lat != null && f.lon != null)
                        .map(f => ({
                            type: 'Feature',
                            geometry: {
                                type: 'Point',
                                coordinates: [Number(f.lon), Number(f.lat)]
                            },
                            properties: {
                                id: f.id ?? null,
                                name: f.name ?? '',
                                levelKey: f.levelKey ?? '',
                                levelText: f.levelText ?? '',
                                approved: !!f.approved,
                                province: f.province ?? '',
                                district: f.district ?? '',
                                subdistrict: f.subdistrict ?? '',
                                provinceCode: f.provinceCode ?? '',
                                districtCode: f.districtCode ?? '',
                                subdistrictCode: f.subdistrictCode ?? '',
                                phone: f.phone ?? '',
                                email: f.email ?? '',
                                services: Array.isArray(f.services) ? f.services.join(', ') : ''
                            }
                        }));
                    return {
                        type: 'FeatureCollection',
                        features: feats
                    };
                }

                // helper: download as CSV with BOM
                function downloadCSV(text, filename) {
                    const BOM = '\uFEFF';
                    const blob = new Blob([BOM, text], {
                        type: 'text/csv;charset=utf-8'
                    });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = filename;
                    a.style.display = 'none';
                    document.body.appendChild(a);
                    a.click();
                    setTimeout(() => {
                        URL.revokeObjectURL(url);
                        a.remove();
                    }, 0);
                }

                function facilitiesToCSV() {
                    const rows = Array.isArray(facilities) ? facilities : [];

                    const headers = [
                        'id',
                        'ชื่อหน่วยบริการ',
                        'ระดับ',
                        'สถานะประเมิน',
                        'จังหวัด',
                        'อำเภอ',
                        'ตำบล',
                        'ละติจูด',
                        'ลองจิจูด',
                        'โทรศัพท์',
                        'อีเมล์',
                        'การให้บริการ'
                    ];

                    const q = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`;

                    const lines = [headers.join(',')];
                    rows.forEach(f => {
                        lines.push([
                            f.id,
                            f.name,
                            f.levelText,
                            f.approved ? 'อนุมัติ' : 'รอดำเนินการ',
                            f.province,
                            f.district,
                            f.subdistrict,
                            f.lat,
                            f.lon,
                            f.phone,
                            f.email,
                            Array.isArray(f.services) ? f.services.join(' | ') : ''
                        ].map(q).join(','));
                    });

                    return lines.join('\r\n');
                }

                const ExportToggle = L.Control.extend({
                    options: {
                        position: 'topleft'
                    },
                    onAdd: function() {
                        const container = L.DomUtil.create('div', 'leaflet-control tmc-export-toggle leaflet-bar');
                        const btn = L.DomUtil.create('a', '', container);
                        btn.href = '#';
                        btn.title = 'Export';

                        L.DomEvent.disableClickPropagation(container);
                        L.DomEvent.disableScrollPropagation(container);

                        L.DomEvent.on(btn, 'click', (e) => {
                            L.DomEvent.stop(e);
                            menu.getContainer().classList.toggle('d-none');
                        });

                        return container;
                    }
                });

                const ExportMenu = L.Control.extend({
                    options: {
                        position: 'topleft'
                    },
                    onAdd: function() {
                        const div = L.DomUtil.create('div', 'tmc-export d-none');
                        div.id = 'tmc-export-menu';
                        div.innerHTML = `
                        <a href="#" class="btn" data-act="png">
                            <i class="ti ti-photo"></i> Download PNG
                        </a>
                        <a href="#" class="btn" data-act="geojson">
                            <i class="ti ti-file-code"></i> Download GeoJSON
                        </a>
                        <a href="#" class="btn" data-act="csv">
                            <i class="ti ti-file-spreadsheet"></i> Download CSV
                        </a>
                    `;

                        L.DomEvent.disableClickPropagation(div);
                        L.DomEvent.disableScrollPropagation(div);

                        div.addEventListener('click', async (ev) => {
                            const a = ev.target.closest('a.btn');
                            if (!a) return;
                            ev.preventDefault();

                            const act = a.dataset.act;
                            const today = new Date();
                            const stamp = `${today.getFullYear()}${String(today.getMonth()+1).padStart(2,'0')}${String(today.getDate()).padStart(2,'0')}`;

                            if (act === 'png') {
                                const blob = await screenshoter.takeScreen('blob');
                                downloadBlob(blob, `tmc-map_${stamp}.png`);
                            } else if (act === 'geojson') {
                                const gj = facilitiesToGeoJSON();
                                downloadBlob(
                                    new Blob([JSON.stringify(gj)], {
                                        type: 'application/geo+json;charset=utf-8'
                                    }),
                                    `tmc-facilities_${stamp}.geojson`
                                );
                            } else if (act === 'csv') {
                                const csv = facilitiesToCSV();
                                downloadCSV(csv, `tmc-facilities_${stamp}.csv`);
                            }

                            div.classList.add('d-none');
                        });

                        return div;
                    }
                });

                const toggle = new ExportToggle().addTo(map);
                const menu = new ExportMenu().addTo(map);

                map.on('click', () => menu.getContainer().classList.add('d-none'));
            })(); // IIFE end
        </script>
    @endpush






    {{-- --------------------------------------------------------------------------------------------------------------------------------
         ██████╗  █████╗ ██████╗
        ██╔════╝ ██╔══██╗██╔══██╗
        ██║  ███╗███████║██████╔╝
        ██║   ██║██╔══██║██╔═══╝
        ╚██████╔╝██║  ██║██║
        ╚═════╝ ╚═╝  ╚═╝╚═╝

        SECTION 3: GAP SUMMARY & GAP UNITS (การ์ดสรุป GAP + Modal รายชื่อหน่วยที่มี GAP + Script ดึงข้อมูล)
    -------------------------------------------------------------------------------------------------------------------------------- --}}

    @php
        // ความสูงพื้นที่เลื่อน (ปรับได้)
        $gapScrollMaxHeight = 360; // px
    @endphp

    <div class="row g-3 mb-3">

        {{-- ===== GAP: ระดับพื้นฐาน ===== --}}
        <div class="col-lg-4">
            <div class="card h-100 position-relative">
                <span class="position-absolute top-0 bottom-0 start-0 {{ $levelBg['basic'] }}" style="width:6px;border-top-left-radius:.5rem;border-bottom-left-radius:.5rem;"></span>

                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <span class="fw-semibold {{ $levelText['basic'] }}"><i class="ph-duotone ph-chart-bar"></i> GAP ระดับพื้นฐาน</span>
                    <span class="badge bg-{{ $levelBg['basic'] }} {{ $levelText['basic'] }}">
                        {{ number_format($gapBasic->count()) }}
                    </span>
                </div>

                {{-- ★ แสดงครบทุกแถว + มี scrollbar ภายในการ์ด --}}
                <div class="card-body p-0" style="max-height: {{ $gapScrollMaxHeight }}px; overflow: auto;">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th style="width:60%">รายการ GAP</th>
                                <th class="text-end" style="width:40%">จำนวนหน่วยบริการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($gapBasic as $row)
                                <tr class="gap-row" data-level="basic" data-qid="{{ $row->question_id }}" data-label="{{ e($row->gap_label) }}" style="cursor:pointer;">
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

        {{-- ===== GAP: ระดับกลาง ===== --}}
        <div class="col-lg-4">
            <div class="card h-100 position-relative">
                <span class="position-absolute top-0 bottom-0 start-0 {{ $levelBg['medium'] }}" style="width:6px;border-top-left-radius:.5rem;border-bottom-left-radius:.5rem;"></span>

                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <span class="fw-semibold {{ $levelText['medium'] }}"><i class="ph-duotone ph-chart-bar"></i> GAP ระดับกลาง</span>
                    <span class="badge {{ $levelBg['medium'] }} {{ $levelText['medium'] }}">
                        {{ number_format($gapIntermediate->count()) }}
                    </span>
                </div>

                <div class="card-body p-0" style="max-height: {{ $gapScrollMaxHeight }}px; overflow: auto;">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th style="width:60%">รายการ GAP</th>
                                <th class="text-end" style="width:40%">จำนวนหน่วยบริการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($gapIntermediate as $row)
                                <tr class="gap-row" data-level="medium" data-qid="{{ $row->question_id }}" data-label="{{ e($row->gap_label) }}" style="cursor:pointer;">
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

        {{-- ===== GAP: ระดับสูง ===== --}}
        <div class="col-lg-4">
            <div class="card h-100 position-relative">
                <span class="position-absolute top-0 bottom-0 start-0 {{ $levelBg['advanced'] }}" style="width:6px;border-top-left-radius:.5rem;border-bottom-left-radius:.5rem;"></span>

                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <span class="fw-semibold {{ $levelText['advanced'] }}"><i class="ph-duotone ph-chart-bar"></i> GAP ระดับสูง</span>
                    <span class="badge {{ $levelBg['advanced'] }} {{ $levelText['advanced'] }}">
                        {{ number_format($gapAdvanced->count()) }}
                    </span>
                </div>

                <div class="card-body p-0" style="max-height: {{ $gapScrollMaxHeight }}px; overflow: auto;">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th style="width:60%">รายการ GAP</th>
                                <th class="text-end" style="width:40%">จำนวนหน่วยบริการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($gapAdvanced as $row)
                                <tr class="gap-row" data-level="advanced" data-qid="{{ $row->question_id }}" data-label="{{ e($row->gap_label) }}" style="cursor:pointer;">
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

    {{-- ==== Modal: รายชื่อหน่วยบริการที่มี GAP ==== --}}
    <div class="modal fade" id="modal-gap-units" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">หน่วยบริการที่มี GAP</h5>
                        <div class="small text-muted"><span id="gap-meta"></span></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="gap-loading" class="p-3 text-center d-none">
                        <div class="spinner-border" role="status" aria-hidden="true"></div>
                        <div class="mt-2">กำลังโหลด...</div>
                    </div>

                    <div id="gap-empty" class="p-3 text-center text-muted d-none">ไม่พบบันทึก</div>

                    <div id="gap-table-wrap" class="table-responsive d-none">
                        <table class="table table-sm table-striped mb-0 align-middle">
                            <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th style="width:5%">#</th>
                                    <th style="width:65%">ชื่อหน่วยบริการ</th>
                                    <th>จังหวัด</th>
                                </tr>
                            </thead>
                            <tbody id="gap-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- ----------------------------------------------------------------------------------------------------------------------------
      GAP: SCRIPT (โหลดรายชื่อหน่วยบริการที่มี GAP ตาม level + question_id ตามฟิลเตอร์ปี/รอบ/เขต/จังหวัด)
    ---------------------------------------------------------------------------------------------------------------------------- --}}
    <script>
        (function() {
            // ฟิลเตอร์ปัจจุบัน (มาจาก Controller; ถ้าไม่ได้ส่งมาก็เป็น null)
            const FILTERS = {
                year: {{ isset($filterYear) ? (int) $filterYear : 'null' }},
                round: {{ isset($filterRound) ? (int) $filterRound : 'null' }},
                region: {{ isset($filterRegion) && $filterRegion ? (int) $filterRegion : 'null' }},
                province_code: {!! isset($filterProvinceCode) && $filterProvinceCode ? "'" . $filterProvinceCode . "'" : 'null' !!}
            };

            const routeGapUnits = "{{ route('backend.dashboard.gap-units') }}";
            const $modal = document.getElementById('modal-gap-units');
            const $tbody = document.getElementById('gap-tbody');
            const $wrap = document.getElementById('gap-table-wrap');
            const $loading = document.getElementById('gap-loading');
            const $empty = document.getElementById('gap-empty');
            const $meta = document.getElementById('gap-meta');

            function showState(state) {
                // state: 'loading' | 'empty' | 'table'
                $loading.classList.toggle('d-none', state !== 'loading');
                $empty.classList.toggle('d-none', state !== 'empty');
                $wrap.classList.toggle('d-none', state !== 'table');
            }

            function esc(s) {
                return String(s ?? '').replace(/[&<>\"']/g, m => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                } [m]));
            }

            async function openGapModal(level, qid, label) {
                // heading/meta
                const LVL = {
                    basic: 'ระดับพื้นฐาน',
                    medium: 'ระดับกลาง',
                    advanced: 'ระดับสูง'
                };
                $meta.textContent = `${LVL[level] || '-'} • ${label}`;

                // reset UI
                $tbody.innerHTML = '';
                showState('loading');

                // build query
                const params = new URLSearchParams({
                    level,
                    qid,
                    year: FILTERS.year ?? '',
                    round: FILTERS.round ?? '',
                });
                if (FILTERS.region) params.set('region', FILTERS.region);
                if (FILTERS.province_code) params.set('province_code', FILTERS.province_code);

                try {
                    const res = await fetch(`${routeGapUnits}?${params.toString()}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();
                    const rows = Array.isArray(data.rows) ? data.rows : [];

                    if (!rows.length) {
                        showState('empty');
                    } else {
                        let i = 1;
                        $tbody.innerHTML = rows.map(r => `
                            <tr>
                                <td class="text-center">${i++}</td>
                                <td>${esc(r.name)}</td>
                                <td>${esc(r.province)}</td>
                            </tr>
                        `).join('');
                        showState('table');
                    }

                    // เปิด modal
                    const bsModal = bootstrap.Modal.getOrCreateInstance($modal);
                    bsModal.show();
                } catch (err) {
                    console.error(err);
                    $empty.textContent = 'เกิดข้อผิดพลาดในการโหลดข้อมูล';
                    showState('empty');
                    const bsModal = bootstrap.Modal.getOrCreateInstance($modal);
                    bsModal.show();
                }
            }

            // delegate คลิกในทุกตาราง GAP
            document.addEventListener('click', function(e) {
                const tr = e.target.closest('tr.gap-row');
                if (!tr) return;
                const level = tr.dataset.level;
                const qid = tr.dataset.qid;
                const label = tr.dataset.label || '';
                if (!level || !qid) return;
                openGapModal(level, qid, label);
            });
        })();
    </script>



    {{-- --------------------------------------------------------------------------------------------------------------------------------

    ██╗  ██╗     █████╗ ██████╗ ██████╗ ██████╗  ██████╗ ██╗   ██╗ █████╗ ██╗         ███████╗████████╗ █████╗ ████████╗██╗   ██╗███████╗
    ╚██╗██╔╝    ██╔══██╗██╔══██╗██╔══██╗██╔══██╗██╔═══██╗██║   ██║██╔══██╗██║         ██╔════╝╚══██╔══╝██╔══██╗╚══██╔══╝██║   ██║██╔════╝
     ╚███╔╝     ███████║██████╔╝██████╔╝██████╔╝██║   ██║██║   ██║███████║██║         ███████╗   ██║   ███████║   ██║   ██║   ██║███████╗
     ██╔██╗     ██╔══██║██╔═══╝ ██╔═══╝ ██╔══██╗██║   ██║╚██╗ ██╔╝██╔══██║██║         ╚════██║   ██║   ██╔══██║   ██║   ██║   ██║╚════██║
    ██╔╝ ██╗    ██║  ██║██║     ██║     ██║  ██║╚██████╔╝ ╚████╔╝ ██║  ██║███████╗    ███████║   ██║   ██║  ██║   ██║   ╚██████╔╝███████║
    ╚═╝  ╚═╝    ╚═╝  ╚═╝╚═╝     ╚═╝     ╚═╝  ╚═╝ ╚═════╝   ╚═══╝  ╚═╝  ╚═╝╚══════╝    ╚══════╝   ╚═╝   ╚═╝  ╚═╝   ╚═╝    ╚═════╝ ╚══════╝

    ██████╗  █████╗ ████████╗ █████╗
    ██╔══██╗██╔══██╗╚══██╔══╝██╔══██╗
    ██║  ██║███████║   ██║   ███████║
    ██║  ██║██╔══██║   ██║   ██╔══██║
    ██████╔╝██║  ██║   ██║   ██║  ██║
    ╚═════╝ ╚═╝  ╚═╝   ╚═╝   ╚═╝  ╚═╝

    SECTION X: STATUS DATA (เตรียมข้อมูลสถานะ) -> ใช้ร่วมกันทั้ง CHART และ CARD
    -------------------------------------------------------------------------------------------------------------------------------- --}}
    @php
        use App\Models\AssessmentServiceUnitLevel;

        // ====== ดึงค่าจาก config แทนฮาร์ดโค้ด ======
        $displayOrder = config('tmc.approval_display_order', ['pending', 'reviewing', 'returned', 'approved', 'rejected', 'no_form']);

        $labels = config('tmc.approval_text', []);
        $bgMapHex = config('tmc.approval_card_bg', []); // ตอนนี้เป็นรหัสสี HEX แล้ว
        $fgMapClass = config('tmc.approval_card_fg', []); // text-white / text-dark
        $chartColorsHex = config('tmc.approval_chart_colors', []); // ยังกันไว้ ถ้าอยากใช้ต่อที่อื่น

        // สถานะที่ระบบฐานข้อมูลจริง ๆ จะส่งมา (ไม่รวม no_form)
        $dbStatuses = ['pending', 'reviewing', 'returned', 'approved', 'rejected'];

        // เตรียม record ล่าสุดของแต่ละหน่วย ปี/รอบ ที่เลือก
        $unitIds = $serviceUnits->pluck('id')->all();
        $latestAll = collect();

        if (!empty($unitIds)) {
            $latestAll = AssessmentServiceUnitLevel::select('id', 'service_unit_id', 'assess_year', 'assess_round', 'approval_status')->whereIn('service_unit_id', $unitIds)->where('assess_year', $filterYear)->where('assess_round', $filterRound)->whereNull('deleted_at')->orderByDesc('id')->get()->groupBy('service_unit_id')->map->first();
        }

        // เตรียม bucket แต่ละสถานะ
        $status = [];
        foreach ($displayOrder as $key) {
            $status[$key] = [
                'count' => 0,
                'units' => collect(),
            ];
        }

        // ใส่หน่วยบริการแต่ละอันลง bucket
        foreach ($serviceUnits as $su) {
            $record = $latestAll->get($su->id);

            if (!$record) {
                // ไม่มีเรคคอร์ดเลยในปี/รอบ -> ยังไม่ทำแบบประเมิน
                $bucket = 'no_form';
            } else {
                $st = $record->approval_status;

                if ($st === 'pending' || $st === null || $st === '') {
                    // รวมค่าว่างเข้า pending
                    $bucket = 'pending';
                } elseif (in_array($st, $dbStatuses, true)) {
                    $bucket = $st;
                } else {
                    // สถานะไม่รู้จัก -> ข้าม
                    continue;
                }
            }

            $status[$bucket]['count']++;

            $status[$bucket]['units']->push(
                (object) [
                    'org_name' => $su->org_name ?? ($su->name ?? '—'),
                ],
            );
        }

        // เรียงชื่อหน่วยบริการในแต่ละ bucket ตามตัวอักษร
        foreach ($status as $k => $it) {
            $status[$k]['units'] = $it['units']->sortBy('org_name')->values();
        }

        // ----- เตรียมข้อมูลส่งเข้า chart -----
        // จำนวนหน่วย ตามลำดับที่กำหนด
        $seriesCounts = collect($displayOrder)->map(fn($k) => (int) data_get($status, "$k.count", 0))->all();

        // label ไทย ตาม config
        $seriesLabels = collect($displayOrder)->map(fn($k) => $labels[$k] ?? $k)->all();

        // ✅ สีของแต่ละสถานะ
        //    ดึงจาก approval_card_bg (ซึ่งตอนนี้เป็น HEX 100%)
        //    ถ้า config ไม่มี ก็ fallback #999999
        $seriesColors = collect($displayOrder)->map(fn($k) => $bgMapHex[$k] ?? '#999999')->all();
    @endphp




    {{-- --------------------------------------------------------------------------------------------------------------------------------

     █████╗      █████╗ ██████╗ ██████╗ ██████╗  ██████╗ ██╗   ██╗ █████╗ ██╗         ███████╗████████╗ █████╗ ████████╗██╗   ██╗███████╗
    ██╔══██╗    ██╔══██╗██╔══██╗██╔══██╗██╔══██╗██╔═══██╗██║   ██║██╔══██╗██║         ██╔════╝╚══██╔══╝██╔══██╗╚══██╔══╝██║   ██║██╔════╝
    ███████║    ███████║██████╔╝██████╔╝██████╔╝██║   ██║██║   ██║███████║██║         ███████╗   ██║   ███████║   ██║   ██║   ██║███████╗
    ██╔══██║    ██╔══██║██╔═══╝ ██╔═══╝ ██╔══██╗██║   ██║╚██╗ ██╔╝██╔══██║██║         ╚════██║   ██║   ██╔══██║   ██║   ██║   ██║╚════██║
    ██║  ██║    ██║  ██║██║     ██║     ██║  ██║╚██████╔╝ ╚████╔╝ ██║  ██║███████╗    ███████║   ██║   ██║  ██║   ██║   ╚██████╔╝███████║
    ╚═╝  ╚═╝    ╚═╝  ╚═╝╚═╝     ╚═╝     ╚═╝  ╚═╝ ╚═════╝   ╚═══╝  ╚═╝  ╚═╝╚══════╝    ╚══════╝   ╚═╝   ╚═╝  ╚═╝   ╚═╝    ╚═════╝ ╚══════╝

     ██████╗██╗  ██╗ █████╗ ██████╗ ████████╗
    ██╔════╝██║  ██║██╔══██╗██╔══██╗╚══██╔══╝
    ██║     ███████║███████║██████╔╝   ██║
    ██║     ██╔══██║██╔══██║██╔══██╗   ██║
    ╚██████╗██║  ██║██║  ██║██║  ██║   ██║
     ╚═════╝╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝   ╚═╝

    SECTION A: CHART – สรุปสถานะด้วย ApexCharts (ย้ายขึ้นมาก่อน CARD)
    -------------------------------------------------------------------------------------------------------------------------------- --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">จำนวนหน่วยบริการตามสถานะ (Bar)</h6>
                </div>
                <div class="card-body">
                    <div id="chart-status-bar"></div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                const seriesCounts = @json($seriesCounts);
                const seriesLabels = @json($seriesLabels);
                const seriesColors = @json($seriesColors);
                const statusKeys = @json($displayOrder);

                // ✅ ดึงสีตัวเลขจาก config
                const chartTextColors = @json(config('tmc.approval_chart_text_colors', []));

                // zip ข้อมูลทั้งหมด
                const zippedAll = statusKeys.map((key, i) => ({
                    key: key,
                    label: seriesLabels[i] ?? key,
                    count: seriesCounts[i] ?? 0,
                    color: seriesColors[i] ?? '#999999',
                    textColor: chartTextColors[key] ?? '#000000'
                }));

                // แยก no_form ออกมาไว้ล่างสุด
                const normalItems = zippedAll.filter(item => item.key !== 'no_form');
                const noFormItem = zippedAll.find(item => item.key === 'no_form');
                normalItems.sort((a, b) => b.count - a.count);
                const zipped = noFormItem ? [...normalItems, noFormItem] : normalItems;

                const el = document.querySelector("#chart-status-bar");
                if (!el) return;

                const now = new Date();
                const baseName = `status-by-approval_${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}${String(now.getDate()).padStart(2, '0')}`;

                const options = {
                    chart: {
                        type: 'bar',
                        height: 420,
                        toolbar: {
                            show: true,
                            tools: {
                                download: true,
                                selection: false,
                                zoom: false,
                                zoomin: false,
                                zoomout: false,
                                pan: false,
                                reset: false
                            },
                            export: {
                                csv: {
                                    filename: baseName,
                                    headerCategory: 'สถานะ',
                                    headerValue: 'จำนวนหน่วยบริการ'
                                },
                                svg: {
                                    filename: baseName
                                },
                                png: {
                                    filename: baseName
                                }
                            }
                        },
                        animations: {
                            enabled: true
                        },
                        events: {
                            dataPointSelection: (event) => {
                                event.stopPropagation();
                                return false;
                            }
                        }
                    },

                    series: [{
                        name: 'จำนวนหน่วยบริการ',
                        data: zipped.map(z => z.count)
                    }],

                    xaxis: {
                        categories: zipped.map(z => z.label),
                        labels: {
                            rotate: -10,
                            trim: true
                        },
                        title: {
                            text: 'จำนวนหน่วยบริการ',
                            style: {
                                fontSize: '14px',
                                fontWeight: 600,
                                color: '#333'
                            }
                        }
                    },

                    // ✅ สีพื้นแท่ง
                    colors: zipped.map(z => z.color),

                    plotOptions: {
                        bar: {
                            horizontal: true,
                            barHeight: '60%',
                            distributed: true,
                            dataPointSelection: false
                        }
                    },

                    dataLabels: {
                        enabled: true,
                        formatter: (val) => new Intl.NumberFormat().format(val),
                        // ✅ กำหนดสีแต่ละแท่ง จาก config
                        style: {
                            fontSize: '13px',
                            colors: zipped.map(z => z.textColor)
                        }
                    },

                    tooltip: {
                        enabled: true,
                        y: {
                            formatter: (val) => new Intl.NumberFormat().format(val)
                        }
                    },

                    grid: {
                        strokeDashArray: 4
                    },
                    states: {
                        hover: {
                            filter: {
                                type: 'none'
                            }
                        },
                        active: {
                            filter: {
                                type: 'none'
                            }
                        }
                    },
                    legend: {
                        show: false
                    }
                };

                const bar = new ApexCharts(el, options);
                bar.render();
            })();
        </script>
    @endpush







    {{-- --------------------------------------------------------------------------------------------------------------------------------

    ██████╗      █████╗ ██████╗ ██████╗ ██████╗  ██████╗ ██╗   ██╗ █████╗ ██╗         ███████╗████████╗ █████╗ ████████╗██╗   ██╗███████╗
    ██╔══██╗    ██╔══██╗██╔══██╗██╔══██╗██╔══██╗██╔═══██╗██║   ██║██╔══██╗██║         ██╔════╝╚══██╔══╝██╔══██╗╚══██╔══╝██║   ██║██╔════╝
    ██████╔╝    ███████║██████╔╝██████╔╝██████╔╝██║   ██║██║   ██║███████║██║         ███████╗   ██║   ███████║   ██║   ██║   ██║███████╗
    ██╔══██╗    ██╔══██║██╔═══╝ ██╔═══╝ ██╔══██╗██║   ██║╚██╗ ██╔╝██╔══██║██║         ╚════██║   ██║   ██╔══██║   ██║   ██║   ██║╚════██║
    ██████╔╝    ██║  ██║██║     ██║     ██║  ██║╚██████╔╝ ╚████╔╝ ██║  ██║███████╗    ███████║   ██║   ██║  ██║   ██║   ╚██████╔╝███████║
    ╚═════╝     ╚═╝  ╚═╝╚═╝     ╚═╝     ╚═╝  ╚═╝ ╚═════╝   ╚═══╝  ╚═╝  ╚═╝╚══════╝    ╚══════╝   ╚═╝   ╚═╝  ╚═╝   ╚═╝    ╚═════╝ ╚══════╝

     ██████╗ █████╗ ██████╗ ██████╗
    ██╔════╝██╔══██╗██╔══██╗██╔══██╗
    ██║     ███████║██████╔╝██║  ██║
    ██║     ██╔══██║██╔══██╗██║  ██║
    ╚██████╗██║  ██║██║  ██║██████╔╝
     ╚═════╝╚═╝  ╚═╝╚═╝  ╚═╝╚═════╝

    SECTION B: CARD – สถานะการพิจารณาหน่วยบริการ + การ์ด "ยังไม่ได้ทำแบบประเมิน" (แสดงทั้งหมดพร้อม scrollbar)
    (ใช้ตัวแปรจาก SECTION X ด้านบน)
    -------------------------------------------------------------------------------------------------------------------------------- --}}
    @php
        $displayOrder = config('tmc.approval_display_order', []);
        $labels = config('tmc.approval_text', []);
        $bgMap = config('tmc.approval_card_bg', []);
        $fgMap = config('tmc.approval_card_fg', []);
    @endphp

    <div class="row g-3 mb-3">
        @foreach ($displayOrder as $key)
            @php
                $label = $labels[$key] ?? $key;
                $bgHex = $bgMap[$key] ?? '#e9ecef'; // fallback เทาอ่อน
                $fgCls = $fgMap[$key] ?? 'text-dark';
                $count = data_get($status, "$key.count", 0);
                $unitsInStatus = data_get($status, "$key.units", collect());
            @endphp

            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 position-relative">

                    {{-- แถบสีซ้าย ใช้ background-color จาก HEX --}}
                    <span class="position-absolute top-0 bottom-0 start-0" style="background-color: {{ $bgHex }}; width:6px; border-top-left-radius:.5rem; border-bottom-left-radius:.5rem;"></span>

                    <div class="card-header d-flex justify-content-between align-items-center py-2" style="border-bottom: none;">
                        <span class="fw-semibold {{ $fgCls }}">
                            <i class="ti ti-building-community me-1 {{ $fgCls }}"></i>{{ $label }}
                        </span>

                        {{-- ใช้สีพื้น HEX เช่นกัน --}}
                        <span class="badge {{ $fgCls }}" style="background-color: {{ $bgHex }};">
                            {{ number_format($count) }}
                        </span>
                    </div>

                    {{-- Scroll ภายในการ์ด + sticky header --}}
                    <div class="card-body p-0" style="max-height: 360px; overflow: auto;">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th style="width:6%">#</th>
                                    <th>ชื่อหน่วยบริการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($unitsInStatus as $i => $u)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td class="text-wrap" title="{{ $u->org_name }}">{{ $u->org_name }}</td>
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
        @endforeach
    </div>








    {{--
    --------------------------------------------------------------------------------------------------------------------------------

         ██████╗██╗  ██╗ ██████╗ ██████╗  ██████╗ ██████╗ ██╗     ███████╗████████╗██╗  ██╗
        ██╔════╝██║  ██║██╔═══██╗██╔══██╗██╔═══██╗██╔══██╗██║     ██╔════╝╚══██╔══╝██║  ██║
        ██║     ███████║██║   ██║██████╔╝██║   ██║██████╔╝██║     █████╗     ██║   ███████║
        ██║     ██╔══██║██║   ██║██╔══██╗██║   ██║██╔═══╝ ██║     ██╔══╝     ██║   ██╔══██║
        ╚██████╗██║  ██║╚██████╔╝██║  ██║╚██████╔╝██║     ███████╗███████╗   ██║   ██║  ██║
         ╚═════╝╚═╝  ╚═╝ ╚═════╝ ╚═╝  ╚═╝ ╚═════╝ ╚═╝     ╚══════╝╚══════╝   ╚═╝   ╚═╝  ╚═╝

        CHOROPLETH: สัดส่วน “อนุมัติ (%)” ต่อจังหวัด (service_units.org_province_code ↔ geo: pro_code)
    --------------------------------------------------------------------------------------------------------------------------------

    @php
        $approvedRows = \App\Models\AssessmentServiceUnitLevel::query()
            ->join('service_units as su', 'su.id', '=', 'assessment_service_unit_levels.service_unit_id')
            ->where('assessment_service_unit_levels.assess_year', $filterYear)
            ->where('assessment_service_unit_levels.assess_round', $filterRound)
            ->where('assessment_service_unit_levels.approval_status', 'approved')
            ->whereNull('assessment_service_unit_levels.deleted_at')
            ->selectRaw('LPAD(su.org_province_code, 2, "0") as code, COUNT(*) as approved_count')
            ->groupBy('code')
            ->get();

        $totalRows = $serviceUnits->groupBy(fn($su) => str_pad((string) $su->org_province_code, 2, '0', STR_PAD_LEFT))->map->count();

        $provStat = [];
        foreach ($totalRows as $code => $ttl) {
            $appr = (int) ($approvedRows->firstWhere('code', $code)->approved_count ?? 0);
            $pct = $ttl > 0 ? round(($appr / $ttl) * 100, 1) : 0.0;
            $provStat[$code] = ['approved' => $appr, 'total' => (int) $ttl, 'pct' => $pct];
        }

        $provinceNameByCode = \App\Models\Province::query()
            ->get(['code', 'title'])
            ->mapWithKeys(fn($p) => [str_pad((string) $p->code, 2, '0', STR_PAD_LEFT) => $p->title])
            ->toArray();

        $TH_GEOJSON_URL = asset('geo/provinces.geojson');
    @endphp

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div><i class="ph-duotone ph-map-trifold"></i>
                <span class="ms-1">สัดส่วน “อนุมัติ (%)” ต่อจังหวัด ({{ (int) $filterYear }} / รอบ {{ (int) $filterRound }})</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div id="tmc-choropleth" style="height:600px;"></div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin>
        <style>
            .choropleth-legend {
                background: #fff;
                padding: .5rem .75rem;
                border-radius: .5rem;
                box-shadow: 0 6px 18px rgba(0, 0, 0, .12);
                line-height: 1.2;
                font-size: .9rem;
                z-index: 500;
            }

            .choropleth-legend .row {
                display: flex;
                align-items: center;
                gap: .5rem;
                margin: .25rem 0;
            }

            .choropleth-legend .swatch {
                width: 18px;
                height: 12px;
                border-radius: 3px;
                box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .08);
            }

            .leaflet-tooltip.prov-tip {
                background: rgba(255, 255, 255, .95);
                border-radius: .5rem;
                box-shadow: 0 6px 18px rgba(0, 0, 0, .12);
                border: 1px solid #e9ecef;
                padding: .4rem .6rem;
            }
        </style>
    @endpush

    @push('scripts')
    <script>
        (function() {
            // ===== ข้อมูลจาก Blade =====
            const PROV_STAT = @json($provStat);
            const PROV_NAME = @json($provinceNameByCode);
            const GEO_URL = @json($TH_GEOJSON_URL);

            function getColor(p) {
                if (p == null) return '#e5e7eb';
                if (p <= 0) return '#e5e7eb';
                if (p <= 20) return '#fde68a';
                if (p <= 40) return '#fbbf24';
                if (p <= 60) return '#f59e0b';
                if (p <= 80) return '#f97316';
                return '#ef4444';
            }

            function getProvCode(f) {
                const v = f.properties.pro_code ?? f.properties.PRO_CODE ?? '';
                return String(v).padStart(2, '0');
            }

            function getProvNameFromGeo(f) {
                return f.properties.pro_th ?? f.properties.PRO_TH ?? '';
            }

            function style(feature) {
                const code = getProvCode(feature);
                const pct = (PROV_STAT[code] && typeof PROV_STAT[code].pct === 'number') ? PROV_STAT[code].pct : 0;
                return {
                    fillColor: getColor(pct),
                    weight: 1,
                    opacity: 1,
                    color: '#ffffff',
                    fillOpacity: 0.8
                };
            }

            const map = L.map('tmc-choropleth', { zoomControl: true }).setView([15.3, 101], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);

            fetch(GEO_URL).then(r => r.json()).then(geo => {
                const layer = L.geoJSON(geo, {
                    style,
                    onEachFeature: (feature, lyr) => {
                        const code = getProvCode(feature);
                        const stat = PROV_STAT[code] ? PROV_STAT[code] : { approved: 0, total: 0, pct: 0 };
                        const name = PROV_NAME[code] ? PROV_NAME[code] : (getProvNameFromGeo(feature) || 'ไม่ทราบจังหวัด');

                        const tip = `<div><strong>${escapeHtml(name)}</strong></div>
                            <div class="text-muted">อนุมัติ: ${Number(stat.pct).toFixed(1)}% (${Number(stat.approved).toLocaleString()} / ${Number(stat.total).toLocaleString()} หน่วย)</div>`;
                        lyr.bindTooltip(tip, { sticky: true, className: 'prov-tip' });

                        lyr.on({
                            mouseover: e => {
                                e.target.setStyle({ weight: 2, color: '#111', fillOpacity: 0.9 });
                                e.target.bringToFront();
                            },
                            mouseout: e => layer.resetStyle(e.target)
                        });
                    }
                }).addTo(map);
                map.fitBounds(layer.getBounds().pad(0.08));

                const legend = L.control({ position: 'bottomright' });
                legend.onAdd = function() {
                    const div = L.DomUtil.create('div', 'choropleth-legend');
                    const title = L.DomUtil.create('div', 'fw-semibold mb-1', div);
                    title.textContent = 'สัดส่วนอนุมัติ (%)';
                    const ranges = [
                        { lab: '0', val: 0 },
                        { lab: '1–20', val: 20 },
                        { lab: '21–40', val: 40 },
                        { lab: '41–60', val: 60 },
                        { lab: '61–80', val: 80 },
                        { lab: '81–100', val: 100 },
                    ];
                    ranges.forEach(r => {
                        const row = L.DomUtil.create('div', 'row', div);
                        const sw = L.DomUtil.create('span', 'swatch', row);
                        sw.style.background = getColor(r.val);
                        const txt = L.DomUtil.create('span', '', row);
                        txt.textContent = r.lab;
                    });
                    L.DomEvent.disableClickPropagation(div);
                    L.DomEvent.disableScrollPropagation(div);
                    return div;
                };
                legend.addTo(map);
            });

            function escapeHtml(s) {
                return String(s ?? '').replace(/[&<>\"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[m]));
            }
        })();
    </script>
    @endpush

--}}



    {{-- แสดงส่วนนี้เฉพาะเมื่อยังไม่ได้กรองด้วยพารามิเตอร์ `region` --}}
    @if (!request()->filled('region'))

        {{-- --------------------------------------------------------------------------------------------------------------------------------

    ███████╗████████╗ █████╗  ██████╗██╗  ██╗███████╗██████╗     ██╗   ██╗      ██████╗  █████╗ ██████╗
    ██╔════╝╚══██╔══╝██╔══██╗██╔════╝██║ ██╔╝██╔════╝██╔══██╗    ██║   ██║      ██╔══██╗██╔══██╗██╔══██╗
    ███████╗   ██║   ███████║██║     █████╔╝ █████╗  ██║  ██║    ██║   ██║█████╗██████╔╝███████║██████╔╝
    ╚════██║   ██║   ██╔══██║██║     ██╔═██╗ ██╔══╝  ██║  ██║    ╚██╗ ██╔╝╚════╝██╔══██╗██╔══██║██╔══██╗
    ███████║   ██║   ██║  ██║╚██████╗██║  ██╗███████╗██████╔╝     ╚████╔╝       ██████╔╝██║  ██║██║  ██║
    ╚══════╝   ╚═╝   ╚═╝  ╚═╝ ╚═════╝╚═╝  ╚═╝╚══════╝╚═════╝       ╚═══╝        ╚═════╝ ╚═╝  ╚═╝╚═╝  ╚═╝

    STACKED V-BAR + ตารางข้อมูล: ระดับบริการ × สคร.
    - ชื่อ สคร. มาจาก \App\Models\HealthRegion
    - ข้อความระดับมาจาก config('tmc.level_text')
    - คำนวณทั้งหมดใน Blade
    -------------------------------------------------------------------------------------------------------------------------------- --}}

        @php
            // 1) ดึงชื่อ สคร. จาก DB (เลือก short_title ถ้ามี, รองลงมา title)
            $REGION_NAMES = \App\Models\HealthRegion::query()
                ->orderBy('id')
                ->get(['id', 'title', 'short_title'])
                ->mapWithKeys(function ($r) {
                    $name = $r->short_title ?: $r->title ?: 'สคร.' . $r->id;
                    return [(int) $r->id => $name];
                });

            // 2) config ระดับ (ข้อความจาก tmc.php)
            // รวม default เผื่อ config ขาด key
            $LEVEL_TEXTS = array_merge(
                [
                    'basic' => 'ระดับพื้นฐาน',
                    'medium' => 'ระดับกลาง',
                    'advanced' => 'ระดับสูง',
                    'unassessed' => 'ยังไม่ได้ประเมิน',
                ],
                (array) config('tmc.level_text', []),
            );

            // 2.1 สีของกราฟ (HEX) ดึงจาก config('tmc.level_colors')
            // ใน config ปัจจุบันเรามี:
            // 'level_colors' => [
            //     'basic'      => '#FF4560', // ชมพู
            //     'medium'     => '#FEB019', // ส้ม
            //     'advanced'   => '#00E396', // เขียว
            //     'unassessed' => '#A8A8A8', // เทา
            // ],
            $LEVEL_COLORS_CONF = (array) config('tmc.level_colors', []);

            // สร้าง map สีพร้อม fallback กรณี config ไม่มี key ใด key หนึ่ง
            $LEVEL_HEX = [
                'basic' => $LEVEL_COLORS_CONF['basic'] ?? '#FF4560', // ชมพู
                'medium' => $LEVEL_COLORS_CONF['medium'] ?? '#FEB019', // ส้ม
                'advanced' => $LEVEL_COLORS_CONF['advanced'] ?? '#00E396', // เขียว
                'unassessed' => $LEVEL_COLORS_CONF['unassessed'] ?? '#A8A8A8', // เทา
            ];

            // 3) map ระดับที่อ่านได้จากข้อมูล + ลำดับเลือก "ระดับสูงสุด" ต่อหน่วย
            $mapLevel = function ($v) {
                $s = strtolower((string) $v);
                return match ($s) {
                    'พื้นฐาน', 'ระดับพื้นฐาน', 'basic' => 'basic',
                    'กลาง', 'ระดับกลาง', 'medium' => 'medium',
                    'สูง', 'ระดับสูง', 'advanced' => 'advanced',
                    default => null,
                };
            };

            // เลือกระดับสูงสุด (priority) ของแต่ละหน่วย: ถ้ามี advanced ใช้ advanced ก่อน medium ก่อน basic
            $prefer = ['advanced', 'medium', 'basic'];

            // ลำดับ series ตอน plot (ล่าง → บน ของ stacked bar หรือซ้าย→ขวาตาม series legend)
            $order = ['unassessed', 'basic', 'medium', 'advanced'];

            // 4) รวมจำนวนหน่วย ต่อ สคร. × ระดับ
            $byRegion = [];
            foreach ($serviceUnits as $su) {
                $rid = (int) data_get($su, 'province.health_region_id', 0);
                if ($rid === 0) {
                    continue;
                }

                if (!isset($byRegion[$rid])) {
                    $byRegion[$rid] = [
                        'region_id' => $rid,
                        'region_name' => $REGION_NAMES->get($rid, "สคร.$rid"),
                        'basic' => 0,
                        'medium' => 0,
                        'advanced' => 0,
                        'unassessed' => 0,
                    ];
                }

                // ดึงระดับที่ approved ของหน่วยนี้
                $approvedLevels = collect(data_get($su, 'assessmentLevels', []))->map(fn($a) => $mapLevel(data_get($a, 'level')))->filter()->unique()->values();

                // ถ้าไม่มีระดับที่อนุมัติเลย → unassessed
                $picked = 'unassessed';
                foreach ($prefer as $k) {
                    if ($approvedLevels->contains($k)) {
                        $picked = $k;
                        break;
                    }
                }

                $byRegion[$rid][$picked] = (int) $byRegion[$rid][$picked] + 1;
            }

            // 5) ให้ครบทุก สคร. แม้ไม่มีหน่วย (เติม 0)
            foreach ($REGION_NAMES as $rid => $rname) {
                if (!isset($byRegion[$rid])) {
                    $byRegion[$rid] = [
                        'region_id' => (int) $rid,
                        'region_name' => $rname,
                        'basic' => 0,
                        'medium' => 0,
                        'advanced' => 0,
                        'unassessed' => 0,
                    ];
                }
            }

            // 6) เตรียมข้อมูลสำหรับกราฟ
            $rows = collect($byRegion)
                ->values()
                ->map(function ($r) use ($order) {
                    $sum = 0;
                    foreach ($order as $lv) {
                        $sum += (int) ($r[$lv] ?? 0);
                    }
                    $r['_total'] = $sum;
                    return $r;
                })
                // สามารถเปลี่ยนลำดับแกน Y ได้ เช่นเรียงตามจำนวนรวมมาก→น้อย:
                // ->sortByDesc('_total')
                ->sortBy('region_id')
                ->values();

            // หมวดแกน X/Y (ชื่อ สคร.)
            $cats = $rows->pluck('region_name')->values();

            // series ของกราฟ (อันนี้แหละที่จะถูกโยนเข้า ApexCharts)
            // ใช้ชื่อจาก config('tmc.level_text') และสีจาก config('tmc.level_colors')
            $series = collect($order)
                ->map(function ($lv) use ($rows, $LEVEL_TEXTS, $LEVEL_HEX) {
                    return [
                        'name' => $LEVEL_TEXTS[$lv] ?? ucfirst($lv),
                        'data' => $rows->map(fn($r) => (int) ($r[$lv] ?? 0))->values(),
                        'color' => $LEVEL_HEX[$lv] ?? '#999999',
                    ];
                })
                ->values();

            $grandTotal = (int) $rows->sum('_total');
        @endphp


        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0"><i class="ti ti-chart-bar"></i> ระดับของหน่วยบริการตาม สคร.</h6>
                <span class="badge bg-light text-dark">รวม {{ number_format($grandTotal) }} หน่วย</span>
            </div>
            <div class="card-body">
                <div id="chart-level-by-dco"></div>

                {{-- =========================
            ตารางสรุประดับบริการ × สคร.
            (ใช้ตัวแปร $rows, $order, $LEVEL_TEXTS ที่คำนวณไว้ด้านบน)
            ========================= --}}
                @php
                    // รวมคอลัมน์ (sum ตามระดับ)
                    $colTotals = [];
                    foreach ($order as $lv) {
                        $colTotals[$lv] = (int) collect($rows)->sum($lv);
                    }
                    $grandTotal = (int) collect($rows)->sum('_total');
                @endphp

                <div class="mt-3 table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:18%">สคร.</th>
                                @foreach ($order as $lv)
                                    <th class="text-end">{{ $LEVEL_TEXTS[$lv] ?? ucfirst($lv) }}</th>
                                @endforeach
                                <th class="text-end" style="width:10%">รวม</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $r)
                                @php $rowTotal = (int) ($r['_total'] ?? 0); @endphp
                                <tr>
                                    <td>{{ $r['region_name'] }}</td>
                                    @foreach ($order as $lv)
                                        <td class="text-end">{{ number_format((int) ($r[$lv] ?? 0)) }}</td>
                                    @endforeach
                                    <td class="text-end fw-semibold">{{ number_format($rowTotal) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($order) + 2 }}" class="text-center text-muted">ไม่มีข้อมูล</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <th>รวมทั้งหมด</th>
                                @foreach ($order as $lv)
                                    <th class="text-end">{{ number_format($colTotals[$lv] ?? 0) }}</th>
                                @endforeach
                                <th class="text-end">{{ number_format($grandTotal) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            </div>
        </div>

        @push('scripts')
            <script>
                (function() {
                    const cats = @json($cats);
                    const series = @json($series);

                    const el = document.querySelector('#chart-level-by-dco');
                    if (!el) return;

                    // ตั้งชื่อไฟล์ export
                    const now = new Date();
                    const y = String(now.getFullYear());
                    const m = String(now.getMonth() + 1).padStart(2, '0');
                    const d = String(now.getDate()).padStart(2, '0');
                    const baseName = `level-by-dco_${y}${m}${d}`;

                    new ApexCharts(el, {
                        chart: {
                            type: 'bar',
                            height: 420,
                            stacked: true,
                            toolbar: {
                                show: true,
                                tools: {
                                    download: true, // ✅ แสดงปุ่มเมนูดาวน์โหลด (SVG/PNG/CSV)
                                    selection: false,
                                    zoom: false,
                                    zoomin: false,
                                    zoomout: false,
                                    pan: false,
                                    reset: false
                                },
                                export: {
                                    csv: {
                                        filename: baseName,
                                        headerCategory: 'สคร.' // หัวคอลัมน์ของแกน X
                                    },
                                    svg: {
                                        filename: baseName
                                    },
                                    png: {
                                        filename: baseName
                                    }
                                }
                            },
                            animations: {
                                enabled: true
                            }
                        },
                        plotOptions: {
                            bar: {
                                horizontal: false,
                                borderRadius: 4,
                                columnWidth: '55%'
                            }
                        },
                        series: series,
                        xaxis: {
                            categories: cats,
                            labels: {
                                rotate: -10,
                                trim: true
                            }
                        },
                        yaxis: {
                            labels: {
                                formatter: v => new Intl.NumberFormat().format(v)
                            },
                            title: {
                                text: 'จำนวนหน่วยบริการ'
                            }
                        },
                        dataLabels: {
                            enabled: false
                        },
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            shared: true,
                            intersect: false,
                            y: {
                                formatter: v => new Intl.NumberFormat().format(v)
                            }
                        },
                        grid: {
                            strokeDashArray: 4
                        },
                        states: {
                            hover: {
                                filter: {
                                    type: 'none'
                                }
                            },
                            active: {
                                filter: {
                                    type: 'none'
                                }
                            }
                        },
                        noData: {
                            text: 'ไม่มีข้อมูล'
                        }
                    }).render();
                })();
            </script>
        @endpush


    @endif





    {{-- --------------------------------------------------------------------------------------------------------------------------------

    ███████╗████████╗ █████╗  ██████╗██╗  ██╗███████╗██████╗     ██╗   ██╗      ██████╗  █████╗ ██████╗
    ██╔════╝╚══██╔══╝██╔══██╗██╔════╝██║ ██╔╝██╔════╝██╔══██╗    ██║   ██║      ██╔══██╗██╔══██╗██╔══██╗
    ███████╗   ██║   ███████║██║     █████╔╝ █████╗  ██║  ██║    ██║   ██║█████╗██████╔╝███████║██████╔╝
    ╚════██║   ██║   ██╔══██║██║     ██╔═██╗ ██╔══╝  ██║  ██║    ╚██╗ ██╔╝╚════╝██╔══██╗██╔══██║██╔══██╗
    ███████║   ██║   ██║  ██║╚██████╗██║  ██╗███████╗██████╔╝     ╚████╔╝       ██████╔╝██║  ██║██║  ██║
    ╚══════╝   ╚═╝   ╚═╝  ╚═╝ ╚═════╝╚═╝  ╚═╝╚══════╝╚═════╝       ╚═══╝        ╚═════╝ ╚═╝  ╚═╝╚═╝  ╚═╝

    STACKED H-BAR: ระดับบริการ × จังหวัด
    - รายชื่อจังหวัดจาก \App\Models\Province
    - ข้อความระดับจาก config('tmc.level_text')
    - ทำทั้งหมดใน Blade
    -------------------------------------------------------------------------------------------------------------------------------- --}}

    @php
        // 1) รายชื่อจังหวัด (ถ้ามี $filterRegion ใช้กรองให้เหลือเฉพาะจังหวัดใน สคร. นั้น)
        $PROVINCE_NAMES = \App\Models\Province::query()
            ->when(isset($filterRegion) && $filterRegion, fn($q) => $q->where('health_region_id', (int) $filterRegion))
            ->orderBy('code')
            ->get(['code', 'title'])
            ->mapWithKeys(fn($p) => [(int) $p->code => $p->title]);

        // 2) ข้อความระดับจาก config (เติม default กัน key หลุด)
        $LEVEL_TEXTS = array_merge(
            [
                'basic' => 'ระดับพื้นฐาน',
                'medium' => 'ระดับกลาง',
                'advanced' => 'ระดับสูง',
                'unassessed' => 'ยังไม่ได้ประเมิน',
            ],
            (array) config('tmc.level_text', []),
        );

        // 2.1 สีระดับจาก config('tmc.level_colors')
        // config('tmc.level_colors') ควรหน้าตาประมาณนี้:
        // [
        //   'basic'      => '#FF4560', // ชมพู
        //   'medium'     => '#FEB019', // ส้ม
        //   'advanced'   => '#00E396', // เขียว
        //   'unassessed' => '#A8A8A8', // เทา
        // ]
        $LEVEL_COLORS_CONF = (array) config('tmc.level_colors', []);

        // map สีพร้อม fallback
        $LEVEL_HEX = [
            'basic' => $LEVEL_COLORS_CONF['basic'] ?? '#FF4560',
            'medium' => $LEVEL_COLORS_CONF['medium'] ?? '#FEB019',
            'advanced' => $LEVEL_COLORS_CONF['advanced'] ?? '#00E396',
            'unassessed' => $LEVEL_COLORS_CONF['unassessed'] ?? '#A8A8A8',
        ];

        // 3) map ระดับ + ลำดับเลือกระดับสูงสุดของหน่วยบริการ
        $mapLevel = function ($v) {
            return match (strtolower((string) $v)) {
                'พื้นฐาน', 'ระดับพื้นฐาน', 'basic' => 'basic',
                'กลาง', 'ระดับกลาง', 'medium' => 'medium',
                'สูง', 'ระดับสูง', 'advanced' => 'advanced',
                default => null,
            };
        };

        // ถ้ามีหลายระดับในหน่วยเดียว ให้เลือก "ดีที่สุด" ตามลำดับนี้
        $prefer = ['advanced', 'medium', 'basic'];

        // ลำดับการซ้อนของ series (ล่าง → บน / ซ้าย → ขวา ใน stack)
        // โดยให้ 'unassessed' (ยังไม่ได้ประเมิน) อยู่ล่างสุด
        $order = ['unassessed', 'basic', 'medium', 'advanced'];

        // 4) รวมจำนวนหน่วย ต่อจังหวัด × ระดับ
        $byProvince = [];
        foreach ($serviceUnits as $su) {
            $pcode = (int) data_get($su, 'org_province_code', 0);

            // ถ้ามีการกรอง region แล้ว จังหวัดนี้ไม่ได้อยู่ใน PROVINCE_NAMES ก็ข้าม
            if ($pcode === 0 || !$PROVINCE_NAMES->has($pcode)) {
                continue;
            }

            if (!isset($byProvince[$pcode])) {
                $byProvince[$pcode] = [
                    'province_code' => $pcode,
                    'province_name' => $PROVINCE_NAMES->get($pcode),
                    'basic' => 0,
                    'medium' => 0,
                    'advanced' => 0,
                    'unassessed' => 0,
                ];
            }

            // ระดับที่ได้รับการอนุมัติ (assessmentLevels ควรโหลดมาก่อนแล้ว)
            $approvedLevels = collect(data_get($su, 'assessmentLevels', []))->map(fn($a) => $mapLevel(data_get($a, 'level')))->filter()->unique()->values();

            // ถ้าไม่มีระดับที่อนุมัติเลย ให้ถือเป็น 'unassessed'
            $picked = 'unassessed';
            foreach ($prefer as $k) {
                if ($approvedLevels->contains($k)) {
                    $picked = $k;
                    break;
                }
            }

            $byProvince[$pcode][$picked] = (int) $byProvince[$pcode][$picked] + 1;
        }

        // 5) เติมจังหวัดที่ไม่มีหน่วย (set เป็น 0) เพื่อให้กราฟครบจังหวัดตาม $PROVINCE_NAMES
        foreach ($PROVINCE_NAMES as $code => $title) {
            if (!isset($byProvince[$code])) {
                $byProvince[$code] = [
                    'province_code' => (int) $code,
                    'province_name' => $title,
                    'basic' => 0,
                    'medium' => 0,
                    'advanced' => 0,
                    'unassessed' => 0,
                ];
            }
        }

        // 6) เตรียมข้อมูลสำหรับกราฟ/ตาราง
        $rows = collect($byProvince)
            ->values()
            ->map(function ($r) use ($order) {
                $sum = 0;
                foreach ($order as $lv) {
                    $sum += (int) ($r[$lv] ?? 0);
                }
                $r['_total'] = $sum;
                return $r;
            })
            // ถ้าต้องการเรียงจังหวัดในกราฟเป็นจังหวัดที่มีจำนวนเยอะสุดก่อน:
            // ->sortByDesc('_total')
            ->sortBy('province_code')
            ->values();

        // ชื่อจังหวัด (categories แกน)
        $cats = $rows->pluck('province_name')->values();

        // series สำหรับ ApexCharts
        // ใช้ชื่อจาก config('tmc.level_text') และ สีจาก config('tmc.level_colors')
        $series = collect($order)
            ->map(function ($lv) use ($rows, $LEVEL_TEXTS, $LEVEL_HEX) {
                return [
                    'name' => $LEVEL_TEXTS[$lv] ?? ucfirst($lv),
                    'data' => $rows->map(fn($r) => (int) ($r[$lv] ?? 0))->values(),
                    'color' => $LEVEL_HEX[$lv] ?? '#999999',
                ];
            })
            ->values();

        $grandTotal = (int) $rows->sum('_total');
    @endphp


    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h6 class="mb-0"><i class="ti ti-chart-bar"></i> ระดับของหน่วยบริการตาม “จังหวัด”</h6>
            <span class="badge bg-light text-dark">รวม {{ number_format($grandTotal) }} หน่วย</span>
        </div>
        <div class="card-body">
            <div id="chart-province-level"></div>

            {{-- ===== ตารางสรุป: จังหวัด × ระดับ ===== --}}
            @php
                $colTotals = [];
                foreach ($order as $lv) {
                    $colTotals[$lv] = (int) collect($rows)->sum($lv);
                }
                $grandTotal = (int) collect($rows)->sum('_total');
            @endphp
            <div class="mt-3 table-responsive">
                <table class="table table-sm table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:160px">จังหวัด</th>
                            @foreach ($order as $lv)
                                <th class="text-end">{{ $LEVEL_TEXTS[$lv] ?? ucfirst($lv) }}</th>
                            @endforeach
                            <th class="text-end" style="width:10%">รวม</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            @php $rowTotal = (int) ($r['_total'] ?? 0); @endphp
                            <tr>
                                <td>{{ $r['province_name'] }}</td>
                                @foreach ($order as $lv)
                                    <td class="text-end">{{ number_format((int) ($r[$lv] ?? 0)) }}</td>
                                @endforeach
                                <td class="text-end fw-semibold">{{ number_format($rowTotal) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($order) + 2 }}" class="text-center text-muted">ไม่มีข้อมูล</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th>รวมทั้งหมด</th>
                            @foreach ($order as $lv)
                                <th class="text-end">{{ number_format($colTotals[$lv] ?? 0) }}</th>
                            @endforeach
                            <th class="text-end">{{ number_format($grandTotal) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                // ===== ข้อมูลจาก PHP =====
                // $cats: รายชื่อจังหวัดตามลำดับแกน
                // $series: [{ name: 'ยังไม่ได้ประเมิน', data: [...], color: '#A8A8A8' }, ...]
                const categories = @json($cats);
                const stackedSeries = @json($series);

                const el = document.querySelector('#chart-province-level');
                if (!el) return;

                // ตั้งชื่อไฟล์ export
                const now = new Date();
                const y = String(now.getFullYear());
                const m = String(now.getMonth() + 1).padStart(2, '0');
                const d = String(now.getDate()).padStart(2, '0');
                const baseName = `province-level_${y}${m}${d}`;

                const options = {
                    chart: {
                        type: 'bar',
                        height: 420,
                        stacked: true, // ✅ stack ตามระดับ
                        toolbar: {
                            show: true,
                            tools: {
                                download: true,
                                selection: false,
                                zoom: false,
                                zoomin: false,
                                zoomout: false,
                                pan: false,
                                reset: false
                            },
                            export: {
                                csv: {
                                    filename: baseName,
                                    headerCategory: 'จังหวัด',
                                    headerValue: 'จำนวนหน่วยบริการ'
                                },
                                svg: {
                                    filename: baseName
                                },
                                png: {
                                    filename: baseName
                                }
                            }
                        },
                        animations: {
                            enabled: true
                        },
                        // ไม่ต้องการให้คลิกแล้ว freeze segment
                        events: {
                            dataPointSelection: function(event) {
                                event.stopPropagation();
                                return false;
                            }
                        }
                    },
                    series: stackedSeries.map(s => ({
                        name: s.name,
                        data: s.data,
                        color: s.color, // ✅ ใช้สีจาก config('tmc.level_colors') ที่เราดึงมาใน PHP แล้ว
                    })),
                    xaxis: {
                        categories: categories,
                        labels: {
                            rotate: -45, // ✅ เอียง 45 องศา
                            rotateAlways: true, // ✅ บังคับให้เอียงเสมอ
                            hideOverlappingLabels: false, // ✅ แสดงครบทุกจังหวัด
                            trim: false,
                            style: {
                                fontSize: '12px'
                            }
                        },
                        title: {
                            text: 'จังหวัด',
                            style: {
                                fontSize: '13px',
                                fontWeight: 600,
                                color: '#333'
                            }
                        }
                    },
                    yaxis: {
                        title: {
                            text: 'จำนวนหน่วยบริการ',
                            style: {
                                fontSize: '13px',
                                fontWeight: 600,
                                color: '#333'
                            }
                        },
                        labels: {
                            formatter: (val) => new Intl.NumberFormat().format(val),
                        }
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'left',
                        markers: {
                            radius: 4
                        },
                        labels: {
                            colors: '#333',
                            useSeriesColors: false
                        }
                    },

                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '60%',
                            borderRadius: 4,
                        }
                    },

                    dataLabels: {
                        enabled: false // stacked เยอะๆ เปิดแล้วจะรก
                    },

                    tooltip: {
                        shared: true,
                        intersect: false,
                        y: {
                            formatter: (val) => new Intl.NumberFormat().format(val)
                        }
                    },

                    grid: {
                        strokeDashArray: 4
                    },
                    states: {
                        hover: {
                            filter: {
                                type: 'none'
                            }
                        },
                        active: {
                            filter: {
                                type: 'none'
                            }
                        }
                    },
                };

                const chart = new ApexCharts(el, options);
                chart.render();
            })();
        </script>
    @endpush


@endsection
