{{-- resources/views/backend/dashboard/index.blade.php --}}
@extends('layouts.main')

@section('title', 'แดชบอร์ดหน่วยบริการสุขภาพผู้เดินทาง')
@section('breadcrumb-item', 'Backend')
@section('breadcrumb-item-active', 'แดชบอร์ด')

@section('content')
    @include('backend.dashboard._filter')

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

        $levelBg = config('assessment.level_badge_class');
        $levelText = config('assessment.level_badge_text_color');
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
            </a>
        </div>

        {{-- การ์ดกลาง --}}
        <div class="col-lg-3 col-md-6">
            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#modal-medium">
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
                                    <h4 class="mb-0">{{ number_format($summary['medium']) }}</h4>
                                    <span class="fw-medium text-{{ $levelText['medium'] }}">{{ $pct($summary['medium']) }}</span>
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
            </a>
        </div>

        {{-- การ์ดยังไม่ได้ประเมิน --}}
        <div class="col-lg-3 col-md-6">
            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#modal-unassessed">
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
            <div><i class="ph-duotone ph-map-pin"></i> <span class="ms-1">แผนที่หน่วยบริการสุขภาพผู้เดินทาง</span></div>
        </div>
        <div class="card-body p-0">
            <div id="tmc-map" style="height:560px;"></div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin>
        <link rel="stylesheet" href="https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/leaflet.fullscreen.css">

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
        </style>
    @endpush


    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin></script>
        <script src="https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/Leaflet.fullscreen.min.js"></script>

        {{-- ----------------------------------------------------------------------------------------------------------------------------
          MAP: SCRIPTS (สร้างแผนที่, หมุด, ป้าย, popup, แผงรายชื่อ, การเลื่อนกล้อง)
        ---------------------------------------------------------------------------------------------------------------------------- --}}
        <script>
            (function() {
                const facilities = @json($facilities);
                const LEVEL_COLORS = {
                    basic: '#ef83c2',
                    medium: '#f0c419',
                    advanced: '#0dcc93',
                    unassessed: '#9aa0a6'
                };

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
                    const c = LEVEL_COLORS[f.levelKey] || '#9aa0a6';
                    const key = String(f.id ?? `${f.lat},${f.lon}`);
                    const mk = L.marker([f.lat, f.lon], {
                        icon: coloredPin(c)
                    }).addTo(map);
                    mk._tmcKey = key;

                    const contactLine = [f.phone, f.email].filter(Boolean).join(' / ');
                    const isBkk = String(f.provinceCode) === '10';
                    const districtLabel = isBkk ? 'เขต' : 'อำเภอ';
                    const subdistrictLabel = isBkk ? 'แขวง' : 'ตำบล';

                    // ✅ แสดง “การให้บริการของหน่วยงาน” เฉพาะหน่วยที่ approved และมีรายการ services
                    const svcHtml = (f.approved && Array.isArray(f.services) && f.services.length) ?
                        `<div class="mt-2">
                               <div class="fw-semibold mb-1">บริการ</div>
                               <ul class="mb-2 ps-3">
                                   ${f.services.map(s => `<li class="small">${esc(s)}</li>`).join('')}
                               </ul>
                           </div>` :
                        '';

                    mk.bindPopup(`
                        <div class="tmc-pop" style="min-width:260px;">
                            <h6 class="mb-2 text-primary">${esc(f.name)}</h6>
                            ${f.address ? `<p class="mb-1"><span class="text-muted">ที่อยู่:</span> ${esc(f.address)}</p>` : ''}
                            <p class="mb-2">
                              <span class="text-muted">จังหวัด:</span> ${esc(f.province || '-')}
                              <span class="ms-2 text-muted">${districtLabel}:</span> ${esc(f.district || '-')}
                              <span class="ms-2 text-muted">${subdistrictLabel}:</span> ${esc(f.subdistrict || '-')}
                            </p>
                            ${svcHtml}
                            ${contactLine ? `<p class="text-muted small mt-2 mb-2"><strong>ติดต่อ:</strong> ${esc(contactLine)}</p>` : ''}
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
                        panToOffset(mk.getLatLng(), L.point(-100, 200)); // ขยับลง 200px
                    });
                    markers.push(mk);
                    markerById.set(key, mk);

                    // label ไม่มี badge ระดับ
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

                    const latlng = mk.getLatLng();
                    const cur = map.getCenter();
                    const distM = map.distance(cur, latlng);

                    let z;
                    if (distM > 600000) z = 8;
                    else if (distM > 200000) z = 10;
                    else if (distM > 50000) z = 12;
                    else z = Math.max(map.getZoom(), 14);

                    const needZoom = Math.abs(map.getZoom() - z) > 0.6;
                    const done = () => mk.openPopup();

                    // ✅ คำนวณตำแหน่ง offset ล่วงหน้า (ให้หมุดอยู่ต่ำกว่ากึ่งกลาง)
                    const offsetPx = L.point(-100, 200);
                    const target = (() => {
                        const zoom = z;
                        const pt = map.project(latlng, zoom).subtract(offsetPx);
                        return map.unproject(pt, zoom);
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
                    // offsetPx: L.point(x, y). y ติดลบ = ขยับหมุดลง
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
                    wrap.innerHTML = `<div class="hdr"><span>หน่วยบริการทั้งหมด</span><button class="toggle" type="button" title="ย่อ/ขยาย">▣</button></div>
                                      <div class="srch"><input type="search" placeholder="ค้นหาชื่อ/จังหวัด"></div>
                                      <div class="body"></div>`;
                    L.DomEvent.disableClickPropagation(wrap);
                    L.DomEvent.disableScrollPropagation(wrap);

                    const body = wrap.querySelector('.body'),
                        input = wrap.querySelector('input[type="search"]'),
                        toggleBtn = wrap.querySelector('.toggle');

                    // กันโฟกัสหลุด / ป้องกัน Leaflet แย่งโฟกัสและคีย์บอร์ด
                    L.DomEvent.disableClickPropagation(input);
                    L.DomEvent.disableScrollPropagation(input);
                    ['keydown', 'keypress', 'keyup', 'input', 'click', 'mousedown', 'dblclick', 'touchstart', 'pointerdown', 'wheel', 'contextmenu']
                    .forEach(evt => L.DomEvent.on(input, evt, e => e.stopPropagation()));
                    input.addEventListener('focus', () => map.keyboard && map.keyboard.disable());
                    input.addEventListener('blur', () => map.keyboard && map.keyboard.enable());
                    input.setAttribute('autocomplete', 'off');
                    input.setAttribute('spellcheck', 'false');
                    input.setAttribute('autocapitalize', 'off');

                    const rowsData = facilities
                        .filter(f => f.lat != null && f.lon != null)
                        .map(f => {
                            const c = LEVEL_COLORS[f.levelKey] || '#9aa0a6';
                            const key = String(f.id ?? `${f.lat},${f.lon}`);
                            const lvlText = f.levelKey === 'unassessed' ? 'ยังไม่ได้ประเมิน' : `ระดับ${String(f.levelText||'-')}`;
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
                        el.innerHTML = `<span class="dot" style="background:${row.color}"></span>
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
                            rowsData.filter(r => (r.name + ' ' + r.province + ' ' + r.levelText).toLowerCase().includes(s)) :
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
                        if (scrollIntoView) activeEl.scrollIntoView({
                            block: 'nearest',
                            inline: 'nearest',
                            behavior: 'smooth'
                        });
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
                        iconUrl: 'data:image/svg+xml;utf8,' + encodeURIComponent(
                            `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="38" viewBox="0 0 28 38" fill="none">
                               <path d="M14 1C9 1 4 5.6 4 11.6 4 21.8 14 37 14 37s10-15.2 10-25.4C24 5.6 19 1 14 1z" fill="${c}"/>
                               <circle cx="14" cy="11.6" r="3.6" fill="#fff"/>
                             </svg>`),
                        iconSize: [28, 38],
                        iconAnchor: [14, 37],
                        popupAnchor: [0, -38]
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
            })();
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
                <span class="position-absolute top-0 bottom-0 start-0 bg-{{ $levelBg['basic'] }}" style="width:6px;border-top-left-radius:.5rem;border-bottom-left-radius:.5rem;"></span>

                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <span class="text-{{ $levelText['basic'] }}"><i class="ph-duotone ph-chart-bar"></i> GAP ระดับพื้นฐาน</span>
                    <span class="badge bg-{{ $levelBg['basic'] }} text-{{ $levelText['basic'] }}">
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
                <span class="position-absolute top-0 bottom-0 start-0 bg-{{ $levelBg['medium'] }}" style="width:6px;border-top-left-radius:.5rem;border-bottom-left-radius:.5rem;"></span>

                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <span class="text-{{ $levelText['medium'] }}"><i class="ph-duotone ph-chart-bar"></i> GAP ระดับกลาง</span>
                    <span class="badge bg-{{ $levelBg['medium'] }} text-{{ $levelText['medium'] }}">
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
                <span class="position-absolute top-0 bottom-0 start-0 bg-{{ $levelBg['advanced'] }}" style="width:6px;border-top-left-radius:.5rem;border-bottom-left-radius:.5rem;"></span>

                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <span class="text-{{ $levelText['advanced'] }}"><i class="ph-duotone ph-chart-bar"></i> GAP ระดับสูง</span>
                    <span class="badge bg-{{ $levelBg['advanced'] }} text-{{ $levelText['advanced'] }}">
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

        // ลำดับ/ป้าย/สี ของสถานะ (ใช้ร่วมกันทั้ง Chart และ Card)
        $displayOrder = ['pending', 'reviewing', 'returned', 'approved', 'rejected', 'no_form'];

        $labels = [
            'pending' => 'รอดำเนินการ',
            'reviewing' => 'อยู่ระหว่างการพิจารณา',
            'returned' => 'ส่งกลับแก้ไข',
            'approved' => 'อนุมัติ',
            'rejected' => 'ไม่อนุมัติ',
            'no_form' => 'ยังไม่ได้ทำแบบประเมิน',
        ];

        // สีสำหรับการ์ด (Light Able utility classes)
        $bgMap = [
            'pending' => 'gray-100',
            'reviewing' => 'blue-100',
            'returned' => 'yellow-100',
            'approved' => 'green-100',
            'rejected' => 'red-100',
            'no_form' => 'indigo-100',
        ];
        $fgMap = [
            'pending' => 'gray-900',
            'reviewing' => 'blue-900',
            'returned' => 'yellow-900',
            'approved' => 'green-900',
            'rejected' => 'red-900',
            'no_form' => 'indigo-900',
        ];

        // สีสำหรับกราฟ (HEX)
        $chartColors = [
            'pending' => '#9aa0a6', // gray-500
            'reviewing' => '#60a5fa', // blue-400
            'returned' => '#fbbf24', // amber-400
            'approved' => '#34d399', // emerald-400
            'rejected' => '#f87171', // red-400
            'no_form' => '#a78bfa', // indigo-400
        ];

        // สถานะที่ถูกต้องตามฐานข้อมูล
        $dbStatuses = ['pending', 'reviewing', 'returned', 'approved', 'rejected'];

        // หาเรคคอร์ดล่าสุดของแต่ละหน่วย ในปี/รอบที่เลือก
        $unitIds = $serviceUnits->pluck('id')->all();
        $latestAll = collect();
        if (!empty($unitIds)) {
            $latestAll = AssessmentServiceUnitLevel::select('id', 'service_unit_id', 'assess_year', 'assess_round', 'approval_status')->whereIn('service_unit_id', $unitIds)->where('assess_year', $filterYear)->where('assess_round', $filterRound)->whereNull('deleted_at')->orderByDesc('id')->get()->groupBy('service_unit_id')->map->first();
        }

        // สร้าง buckets ของสถานะ
        $status = [];
        foreach ($displayOrder as $key) {
            $status[$key] = ['count' => 0, 'units' => collect()];
        }

        foreach ($serviceUnits as $su) {
            $record = $latestAll->get($su->id);

            if (!$record) {
                $bucket = 'no_form';
            } else {
                $st = $record->approval_status;
                if ($st === 'pending' || $st === null || $st === '') {
                    $bucket = 'pending'; // รวมค่าว่างเข้าที่ pending
                } elseif (in_array($st, $dbStatuses, true)) {
                    $bucket = $st;
                } else {
                    continue; // ข้ามสถานะอื่นที่ไม่รองรับ
                }
            }

            $status[$bucket]['count']++;
            $status[$bucket]['units']->push(
                (object) [
                    'org_name' => $su->org_name ?? ($su->name ?? '—'),
                ],
            );
        }

        // เรียงชื่อหน่วยในแต่ละสถานะ
        foreach ($status as $k => $it) {
            $status[$k]['units'] = $it['units']->sortBy('org_name')->values();
        }

        // ----- เตรียมชุดข้อมูลสำหรับกราฟ -----
        $seriesCounts = collect($displayOrder)->map(fn($k) => (int) data_get($status, "$k.count", 0))->all();
        $seriesLabels = collect($displayOrder)->map(fn($k) => $labels[$k])->all();
        $seriesColors = collect($displayOrder)->map(fn($k) => $chartColors[$k])->all();
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
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script>
            (function() {
                const seriesCounts = @json($seriesCounts);
                const seriesLabels = @json($seriesLabels);
                const seriesColors = @json($seriesColors);

                // เรียงข้อมูลจากมากไปน้อย พร้อมคงสี
                const zipped = seriesLabels.map((label, i) => ({
                    label,
                    count: seriesCounts[i],
                    color: seriesColors[i]
                })).sort((a, b) => b.count - a.count);

                const el = document.querySelector("#chart-status-bar");
                if (!el) return;

                const options = {
                    chart: {
                        type: 'bar',
                        height: 420,
                        toolbar: {
                            show: false
                        },
                        animations: {
                            enabled: true
                        },
                        events: {
                            dataPointSelection: function(event, chartContext, config) {
                                // ❌ ไม่ให้คลิกทำอะไรเลย
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
                        title: { // ✅ เพิ่มคำอธิบายแนวนอน
                            text: 'จำนวนหน่วยบริการ',
                            style: {
                                fontSize: '14px',
                                fontWeight: 600,
                                color: '#333'
                            }
                        }
                    },
                    colors: zipped.map(z => z.color),
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            barHeight: '60%',
                            distributed: true,
                            // ✅ ปิดการ hover และการคลิก
                            dataPointSelection: false
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: (val) => new Intl.NumberFormat().format(val),
                        style: {
                            fontSize: '12px'
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
    <div class="row g-3 mb-3">
        @foreach ($displayOrder as $key)
            @php
                $label = $labels[$key];
                $bg = $bgMap[$key];
                $fg = $fgMap[$key];
                $count = data_get($status, "$key.count", 0);
                $unitsInStatus = data_get($status, "$key.units", collect());
            @endphp

            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 position-relative">
                    {{-- แถบสีซ้าย (สไตล์เดียวกับ GAP) --}}
                    <span class="position-absolute top-0 bottom-0 start-0 bg-{{ $bg }}" style="width:6px;border-top-left-radius:.5rem;border-bottom-left-radius:.5rem;"></span>

                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                        <span class="fw-semibold text-{{ $fg }}">
                            <i class="ti ti-building-community me-1 text-{{ $fg }}"></i>{{ $label }}
                        </span>
                        <span class="badge bg-{{ $bg }} text-{{ $fg }}">{{ number_format($count) }}</span>
                    </div>

                    {{-- ใช้ scroll ภายในการ์ด + sticky header --}}
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

@endsection
