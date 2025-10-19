{{-- resources/views/frontend/service_units/index.blade.php --}}
@extends('layouts.frontend')

@section('title', 'หน่วยบริการสุขภาพผู้เดินทาง')
@section('meta_description', 'ทำแบบประเมินปีงบประมาณปัจจุบันและได้รับการอนุมัติแล้ว')

@section('page_header')
    <header class="bg-light-page border-bottom py-3">
        <div class="container">
            <h1 class="h3 mb-2 section-title text-dark">หน่วยบริการสุขภาพผู้เดินทาง</h1>
            <nav aria-label="breadcrumb" class="text-end">
                <ol class="breadcrumb mb-0 d-inline-flex justify-content-end">
                    <li class="breadcrumb-item"><a href="{{ url('/') }}">หน้าแรก</a></li>
                    <li class="breadcrumb-item active" aria-current="page">หน่วยบริการสุขภาพผู้เดินทาง</li>
                </ol>
            </nav>
        </div>
    </header>
@endsection

@section('content')
    <section id="services" class="py-5">
        <div class="container pt-lg-2">

            @php
                // === ค่าคงที่สำหรับแปลงระดับเป็นข้อความและสีบน UI ===
                $LEVEL_TEXTS = ['basic' => 'พื้นฐาน', 'medium' => 'กลาง', 'advanced' => 'สูง'];
                $LEVEL_COLORS = ['basic' => '#ef83c2', 'medium' => '#f0c419', 'advanced' => '#0dcc93'];

                // รายการตัวกรองจังหวัดและระดับ
                $provinces = $provinces ?? collect();
                $levels = $levels ?? ['' => 'ระดับทั้งหมด', 'basic' => 'พื้นฐาน', 'medium' => 'กลาง', 'advanced' => 'สูง'];

                // ฟังก์ชันช่วยคืนค่า key/text/color ของระดับจากโมเดล
                $resolveLevel = function ($su) use ($LEVEL_TEXTS, $LEVEL_COLORS) {
                    $key = optional($su->assessmentLevelApprovedCurrent)->level ?? 'basic';
                    return [
                        'key' => $key,
                        'text' => $LEVEL_TEXTS[$key] ?? 'พื้นฐาน',
                        'color' => $LEVEL_COLORS[$key] ?? '#ef83c2',
                    ];
                };
            @endphp

            {{-- ฟอร์มค้นหา --}}
            <form method="GET" action="{{ route('frontend.service-units.index') }}" class="row g-2 align-items-end mb-4">
                <div class="col-md-4 col-lg-5">
                    <label for="q" class="form-label visually-hidden">คำค้น</label>
                    <div class="input-group">
                        <input type="text" name="q" id="q" value="{{ $q ?? request('q') }}" class="form-control form-control-lg" placeholder="ค้นหาชื่อหน่วยบริการหรือที่อยู่">
                    </div>
                </div>
                <div class="col-md-4 col-lg-3">
                    <label for="province" class="form-label visually-hidden">จังหวัด</label>
                    <select name="province" id="province" class="form-select form-select-lg">
                        <option value="">ทุกจังหวัด</option>
                        @foreach ($provinces as $code => $name)
                            <option value="{{ $code }}" @selected(request('province') == $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-lg-2">
                    <label for="level" class="form-label visually-hidden">ระดับ</label>
                    <select name="level" id="level" class="form-select form-select-lg">
                        @foreach ($levels as $key => $label)
                            <option value="{{ $key }}" @selected(request('level') == $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-lg-2">
                    <button class="btn btn-lg btn-primary w-100" type="submit">
                        <i class="bi bi-search"></i> ค้นหา
                    </button>
                </div>
            </form>

            {{-- แผนที่ --}}
            <div class="row">
                <div class="col-12">
                    <div id="map" class="rounded shadow" style="height:420px;"></div>
                </div>
            </div>

            {{-- รายการการ์ดหน่วยบริการ --}}
            <div id="map-section" class="card border-0 shadow-sm my-4">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h5 class="mb-0 fw-semibold">
                            หน่วยบริการทั้งหมด <span class="text-primary">{{ number_format($serviceUnits->total() ?? $serviceUnits->count()) }}</span>
                        </h5>
                        <div class="small text-muted">คลิก <i class="bi bi-geo-alt"></i> เพื่อโฟกัสแผนที่</div>
                    </div>
                </div>

                <div class="card-body pt-3">
                    <style>
                        /* สไตล์การ์ด */
                        .unit-card {
                            border: 1px solid var(--bs-border-color);
                            border-radius: .75rem;
                            background: #fff;
                            transition: transform .15s, box-shadow .15s, border-color .15s;
                            height: 100%
                        }

                        .unit-card:hover {
                            transform: translateY(-2px);
                            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .08);
                            border-color: rgba(13, 110, 253, .25)
                        }

                        .level-dot {
                            width: 14px;
                            height: 14px;
                            border-radius: 4px;
                            flex: 0 0 14px;
                            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .05)
                        }

                        .level-badge {
                            --_c: var(--lv-color, #0d6efd);
                            background: color-mix(in srgb, var(--_c) 12%, transparent);
                            color: #2b2b2b;
                            border: 1px solid color-mix(in srgb, var(--_c) 45%, transparent);
                            font-weight: 600
                        }

                        .meta-line {
                            color: #6c757d;
                            font-size: .92rem
                        }

                        .meta-line .bi {
                            opacity: .75
                        }

                        .btn-map {
                            --bs-btn-border-color: rgba(13, 110, 253, .25);
                            --bs-btn-hover-bg: rgba(13, 110, 253, .08)
                        }
                    </style>

                    <div class="row g-3">
                        @forelse ($serviceUnits as $su)
                            @php $lv = $resolveLevel($su); @endphp
                            <div class="col-12 col-md-6">
                                <div class="unit-card p-3">
                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <div class="level-dot" style="background:{{ $lv['color'] }};"></div>
                                            <div class="fw-semibold" style="font-size:1.05rem">{{ $su->org_name }}</div>
                                            <span class="badge level-badge" style="--lv-color: {{ $lv['color'] }};">ระดับ{{ $lv['text'] }}</span>
                                        </div>
                                        {{-- ปุ่มโฟกัสไปยังพิกัดในแผนที่ --}}
                                        <button class="btn btn-sm btn-outline-primary btn-map" data-lat="{{ $su->org_lat }}" data-lng="{{ $su->org_lng }}" onclick="focusOnLatLng(this)" title="ดูบนแผนที่">
                                            <i class="bi bi-geo-alt"></i>
                                        </button>
                                    </div>

                                    <div class="mt-2 meta-line">
                                        <i class="bi bi-geo"></i>
                                        <span>
                                            {{ $su->org_address ?: '— ไม่ระบุที่อยู่ —' }}
                                            @if ($su->geo_titles)
                                                • {{ $su->geo_titles }}
                                            @endif
                                        </span>
                                    </div>

                                    @if ($su->org_tel)
                                        <div class="mt-1 meta-line">
                                            <i class="bi bi-telephone"></i>
                                            <a href="tel:{{ preg_replace('/\D/', '', $su->org_tel) }}" class="link-secondary text-decoration-none">
                                                {{ $su->org_tel }}
                                            </a>
                                        </div>
                                    @endif

                                    <div class="d-flex gap-2 mt-3">
                                        <a href="https://maps.google.com/?q={{ $su->org_lat }},{{ $su->org_lng }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-map"></i> เปิดด้วย Google Maps
                                        </a>
                                        @isset($su->id)
                                            @if (Route::has('frontend.service-units.show'))
                                                <a href="{{ route('frontend.service-units.show', $su->id) }}" class="btn btn-sm btn-primary">รายละเอียด</a>
                                            @else
                                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                                    <i class="bi bi-info-circle"></i> รายละเอียด
                                                </button>
                                            @endif
                                        @endisset
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-muted">ไม่พบผลลัพธ์</div>
                        @endforelse
                    </div>

                    <div class="pt-3">
                        {{-- แสดง pagination และคงพารามิเตอร์ค้นหาเดิม --}}
                        {{ $serviceUnits->appends(request()->only('q', 'province', 'level'))->links() }}
                    </div>
                </div>
            </div>

            {{-- Modal ส่งข้อความ --}}
            <div class="modal fade" id="messageModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content p-3">
                        <div class="modal-header">
                            <h5 class="modal-title text-primary">ส่งข้อความถึงหน่วยบริการ</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                        </div>
                        <div class="modal-body">
                            <form id="messageForm">
                                <input type="hidden" id="msgFacilityId" />
                                <div class="mb-2">
                                    <label class="form-label">ถึง</label>
                                    <input id="msgTo" class="form-control" readonly />
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">ชื่อ-นามสกุล</label>
                                    <input id="msgName" class="form-control" required />
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">อีเมล</label>
                                    <input id="msgEmail" type="email" class="form-control" required />
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">ข้อความ</label>
                                    <textarea id="msgBody" class="form-control" rows="5" required></textarea>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">ส่งข้อความ</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection

@push('styles')
    {{-- โหลด CSS ของ Leaflet --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <style>
        /* element ป้ายชื่อแบบ DivIcon ให้คลิกได้ */
        .facility-label {
            pointer-events: auto
        }

        /* โหมดเต็มหน้าจอแบบ fallback เมื่อ Fullscreen API ใช้ไม่ได้ */
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

        /* ปุ่มควบคุม Fullscreen บนแผนที่ */
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

        /* เอฟเฟกต์ pulse เมื่อโฟกัสจุด */
        .pulse-wrap {
            position: relative;
            width: 24px;
            height: 24px;
            transform: translate(-12px, -12px)
        }

        .pulse-dot {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #0d6efd;
            transform: translate(-50%, -50%)
        }

        .pulse-ring {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 8px;
            height: 8px;
            border: 2px solid #0d6efd;
            border-radius: 999px;
            opacity: .6;
            transform: translate(-50%, -50%) scale(1);
            animation: pulse-ring 1200ms ease-out forwards
        }

        @keyframes pulse-ring {
            0% {
                opacity: .6;
                transform: translate(-50%, -50%) scale(1)
            }

            80% {
                opacity: .15
            }

            100% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(3)
            }
        }

        /* สไตล์ป้ายชื่อบนแผนที่ (แถบมนพร้อมจุดสีและ badge ระดับ) */
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
            width: 0;
            height: 0;
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

        .opacity-0 {
            opacity: 0
        }
    </style>
@endpush

@push('scripts')
    {{-- โหลด JS Leaflet และ Turf (สำหรับงาน Geo ที่คอมเมนต์ไว้ด้านล่าง) --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://unpkg.com/@turf/turf@6/turf.min.js"></script>

    @php
        // เตรียมข้อมูลจุดสำหรับปักบนแผนที่ให้เป็น array ของ JS
        $facilities = $serviceUnits
            ->getCollection()
            ->map(function ($su) {
                $key = optional($su->assessmentLevelApprovedCurrent)->level ?? 'basic';
                $textMap = ['basic' => 'พื้นฐาน', 'medium' => 'กลาง', 'advanced' => 'สูง'];
                return [
                    'id' => $su->id,
                    'name' => $su->org_name,
                    'province' => optional($su->province)->title,
                    'lat' => $su->org_lat ? (float) $su->org_lat : null,
                    'lon' => $su->org_lng ? (float) $su->org_lng : null,
                    'phone' => $su->org_tel,
                    'address' => $su->org_address,
                    'levelKey' => $key,
                    'levelText' => $textMap[$key] ?? 'พื้นฐาน',
                ];
            })
            ->values()
            ->all();
    @endphp

    <script>
        'use strict';

        // === ข้อมูลจุดจาก PHP ===
        const facilities = @json($facilities);

        // === แผนที่: สร้าง instance และตั้งค่าพฤติกรรมการซูม/เลื่อนให้นุ่มนวล ===
        const mapEl = document.getElementById('map');
        const map = L.map(mapEl, {
            zoomControl: true, // แสดงปุ่ม +/-
            zoomSnap: 0.25, // step ของระดับซูม
            zoomDelta: 0.5, // ระยะเปลี่ยนซูมต่อหนึ่งสกอลล์
            wheelDebounceTime: 40, // หน่วงเวลาการคำนวณสกอลล์
            wheelPxPerZoomLevel: 100, // ความไวของล้อเมาส์
            inertia: true, // เปิดแรงเฉื่อยเวลา panning
            inertiaDeceleration: 3000,
            easeLinearity: 0.2
        }).setView([13.736717, 100.523186], 6); // กำหนดศูนย์และระดับซูมเริ่มต้น

        // ชั้นแผนที่พื้นฐาน (OSM)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // === บล็อกที่คอมเมนต์ไว้: ล้อมเส้นรอบประเทศไทยจากไฟล์จังหวัด (คงไว้ตามคำขอ) ===
        // const PROVINCES_URL = "{{ asset('geo/reg_royin.geojson') }}";
        // async function addThailandOutlineFromProvinces(m) {
        //     // สร้าง pane แยกเพื่อจัด z-index ของเส้น
        //     if (!m.getPane('th-outline')) {
        //         m.createPane('th-outline');
        //         m.getPane('th-outline').style.zIndex = 650;
        //     }
        //     // สไตล์เส้นหลักและเงาเรือง
        //     const styleMain = { color:'#0d6efd', weight:3, opacity:1, fillOpacity:0, interactive:false, pane:'th-outline' };
        //     const styleGlow = { color:'#0d6efd', weight:10, opacity:.25, fillOpacity:0, interactive:false, pane:'th-outline' };
        //     try {
        //         // โหลด GeoJSON และ union รวมเป็นรูปร่างประเทศเดียว
        //         const res = await fetch(PROVINCES_URL, { headers: { 'Accept': 'application/json' }});
        //         if (!res.ok) throw new Error('HTTP ' + res.status);
        //         const fc = await res.json();
        //         let merged = null;
        //         (fc.features || []).forEach(f => {
        //             const ok = f.geometry && (f.geometry.type === 'Polygon' || f.geometry.type === 'MultiPolygon');
        //             if (!ok) return;
        //             merged = merged ? turf.union(merged, f) : f;
        //         });
        //         if (!merged) return;
        //         // วาดเงาก่อน แล้ววาดเส้นหลัก
        //         L.geoJSON(merged, { style: styleGlow }).addTo(m);
        //         const line = L.geoJSON(merged, { style: styleMain }).addTo(m);
        //         const b = line.getBounds();
        //         if (b.isValid()) m.fitBounds(b.pad(0.05));
        //     } catch (e) {
        //         // ถ้า union ล้มเหลว fallback เป็นเส้นจังหวัดทั้งหมด
        //         console.error('Outline failed:', e);
        //         try {
        //             const r2 = await fetch(PROVINCES_URL);
        //             const fc2 = await r2.json();
        //             L.geoJSON(fc2, { style: styleMain, pane: 'th-outline' }).addTo(m);
        //         } catch (_) {}
        //     }
        // }
        // addThailandOutlineFromProvinces(map);

        // === บล็อกที่คอมเมนต์ไว้: วาดเส้นขอบเขตทุกจังหวัดแบบไม่ถมสี (คงไว้ตามคำขอ) ===
        // async function addProvinceBorders(m) {
        //     if (!m.getPane('th-outline')) {
        //         m.createPane('th-outline');
        //         m.getPane('th-outline').style.zIndex = 650;
        //     }
        //     const lineStyle = { color:'#0d6efd', weight:1.8, opacity:1, fill:false, fillOpacity:0, pane:'th-outline' };
        //     const glowStyle = { color:'#0d6efd', weight:7, opacity:0.18, fill:false, fillOpacity:0, pane:'th-outline' };
        //     try {
        //         const res = await fetch(PROVINCES_URL, { headers: { 'Accept': 'application/json' }});
        //         if (!res.ok) throw new Error('HTTP ' + res.status);
        //         const fc = await res.json();
        //         L.geoJSON(fc, { style: glowStyle, interactive:false }).addTo(m);
        //         const line = L.geoJSON(fc, { style: lineStyle, interactive:false }).addTo(m);
        //         const b = line.getBounds();
        //         if (b.isValid()) m.fitBounds(b.pad(0.05));
        //     } catch (e) { console.error('โหลด .geojson ล้มเหลว:', e); }
        // }
        // addProvinceBorders(map);
        // ===== end commented blocks =====

        // === สีกำกับระดับหน่วยบริการ ===
        function getLevelColor(level) {
            const m = {
                basic: '#ef83c2',
                medium: '#f0c419',
                advanced: '#0dcc93'
            };
            return m[level] ?? '#ef83c2';
        }

        // === วางหมุดและป้ายชื่อบนแผนที่ ===
        const markers = []; // เก็บ Marker หลัก
        const labels = []; // เก็บ Label แบบ DivIcon

        facilities.forEach(f => {
            if (f.lat == null || f.lon == null) return; // ข้ามถ้าไม่มีพิกัด

            const c = getLevelColor(f.levelKey);

            // 1) สร้าง Marker แบบ SVG สีตามระดับ
            const mk = L.marker([f.lat, f.lon], {
                icon: createColoredIcon(c)
            }).addTo(map);

            // 2) ผูก Popup แสดงรายละเอียด
            mk.bindPopup(
                `<div style="min-width:240px;">
                    <h6 class="mb-1 text-primary">${escapeHtml(f.name)}</h6>
                    <p class="text-muted small mb-1">${escapeHtml(f.address ?? '-')}${f.province ? ' • ' + escapeHtml(f.province) : ''}</p>
                    <span class="badge" style="background:${c}20;border:1px solid ${c}50;color:#333;">ระดับ${f.levelText}</span>
                    ${f.phone ? `<p class="text-muted small mt-2 mb-0"><strong>ติดต่อ:</strong> ${escapeHtml(f.phone)}</p>` : ''}
                 </div>`, {
                    maxWidth: 320
                }
            );

            // meta สำหรับค้นหา marker ทีหลัง
            mk._meta = {
                lat: f.lat,
                lon: f.lon
            };
            markers.push(mk);

            // 3) สร้าง Label ชื่อหน่วยบริการ แบบ DivIcon
            const lblHtml = `
                <div class="flabel" style="--c:${c}">
                    <span class="dot"></span>
                    <span class="name">${escapeHtml(f.name)}</span>
                    <span class="level">ระดับ${f.levelText}</span>
                </div>`;

            const lbl = L.marker([f.lat, f.lon], {
                interactive: true, // ให้คลิกได้
                icon: L.divIcon({
                    className: 'facility-label', // ใช้เพื่อควบคุม pointer-events
                    html: lblHtml,
                    iconSize: null, // ให้ขนาดยืดตามเนื้อหา
                    iconAnchor: [-6, 18] // ยึดฐานซ้ายของป้ายให้ชิดปลายหมุด
                }),
                zIndexOffset: 1000 // ให้อยู่เหนือชั้นอื่นเล็กน้อย
            }).addTo(map);

            // คลิกที่ป้ายให้เปิด popup ของ marker หลัก
            lbl.on('click', () => mk.openPopup());

            labels.push(lbl);
        });

        // ถ้ามีจุด ให้ปรับมุมมองครอบทั้งหมด
        if (markers.length) {
            const g = L.featureGroup(markers);
            map.fitBounds(g.getBounds().pad(0.2));
        }

        // === ไอคอนหมุดแบบ SVG ที่กำหนดสีได้ ===
        function createColoredIcon(c) {
            return L.icon({
                iconUrl: 'data:image/svg+xml;utf8,' + encodeURIComponent(
                    `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="38" viewBox="0 0 28 38" fill="none">
                        <path d="M14 1C9 1 4 5.6 4 11.6 4 21.8 14 37 14 37s10-15.2 10-25.4C24 5.6 19 1 14 1z" fill="${c}"/>
                        <circle cx="14" cy="11.6" r="3.6" fill="#fff"/>
                    </svg>`
                ),
                iconSize: [28, 38],
                iconAnchor: [14, 37],
                popupAnchor: [0, -38]
            });
        }

        // === escape ข้อความเพื่อความปลอดภัย ===
        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, m => ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#39;"
            } [m]));
        }

        // === เอฟเฟกต์ pulse + การเลื่อนโฟกัสแบบนุ่มนวล ===
        let pulseMarker = null;

        // แสดงวงแหวน pulse ชั่วคราวที่ตำแหน่ง lat/lng
        // function showPulse(lat, lng, color = '#0d6efd') {
        //     if (pulseMarker) {
        //         map.removeLayer(pulseMarker);
        //         pulseMarker = null;
        //     }
        //     const html = `<div class="pulse-wrap">
    //         <div class="pulse-dot" style="background:${color}"></div>
    //         <div class="pulse-ring" style="border-color:${color}"></div>
    //     </div>`;
        //     pulseMarker = L.marker([lat, lng], {
        //         interactive: false,
        //         icon: L.divIcon({
        //             className: '',
        //             html,
        //             iconSize: [24, 24],
        //             iconAnchor: [12, 12]
        //         })
        //     }).addTo(map);
        //     setTimeout(() => {
        //         if (pulseMarker) {
        //             map.removeLayer(pulseMarker);
        //             pulseMarker = null;
        //         }
        //     }, 1300);
        // }

        // เลื่อน/ซูมไปยังเป้าหมายอย่างนุ่มนวล เลือก flyTo หรือ panTo ตามระยะ
        function smoothFocusTo(lat, lng, targetZoom = 13) {
            const cur = map.getCenter();
            const dist = map.distance(cur, L.latLng(lat, lng));
            const needZoom = Math.abs(map.getZoom() - targetZoom) > 0.6;
            if (dist > 1500 || needZoom) {
                map.flyTo([lat, lng], Math.max(map.getZoom(), targetZoom), {
                    animate: true,
                    duration: 3,
                    easeLinearity: 0.1
                });
            } else {
                map.panTo([lat, lng], {
                    animate: true,
                    duration: 1.2,
                    easeLinearity: 0.2
                });
            }
        }

        // หา marker จากพิกัด (ใช้ความคลาดเคลื่อนเล็กน้อย)
        function findMarkerByLatLng(lat, lng) {
            const eps = 1e-6;
            for (const m of markers) {
                const ll = m.getLatLng();
                if (Math.abs(ll.lat - lat) < eps && Math.abs(ll.lng - lng) < eps) return m;
            }
            return null;
        }

        // ฟังก์ชัน global เรียกจากปุ่มในการ์ด: โชว์ pulse + โฟกัส + เปิด popup
        window.focusOnLatLng = function(el) {
            const lat = parseFloat(el.dataset.lat),
                lng = parseFloat(el.dataset.lng);
            if (isNaN(lat) || isNaN(lng)) return;
            showPulse(lat, lng, '#0d6efd');
            smoothFocusTo(lat, lng, 13);
            const mk = findMarkerByLatLng(lat, lng);
            if (mk) map.once('moveend', () => setTimeout(() => mk.openPopup(), 250));
        };

        // === ควบคุมการแสดงป้ายชื่อเพื่อลดความรกเมื่อซูมน้อย ===
        const LABEL_MIN_ZOOM = 6.5; // ซูมน้อยกว่านี้ซ่อนป้ายชื่อทั้งหมด
        function updateLabelsVisibility() {
            const show = map.getZoom() >= LABEL_MIN_ZOOM;
            labels.forEach(l => {
                l.getElement()?.classList.toggle('opacity-0', !show);
            });
        }
        map.on('zoomend', updateLabelsVisibility);
        map.whenReady(updateLabelsVisibility);

        // === ปุ่ม Fullscreen แบบ custom ===
        const FullscreenControl = L.Control.extend({
            options: {
                position: 'topleft',
                titleEnter: 'แสดงเต็มจอ (F)',
                titleExit: 'ออกจากเต็มจอ (Esc)'
            },
            onAdd: function(m) {
                const container = L.DomUtil.create('div', 'leaflet-control-fullscreen');
                const link = L.DomUtil.create('a', '', container);
                link.href = '#';
                link.innerHTML = '⛶';
                link.title = this._isFullscreen() ? this.options.titleExit : this.options.titleEnter;

                // ป้องกันเหตุการณ์ mouse ผ่านไปยังแผนที่
                L.DomEvent.disableClickPropagation(container);

                // คลิก = toggle โหมดเต็มจอ
                L.DomEvent.on(link, 'click', (e) => {
                    L.DomEvent.preventDefault(e);
                    toggleFullscreen();
                });

                // ทางลัดแป้นพิมพ์ F
                L.DomEvent.on(document, 'keydown', (e) => {
                    if (e.key.toLowerCase() === 'f' && document.activeElement === document.body) {
                        e.preventDefault();
                        toggleFullscreen();
                    }
                });

                // อัปเดต UI เมื่อสถานะ fullscreen เปลี่ยน
                document.addEventListener('fullscreenchange', updateUi);

                function updateUi() {
                    const fs = isFullscreenApiOn();
                    link.innerHTML = fs ? '🗗' : '⛶';
                    link.title = fs ? 'ออกจากเต็มจอ (Esc)' : 'แสดงเต็มจอ (F)';
                    setTimeout(() => m.invalidateSize(), 200);
                }
                return container;
            },
            _isFullscreen: function() {
                return isFullscreenApiOn() || mapEl.classList.contains('is-fullscreen');
            }
        });
        map.addControl(new FullscreenControl());

        // ตรวจ Fullscreen API
        function isFullscreenApiOn() {
            return !!(document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement);
        }
        // ร้องขอเข้าโหมดเต็มจอ
        function requestFS(el) {
            return (el.requestFullscreen?.call(el) || el.webkitRequestFullscreen?.call(el) || el.msRequestFullscreen?.call(el));
        }
        // ออกจากโหมดเต็มจอ
        function exitFS() {
            return (document.exitFullscreen?.call(document) || document.webkitExitFullscreen?.call(document) || document.msExitFullscreen?.call(document));
        }

        // สลับโหมดเต็มจอ: ใช้ API ถ้ามี ไม่งั้นใช้คลาส fallback
        function toggleFullscreen() {
            if (isFullscreenApiOn() || mapEl.classList.contains('is-fullscreen')) {
                if (isFullscreenApiOn()) exitFS();
                mapEl.classList.remove('is-fullscreen');
                document.body.classList.remove('map-fs-hide-scroll');
                setTimeout(() => map.invalidateSize(), 200);
            } else {
                const ok = requestFS(mapEl);
                if (ok === undefined) {
                    mapEl.classList.add('is-fullscreen');
                    document.body.classList.add('map-fs-hide-scroll');
                    setTimeout(() => map.invalidateSize(), 200);
                }
            }
        }

        // ปิด fullscreen แบบ fallback เมื่อกด Esc
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && mapEl.classList.contains('is-fullscreen')) {
                mapEl.classList.remove('is-fullscreen');
                document.body.classList.remove('map-fs-hide-scroll');
                setTimeout(() => map.invalidateSize(), 200);
            }
        });

        // จัด layout ใหม่เมื่อขนาด viewport เปลี่ยน
        document.addEventListener('fullscreenchange', () => setTimeout(() => map.invalidateSize(), 200));
        window.addEventListener('resize', () => setTimeout(() => map.invalidateSize(), 200));
    </script>

    {{-- ส่วน optional: ถ้ามี #suList ให้คลิกเพื่อโฟกัสแผนที่ได้ --}}
    <script>
        (function() {
            if (typeof map === 'undefined' || !Array.isArray(markers)) return;
            const suList = document.getElementById('suList');
            if (!suList) return;

            suList.addEventListener('click', (e) => {
                const item = e.target.closest('.list-group-item');
                if (!item || item.classList.contains('disabled')) return;

                const lat = Number(item.dataset.lat);
                const lng = Number(item.dataset.lng);
                if (Number.isNaN(lat) || Number.isNaN(lng)) return;

                suList.querySelectorAll('.list-group-item').forEach(el => el.classList.remove('active'));
                item.classList.add('active');
                item.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });

                showPulse(lat, lng, '#0d6efd');
                smoothFocusTo(lat, lng, 16);

                const mk = (function(lat, lng) {
                    const eps = 1e-6;
                    for (const m of markers) {
                        const ll = m.getLatLng();
                        if (Math.abs(ll.lat - lat) < eps && Math.abs(ll.lng - lng) < eps) return m;
                    }
                    return null;
                })(lat, lng);

                if (mk) map.once('moveend', () => setTimeout(() => mk.openPopup(), 250));
            });
        })();
    </script>
@endpush
