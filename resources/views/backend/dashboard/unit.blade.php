{{-- resources/views/backend/dashboard/unit.blade.php --}}

@extends('layouts.main')

@section('title', 'รายงานหน่วยบริการ')
@section('breadcrumb-item', 'Backend')
@section('breadcrumb-item-active', 'รายงานหน่วยบริการ')

@section('content')
    @include('backend.dashboard._filter')

    @php
        $filterYear = (int) request('year');
        $filterRound = (int) request('round');

        /** @var \App\Models\ServiceUnit $unit */
        $asul = optional($unit->assessmentLevelFor($filterYear, $filterRound)->first());

        $provinceTitle = $unit->province->title ?? null;
        $districtTitle = $unit->district->title ?? null;
        $subdistrictTitle = $unit->subdistrict->title ?? null;

        $summaryRow = (object) [
            'serviceUnit' => (object) ['org_name' => $unit->org_name],
            'level' => $asul?->level,
            'assess_year' => $filterYear,
            'assess_round' => $filterRound,
            'approval_status' => $asul?->approval_status,
        ];

        $levelCode = $asul?->level;
        $asulId = $asul?->id;
    @endphp

    <div class="row g-3">
        <div class="col-12">
            <div class="card border-2 border-primary-subtle">
                <div class="card-body p-3 p-lg-4">

                    {{-- สรุปผลการประเมิน --}}
                    @include('backend.self_assessment_service_unit_level._summary', ['row' => $summaryRow])

                    <hr class="my-4">

                    <div class="row g-3 align-items-stretch">
                        {{-- ซ้าย: ข้อมูลหน่วยบริการ (คอลัมน์เดียว) --}}
                        <div class="col-12 col-xl-8 d-flex">
                            <div class="border rounded p-3 flex-fill h-100">
                                <div class="mb-3 d-flex align-items-center gap-2">
                                    <i class="ph-duotone ph-hospital"></i>
                                    <span class="fw-semibold">ข้อมูลหน่วยบริการ</span>
                                </div>

                                {{-- บล็อกรายละเอียดพื้นฐาน --}}
                                <div class="mb-2"><strong>ชื่อหน่วย:</strong> {{ $unit->org_name ?? '-' }}</div>
                                <div class="mb-2">
                                    <strong>สังกัด:</strong> {{ $unit->org_affiliation ?? '-' }}
                                    @if (($unit->org_affiliation ?? '') === 'อื่น ๆ' && !empty($unit->org_affiliation_other))
                                        ({{ $unit->org_affiliation_other }})
                                    @endif
                                </div>
                                <div class="mb-2"><strong>โทรศัพท์:</strong> {{ $unit->org_tel ?? '-' }}</div>
                                <div class="mb-2">
                                    <strong>ที่อยู่:</strong>
                                    <div>{{ $unit->org_address ?? '-' }}</div>
                                    <div class="small text-muted">
                                        จ.{{ $provinceTitle ?? '-' }}
                                        อ.{{ $districtTitle ?? '-' }}
                                        ต.{{ $subdistrictTitle ?? '-' }}
                                        {{ $unit->org_postcode ?? '' }}
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <strong>พิกัด:</strong>
                                    @if ($unit->org_lat && $unit->org_lng)
                                        {{ $unit->org_lat }}, {{ $unit->org_lng }}
                                        <a class="ms-2 small" target="_blank" href="https://www.google.com/maps?q={{ $unit->org_lat }},{{ $unit->org_lng }}">เปิดแผนที่</a>
                                    @else
                                        -
                                    @endif
                                </div>

                                {{-- แผนที่หลัก --}}
                                <div class="rounded border position-relative mb-3">
                                    <div id="map" style="height:300px;"></div>
                                </div>

                                {{-- วัน-เวลาทำการ: ย้ายมาต่อจากแผนที่ --}}
                                <div class="mb-2"><strong>วัน-เวลาทำการ:</strong></div>
                                {!! renderWorkingHoursTable($unit->org_working_hours_json ?? null) !!}
                            </div>
                        </div>

                        {{-- ขวา: การตั้งค่าการแสดงผลหน้าบ้าน --}}
                        <div class="col-12 col-xl-4 d-flex">
                            @php
                                use App\Models\StHealthService;
                                use App\Models\AssessmentServiceConfig;

                                $services = collect();
                                if ($levelCode && $asulId) {
                                    $base = StHealthService::active()->forLevel($levelCode)->orderBy('ordering')->orderBy('id')->get();
                                    $pivot = AssessmentServiceConfig::where('assessment_service_unit_level_id', $asulId)->pluck('is_enabled', 'st_health_service_id');

                                    $services = $base->map(function ($svc) use ($pivot) {
                                        $svc->resolved_enabled = $pivot->has($svc->id) ? (bool) $pivot[$svc->id] : (bool) $svc->default_enabled;
                                        return $svc;
                                    });
                                }

                                $levelText = config('tmc.assessment.level_text', []);
                                $cardTitle =
                                    $levelCode && isset($levelText[$levelCode])
                                        ? 'หน่วยบริการ' . $levelText[$levelCode] // ได้ "หน่วยบริการระดับพื้นฐาน/กลาง/สูง"
                                        : 'การตั้งค่าการแสดงผลหน้าบ้าน';
                                $toggleUrl = $levelCode && $asulId ? route('backend.assessment-service-configs.services.toggle', $asulId) : null;
                            @endphp

                            @if ($levelCode && $asulId)
                                <div class="border rounded p-3 flex-fill h-100 border-2 border-primary-subtle">
                                    <div class="mb-3 d-flex align-items-center gap-2">
                                        <i class="ph-duotone ph-sliders-horizontal"></i>
                                        <span class="fw-semibold">{{ $cardTitle }}</span>
                                    </div>

                                    <div class="alert alert-info py-2 d-flex align-items-start gap-2">
                                        <i class="ph-duotone ph-info mt-1"></i>
                                        <div>โปรดเปิด–ปิดบริการให้ตรงกับงานที่หน่วยบริการดำเนินการจริง การตั้งค่านี้จะแสดงบนหน้าเว็บไซต์ของหน่วยบริการทันที</div>
                                    </div>

                                    <div class="row g-3">
                                        @forelse($services as $svc)
                                            <div class="col-12">
                                                <div class="d-flex align-items-start gap-2">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input js-svc-toggle" type="checkbox" @checked($svc->resolved_enabled) data-url="{{ $toggleUrl }}" data-svc-id="{{ $svc->id }}">
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold">{{ $svc->name }}</div>
                                                        @if ($svc->description)
                                                            <div class="small text-muted">{{ $svc->description }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="col-12 text-muted">ยังไม่มีการกำหนดบริการสำหรับระดับนี้</div>
                                        @endforelse
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>{{-- /row --}}

                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    {{-- Leaflet CSS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <style>
        #map.is-fullscreen {
            position: fixed !important;
            inset: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            z-index: 1060 !important;
            border-radius: 0 !important
        }

        body.map-fs-hide-scroll {
            overflow: hidden
        }

        .leaflet-control-fullscreen {
            background: #fff;
            border: 1px solid rgba(0, 0, 0, .15);
            border-radius: .375rem;
            box-shadow: 0 .25rem .5rem rgba(0, 0, 0, .08);
            overflow: hidden
        }

        .leaflet-control-fullscreen a {
            display: block;
            width: 36px;
            height: 36px;
            line-height: 36px;
            text-align: center;
            color: #212529;
            text-decoration: none
        }

        .leaflet-control-fullscreen a:hover {
            background: rgba(13, 110, 253, .08);
            color: #0d6efd
        }
    </style>
@endpush

@push('scripts')
    {{-- Leaflet JS --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const lat = Number(@json($unit->org_lat ?? 0));
            const lng = Number(@json($unit->org_lng ?? 0));
            const hasCoord = isFinite(lat) && isFinite(lng) && (lat !== 0 || lng !== 0);

            const mapEl = document.getElementById('map');
            const map = L.map(mapEl, {
                zoomControl: true,
                scrollWheelZoom: true
            });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            if (hasCoord) {
                L.marker([lat, lng]).addTo(map).bindPopup(@json($unit->org_name));
                map.setView([lat, lng], 15);
            } else {
                map.setView([13.7563, 100.5018], 6);
            }
            setTimeout(() => map.invalidateSize(), 200);

            const FullscreenControl = L.Control.extend({
                options: {
                    position: 'topleft',
                    titleEnter: 'แสดงเต็มจอ (F)',
                    titleExit: 'ออกจากเต็มจอ (Esc)'
                },
                onAdd: function(m) {
                    const box = L.DomUtil.create('div', 'leaflet-control-fullscreen');
                    const link = L.DomUtil.create('a', '', box);
                    link.href = '#';
                    link.innerHTML = '⛶';
                    link.title = this._isFs() ? this.options.titleExit : this.options.titleEnter;

                    L.DomEvent.disableClickPropagation(box);
                    L.DomEvent.on(link, 'click', e => {
                        L.DomEvent.preventDefault(e);
                        toggleFs();
                    });

                    L.DomEvent.on(document, 'keydown', e => {
                        if (e.key.toLowerCase() === 'f' && document.activeElement === document.body) {
                            e.preventDefault();
                            toggleFs();
                        }
                    });

                    document.addEventListener('fullscreenchange', updateUi);
                    document.addEventListener('webkitfullscreenchange', updateUi);

                    function updateUi() {
                        const fs = isFsApiOn();
                        link.innerHTML = fs ? '🗗' : '⛶';
                        link.title = fs ? 'ออกจากเต็มจอ (Esc)' : 'แสดงเต็มจอ (F)';
                        setTimeout(() => m.invalidateSize(), 200);
                    }
                    return box;
                },
                _isFs: function() {
                    return isFsApiOn() || mapEl.classList.contains('is-fullscreen');
                }
            });
            map.addControl(new FullscreenControl());

            function isFsApiOn() {
                return !!(document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement);
            }

            function requestFs(el) {
                return (el.requestFullscreen?.call(el) || el.webkitRequestFullscreen?.call(el) || el.msRequestFullscreen?.call(el));
            }

            function exitFs() {
                return (document.exitFullscreen?.call(document) || document.webkitExitFullscreen?.call(document) || document.msExitFullscreen?.call(document));
            }

            function toggleFs() {
                if (isFsApiOn() || mapEl.classList.contains('is-fullscreen')) {
                    if (isFsApiOn()) exitFs();
                    mapEl.classList.remove('is-fullscreen');
                    document.body.classList.remove('map-fs-hide-scroll');
                    setTimeout(() => map.invalidateSize(), 200);
                } else {
                    const ok = requestFs(mapEl);
                    if (ok === undefined) {
                        mapEl.classList.add('is-fullscreen');
                        document.body.classList.add('map-fs-hide-scroll');
                        setTimeout(() => map.invalidateSize(), 200);
                    }
                }
            }

            document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && mapEl.classList.contains('is-fullscreen')) {
                    mapEl.classList.remove('is-fullscreen');
                    document.body.classList.remove('map-fs-hide-scroll');
                    setTimeout(() => map.invalidateSize(), 200);
                }
            });
            window.addEventListener('resize', () => setTimeout(() => map.invalidateSize(), 200));
        });
    </script>
@endpush

@push('scripts')
    {{-- Toggle service --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function toggleService(el) {
                const url = el.dataset.url;
                const svcId = el.dataset.svcId;
                const enabled = el.checked ? 1 : 0;
                if (!url || !svcId) return;

                el.disabled = true;

                fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            service_id: Number(svcId),
                            enabled: Boolean(enabled)
                        })
                    })
                    .then(async res => {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.json();
                    })
                    .catch(err => {
                        el.checked = !enabled;
                        alert('บันทึกไม่สำเร็จ กรุณาลองใหม่');
                        console.error(err);
                    })
                    .finally(() => {
                        el.disabled = false;
                    });
            }

            document.querySelectorAll('.js-svc-toggle').forEach(el => {
                el.addEventListener('change', () => toggleService(el));
            });
        });
    </script>
@endpush
