@extends('layouts.main')

@section('title', 'แดชบอร์ดหน่วยบริการสุขภาพผู้เดินทาง')
@section('breadcrumb-item', 'Backend')
@section('breadcrumb-item-active', 'แดชบอร์ด')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush


@section('content')
    {{-- ฟอร์มค้นหา: ปีงบประมาณ · รอบการประเมิน · สคร. · จังหวัด --}}
    @php
        $yearCE = fiscalYearCE();
        $roundNow = fiscalRound();
        $yearOpts = fiscalYearOptionsBE(5);
        $filterYear = (int) request('year', $yearCE);
        $filterRound = (int) request('round', $roundNow);

        $regionId = request('region');
        $provinceCode = request('province_code');
    @endphp

    <form method="GET" class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center gap-2">
                {{-- ปีงบประมาณ --}}
                <div class="input-group" style="max-width: 260px;">
                    <span class="input-group-text">ปีงบประมาณ</span>
                    <select name="year" class="form-select">
                        @foreach ($yearOpts as $y)
                            <option value="{{ $y['ce'] }}" @selected($filterYear === (int) $y['ce'])>
                                {{ $y['be'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- รอบการประเมิน --}}
                <div class="input-group" style="max-width: 280px;">
                    <span class="input-group-text">รอบ</span>
                    <select name="round" class="form-select">
                        <option value="1" @selected($filterRound === 1)>รอบที่ 1 (ต.ค. – มี.ค.)</option>
                        <option value="2" @selected($filterRound === 2)>รอบที่ 2 (เม.ย. – ก.ย.)</option>
                    </select>
                </div>

                {{-- สคร. --}}
                <div class="input-group" style="max-width: 260px;">
                    <span class="input-group-text">สคร.</span>
                    <select name="region" class="form-select">
                        <option value="">ทุกเขต</option>
                        @foreach ($regions as $r)
                            <option value="{{ $r->id }}" @selected((string) $regionId === (string) $r->id)>
                                {{ $r->short_title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- จังหวัด --}}
                <div class="input-group" style="max-width: 320px;">
                    <span class="input-group-text">จังหวัด</span>
                    <select name="province_code" class="form-select">
                        <option value="">ทุกจังหวัด</option>
                        @foreach ($provinces as $p)
                            <option value="{{ $p->code }}" @selected((string) $provinceCode === (string) $p->code)>
                                {{ $p->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button class="btn btn-outline-primary" type="submit">
                    <i class="ph-duotone ph-magnifying-glass"></i> ค้นหา
                </button>
            </div>
        </div>
    </form>



    @php
        $total = array_sum($summary) + $notAssessed;
        $pct = fn($v) => $total > 0 ? number_format(($v / $total) * 100, 1) . '%' : '0%';

        $levelBg = config('assessment.level_badge_class');
        $levelText = config('assessment.level_badge_text_color');
    @endphp

    <div class="row g-3 mb-3">
        {{-- ระดับพื้นฐาน --}}
        <div class="col-lg-3 col-md-6">
            <div class="card">
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
            <div class="card">
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
            <div class="card">
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
            <div class="card">
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

    <div class="card mb-3">
        <div class="card-header"><i class="ph-duotone ph-chart-bar"></i> GAP สูงสุดต่อหน่วยบริการ</div>
        <div class="card-body">
            <canvas id="gapChart" height="120"></canvas>
            <div class="table-responsive mt-3">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>ระดับ</th>
                            <th>หน่วยบริการ</th>
                            <th class="text-end">จำนวน GAP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gaps as $g)
                            <tr>
                                <td>
                                    @php
                                        $levelName = match ($g->level_code) {
                                            'basic' => 'พื้นฐาน',
                                            'intermediate', 'medium' => 'กลาง',
                                            'advanced' => 'สูง',
                                            default => '-',
                                        };
                                    @endphp
                                    {{ $levelName }}
                                </td>
                                <td>{{ $g->org_name }}</td>
                                <td class="text-end">{{ $g->gap_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">ไม่พบข้อมูล</td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach (['pending' => 'รอการพิจารณา', 'reviewing' => 'อยู่ระหว่างการพิจารณา', 'approved' => 'อนุมัติแล้ว'] as $key => $label)
            <div class="col-md-4">
                <div class="card border-start border-{{ $key === 'approved' ? 'success' : ($key === 'reviewing' ? 'warning' : 'secondary') }} border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="text-muted">{{ $label }}</div>
                            <span class="badge bg-light text-dark">{{ number_format($status[$key]['count']) }}</span>
                        </div>
                        <ul class="mb-0 mt-2 small">
                            @foreach ($status[$key]['units'] as $u)
                                <li>{{ $u->org_name }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-header">รายงานภาพรวม (กราฟ)</div>
        <div class="card-body">
            <canvas id="overallChart" height="120"></canvas>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">ระดับของหน่วยบริการ</div>
                <div class="card-body">
                    <canvas id="levelChart" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">องค์ประกอบ / ข้อเสนอแนะ</div>
                <div class="card-body">
                    <canvas id="componentChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">คุณสมบัติ(มี) / GAP(ไม่มี)</div>
        <div class="card-body">
            <canvas id="boolChart" height="120"></canvas>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">รายงานภาพรวม (ตาราง) — ตามเขต</div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>เขต</th>
                        <th class="text-end">จำนวนหน่วยบริการ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($regions as $r)
                        @php $rc = ($regionTable[$r->id]->total ?? 0); @endphp
                        <tr>
                            <td>{{ $r->short_title }}</td>
                            <td class="text-end">{{ number_format($rc) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">รายงานภาพรวม (ตาราง) — ตามจังหวัด</div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>จังหวัด</th>
                        <th class="text-end">จำนวนหน่วยบริการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($provinceTable as $p)
                        <tr>
                            <td>{{ $p->title }}</td>
                            <td class="text-end">{{ number_format($p->total) }}</td>
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
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new Chart(document.getElementById('gapChart'), {
                type: 'bar',
                data: {
                    labels: @json($gapChart['labels']),
                    datasets: [{
                        label: 'จำนวนช่องว่าง',
                        data: @json($gapChart['data'])
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            new Chart(document.getElementById('overallChart'), {
                type: 'pie',
                data: @json($overallChart),
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            new Chart(document.getElementById('levelChart'), {
                type: 'doughnut',
                data: @json($levelChart),
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            new Chart(document.getElementById('componentChart'), {
                type: 'bar',
                data: @json($componentChart),
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            new Chart(document.getElementById('boolChart'), {
                type: 'bar',
                data: @json($boolChart),
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        });
    </script>
@endpush


@push('js')
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

                map.flyTo([lat, lng], 13, {
                    animate: true
                });
                map.once('moveend', () => m.openPopup());
            });
        });
    </script>
@endpush
