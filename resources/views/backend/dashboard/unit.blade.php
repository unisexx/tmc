{{-- resources/views/backend/dashboard/unit.blade.php --}}

@extends('layouts.main')

@section('title', 'รายงานหน่วยบริการ')
@section('breadcrumb-item', 'Backend')
@section('breadcrumb-item-active', 'รายงานหน่วยบริการ')

@section('content')
    @include('backend.dashboard._filter')

    <div class="row g-3">
        {{-- ===== ข้อมูลหน่วยบริการ (กว้างขึ้น) + การตั้งค่าการแสดงผลหน้าบ้าน (แคบลง) ===== --}}
        <div class="row align-items-stretch g-3">
            {{-- ข้อมูลหน่วยบริการ --}}
            <div class="col-12 col-xl-8 d-flex">
                <div class="card flex-fill h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="ph-duotone ph-hospital"></i> ข้อมูลหน่วยบริการ</span>
                    </div>
                    <div class="card-body">
                        @php
                            $summaryRow = (object) [
                                'serviceUnit' => (object) ['org_name' => $unit->org_name],
                                'level' => $unit->level,
                                'assess_year' => (int) ($filterYear ?? fiscalYearCE()),
                                'assess_round' => (int) ($filterRound ?? fiscalRound()),
                            ];
                            $yearBE = ($summaryRow->assess_year ?? date('Y')) + 543;
                        @endphp
                        @include('backend.self_assessment_service_unit_level._summary', [
                            'row' => $summaryRow,
                            'yearBE' => $yearBE,
                            'form' => $form ?? null,
                            'approvalStatus' => $unit->approval_status,
                        ])

                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
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
                                            จ.{{ $unit->province_title ?? '-' }}
                                            อ.{{ $unit->district_title ?? '-' }}
                                            ต.{{ $unit->subdistrict_title ?? '-' }}
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
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="mb-2"><strong>วัน-เวลาทำการ:</strong></div>
                                    {!! renderWorkingHoursTable($unit->org_working_hours_json ?? null) !!}
                                </div>
                            </div>
                        </div>

                        <div class="map-wrap mt-3">
                            <div id="map"></div>
                        </div>
                    </div>
                </div>
            </div>


            {{-- การตั้งค่าการแสดงผลหน้าบ้าน (แคบลง) --}}
            <div class="col-12 col-xl-4 d-flex">
                @php
                    use App\Models\StHealthService;
                    use App\Models\AssessmentServiceConfig;

                    // อ่านจาก $unit ที่ Controller ใส่ค่าไว้แล้ว
                    $levelCode = $unit->level ?? null; // basic | medium | advanced
                    $levelId = $unit->asul_id ?? null; // assessment_service_unit_levels.id

                    $services = collect();
                    if ($levelCode && $levelId) {
                        $base = StHealthService::active()->forLevel($levelCode)->orderBy('ordering')->orderBy('id')->get();

                        $pivot = AssessmentServiceConfig::where('assessment_service_unit_level_id', $levelId)->pluck('is_enabled', 'st_health_service_id');

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

                    // ใช้ URL จริงตั้งแต่ฝั่ง Blade เพื่อตัดปัญหา GET
                    $toggleUrl = $levelCode && $levelId ? route('backend.assessment-service-configs.services.toggle', $levelId) : null;
                @endphp

                @if ($levelCode && $levelId)
                    <div class="card flex-fill h-100 border-2 border-primary-subtle">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="ph-duotone ph-sliders-horizontal me-1"></i> {{ $cardTitle }}</span>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info py-2 small d-flex align-items-start gap-2">
                                <i class="ph-duotone ph-info mt-1"></i>
                                <div>
                                    การตั้งค่านี้จะบันทึกลงตาราง <code>assessment_service_configs</code> ตามระดับของหน่วยบริการนี้
                                    การเปลี่ยนแปลงบันทึกอัตโนมัติเมื่อสลับสวิตช์
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
                    </div>
                @endif
            </div>




        </div>



        {{-- ===== เริ่มแถวใหม่: รายการ GAP ของหน่วยนี้ ===== --}}
        <div class="col-12">
            @php
                $gaps = collect($unitGaps ?? [])->filter(fn($x) => (int) ($x->gap_count ?? 0) > 0);
                $gapCount = $gaps->count();
            @endphp

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

        {{-- ===== ตารางองค์ประกอบ 1–6 + แผนพัฒนา ===== --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header">องค์ประกอบของหน่วยบริการนี้</div>
                <div class="card-body">
                    @include('backend.self_assessment_service_unit_level._summary_table', [
                        'components' => $components ?? [],
                        'form' => $form ?? null,
                        'row' => (object) ['id' => $unit->asul_id],
                    ])
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const lat = Number(@json($unit->org_lat ?? 0));
            const lng = Number(@json($unit->org_lng ?? 0));
            const hasCoord = isFinite(lat) && isFinite(lng) && (lat !== 0 || lng !== 0);
            const fallback = {
                lat: 13.7563,
                lng: 100.5018,
                zoom: 6
            };

            // แผนที่หลัก
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

            // mini map
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
                        if (!res.ok) {
                            // 405 = method not allowed, 419 = csrf
                            throw new Error('HTTP ' + res.status);
                        }
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
