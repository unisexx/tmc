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

    @php
        // ===== MAP: หน่วยบริการทั่วประเทศ (Leaflet) =====

        // เลือกระดับที่สูงสุดจากรายการ approved ของแต่ละหน่วย
        $prefer = ['advanced', 'medium', 'basic'];
        $pickLevel = function ($unitId) use ($approvedByUnit, $prefer) {
            $lvCol = collect(data_get($approvedByUnit, "{$unitId}.levels", collect()));
            foreach ($prefer as $k) {
                if ($lvCol->contains($k)) {
                    return $k; // advanced > medium > basic
                }
            }
            return 'unassessed'; // ✅ ถ้าไม่มี approved level ใดเลย
        };

        // map ข้อความและสี
        $LEVEL_TEXTS = ['basic' => 'พื้นฐาน', 'medium' => 'กลาง', 'advanced' => 'สูง', 'unassessed' => 'ยังไม่ได้ประเมิน'];
        $LEVEL_COLORS = ['basic' => '#ef83c2', 'medium' => '#f0c419', 'advanced' => '#0dcc93', 'unassessed' => '#9aa0a6'];

        // เตรียมข้อมูลสำหรับ JS
        $facilities = $serviceUnits
            ->map(function ($su) use ($pickLevel, $LEVEL_TEXTS) {
                $key = $pickLevel($su->id);
                return [
                    'id' => (int) $su->id,
                    'name' => (string) $su->org_name,
                    'province' => (string) optional($su->province)->title,
                    'lat' => $su->org_lat !== null ? (float) $su->org_lat : null,
                    'lon' => $su->org_lng !== null ? (float) $su->org_lng : null,
                    'phone' => (string) $su->org_tel,
                    'address' => (string) $su->org_address,
                    'levelKey' => $key, // basic|medium|advanced|unassessed
                    'levelText' => $LEVEL_TEXTS[$key] ?? 'ยังไม่ได้ประเมิน',
                ];
            })
            ->values()
            ->all();
    @endphp

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div><i class="ph-duotone ph-map-pin"></i> <span class="ms-1">แผนที่หน่วยบริการสุขภาพผู้เดินทาง</span></div>
            <div class="small text-muted">สีหมุดและป้ายเหมือนหน้าบ้าน • หมุดเท่า = ยังไม่ได้ประเมิน</div>
        </div>
        <div class="card-body p-0">
            <div id="tmc-map" style="height:520px;"></div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin>
        <link rel="stylesheet" href="https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/leaflet.fullscreen.css">
        <style>
            #tmc-map {
                height: 560px
            }

            .flabel {
                --c: #0d6efd;
                position: relative;
                display: inline-flex;
                align-items: center;
                gap: .5rem;
                background: color-mix(in srgb, var(--c) 18%, white);
                border: 1px solid color-mix(in srgb, var(--c) 45%, transparent);
                color: #0b2e13;
                padding: .35rem .75rem;
                border-radius: 999px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, .12);
                white-space: nowrap;
                font-size: .9rem;
                line-height: 1
            }

            .flabel:before {
                content: "";
                position: absolute;
                left: -10px;
                top: 50%;
                transform: translateY(-50%);
                border-top: 7px solid transparent;
                border-bottom: 7px solid transparent;
                border-right: 10px solid color-mix(in srgb, var(--c) 45%, transparent)
            }

            .flabel .dot {
                width: .66rem;
                height: .66rem;
                border-radius: 4px;
                background: var(--c);
                box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .08)
            }

            .flabel .name {
                font-weight: 600
            }

            .flabel .level {
                font-weight: 600;
                font-size: .78rem;
                padding: .15rem .45rem;
                border-radius: .5rem;
                background: color-mix(in srgb, var(--c) 12%, transparent);
                border: 1px solid color-mix(in srgb, var(--c) 45%, transparent);
                color: #222
            }

            .facility-label {
                transition: opacity .2s ease
            }

            .opacity-0 {
                opacity: 0
            }

            .tmc-list {
                width: 320px;
                max-width: 70vw;
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, .12);
                overflow: hidden
            }

            .tmc-list .hdr {
                display: flex;
                align-items: center;
                gap: .5rem;
                padding: .6rem .75rem;
                border-bottom: 1px solid #e9ecef;
                font-weight: 600
            }

            .tmc-list .hdr .toggle {
                margin-left: auto;
                cursor: pointer;
                font-size: .95rem;
                border: 0;
                background: transparent
            }

            .tmc-list .srch {
                padding: .5rem .75rem;
                border-bottom: 1px solid #f1f3f5
            }

            .tmc-list input[type="search"] {
                width: 100%;
                padding: .45rem .6rem;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                font-size: .9rem
            }

            .tmc-list .body {
                max-height: 360px;
                overflow: auto
            }

            .tmc-item {
                display: flex;
                gap: .5rem;
                align-items: flex-start;
                padding: .55rem .75rem;
                border-bottom: 1px dashed #f1f3f5;
                cursor: pointer;
                border-left: 4px solid transparent
            }

            .tmc-item:last-child {
                border-bottom: 0
            }

            .tmc-item .dot {
                width: .66rem;
                height: .66rem;
                border-radius: 50%;
                margin-top: .25rem;
                flex: 0 0 auto
            }

            .tmc-item .tit {
                font-size: .92rem;
                font-weight: 600;
                line-height: 1.1
            }

            .tmc-item .sub {
                font-size: .78rem;
                color: #6c757d
            }

            .tmc-empty {
                padding: .75rem .75rem;
                color: #6c757d;
                font-size: .9rem
            }

            .tmc-item.active {
                background: #fffbea;
                box-shadow: inset 0 0 0 1px #ffe08a;
                border-left-color: var(--c)
            }

            .tmc-list.min .srch,
            .tmc-list.min .body {
                display: none
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin></script>
        <script src="https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/Leaflet.fullscreen.min.js"></script>
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
                    markerById = new Map();
                const elByKey = new Map();
                let activeKey = null,
                    activeEl = null;

                facilities.forEach(f => {
                    if (f.lat == null || f.lon == null) return;
                    const c = LEVEL_COLORS[f.levelKey] || '#9aa0a6';
                    const key = String(f.id ?? `${f.lat},${f.lon}`);
                    const lvlText = f.levelKey === 'unassessed' ? 'ยังไม่ได้ประเมิน' : `ระดับ${esc(f.levelText)}`;

                    const mk = L.marker([f.lat, f.lon], {
                        icon: coloredPin(c)
                    }).addTo(map);
                    mk._tmcKey = key;
                    mk.bindPopup(`
                    <div style="min-width:240px;">
                        <h6 class="mb-1 text-primary">${esc(f.name)}</h6>
                        <p class="text-muted small mb-1">${esc(f.address||'-')}${f.province?' • '+esc(f.province):''}</p>
                        <span class="badge" style="background:${c}20;border:1px solid ${c}50;color:#333;">${lvlText}</span>
                        ${f.phone?`<p class="text-muted small mt-2 mb-0"><strong>ติดต่อ:</strong> ${esc(f.phone)}</p>`:''}
                    </div>
                `, {
                        maxWidth: 320,
                        autoPan: true
                    });

                    mk.on('popupopen', () => {
                        setActiveByKey(key, true);
                        ensureVisibleWithPanel(mk);
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
                                <span class="level">${lvlText}</span>
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

                // ===== ซูม–แพนช้า และหลบแผงลิสต์ =====
                function flyPanZoom(mk) {
                    if (!mk) return;
                    const target = mk.getLatLng(),
                        cur = map.getCenter();
                    const distM = map.distance(cur, target);
                    let z;
                    if (distM > 600000) z = 8;
                    else if (distM > 200000) z = 10;
                    else if (distM > 50000) z = 12;
                    else z = Math.max(map.getZoom(), 14);
                    const needZoom = Math.abs(map.getZoom() - z) > 0.6;
                    const done = () => mk.openPopup();

                    if (distM > 1500 || needZoom) {
                        map.flyTo(target, z, {
                            animate: true,
                            duration: 3,
                            easeLinearity: 0.1
                        });
                        map.once('moveend', () => {
                            ensureVisibleWithPanel(mk);
                            done();
                        });
                    } else {
                        map.panTo(target, {
                            animate: true,
                            duration: 1.2,
                            easeLinearity: 0.2
                        });
                        map.once('moveend', () => {
                            ensureVisibleWithPanel(mk);
                            done();
                        });
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

                // ===== แผงลิสต์
                const listCtrl = L.control({
                    position: 'topright'
                });
                listCtrl.onAdd = function() {
                    const wrap = L.DomUtil.create('div', 'tmc-list');
                    wrap.innerHTML = `
                    <div class="hdr"><span>หน่วยบริการทั้งหมด</span><button class="toggle" type="button" title="ย่อ/ขยาย">▣</button></div>
                    <div class="srch"><input type="search" placeholder="ค้นหาชื่อ/จังหวัด"></div>
                    <div class="body"></div>`;
                    L.DomEvent.disableClickPropagation(wrap);
                    L.DomEvent.disableScrollPropagation(wrap);

                    const body = wrap.querySelector('.body'),
                        input = wrap.querySelector('input[type="search"]'),
                        toggleBtn = wrap.querySelector('.toggle');

                    const rowsData = facilities.filter(f => f.lat != null && f.lon != null).map(f => {
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
                        el.innerHTML = `
                        <span class="dot" style="background:${row.color}"></span>
                        <div><div class="tit">${esc(row.name)}</div><div class="sub">${esc(row.province)} • ${esc(row.levelText)}</div></div>`;
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
                        const list = s ? rowsData.filter(r => (r.name + ' ' + r.province + ' ' + r.levelText).toLowerCase().includes(s)) : rowsData;
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

                window.addEventListener('resize', () => {
                    if (activeKey) {
                        const mk = markerById.get(activeKey);
                        if (mk) ensureVisibleWithPanel(mk);
                    }
                });
                map.on('enterFullscreen exitFullscreen', () => {
                    if (activeKey) {
                        const mk = markerById.get(activeKey);
                        if (mk) setTimeout(() => ensureVisibleWithPanel(mk), 10);
                    }
                });

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
@endsection
