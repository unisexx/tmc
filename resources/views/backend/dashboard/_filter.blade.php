{{-- resources/views/backend/dashboard/_filter.blade.php --}}
@php
    $yearCE = $yearCE ?? fiscalYearCE();
    $roundNow = $roundNow ?? fiscalRound();
    $yearOpts = $yearOpts ?? fiscalYearOptionsBE(5);
    $filterYear = (int) ($filterYear ?? request('year', $yearCE));
    $filterRound = (int) ($filterRound ?? request('round', $roundNow));

    $regionId = $regionId ?? request('region');
    $provinceCode = $provinceCode ?? request('province_code');
    $serviceUnitId = $serviceUnitId ?? request('service_unit_id');
@endphp

<form method="GET" class="card mb-3 filter-bar">
    <div class="card-body">
        <div class="row gx-2 gy-2 align-items-stretch">

            {{-- ปีงบประมาณ --}}
            <div class="col-12 col-sm-6 col-lg-auto">
                <div class="input-group w-100">
                    <span class="input-group-text">ปีงบประมาณ</span>
                    <select name="year" class="form-select">
                        @foreach ($yearOpts as $y)
                            <option value="{{ $y['ce'] }}" @selected($filterYear === (int) $y['ce'])>
                                {{ $y['be'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- รอบ --}}
            <div class="col-12 col-sm-6 col-lg-auto">
                <div class="input-group w-100">
                    <span class="input-group-text">รอบ</span>
                    <select name="round" class="form-select">
                        <option value="1" @selected($filterRound === 1)>รอบที่ 1 (ต.ค. – มี.ค.)</option>
                        <option value="2" @selected($filterRound === 2)>รอบที่ 2 (เม.ย. – ก.ย.)</option>
                    </select>
                </div>
            </div>

            {{-- สคร. --}}
            <div class="col-12 col-sm-6 col-lg-auto">
                <div class="input-group w-100">
                    <span class="input-group-text">สคร.</span>
                    <select id="filter-region" name="region" class="form-select">
                        <option value="">ทุกเขต</option>
                        @foreach ($regions as $r)
                            <option value="{{ $r->id }}" @selected((string) $regionId === (string) $r->id)>
                                {{ $r->short_title }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- จังหวัด --}}
            <div class="col-12 col-sm-6 col-lg-auto">
                <div class="input-group w-100">
                    <span class="input-group-text">จังหวัด</span>
                    <select id="filter-province" name="province_code" class="form-select" data-selected="{{ $provinceCode ?? '' }}">
                        <option value="">ทุกจังหวัด</option>
                        @isset($provinces)
                            @foreach ($provinces as $p)
                                <option value="{{ $p->code }}" @selected((string) $provinceCode === (string) $p->code)>
                                    {{ $p->title }}
                                </option>
                            @endforeach
                        @endisset
                    </select>
                </div>
            </div>

            {{-- หน่วยบริการ --}}
            <div class="col-12 col-lg">
                <div class="input-group w-100">
                    <span class="input-group-text">หน่วยบริการ</span>
                    <select id="filter-service-unit" name="service_unit_id" class="form-select" data-selected="{{ $serviceUnitId ?? '' }}">
                        <option value="">ทุกหน่วยบริการ</option>
                    </select>
                </div>
            </div>

            {{-- ปุ่มค้นหา --}}
            <div class="col-12 col-sm-auto">
                <button class="btn btn-outline-primary w-100">
                    <i class="ph-duotone ph-magnifying-glass"></i> ค้นหา
                </button>
            </div>
        </div>
    </div>
</form>

@push('styles')
    <style>
        .filter-bar .input-group>.form-select {
            min-width: 0;
        }

        @media (max-width: 576.98px) {

            .filter-bar .input-group-text,
            .filter-bar .form-select {
                font-size: .875rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function() {
            const regionEl = document.getElementById('filter-region');
            const provEl = document.getElementById('filter-province');
            const unitEl = document.getElementById('filter-service-unit');

            const selProv = provEl?.dataset.selected || '';
            const selUnit = unitEl?.dataset.selected || '';

            const routes = {
                provinces(regionId) {
                    return `{{ route('ajax.cascade.provinces', ['region' => '___']) }}`
                        .replace('___', encodeURIComponent(regionId ?? 'all'));
                },
                serviceUnits(provCode, regionId) {
                    const url = `{{ route('ajax.cascade.serviceUnits', ['province' => '___']) }}`
                        .replace('___', encodeURIComponent(provCode ?? 'all'));
                    const params = new URLSearchParams();
                    if (regionId && regionId !== 'all') params.set('region', regionId);
                    const s = params.toString();
                    return s ? `${url}?${s}` : url;
                }
            };

            function setLoading(selectEl, isLoading, placeholder = 'กำลังโหลด...') {
                if (!selectEl) return;
                selectEl.disabled = !!isLoading;
                if (isLoading) {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = placeholder;
                    selectEl.replaceChildren(opt);
                }
            }

            function fillOptions(selectEl, items, placeholder) {
                const fr = document.createDocumentFragment();
                const first = document.createElement('option');
                first.value = '';
                first.textContent = placeholder;
                fr.appendChild(first);
                (items || []).forEach(({
                    value,
                    text
                }) => {
                    const o = document.createElement('option');
                    o.value = value;
                    o.textContent = text;
                    fr.appendChild(o);
                });
                selectEl.replaceChildren(fr);
            }

            async function loadProvinces(regionId, keep = true) {
                setLoading(provEl, true);
                try {
                    const res = await fetch(routes.provinces(regionId), {
                        credentials: 'same-origin'
                    });
                    const data = await res.json();
                    fillOptions(provEl, data.items, 'ทุกจังหวัด');
                    if (keep && selProv) provEl.value = selProv;
                } finally {
                    setLoading(provEl, false);
                }
            }

            // เงื่อนไขการแสดงหน่วยบริการ:
            // - ไม่เลือก สคร + ไม่เลือกจังหวัด -> ทั้งหมด
            // - เลือก สคร -> เฉพาะใน สคร นั้น
            // - เลือก จังหวัด -> เฉพาะจังหวัดนั้น (override สคร)
            async function loadServiceUnits(provCode, regionId, keep = true) {
                setLoading(unitEl, true);
                try {
                    const res = await fetch(routes.serviceUnits(provCode || 'all', regionId || 'all'), {
                        credentials: 'same-origin'
                    });
                    const data = await res.json();
                    fillOptions(unitEl, data.items, 'ทุกหน่วยบริการ');
                    if (keep && selUnit) unitEl.value = selUnit;
                } finally {
                    setLoading(unitEl, false);
                }
            }

            regionEl?.addEventListener('change', async e => {
                const rid = e.target.value || 'all';
                await loadProvinces(rid, false);
                const pcode = provEl.value || 'all';
                await loadServiceUnits(pcode, rid, false);
            });

            provEl?.addEventListener('change', async e => {
                const pcode = e.target.value || 'all';
                const rid = regionEl?.value || 'all';
                await loadServiceUnits(pcode, rid, false);
            });

            (async function init() {
                const rid = regionEl?.value || 'all';
                await loadProvinces(rid, true);
                const pcode = provEl?.value || 'all';
                await loadServiceUnits(pcode, rid, true);
            })();
        })();
    </script>
@endpush
