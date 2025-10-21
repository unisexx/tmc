{{-- resources/views/backend/dashboard/_filter.blade.php --}}
@php
    use App\Models\HealthRegion;

    // ปี/รอบ
    $yearCE = $yearCE ?? fiscalYearCE();
    $roundNow = $roundNow ?? fiscalRound();
    $yearOpts = $yearOpts ?? fiscalYearOptionsBE(5);
    $filterYear = (int) ($filterYear ?? request('year', $yearCE));
    $filterRound = (int) ($filterRound ?? request('round', $roundNow));

    // พารามิเตอร์ที่อาจถูกส่งมาจาก URL
    $regionId = $regionId ?? request('region');
    $provinceCode = $provinceCode ?? request('province_code');
    $serviceUnitId = $serviceUnitId ?? request('service_unit_id');

    // โหลดรายการ “สคร.” ที่นี่ ถ้า Controller ไม่ได้ส่งมา
    $regions =
        $regions ??
        HealthRegion::query()
            ->orderBy('id')
            ->get(['id', 'code', 'title', 'short_title']);

    // ดึง “ระดับหน่วยบริการ” จาก config/tmc.php เฉพาะ 3 ระดับสำหรับฟิลเตอร์
    $levels =
        $levels ??
        collect(config('tmc.assessment.level_text', []))
            ->only(['basic', 'medium', 'advanced'])
            ->values()
            ->all(); // ['ระดับพื้นฐาน','ระดับกลาง','ระดับสูง']

    // ดึง “สังกัด” จาก config/tmc.php
    $affiliations = $affiliations ?? config('tmc.affiliations', []);

@endphp

