{{-- resources/views/backend/dashboard/unit.blade.php --}}

@extends('layouts.main')

@section('title', 'รายงานหน่วยบริการ')
@section('breadcrumb-item', 'Backend')
@section('breadcrumb-item-active', 'รายงานหน่วยบริการ')

@section('content')
    @include('backend.dashboard._filter')

    <div class="row g-3">
        {{-- ===== ข้อมูลหน่วยบริการ ===== --}}
        <div class="col-12 col-xl-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="ph-duotone ph-hospital"></i> ข้อมูลหน่วยบริการ</span>
                    {{-- @if (!empty($unit->asul_id))
                        <a href="{{ route('backend.self-assessment-service-unit-level.export-pdf', $unit->asul_id) }}" target="_blank" class="btn btn-danger btn-sm">
                            <i class="ph-duotone ph-download-simple me-1"></i> ดาวน์โหลดผลการประเมิน
                        </a>
                    @endif --}}
                </div>
                <div class="card-body">
                    {{-- สรุประดับ + ปีงบ + รอบ --}}
                    @php
                        $summaryRow = (object) [
                            'serviceUnit' => (object) ['org_name' => $unit->org_name],
                            'level' => $unit->level_code,
                            'assess_year' => (int) ($filterYear ?? fiscalYearCE()),
                            'assess_round' => (int) ($filterRound ?? fiscalRound()),
                        ];
                        $yearBE = ($summaryRow->assess_year ?? date('Y')) + 543;
                    @endphp

                    @include('backend.self_assessment_service_unit_level._summary', [
                        'row' => $summaryRow,
                        'yearBE' => $yearBE,
                        'form' => $form ?? null,
                        'components' => $components ?? [],
                    ])

                    {{-- ===== ข้อมูลหน่วยงานแบบละเอียด ===== --}}
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

        {{-- ===== สรุป มี/ไม่มี (GAP) ===== --}}
        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header">สรุป มี/ไม่มี (GAP)</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="card stat-card border-start border-success border-4">
                                <div class="card-body text-center">
                                    <div class="text-muted">มี</div>
                                    <div class="h2 text-success">{{ (int) ($unitBool->haves ?? 0) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card stat-card border-start border-danger border-4">
                                <div class="card-body text-center">
                                    <div class="text-muted">ไม่มี (GAP)</div>
                                    <div class="h2 text-danger">{{ (int) ($unitBool->gaps ?? 0) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="fw-semibold mb-2">รายการ GAP ของหน่วยนี้</div>
                    <div class="list-group">
                        @forelse($unitGaps as $g)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-truncate" style="max-width: 80%">{{ $g->gap_label }}</span>
                                <span class="badge text-bg-danger">× {{ $g->gap_count }}</span>
                            </div>
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
