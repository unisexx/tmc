{{-- resources/views/backend/dashboard/unit.blade.php --}}

@extends('layouts.main')

@section('title', 'รายงานหน่วยบริการ')
@section('breadcrumb-item', 'Backend')
@section('breadcrumb-item-active', 'รายงานหน่วยบริการ')

@section('content')
    @include('backend.dashboard._filter')

    @php
        /*
        |--------------------------------------------------------------------------
        | สรุปแนวคิด: ย้ายทุกอย่างที่เคย setAttribute ใน Controller มา “คำนวณที่นี่”
        | - form_id    => มาจาก $form?->id
        | - level      => มาจาก $asul?->level
        | - approval_status => มาจาก $asul?->approval_status
        | - asul_id    => มาจาก $asul?->id
        | - province_title / district_title / subdistrict_title => จาก relation ที่ eager loaded
        |--------------------------------------------------------------------------
        */

        // ดึง 1 เรคคอร์ดล่าสุดของแบบประเมิน และระดับหน่วย จากความสัมพันธ์ที่โหลดมาแล้ว
        $form = optional($unit->assessmentForms->first()); // แทน $unit->getAttribute('form_id')
        $asul = optional($unit->serviceUnitLevels->first()); // แทน $unit->getAttribute('level', 'approval_status', 'asul_id')

        // อ่านชื่อจังหวัด/อำเภอ/ตำบลจากความสัมพันธ์ (แทน province_title ฯลฯ)
        $provinceTitle = $unit->province->title ?? null;
        $districtTitle = $unit->district->title ?? null;
        $subdistrictTitle = $unit->subdistrict->title ?? null;

        // ค่าที่ใช้ในสรุปผลด้านบนการ์ด
        $summaryRow = (object) [
            'serviceUnit' => (object) ['org_name' => $unit->org_name],
            'level' => $asul?->level, // เดิม $unit->level
            'assess_year' => (int) ($filterYear ?? fiscalYearCE()),
            'assess_round' => (int) ($filterRound ?? fiscalRound()),
            // 'approval_status' ไม่ได้ใช้ใน _summary โดยตรง แต่ส่งผ่านแยกเป็น $approvalStatus ด้านล่าง
        ];
        $yearBE = ($summaryRow->assess_year ?? date('Y')) + 543;

        // ใช้ซ้ำหลายจุด
        $formId = $form?->id; // เดิม $unit->form_id
        $levelCode = $asul?->level; // เดิม $unit->level
        $approvalStatus = $asul?->approval_status; // เดิม $unit->approval_status
        $asulId = $asul?->id; // เดิม $unit->asul_id
    @endphp

    {{-- ===== การ์ดหลัก: สรุปผลการประเมิน (เต็มกว้าง) + แถวซ้าย/ขวาในใบเดียวกัน ===== --}}
    <div class="row g-3">
        <div class="col-12">
            <div class="card border-2 border-primary-subtle">
                <div class="card-body p-3 p-lg-4">

                    {{-- ===== สรุปผลการประเมิน (เต็มความกว้างบนสุด) ===== --}}
                    @include('backend.self_assessment_service_unit_level._summary', [
                        'row' => $summaryRow,
                        'yearBE' => $yearBE,
                        'form' => $form ?? null, // เปลี่ยนจากเดิมที่ส่ง $form ใน Controller
                        'approvalStatus' => $approvalStatus, // เดิม $unit->approval_status
                    ])

                    <hr class="my-4">

                    {{-- ===== แถวเดียวกัน: ซ้ายข้อมูลหน่วยบริการ + ขวาการตั้งค่าหน้าบ้าน ===== --}}
                    <div class="row g-3 align-items-stretch">
                        {{-- ซ้าย: ข้อมูลหน่วยบริการ --}}
                        <div class="col-12 col-xl-8 d-flex">
                            <div class="border rounded p-3 flex-fill h-100">
                                <div class="mb-3 d-flex align-items-center gap-2">
                                    <i class="ph-duotone ph-hospital"></i>
                                    <span class="fw-semibold">ข้อมูลหน่วยบริการ</span>
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-6">
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
                                        <div class="mb-2">
                                            <strong>พิกัด:</strong>
                                            @if ($unit->org_lat && $unit->org_lng)
                                                {{ $unit->org_lat }}, {{ $unit->org_lng }}
                                                <a class="ms-2 small" target="_blank" href="https://www.google.com/maps?q={{ $unit->org_lat }},{{ $unit->org_lng }}">
                                                    เปิดแผนที่
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </div>
                                        @if ($unit->org_lat && $unit->org_lng)
                                            <div id="unit-mini-map" class="rounded border" style="height:220px;"></div>
                                        @endif
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-2"><strong>วัน-เวลาทำการ:</strong></div>
                                        {!! renderWorkingHoursTable($unit->org_working_hours_json ?? null) !!}
                                    </div>
                                </div>

                                <div class="map-wrap mt-3">
                                    <div id="map"></div>
                                </div>
                            </div>
                        </div>

                        {{-- การตั้งค่าการแสดงผลหน้าบ้าน (แคบลง) --}}
                        <div class="col-12 col-xl-4 d-flex">
                            @php
                                use App\Models\StHealthService;
                                use App\Models\AssessmentServiceConfig;

                                // เดิมอ่านจาก $unit->level และ $unit->asul_id ที่ setAttribute ใน Controller
                                // ปรับมาอ่านจาก $levelCode และ $asulId ที่คำนวณด้านบนแทน
                                $services = collect();
                                if ($levelCode && $asulId) {
                                    $base = StHealthService::active()->forLevel($levelCode)->orderBy('ordering')->orderBy('id')->get();
                                    $pivot = AssessmentServiceConfig::where('assessment_service_unit_level_id', $asulId)->pluck('is_enabled', 'st_health_service_id');

                                    $services = $base->map(function ($svc) use ($pivot) {
                                        $svc->resolved_enabled = $pivot->has($svc->id) ? (bool) $pivot[$svc->id] : (bool) $svc->default_enabled;
                                        return $svc;
                                    });
                                }

                                $levelMap = [
                                    'basic' => 'หน่วยบริการระดับพื้นฐาน',
                                    'medium' => 'หน่วยบริการระดับกลาง',
                                    'advanced' => 'หน่วยบริการระดับสูง',
                                ];
                                $cardTitle = $levelMap[$levelCode] ?? 'การตั้งค่าการแสดงผลหน้าบ้าน';

                                // ใช้ URL จริงตั้งแต่ฝั่ง Blade
                                $toggleUrl = $levelCode && $asulId ? route('backend.assessment-service-configs.services.toggle', $asulId) : null;
                            @endphp

                            @if ($levelCode && $asulId)
                                <div class="border rounded p-3 flex-fill h-100 border-2 border-primary-subtle">
                                    <div class="mb-3 d-flex align-items-center gap-2">
                                        <i class="ph-duotone ph-sliders-horizontal"></i>
                                        <span class="fw-semibold">{{ $cardTitle }}</span>
                                    </div>

                                    {{-- คำอธิบายการตั้งค่าบริการที่จะแสดงหน้าบ้าน --}}
                                    <div class="alert alert-info py-2 d-flex align-items-start gap-2">
                                        <i class="ph-duotone ph-info mt-1"></i>
                                        <div>
                                            โปรดเปิด–ปิดบริการให้ตรงกับงานที่หน่วยบริการดำเนินการจริง การตั้งค่านี้จะแสดงบนหน้าเว็บไซต์ของหน่วยบริการทันที
                                            {{-- ตารางปลายทาง: assessment_service_configs (อัปเดตเมื่อสลับสวิตช์) --}}
                                        </div>
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
                                            <div class="col-12">
                                                <div class="text-muted">ยังไม่มีการกำหนดบริการสำหรับระดับนี้</div>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    {{-- ===== จบแถวภายในการ์ดเดียวกัน ===== --}}

                </div>
            </div>
        </div>
    </div>

    {{-- ===== เริ่มแถวใหม่: รายการ GAP ของหน่วยนี้ ===== --}}
    {{-- @php
        // เดิมเคยคอมเมนต์ปิดไว้ นำกลับมาใช้งาน
        $gaps = collect($unitGaps ?? [])->filter(fn($x) => (int) ($x->gap_count ?? 0) > 0);
        $gapCount = $gaps->count();
    @endphp
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>รายการ GAP ของหน่วยนี้</span>
                    <span class="badge bg-danger-subtle text-danger fw-semibold">{{ $gapCount }}</span>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @forelse ($gaps as $g)
                            <div class="list-group-item">{{ $g->gap_label }}</div>
                        @empty
                            <div class="text-muted">ไม่มี GAP</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

    {{-- ===== ตารางองค์ประกอบ 1–6 + แผนพัฒนา ===== --}}
    {{-- <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">องค์ประกอบของหน่วยบริการนี้</div>
                <div class="card-body">
                    @include('backend.self_assessment_service_unit_level._summary_table', [
                        'components' => $components ?? [],
                        'form'       => $form ?? null,         // ใช้ $form จากด้านบน (แทนเดิมที่ Controller set)
                        'row'        => (object) ['id' => $asulId], // เดิม $unit->asul_id
                    ])
                </div>
            </div>
        </div>
    </div> --}}
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // แผนที่หลัก + mini map
            const lat = Number(@json($unit->org_lat ?? 0));
            const lng = Number(@json($unit->org_lng ?? 0));
            const hasCoord = isFinite(lat) && isFinite(lng) && (lat !== 0 || lng !== 0);
            const fallback = {
                lat: 13.7563,
                lng: 100.5018,
                zoom: 6
            };

            const map = L.map('map', {
                scrollWheelZoom: false
            });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            if (hasCoord) {
                L.marker([lat, lng]).addTo(map).bindPopup(@json($unit->org_name));
                map.setView([lat, lng], 16);
            } else {
                map.setView([fallback.lat, fallback.lng], fallback.zoom);
            }
            setTimeout(() => map.invalidateSize(), 200);

            const mini = document.getElementById('unit-mini-map');
            if (mini && hasCoord) {
                const miniMap = L.map(mini, {
                    scrollWheelZoom: false,
                    zoomControl: true
                });
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(miniMap);
                L.marker([lat, lng]).addTo(miniMap);
                miniMap.setView([lat, lng], 14);
                setTimeout(() => miniMap.invalidateSize(), 300);
            }
        });
    </script>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle สวิตช์บริการหน้าบ้าน
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
                        el.checked = !enabled; // rollback
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