<form method="GET" class="card mb-3 filter-bar">
    <div class="card-body">
        <div class="row gx-2 gy-2 align-items-stretch">

            {{-- ปีงบประมาณ --}}
            <div class="col-12 col-sm-6 col-lg-auto">
                <div class="input-group w-100">
                    <span class="input-group-text">ปีงบประมาณ</span>
                    <select id="filter-year" name="year" class="form-select">
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
                    <select id="filter-round" name="round" class="form-select">
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

            {{-- ปิดไว้: ระดับหน่วยบริการ (ยังไม่เรียกใช้) --}}
            {{--
            <div class="col-12 col-sm-6 col-lg-auto">
                <div class="input-group w-100">
                    <span class="input-group-text">ระดับ</span>
                    <select name="level" class="form-select">
                        <option value="">ทุกระดับ</option>
                        @foreach ($levels as $lv)
                            <option value="{{ $lv }}" @selected(request('level') === $lv)>{{ $lv }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            --}}

            {{-- ปิดไว้: สังกัด (ยังไม่เรียกใช้) --}}
            {{--
            <div class="col-12 col-sm-6 col-lg-auto">
                <div class="input-group w-100">
                    <span class="input-group-text">สังกัด</span>
                    <select name="affiliation" class="form-select">
                        <option value="">ทั้งหมด</option>
                        @foreach ($affiliations as $a)
                            <option value="{{ $a }}" @selected(request('affiliation') === $a)>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            --}}

            {{-- ปุ่มค้นหา --}}
            <div class="col-12 col-sm-auto">
                <button class="btn btn-outline-primary w-100">
                    <i class="ph-duotone ph-magnifying-glass"></i> ค้นหา
                </button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
    <script>
        (function() {
            // ====== ตัวแปร dropdown ======
            const yearEl = document.getElementById('filter-year'); // ปีงบประมาณ
            const roundEl = document.getElementById('filter-round'); // รอบ
            const regionEl = document.getElementById('filter-region'); // สคร.
            const provEl = document.getElementById('filter-province'); // จังหวัด
            const unitEl = document.getElementById('filter-service-unit'); // หน่วยบริการ

            // ====== ค่าปัจจุบันจากเซิร์ฟเวอร์ ======
            const CURRENT_FY_CE = {{ (int) fiscalYearCE() }}; // ปีงบประมาณปัจจุบัน (ค.ศ.)
            const CURRENT_MONTH = {{ (int) now()->month }}; // เดือนปัจจุบัน 1-12
            const SELECTED_ROUND = parseInt(roundEl?.value || '{{ (int) $filterRound }}', 10) || 1;

            // ====== ช่วยสร้าง option ใน <select> ======
            function setOptions(selectEl, items, placeholder = null) {
                const frag = document.createDocumentFragment();
                if (placeholder !== null) {
                    const first = document.createElement('option');
                    first.value = '';
                    first.textContent = placeholder;
                    frag.appendChild(first);
                }
                items.forEach(({
                    value,
                    text,
                    selected
                }) => {
                    const o = document.createElement('option');
                    o.value = value;
                    o.textContent = text;
                    if (selected) o.selected = true;
                    frag.appendChild(o);
                });
                selectEl.replaceChildren(frag);
            }

            // ====== ตรรกะจำนวนรอบตามปีที่เลือก ======
            // ปีที่ผ่านมา (< CURRENT_FY_CE) => มี 2 รอบ
            // ปีปัจจุบัน (=== CURRENT_FY_CE) => ถ้าเดือน 10-3 มี 1 รอบ, ถ้า 4-9 มี 2 รอบ
            function roundsAvailableFor(yearCE) {
                if (yearCE < CURRENT_FY_CE) return 2;
                if (yearCE > CURRENT_FY_CE) return 0; // เผื่ออนาคต (ปกติไม่มีให้เลือก)
                const inRound1 = (CURRENT_MONTH >= 10 || CURRENT_MONTH <= 3);
                return inRound1 ? 1 : 2;
            }

            // ====== เติมรอบตามปีที่เลือก พร้อมรักษาค่าที่เคยเลือกถ้าอยู่ในช่วงที่ใช้ได้ ======
            function rebuildRoundOptions() {
                const yearCE = parseInt(yearEl?.value || CURRENT_FY_CE, 10);
                const count = roundsAvailableFor(yearCE);

                // ถ้าไม่มีรอบให้เลือก (อนาคต) ให้ใส่ placeholder และ disabled
                if (count === 0) {
                    setOptions(roundEl, [], 'ไม่มีรอบให้เลือก');
                    roundEl.disabled = true;
                    return;
                }

                roundEl.disabled = false;

                const opts = [];
                const keep = parseInt(roundEl.value || SELECTED_ROUND, 10);
                const chosen = (keep >= 1 && keep <= count) ? keep : count; // ถ้า keep หลุดช่วง ให้ชี้ตัวเลือกสูงสุดที่มี

                if (count >= 1) {
                    opts.push({
                        value: '1',
                        text: 'รอบที่ 1 (ต.ค. – มี.ค.)',
                        selected: chosen === 1
                    });
                }
                if (count >= 2) {
                    opts.push({
                        value: '2',
                        text: 'รอบที่ 2 (เม.ย. – ก.ย.)',
                        selected: chosen === 2
                    });
                }
                setOptions(roundEl, opts);
            }

            // ====== ส่วน chain-select เดิม: สคร. -> จังหวัด -> หน่วยบริการ ======
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

            // ====== events ======
            // เปลี่ยนปีงบประมาณ → คำนวณจำนวนรอบ แล้ว rebuild รอบ
            yearEl?.addEventListener('change', () => {
                rebuildRoundOptions();
            });

            // เปลี่ยนเขต → โหลดจังหวัดและหน่วยบริการใหม่
            regionEl?.addEventListener('change', async e => {
                const rid = e.target.value || 'all';
                await loadProvinces(rid, false);
                const pcode = provEl.value || 'all';
                await loadServiceUnits(pcode, rid, false);
            });

            // เปลี่ยนจังหวัด → โหลดหน่วยบริการใหม่
            provEl?.addEventListener('change', async e => {
                const pcode = e.target.value || 'all';
                const rid = regionEl?.value || 'all';
                await loadServiceUnits(pcode, rid, false);
            });

            // ====== init ======
            (async function init() {
                // ตั้งค่ารอบตามปีเริ่มต้น
                rebuildRoundOptions();

                // โหลดจังหวัดและหน่วยบริการตามค่าเดิม
                const rid = regionEl?.value || 'all';
                await loadProvinces(rid, true);
                const pcode = provEl?.value || 'all';
                await loadServiceUnits(pcode, rid, true);
            })();
        })();
    </script>
@endpush
