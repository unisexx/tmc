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

        // ===== สีระดับ (ต้องตรงกับหน้า overview) =====
        $LEVEL_COLORS = array_replace(
            [
                'basic' => '#FF4560', // พื้นฐาน = ชมพู
                'medium' => '#FEB019', // กลาง = เหลือง/ส้ม
                'advanced' => '#00E396', // สูง = เขียว
                'unassessed' => '#A8A8A8', // ยังไม่ได้ประเมิน = เทา
            ],
            (array) config('tmc.level_colors', []),
        );

        $levelKey = $levelCode ?: 'unassessed';

        $levelTextMap = [
            'basic' => 'พื้นฐาน',
            'medium' => 'กลาง',
            'advanced' => 'สูง',
            'unassessed' => 'ยังไม่ได้ประเมิน',
        ];

        $levelText = $levelKey === 'unassessed' ? 'ยังไม่ได้ประเมิน' : 'ระดับ' . ($levelTextMap[$levelKey] ?? '-');

        $pinColor = $LEVEL_COLORS[$levelKey] ?? $LEVEL_COLORS['unassessed'];

        // line ติดต่อ
        $contactLine = collect([$unit->org_tel ?: null, $unit->org_email ?: null])
            ->filter()
            ->implode(' / ');

        // BKK vs not BKK
        $isBkk = (string) ($unit->org_province_code ?? '') === '10';
        $districtLabel = $isBkk ? 'เขต' : 'อำเภอ';
        $subdistrictLabel = $isBkk ? 'แขวง' : 'ตำบล';

        // ===== ดึงรายการบริการของระดับนั้น ๆ พร้อมสถานะเปิด/ปิด =====
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

        $enabledServiceNames = $services->filter(fn($s) => $s->resolved_enabled)->pluck('name')->values()->all();

        // ===== popup services HTML (แสดงใน popup บนแผนที่) =====
        $popupServicesHtml = '';
        if (!empty($enabledServiceNames)) {
            $popupServicesHtml = '<div class="mt-2">' . '<div class="fw-semibold mb-1">บริการ</div>' . '<ul class="mb-2 ps-3">' . collect($enabledServiceNames)->map(fn($nm) => '<li class="small">' . e($nm) . '</li>')->implode('') . '</ul>' . '</div>';
        }

        // สำหรับ CSV export
        $approvedText = $asul && $asul->approval_status === 'approved' ? 'อนุมัติ' : 'รอดำเนินการ';

        $servicesCsv = implode(' | ', $enabledServiceNames);

        // สำหรับ header card
        $shortLocation = trim('จ.' . ($provinceTitle ?? '-'));
        $yearRoundText = "ปี {$filterYear} รอบ {$filterRound}";

        // card title ขวา
        $levelTextConfig = config('tmc.assessment.level_text', []);
        $cardTitle = $levelCode && isset($levelTextConfig[$levelCode]) ? 'หน่วยบริการ' . $levelTextConfig[$levelCode] : 'การตั้งค่าการแสดงผลหน้าบ้าน';

        $toggleUrl = $levelCode && $asulId ? route('backend.assessment-service-configs.services.toggle', $asulId) : null;

        // province badge text
        $provinceBadgeText = $shortLocation ?: '-';

        // สถานะอนุมัติล่าสุดของหน่วย (pending / approved / ...)
        $approvalStatusKey = $asul?->approval_status ?? null;

        // ดึง config สี + ข้อความ
        $approvalTextMap = config('tmc.approval_text', []);
        $approvalBgClassMap = config('tmc.approval_badge_class', []);
        $approvalFgClassMap = config('tmc.approval_badge_text_color', []);

        // สร้างค่าที่จะเอาไปใช้ใน view
        $approvalText = $approvalStatusKey ? $approvalTextMap[$approvalStatusKey] ?? $approvalStatusKey : null;

        $approvalBgCls = $approvalStatusKey ? $approvalBgClassMap[$approvalStatusKey] ?? 'bg-secondary' : 'bg-secondary';

        $approvalFgCls = $approvalStatusKey ? $approvalFgClassMap[$approvalStatusKey] ?? 'text-dark' : 'text-dark';
    @endphp

    {{-- ========================================================= --}}
    {{-- แถวบน: ซ้าย (สรุป+ข้อมูลหน่วย) + ขวา (การตั้งค่าหน้าบ้าน) --}}
    {{-- ========================================================= --}}
    <div class="row g-3 mb-3">
        {{-- ====== คอลัมน์ซ้าย ====== --}}
        <div class="col-12 col-xl-8 d-flex flex-column gap-3">

            {{-- CARD: สถานะหน่วยบริการ / สรุปการประเมิน --}}
            <div class="card border-0 shadow-sm flex-fill">
                <div class="card-body p-3 p-lg-4">

                    {{-- header block --}}
                    <div class="d-flex flex-column flex-md-row flex-wrap align-items-md-start align-items-stretch justify-content-between mb-3">
                        <div class="d-flex align-items-start gap-2">
                            <div class="avtar avtar-s bg-light flex-shrink-0">
                                <i class="ph-duotone ph-hospital f-20"></i>
                            </div>
                            <div>
                                <div class="fw-semibold d-flex align-items-center flex-wrap">
                                    <span class="me-2">{{ $unit->org_name ?? '-' }}</span>
                                    <span id="summaryLevelBadge">
                                        @if (!empty($summaryRow->level))
                                            <x-level-badge :level="$summaryRow->level" class="ms-1" />
                                        @else
                                            <span class="badge bg-secondary ms-1">—</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="text-muted small">
                                    {{ $shortLocation ?: '-' }}
                                    @if (!empty($asul?->approval_status))
                                        · สถานะ:
                                        {{-- จุดสีสถานะอนุมัติ --}}
                                        @if ($approvalText)
                                            <span class="d-inline-flex align-items-center gap-1 fw-semibold approval-status-dot">
                                                <span class="dot" style="background-color: var(--approval-color-{{ $approvalStatusKey }});"></span>
                                                <span>{{ $approvalText }}</span>
                                            </span>
                                        @endif

                                        <style>
                                            /* จุดสีสถานะขนาดเล็ก */
                                            .approval-status-dot .dot {
                                                width: .65rem;
                                                height: .65rem;
                                                border-radius: 50%;
                                                display: inline-block;
                                                box-shadow: 0 0 0 1px rgba(0, 0, 0, .15);
                                            }

                                            /* กำหนดสีจาก config/tmc.php -> approval_card_bg */
                                            :root {
                                                --approval-color-pending: {{ config('tmc.approval_card_bg.pending', '#00c4ff') }};
                                                --approval-color-reviewing: {{ config('tmc.approval_card_bg.reviewing', '#00B8A9') }};
                                                --approval-color-returned: {{ config('tmc.approval_card_bg.returned', '#FEB019') }};
                                                --approval-color-approved: {{ config('tmc.approval_card_bg.approved', '#00E396') }};
                                                --approval-color-rejected: {{ config('tmc.approval_card_bg.rejected', '#FF4560') }};
                                                --approval-color-no_form: {{ config('tmc.approval_card_bg.no_form', '#C5CAE9') }};
                                            }
                                        </style>


                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="text-md-end mt-2 mt-md-0">
                            <div class="small text-muted">รอบการประเมิน</div>
                            <div class="fw-semibold">{{ $yearRoundText }}</div>
                        </div>
                    </div>

                    {{-- body summary --}}
                    {{-- <div class="bg-body-tertiary rounded-3 border p-3">
                        @include('backend.self_assessment_service_unit_level._summary', ['row' => $summaryRow])
                    </div> --}}
                </div>
            </div>

            {{-- CARD: ข้อมูลหน่วยบริการ / เวลาทำการ --}}
            <div class="card border-0 shadow-sm flex-fill">
                <div class="card-header border-0 bg-transparent pb-0">
                    <div class="d-flex align-items-start gap-2">
                        <div class="avtar avtar-s bg-light-primary flex-shrink-0">
                            <i class="ph-duotone ph-hospital f-20"></i>
                        </div>
                        <div>
                            <h5 class="mb-1 fw-semibold">ข้อมูลหน่วยบริการ</h5>
                            <div class="text-muted small">รายละเอียดติดต่อ / พิกัด / เวลาทำการ</div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-3 p-lg-4">
                    {{-- GRID info 2 คอลัมน์ --}}
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <div class="small text-muted mb-1">สังกัด</div>
                            <div class="fw-semibold">
                                {{ $unit->org_affiliation ?? '-' }}
                                @if (($unit->org_affiliation ?? '') === 'อื่น ๆ' && !empty($unit->org_affiliation_other))
                                    ({{ $unit->org_affiliation_other }})
                                @endif
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="small text-muted mb-1">โทรศัพท์</div>
                            <div class="fw-semibold">{{ $unit->org_tel ?? '-' }}</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="small text-muted mb-1">ที่อยู่</div>
                            <div class="fw-semibold">{{ $unit->org_address ?? '-' }}</div>
                            <div class="text-muted small">
                                จ.{{ $provinceTitle ?? '-' }}
                                {{ $districtLabel }}{{ $districtTitle ?? '-' }}
                                {{ $subdistrictLabel }}{{ $subdistrictTitle ?? '-' }}
                                {{ $unit->org_postcode ?? '' }}
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="small text-muted mb-1">พิกัด (Lat, Lng)</div>
                            <div class="fw-semibold d-flex flex-wrap align-items-center gap-2">
                                @if ($unit->org_lat && $unit->org_lng)
                                    <span>{{ $unit->org_lat }}, {{ $unit->org_lng }}</span>
                                    <a class="btn btn-light btn-sm border d-inline-flex align-items-center gap-1" target="_blank" href="https://www.google.com/maps?q={{ $unit->org_lat }},{{ $unit->org_lng }}">
                                        <i class="ti ti-external-link"></i>
                                        <span class="small">เปิดแผนที่</span>
                                    </a>
                                @else
                                    <span>-</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <hr class="text-muted opacity-25">

                    {{-- เวลาทำการ --}}
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="avtar avtar-s bg-light-success flex-shrink-0">
                            <i class="ti ti-clock-hour-4 f-20"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">วัน-เวลาทำการ</div>
                            <div class="text-muted small">ช่วงเวลาที่หน่วยบริการเปิดให้บริการ</div>
                        </div>
                    </div>

                    <div class="working-hours-table">
                        {!! renderWorkingHoursTable($unit->org_working_hours_json ?? null) !!}
                    </div>
                </div>
            </div>
        </div>

        {{-- ====== คอลัมน์ขวา ====== --}}
        <div class="col-12 col-xl-4 d-flex">
            @if ($levelCode && $asulId)
                <div class="card border-0 shadow-sm flex-fill w-100">
                    <div class="card-header border-0 bg-transparent pb-0">
                        <div class="d-flex align-items-start gap-2">
                            <div class="avtar avtar-s bg-light flex-shrink-0">
                                <i class="ph-duotone ph-sliders-horizontal f-20"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1 fw-semibold">{{ $cardTitle }}</h5>
                                <div class="text-muted small">
                                    เปิด/ปิดบริการที่จะแสดงบนหน้าเว็บประชาชน
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-3 p-lg-4">
                        <div class="alert alert-info py-2 px-3 d-flex align-items-start gap-2 small">
                            <i class="ph-duotone ph-info mt-1 fs-5"></i>
                            <div>
                                โปรดเปิด–ปิดบริการให้ตรงกับงานที่หน่วยบริการดำเนินการจริง
                                การตั้งค่านี้จะแสดงบนหน้าเว็บไซต์ของหน่วยบริการทันที
                            </div>
                        </div>

                        <div class="vstack gap-3">
                            @forelse($services as $svc)
                                <div class="d-flex align-items-start gap-2">
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input js-svc-toggle" type="checkbox" @checked($svc->resolved_enabled) data-url="{{ $toggleUrl }}" data-svc-id="{{ $svc->id }}">
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $svc->name }}</div>
                                        @if ($svc->description)
                                            <div class="small text-muted">{{ $svc->description }}</div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted small">ยังไม่มีการกำหนดบริการสำหรับระดับนี้</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @else
                {{-- ถ้ายังไม่มี level/อนุมัติ --}}
                <div class="card border-0 shadow-sm flex-fill w-100">
                    <div class="card-body p-4 text-center text-muted">
                        <i class="ti ti-alert-circle fs-1 d-block mb-2 text-warning"></i>
                        <div class="fw-semibold">ยังไม่มีการอนุมัติระดับหน่วยบริการ</div>
                        <div class="small">
                            ระบบจะแสดงเมนูตั้งค่าบริการหลังจากได้รับการประเมินและอนุมัติ
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- แถวล่าง: แผนที่หน่วยบริการ (เต็มความกว้าง) --}}
    {{-- ========================================================= --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 bg-transparent d-flex flex-wrap justify-content-between align-items-start align-items-md-center gap-2">
                    <div class="d-flex align-items-start gap-2">
                        <div class="avtar avtar-s bg-light-danger flex-shrink-0">
                            <i class="ph-duotone ph-map-pin f-20"></i>
                        </div>
                        <div>
                            <h5 class="mb-1 fw-semibold">แผนที่หน่วยบริการสุขภาพผู้เดินทาง</h5>
                            <div class="text-muted small">
                                ระบุตำแหน่งหน่วยบริการในจังหวัด / อำเภอของคุณ
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        {{-- ปุ่ม fullscreen กดแทน control ก็ได้ --}}
                        <button type="button" class="btn btn-light btn-sm border d-inline-flex align-items-center gap-1" id="btnMapFullscreen">
                            <i class="ti ti-arrows-maximize"></i>
                            <span class="small">เต็มจอ</span>
                        </button>

                        @if ($unit->org_lat && $unit->org_lng)
                            <a class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1" target="_blank" href="https://www.google.com/maps?q={{ $unit->org_lat }},{{ $unit->org_lng }}">
                                <i class="ti ti-external-link"></i>
                                <span class="small">เปิดใน Google Maps</span>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="rounded-bottom border-top position-relative">
                        <div id="map" class="tmc-unit-map"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('styles')
    {{-- Leaflet CSS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin />

    {{-- Fullscreen plugin CSS --}}
    <link rel="stylesheet" href="https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/leaflet.fullscreen.css" />

    <style>
        /* map size */
        .tmc-unit-map {
            min-height: 360px;
            height: 560px;
            width: 100%;
            border-radius: 0 0 .5rem .5rem;
        }

        /* avatar style (soft rounded square) */
        .avatar {
            flex-shrink: 0;
            border-radius: .75rem !important;
        }

        /* working hours table tune */
        .working-hours-table table {
            width: 100%;
        }

        .working-hours-table th,
        .working-hours-table td {
            font-size: .875rem;
            vertical-align: top;
            padding: .25rem .5rem;
        }

        .working-hours-table th {
            white-space: nowrap;
            color: var(--bs-secondary-color);
            font-weight: 500;
        }

        /* subtle bg helpers in case theme ไม่มี */
        .bg-primary-subtle {
            background-color: rgba(var(--bs-primary-rgb), .08) !important;
        }

        .bg-info-subtle {
            background-color: rgba(var(--bs-info-rgb), .08) !important;
        }

        .bg-success-subtle {
            background-color: rgba(var(--bs-success-rgb), .08) !important;
        }

        .bg-warning-subtle {
            background-color: rgba(var(--bs-warning-rgb), .08) !important;
        }

        .text-primary {
            color: var(--bs-primary) !important;
        }

        .text-info {
            color: var(--bs-info) !important;
        }

        .text-success {
            color: var(--bs-success) !important;
        }

        .text-warning {
            color: var(--bs-warning) !important;
        }

        /* style ปุ่ม fullscreen plugin บน map */
        .leaflet-control-fullscreen {
            background: #fff;
            border: 1px solid rgba(0, 0, 0, .15);
            border-radius: .375rem;
            box-shadow: 0 .25rem .5rem rgba(0, 0, 0, .08);
            overflow: hidden;
        }

        .leaflet-control-fullscreen a {
            display: block;
            width: 36px;
            height: 36px;
            line-height: 36px;
            text-align: center;
            color: #212529;
            text-decoration: none;
        }

        .leaflet-control-fullscreen a:hover {
            background: rgba(13, 110, 253, .08);
            color: #0d6efd;
        }

        /* ===== label bubble ใต้หมุด ===== */
        .facility-label {
            transition: opacity .2s ease;
        }

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

        /* popup style */
        .tmc-pop {
            font-size: 1rem;
            line-height: 1.45;
            color: #212529;
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

        .tmc-pop .lv-head {
            display: flex;
            align-items: center;
            gap: .5rem;
            line-height: 1.2;
            margin-bottom: .5rem;
        }

        .tmc-pop .lv-dot {
            width: .8rem;
            height: .8rem;
            border-radius: 50%;
            box-shadow: 0 0 0 2px #fff, 0 0 4px rgba(0, 0, 0, .2);
            flex-shrink: 0;
        }

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

        /* EXPORT dropdown floating บน map */
        .tmc-export {
            background: #fff;
            border-radius: .5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .12);
            overflow: hidden;
            font-size: .9rem;
            line-height: 1.1;
            min-width: 180px;
        }

        .tmc-export .btn {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .45rem .75rem;
            color: #333;
            text-decoration: none;
            white-space: nowrap;
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

        .leaflet-control.tmc-export-toggle a {
            position: relative;
            display: block;
            width: 30px;
            height: 30px;
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .08);
        }

        .leaflet-control.tmc-export-toggle a::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 16px;
            height: 2px;
            background: #000;
            border-radius: 2px;
            box-shadow: 0 -5px 0 #000, 0 5px 0 #000;
        }

        .leaflet-top.leaflet-left .tmc-export-toggle {
            margin-top: .25rem;
        }
    </style>
@endpush


@push('scripts')
    {{-- Leaflet core --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    {{-- Fullscreen plugin --}}
    <script src="https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/Leaflet.fullscreen.min.js"></script>

    {{-- Screenshoter for PNG export --}}
    <script src="https://unpkg.com/leaflet-simple-map-screenshoter"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // -------------------------
            // 1) Data from PHP
            // -------------------------
            const lat = Number(@json($unit->org_lat ?? 0));
            const lng = Number(@json($unit->org_lng ?? 0));
            const hasCoord = isFinite(lat) && isFinite(lng) && (lat !== 0 || lng !== 0);

            const pinColor = @json($pinColor);
            const popupName = @json($unit->org_name ?? '-');
            const popupAddress = @json($unit->org_address ?? '');
            const popupProvince = @json($provinceTitle ?? '-');
            const popupDistrict = @json($districtTitle ?? '-');
            const popupSubdistrict = @json($subdistrictTitle ?? '-');
            const popupDistrictLabel = @json($districtLabel);
            const popupSubdistrictLabel = @json($subdistrictLabel);
            const popupContactLine = @json($contactLine ?? '');
            const popupServicesHtml = @json($popupServicesHtml ?? '');
            const levelTextDisplay = @json($levelText); // เช่น "ระดับพื้นฐาน"
            const levelKey = @json($levelKey);

            // CSV meta
            const approvedText = @json($approvedText); // "อนุมัติ" / "รอดำเนินการ"
            const servicesCsv = @json($servicesCsv); // ชื่อบริการคั่นด้วย ' | '

            // -------------------------
            // 2) Init Leaflet map
            // -------------------------
            const mapEl = document.getElementById('map');
            const map = L.map(mapEl, {
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
            });

            // tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            // -------------------------
            // 3) Helpers
            // -------------------------
            function esc(s) {
                return String(s ?? '').replace(/[&<>"']/g, m => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                } [m]));
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

            function buildPopupHTML() {
                const addrHtml = popupAddress ?
                    `<p class="mb-1"><span class="text-muted">ที่อยู่:</span> ${esc(popupAddress)}</p>` :
                    '';

                const geoHtml = `
                    <p class="mb-2">
                        <span class="text-muted">จังหวัด:</span> ${esc(popupProvince || '-')}
                        <span class="ms-2 text-muted">${esc(popupDistrictLabel)}:</span> ${esc(popupDistrict || '-')}
                        <span class="ms-2 text-muted">${esc(popupSubdistrictLabel)}:</span> ${esc(popupSubdistrict || '-')}
                    </p>`;

                const contactHtml = popupContactLine ?
                    `<p class="text-muted small mt-2 mb-2"><strong>ติดต่อ:</strong> ${esc(popupContactLine)}</p>` :
                    '';

                const levelMetaHtml = (levelKey === 'unassessed') ?
                    `<div class="lv-meta">ยังไม่ได้ประเมิน</div>` :
                    `<div class="lv-meta">${esc(levelTextDisplay)}</div>`;

                return `
                <div class="tmc-pop" style="min-width:260px; max-width:320px;">
                    <div class="lv-head">
                        <span class="lv-dot" style="background:${pinColor}"></span>
                        <div>
                            <div class="lv-name">${esc(popupName)}</div>
                            ${levelMetaHtml}
                        </div>
                    </div>

                    ${addrHtml}
                    ${geoHtml}

                    ${popupServicesHtml || ''}

                    ${contactHtml}

                    <button type="button" class="btn btn-primary btn-sm w-100" disabled>
                        <i class="ph-duotone ph-paper-plane-tilt me-1"></i> ส่งข้อความ
                    </button>
                </div>`;
            }

            // -------------------------
            // 4) Marker + label bubble
            // -------------------------
            if (hasCoord) {
                const mk = L.marker([lat, lng], {
                    icon: coloredPinClassic(pinColor)
                }).addTo(map);

                const lbl = L.marker([lat, lng], {
                    interactive: true,
                    icon: L.divIcon({
                        className: 'facility-label',
                        html: `
                            <div class="flabel" style="--c:${pinColor}">
                                <span class="dot"></span>
                                <span class="name">${esc(popupName)}</span>
                            </div>
                        `,
                        iconSize: null,
                        iconAnchor: [-6, 18]
                    }),
                    zIndexOffset: 1000
                }).addTo(map);

                mk.bindPopup(buildPopupHTML(), {
                    maxWidth: 480,
                    autoPan: false // จะ pan เองเพื่อ offset popup
                });

                function focusMarkerWithPopup() {
                    mk.openPopup();
                    // ขยับ center ลง เพื่อให้ popup ไม่บัง
                    setTimeout(() => {
                        const ll = mk.getLatLng();
                        const offset = L.point(0, 170);
                        panToOffset(ll, offset);
                    }, 0);
                }

                lbl.on('click', () => {
                    focusMarkerWithPopup();
                });

                mk.on('click', () => {
                    focusMarkerWithPopup();
                });

                // เริ่มต้น
                map.setView([lat, lng], 15);

                // เปิด popup อัตโนมัติรอบแรก
                setTimeout(() => {
                    focusMarkerWithPopup();
                }, 400);
            } else {
                // ไม่มีพิกัด -> zoom Thailand
                map.setView([13.736717, 100.523186], 6);
            }

            // -------------------------
            // 5) Export Controls (PNG / GeoJSON / CSV)
            // -------------------------
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

            function downloadCSVWithBOM(text, filename) {
                const BOM = '\uFEFF';
                const blob = new Blob([BOM, text], {
                    type: 'text/csv;charset=utf-8'
                });
                downloadBlob(blob, filename);
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
                        const stamp = `${today.getFullYear()}${String(today.getMonth() + 1).padStart(2, '0')}${String(today.getDate()).padStart(2, '0')}`;

                        if (act === 'png' && screenshoter) {
                            // PNG export
                            const blob = await screenshoter.takeScreen('blob');
                            downloadBlob(blob, `unit-map_${stamp}.png`);

                        } else if (act === 'geojson') {
                            // GeoJSON export (จุดเดียว)
                            const gj = {
                                type: 'FeatureCollection',
                                features: [{
                                    type: 'Feature',
                                    geometry: {
                                        type: 'Point',
                                        coordinates: [{{ $unit->org_lng ?? 0 }}, {{ $unit->org_lat ?? 0 }}]
                                    },
                                    properties: {
                                        name: @json($unit->org_name ?? '-'),
                                        level: @json($levelText ?? '-'),
                                        approved: @json($approvedText),
                                        province: @json($provinceTitle ?? '-'),
                                        district: @json($districtTitle ?? '-'),
                                        subdistrict: @json($subdistrictTitle ?? '-'),
                                        lat: @json($unit->org_lat ?? ''),
                                        lon: @json($unit->org_lng ?? ''),
                                        tel: @json($unit->org_tel ?? ''),
                                        email: @json($unit->org_email ?? ''),
                                        services: servicesCsv
                                    }
                                }]
                            };

                            const blob = new Blob([JSON.stringify(gj, null, 2)], {
                                type: 'application/geo+json;charset=utf-8'
                            });
                            downloadBlob(blob, `unit_${stamp}.geojson`);

                        } else if (act === 'csv') {
                            // CSV export
                            const csvHeader = [
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

                            function q(v) {
                                return `"${String(v ?? '').replace(/"/g, '""')}"`;
                            }

                            const csvRow = [
                                @json($unit->org_name ?? '-'),
                                @json($levelText ?? '-'),
                                approvedText,
                                @json($provinceTitle ?? '-'),
                                @json($districtTitle ?? '-'),
                                @json($subdistrictTitle ?? '-'),
                                @json($unit->org_lat ?? ''),
                                @json($unit->org_lng ?? ''),
                                @json($unit->org_tel ?? ''),
                                @json($unit->org_email ?? ''),
                                servicesCsv
                            ];

                            const csvText =
                                csvHeader.map(q).join(',') + '\r\n' +
                                csvRow.map(q).join(',');

                            downloadCSVWithBOM(csvText, `unit_${stamp}.csv`);
                        }

                        // hide menu after click
                        div.classList.add('d-none');
                    });

                    return div;
                }
            });

            const toggle = new ExportToggle().addTo(map);
            const menu = new ExportMenu().addTo(map);

            // click map -> hide dropdown
            map.on('click', () => menu.getContainer().classList.add('d-none'));

            // -------------------------
            // 6) fullscreen button on card header
            // -------------------------
            const btnFs = document.getElementById('btnMapFullscreen');
            if (btnFs) {
                btnFs.addEventListener('click', () => {
                    if (map.toggleFullscreen) {
                        map.toggleFullscreen();
                    }
                });
            }

            // -------------------------
            // 7) resize -> invalidate map
            // -------------------------
            window.addEventListener('resize', () => {
                setTimeout(() => map.invalidateSize(), 200);
            });
        });
    </script>

    {{-- Toggle service AJAX --}}
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
