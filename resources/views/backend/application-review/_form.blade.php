@php
    // หน่วยบริการหลักของผู้ใช้ที่ถูกส่งมาจาก Controller
    // ถ้า controller ไม่ได้ส่งมา ให้ลองดึงเองเผื่อใช้กับหน้า create
    $unit = $unit ?? (optional($user)->serviceUnits()->wherePivot('is_primary', true)->first() ?? optional($user)->serviceUnits()->first());

    // provinces: CODE, TITLE
    $provinces = \Illuminate\Support\Facades\Cache::remember('blade.provinces.all', now()->addDay(), function () {
        return \App\Models\Province::query()
            ->select(['CODE', 'TITLE'])
            ->orderBy('TITLE')
            ->get();
    });

    // health regions: id, code, title, short_title
    $healthRegions = \Illuminate\Support\Facades\Cache::remember('blade.health_regions.all', now()->addDay(), function () {
        return \App\Models\HealthRegion::query()
            ->select(['id', 'code', 'title', 'short_title'])
            ->orderBy('code')
            ->get();
    });
@endphp

{{-- ====================== Error Summary ====================== --}}
@if ($errors->any())
    <div id="error-summary" class="alert alert-danger" role="alert">
        <div class="d-flex align-items-start gap-2">
            <i class="ti ti-alert-circle fs-4 mt-1"></i>
            <div>
                <strong>กรอกข้อมูลไม่ครบหรือไม่ถูกต้อง {{ $errors->count() }} รายการ</strong>
                <div class="small">โปรดตรวจสอบฟิลด์ที่มีเครื่องหมาย <span class="text-danger">*</span> หรือมีกรอบสีแดง</div>
                <ul class="mt-2 mb-0">
                    @foreach ($errors->toArray() as $field => $messages)
                        <li class="small">
                            {{-- แปลงชื่อ field ที่เป็น array ให้อ่านง่าย --}}
                            <a href="#field-{{ \Illuminate\Support\Str::slug($field) }}" class="text-reset text-decoration-underline">
                                {{ str_replace(['working_hours.*.', 'working_hours.'], 'วัน-เวลาทำการ: ', $field) }}
                            </a>
                            : {{ $messages[0] }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @push('js')
        <script>
            // เลื่อนขึ้นไปดูสรุป error อัตโนมัติ
            document.addEventListener('DOMContentLoaded', () => {
                const box = document.getElementById('error-summary');
                if (box) box.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });
        </script>
    @endpush
@endif


<div class="row g-3">
    {{-- ====================== ข้อมูลการลงทะเบียน (TOR) ====================== --}}
    <h5>1. วัตถุประสงค์การลงทะเบียน</h5>

    {{-- ===================== REG PURPOSE + SUPERVISE SELECTS ===================== --}}
    <div class="col-md-12">
        <label class="form-label d-block required" id="field-{{ \Illuminate\Support\Str::slug('reg_purpose') }}">ในฐานะ</label>
        @php
            // 1) ค่าตัวเลือก (label ภาษาไทย)
            $purposeLabels = ['หน่วยบริการสุขภาพผู้เดินทาง', 'ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับจังหวัด (สสจ.)', 'ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับเขต (สคร.)'];

            // 2) mapping รหัส -> label (กรณีฐานข้อมูลเก็บรหัส)
            $purposeCodeToLabel = [
                'T' => 'หน่วยบริการสุขภาพผู้เดินทาง',
                'P' => 'ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับจังหวัด (สสจ.)',
                'R' => 'ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับเขต (สคร.)',
            ];

            // 3) ดึงค่าที่เลือกจาก old() ก่อน, ถ้าไม่มีให้ใช้ของ user
            $selectedPurposes = old('reg_purpose', $user->reg_purpose ?? []);

            // แปลง selected ให้เป็น array เสมอ
            if (is_string($selectedPurposes)) {
                // กรณีเป็น JSON string หรือ CSV string
                $selectedPurposes = json_decode($selectedPurposes, true) ?? explode(',', $selectedPurposes);
            }
            $selectedPurposes = array_filter((array) $selectedPurposes, fn($x) => $x !== null && $x !== '');

            // 4) ถ้ามีรหัส (T,P,R) ให้ map กลับเป็น label ไทย เพื่อใช้เช็ค checked/แสดง UI
            $selectedLabels = collect($selectedPurposes)
                ->map(function ($v) use ($purposeCodeToLabel) {
                    $v = trim((string) $v);
                    return $purposeCodeToLabel[$v] ?? $v; // ถ้าไม่ใช่รหัส ให้ถือว่าเป็น label เดิม
                })
                ->values()
                ->all();

            // province & region ค่าเดิม
            $oldProvince = old('reg_supervise_province_code', $user->reg_supervise_province_code ?? null);
            $oldRegion = old('reg_supervise_region_id', $user->reg_supervise_region_id ?? null);

            // flag สำหรับกำหนดการโชว์ตั้งต้น (กันหน้า "กะพริบ")
            $isProv = in_array('ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับจังหวัด (สสจ.)', $selectedLabels, true);
            $isReg = in_array('ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับเขต (สคร.)', $selectedLabels, true);
        @endphp

        {{-- Checkboxes --}}
        @foreach ($purposeLabels as $index => $option)
            <div class="form-check">
                <input class="form-check-input reg-purpose" type="checkbox" name="reg_purpose[]" id="reg_purpose_{{ $index }}" value="{{ $option }}" {{-- ติ๊กถูกตาม old()/edit --}} {{ in_array($option, $selectedLabels, true) ? 'checked' : '' }} {{-- เสริม data-code เพื่อ JS จะได้ robust ถ้าต้องสลับมาเก็บเป็นรหัส --}} @php
$dataCode = array_search($option, $purposeCodeToLabel, true) ?: ''; @endphp data-code="{{ $dataCode }}">
                <label class="form-check-label" for="reg_purpose_{{ $index }}">{{ $option }}</label>
            </div>
        @endforeach

        @error('reg_purpose')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- ========== สสจ. → เลือกจังหวัด ========== --}}
    <div class="col-md-6 {{ $isProv ? '' : 'd-none' }}" id="wrap_supervise_province">
        <label for="reg_supervise_province_code" class="form-label required">
            เลือกจังหวัด (สำหรับบทบาทผู้กำกับดูแลระดับจังหวัด - สสจ.)
        </label>
        <select id="reg_supervise_province_code" name="reg_supervise_province_code" class="form-select" data-placeholder="--- เลือกจังหวัดของท่าน ---">
            @php
                $provinces = $provinces ?? \App\Models\Province::select('CODE', 'TITLE')->orderBy('TITLE')->get();
                $oldProvince = old('reg_supervise_province_code', $user->reg_supervise_province_code ?? null);
            @endphp
            <option value="">--- เลือกจังหวัดของท่าน ---</option>
            @foreach ($provinces as $p)
                <option value="{{ $p->CODE }}" {{ (string) $oldProvince === (string) $p->CODE ? 'selected' : '' }}>
                    {{ $p->TITLE }}
                </option>
            @endforeach
        </select>
        @error('reg_supervise_province_code')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- ========== สคร. → เลือกเขตสุขภาพ ========== --}}
    <div class="col-md-6 {{ $isReg ? '' : 'd-none' }}" id="wrap_supervise_region">
        <label for="reg_supervise_region_id" class="form-label required">
            เลือกเขตสุขภาพ (สำหรับบทบาทผู้กำกับดูแลระดับเขต - สคร.)
        </label>
        <select id="reg_supervise_region_id" name="reg_supervise_region_id" class="form-select" data-placeholder="--- เลือกเขตสุขภาพ ---">
            @php
                $healthRegions = $healthRegions ?? \App\Models\HealthRegion::select('id', 'code', 'title', 'short_title')->orderBy('code')->get();
                $oldRegion = old('reg_supervise_region_id', $user->reg_supervise_region_id ?? null);
            @endphp
            <option value="">--- เลือกเขตสุขภาพ ---</option>
            @foreach ($healthRegions as $r)
                <option value="{{ $r->id }}" {{ (string) $oldRegion === (string) $r->id ? 'selected' : '' }}>
                    {{ $r->short_title ? $r->short_title . ' - ' : '' }}{{ $r->title }}
                </option>
            @endforeach
        </select>
        @error('reg_supervise_region_id')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // --- init Choices ---
                const provinceSelect = document.querySelector('#reg_supervise_province_code');
                const regionSelect = document.querySelector('#reg_supervise_region_id');

                let provinceChoices = null;
                let regionChoices = null;

                if (window.Choices) {
                    provinceChoices = new Choices('#reg_supervise_province_code', {
                        searchEnabled: true,
                        itemSelectText: '',
                        shouldSort: false
                    });
                    regionChoices = new Choices('#reg_supervise_region_id', {
                        searchEnabled: true,
                        itemSelectText: '',
                        shouldSort: false
                    });

                    provinceSelect.closest('.choices')?.classList.add('choices--lightable');
                    regionSelect.closest('.choices')?.classList.add('choices--lightable');
                }

                // Helper
                const wrapProv = document.getElementById('wrap_supervise_province');
                const wrapReg = document.getElementById('wrap_supervise_region');

                function show(el) {
                    el.classList.remove('d-none');
                }

                function hide(el) {
                    el.classList.add('d-none');
                }

                function clearChoices(instance, selectEl) {
                    if (!instance) { // fallback เมื่อไม่มี Choices
                        if (selectEl) selectEl.value = '';
                        return;
                    }
                    instance.removeActiveItems();
                    instance.setChoiceByValue('');
                }

                // Checkbox elements
                const cbService = document.querySelector('#reg_purpose_0'); // หน่วยบริการฯ (ไม่ exclusive)
                const cbProv = document.querySelector('#reg_purpose_1'); // สสจ.
                const cbRegion = document.querySelector('#reg_purpose_2'); // สคร.

                function toggleExclusive() {
                    // สสจ. vs สคร. exclusive กัน
                    if (cbProv && cbRegion) {
                        if (cbProv.checked) {
                            cbRegion.checked = false;
                            cbRegion.disabled = true;
                            show(wrapProv);
                            hide(wrapReg);
                            clearChoices(regionChoices, regionSelect);
                        } else {
                            cbRegion.disabled = false;
                            // ซ่อนเลือกจังหวัดเมื่อไม่ใช้งาน
                            hide(wrapProv);
                            clearChoices(provinceChoices, provinceSelect);
                        }

                        if (cbRegion.checked) {
                            cbProv.checked = false;
                            cbProv.disabled = true;
                            show(wrapReg);
                            hide(wrapProv);
                            clearChoices(provinceChoices, provinceSelect);
                        } else {
                            cbProv.disabled = false;
                            if (!cbProv.checked) {
                                hide(wrapReg);
                                clearChoices(regionChoices, regionSelect);
                            }
                        }
                    }
                    // หมายเหตุ: cbService ไม่มีผลกับการโชว์ select (ตาม business rule ปัจจุบัน)
                }

                // เรียกครั้งแรก (รองรับกรณี old()/edit)
                toggleExclusive();

                // Bind change
                [cbProv, cbRegion].forEach(el => el && el.addEventListener('change', toggleExclusive));
            });
        </script>
    @endpush


    {{-- ซ่อน/แสดง section 2 ถ้าเลือก สสจ./สคร. --}}
    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // 0=หน่วยบริการ, 1=สสจ., 2=สคร.
                const cbService = document.getElementById('reg_purpose_0');
                const cbProv = document.getElementById('reg_purpose_1');
                const cbRegion = document.getElementById('reg_purpose_2');

                const box = document.getElementById('section2Body'); // collapse target
                const hint = document.getElementById('section2Hint');
                const form = document.querySelector('form');

                // Bootstrap Collapse instance
                const coll = new bootstrap.Collapse(box, {
                    toggle: false
                });

                function isSupervisorOnly() {
                    const isServiceUnit = !!(cbService && cbService.checked);
                    const anySupervisor = !!((cbProv && cbProv.checked) || (cbRegion && cbRegion.checked));
                    return !isServiceUnit && anySupervisor;
                }

                // disable/enable fields when collapsed/expanded
                function setDisabledInside(disabled) {
                    box.querySelectorAll('input, select, textarea, button').forEach(el => {
                        // ยกเว้นปุ่มแผนที่ utility ถ้าอยาก
                        if (['btn-center-marker', 'btn-reset-initial', 'btn-geocode'].includes(el.id)) return;
                        if (disabled) {
                            el.setAttribute('data-was-disabled', el.disabled ? '1' : '0');
                            el.disabled = true;
                        } else {
                            if (el.getAttribute('data-was-disabled') === '0') el.disabled = false;
                            el.removeAttribute('data-was-disabled');
                        }
                    });
                }

                function updateHint(isHidden) {
                    if (!hint) return;
                    hint.textContent = isHidden ?
                        'พับเก็บ “ส่วนที่ 2” และจะไม่ถูกบันทึก' :
                        'กำลังบันทึกข้อมูลส่วนที่ 2';
                    hint.classList.toggle('text-danger', isHidden);
                    hint.classList.toggle('text-muted', !isHidden);
                }

                function applyState() {
                    const hide = isSupervisorOnly();
                    if (hide) {
                        coll.hide();
                    } else {
                        coll.show();
                    }
                }

                // Bootstrap collapse events = สไลด์เสร็จค่อยสลับ disabled เพื่อกันกระตุกอินพุต
                box.addEventListener('shown.bs.collapse', () => {
                    setDisabledInside(false);
                    updateHint(false);
                });
                box.addEventListener('hidden.bs.collapse', () => {
                    setDisabledInside(true);
                    updateHint(true);
                });

                // bind changes
                [cbService, cbProv, cbRegion].forEach(el => el && el.addEventListener('change', applyState));

                // initial
                applyState();

                // กันค่าหลุดตอน submit เผื่อผู้ใช้กดเร็วระหว่างกำลังสไลด์
                form?.addEventListener('submit', () => {
                    if (!box.classList.contains('show')) setDisabledInside(true);
                });
            });
        </script>
    @endpush
    <hr class="my-4">



    <div id="section2" class="col-12">
        <!-- กล่องที่มีเอฟเฟกต์สไลด์ -->
        <div id="section2Body" class="collapse show">

            <div class="row g-3">
                <h5 class="col-12">2. ข้อมูลทั่วไปของหน่วยบริการ/หน่วยงาน</h5>

                {{-- ชื่อหน่วยบริการ/หน่วยงาน (ขึ้นแถวใหม่แบบเต็มความกว้าง) --}}
                <div class="col-12">
                    <label for="org_name" class="form-label required">ชื่อหน่วยบริการ/หน่วยงาน</label>
                    <input type="text" name="org_name" id="org_name" value="{{ old('org_name', $unit->org_name ?? '') }}" class="form-control">
                    @error('org_name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="org_affiliation" class="form-label required">สังกัด</label>
                    <select name="org_affiliation" id="org_affiliation" class="form-select">
                        <option value="">--- เลือก ---</option>
                        @foreach (['สำนักงานปลัดกระทรวงสาธารณสุข', 'กรมควบคุมโรค', 'กรมการแพทย์', 'กรมสุขภาพจิต', 'สภากาชาดไทย', 'สำนักการแพทย์ กรุงเทพมหานคร', 'กระทรวงอุดมศึกษา วิทยาศาสตร์ วิจัยและนวัตกรรม', 'กระทรวงกลาโหม', 'องค์กรปกครองส่วนท้องถิ่น', 'องค์การมหาชน', 'เอกชน', 'อื่น ๆ'] as $option)
                            <option value="{{ $option }}" @selected(old('org_affiliation', $unit->org_affiliation ?? '') == $option)>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                    @error('org_affiliation')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6" id="org_affiliation_other_box" style="display: none;">
                    <label for="org_affiliation_other" class="form-label required">โปรดระบุ</label>
                    <input type="text" name="org_affiliation_other" id="org_affiliation_other" class="form-control" value="{{ old('org_affiliation_other', $unit->org_affiliation_other ?? '') }}">
                    @error('org_affiliation_other')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                @push('js')
                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            const orgSelect = document.getElementById('org_affiliation');
                            const otherBox = document.getElementById('org_affiliation_other_box');


                            function toggleOtherBox(value) {
                                if (value === 'อื่น ๆ') {
                                    otherBox.style.display = 'block';
                                } else {
                                    otherBox.style.display = 'none';
                                    document.getElementById('org_affiliation_other').value = '';
                                }
                            }

                            toggleOtherBox(orgSelect.value);

                            orgSelect.addEventListener('change', function() {
                                toggleOtherBox(this.value);
                            });
                        });
                    </script>
                @endpush


                <div class="col-md-6">
                    <label for="org_tel" class="form-label required">หมายเลขโทรศัพท์</label>
                    <input type="text" name="org_tel" id="org_tel" value="{{ old('org_tel', $unit->org_tel ?? '') }}" class="form-control">
                    @error('org_tel')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- ========== Address + Map (Leaflet + Nominatim) ========== --}}
                <div class="col-12">
                    <label for="org_address" class="form-label required">ที่อยู่หน่วยบริการ</label>
                    <textarea name="org_address" id="org_address" rows="2" class="form-control" placeholder="พิมพ์ที่อยู่ให้ละเอียด แล้วกด “ค้นหาพิกัดจากที่อยู่”">{{ old('org_address', $unit->org_address ?? '') }}</textarea>
                    @error('org_address')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {!! thGeoSelect('org_', [
                    'province_code' => old('org_province_code', $unit->org_province_code ?? ''),
                    'district_code' => old('org_district_code', $unit->org_district_code ?? ''),
                    'subdistrict_code' => old('org_subdistrict_code', $unit->org_subdistrict_code ?? ''),
                    'postcode' => old('org_postcode', $unit->org_postcode ?? ''),
                ]) !!}

                <div class="col-12">
                    <div class="d-flex align-items-center gap-2 my-2">
                        <button type="button" id="btn-geocode" class="btn btn-outline-primary btn-sm">
                            ค้นหาพิกัดจากที่อยู่
                        </button>
                        <button type="button" id="btn-from-subdistrict" class="btn btn-outline-success btn-sm">
                            ใช้พิกัดจากตำบล
                        </button>
                        <button type="button" id="btn-center-marker" class="btn btn-outline-secondary btn-sm">
                            จัดกึ่งกลางที่หมุด
                        </button>
                        <button type="button" id="btn-reset-initial" class="btn btn-outline-danger btn-sm">
                            รีเซ็ตจุดเริ่มต้น
                        </button>
                        <small class="text-muted">หรือคลิกบนแผนที่/ลากหมุดเพื่อกำหนดพิกัดเอง</small>
                        <span id="coord-badge" class="badge bg-secondary ms-auto d-none"></span>
                    </div>
                    <div id="map" style="height: 360px; border-radius: .5rem; overflow: hidden;"></div>
                </div>

                <div class="col-md-6">
                    <label for="org_lat" class="form-label">Latitude</label>
                    <input type="text" name="org_lat" id="org_lat" value="{{ old('org_lat', $unit->org_lat ?? '') }}" class="form-control" placeholder="จะถูกกรอกอัตโนมัติเมื่อเลือกพิกัด">
                </div>

                <div class="col-md-6">
                    <label for="org_lng" class="form-label">Longitude</label>
                    <input type="text" name="org_lng" id="org_lng" value="{{ old('org_lng', $unit->org_lng ?? '') }}" class="form-control" placeholder="จะถูกกรอกอัตโนมัติเมื่อเลือกพิกัด">
                </div>

                @push('js')
                    {{-- Leaflet CSS/JS (CDN) --}}
                    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const latEl = document.getElementById('org_lat');
                            const lngEl = document.getElementById('org_lng');
                            const addrEl = document.getElementById('org_address');
                            const badgeEl = document.getElementById('coord-badge');

                            const hasLatLng = latEl.value && lngEl.value && !isNaN(parseFloat(latEl.value)) && !isNaN(parseFloat(lngEl.value));

                            // const TH_CENTER = [13.736717, 100.523186]; // กทม.
                            const TH_CENTER = [13.853450, 100.527171]; // พิกัดเริ่มต้น:: กรมควบคุมโรค กระทรวงสาธารณสุข
                            const initLatLng = hasLatLng ? [parseFloat(latEl.value), parseFloat(lngEl.value)] : TH_CENTER;
                            const initZoom = hasLatLng ? 17 : 6;

                            // สร้างแผนที่
                            const map = L.map('map', {
                                scrollWheelZoom: true
                            }).setView(initLatLng, initZoom);

                            // พื้นหลังแผนที่ (OSM)
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                maxZoom: 19,
                                attribution: '&copy; OpenStreetMap contributors'
                            }).addTo(map);

                            // หมุด
                            const marker = L.marker(initLatLng, {
                                draggable: true
                            }).addTo(map);

                            function setCoord(lat, lng, zoom = 16, pan = true) {
                                lat = parseFloat(lat);
                                lng = parseFloat(lng);
                                if (isNaN(lat) || isNaN(lng)) return;

                                marker.setLatLng([lat, lng]);
                                if (pan) map.setView([lat, lng], zoom);

                                latEl.value = lat.toFixed(6);
                                lngEl.value = lng.toFixed(6);

                                badgeEl.textContent = `Lat: ${latEl.value} , Lng: ${lngEl.value}`;
                                badgeEl.classList.remove('d-none');
                            }

                            // อัปเดตเมื่อลากหมุด
                            marker.on('dragend', e => {
                                const {
                                    lat,
                                    lng
                                } = e.target.getLatLng();
                                setCoord(lat, lng, map.getZoom(), false);
                            });

                            // คลิกบนแผนที่เพื่อย้ายหมุด
                            map.on('click', e => {
                                setCoord(e.latlng.lat, e.latlng.lng);
                            });

                            // กด Enter ในช่องที่อยู่ => geocode
                            addrEl.addEventListener('keydown', (ev) => {
                                if (ev.key === 'Enter') {
                                    ev.preventDefault();
                                    doGeocode();
                                }
                            });

                            // ปุ่ม geocode
                            document.getElementById('btn-geocode').addEventListener('click', doGeocode);

                            // ฟังก์ชันเรียก Nominatim หา Lat/Lng จากข้อความที่อยู่
                            async function doGeocode() {
                                const q = (addrEl.value || '').trim();
                                if (!q) {
                                    addrEl.focus();
                                    return;
                                }

                                const url = new URL('https://nominatim.openstreetmap.org/search');
                                url.searchParams.set('q', q + ', Thailand'); // บีบให้เน้นประเทศไทย
                                url.searchParams.set('format', 'json');
                                url.searchParams.set('addressdetails', '1');
                                url.searchParams.set('limit', '1');

                                try {
                                    const res = await fetch(url.toString(), {
                                        headers: {
                                            'Accept-Language': 'th'
                                        }
                                    });
                                    if (!res.ok) throw new Error('HTTP ' + res.status);
                                    const data = await res.json();

                                    if (Array.isArray(data) && data.length) {
                                        const p = data[0];
                                        setCoord(parseFloat(p.lat), parseFloat(p.lon), 17);
                                    } else {
                                        alert('ไม่พบพิกัดจากที่อยู่นี้ กรุณาระบุให้ละเอียดขึ้น (เช่น เลขที่ ถนน แขวง/ตำบล เขต/อำเภอ จังหวัด)');
                                    }
                                } catch (err) {
                                    console.error(err);
                                    alert('เกิดข้อผิดพลาดในการค้นหาพิกัด โปรดลองใหม่อีกครั้ง');
                                }
                            }

                            // ถ้ามีพิกัดเดิมในฟอร์มแล้ว แสดง badge ให้เห็น
                            if (hasLatLng) {
                                badgeEl.textContent = `Lat: ${parseFloat(latEl.value).toFixed(6)} , Lng: ${parseFloat(lngEl.value).toFixed(6)}`;
                                badgeEl.classList.remove('d-none');
                            }

                            // ป้องกันผู้ใช้พิมพ์ค่าที่ไม่ใช่ตัวเลขลงช่อง lat/lng
                            function sanitizeCoordInput(el, min, max) {
                                el.addEventListener('input', () => {
                                    let v = el.value.replace(/[^0-9.\-]/g, '');
                                    el.value = v;
                                });
                                el.addEventListener('change', () => {
                                    let n = parseFloat(el.value);
                                    if (isNaN(n)) return;
                                    n = Math.max(min, Math.min(max, n));
                                    el.value = n.toFixed(6);
                                    setCoord(
                                        parseFloat(latEl.value || TH_CENTER[0]),
                                        parseFloat(lngEl.value || TH_CENTER[1]),
                                        map.getZoom()
                                    );
                                });
                            }
                            sanitizeCoordInput(latEl, -90, 90);
                            sanitizeCoordInput(lngEl, -180, 180);

                            // เก็บค่าเริ่มต้นไว้ใช้ตอนรีเซ็ต
                            const initialState = {
                                lat: initLatLng[0],
                                lng: initLatLng[1],
                                zoom: initZoom
                            };

                            // ฟังก์ชันจัดกึ่งกลางที่หมุด (ไม่เปลี่ยน lat/lng)
                            function centerToMarker() {
                                const {
                                    lat,
                                    lng
                                } = marker.getLatLng();
                                map.flyTo([lat, lng], Math.max(map.getZoom(), 16));
                            }

                            // ฟังก์ชันรีเซ็ตกลับค่าตั้งต้น (ย้ายหมุด + แผนที่ + อัพเดตช่องฟอร์ม)
                            function resetToInitial() {
                                setCoord(initialState.lat, initialState.lng, initialState.zoom, true);
                            }

                            // ผูกปุ่ม
                            document.getElementById('btn-center-marker').addEventListener('click', centerToMarker);
                            document.getElementById('btn-reset-initial').addEventListener('click', resetToInitial);

                            // เพิ่มช็อตคัตคีย์: กด `r` = รีเซ็ต, `c` = จัดกึ่งกลางที่หมุด
                            document.addEventListener('keydown', (e) => {
                                if (e.target.matches('input, textarea')) return; // ไม่ให้ชนตอนพิมพ์
                                if (e.key.toLowerCase() === 'r') resetToInitial();
                                if (e.key.toLowerCase() === 'c') centerToMarker();
                            });

                            // ดึงรหัสตำบลจาก select ของ chain-select (id ควรเป็น org_subdistrict_code)
                            // อ้าง select ตำบลแบบปลอดภัย (id ของ geo-select เป็นไดนามิก)
                            const subdistrictSelect =
                                document.querySelector('select[name="org_subdistrict_code"]') ||
                                document.querySelector('[id^="geo_"] select[id$="_subdistrict"]');

                            const btnFromSub = document.getElementById('btn-from-subdistrict');

                            async function moveToSubdistrictCenter() {
                                const code = subdistrictSelect?.value;
                                if (!code) {
                                    alert('กรุณาเลือกตำบลก่อน');
                                    subdistrictSelect?.focus();
                                    return;
                                }
                                try {
                                    const url = new URL('{{ route('geo.subdistrict-center') }}', window.location.origin);
                                    url.searchParams.set('code', code);
                                    const res = await fetch(url.toString(), {
                                        headers: {
                                            'Accept': 'application/json'
                                        }
                                    });
                                    if (!res.ok) throw new Error(await res.text());
                                    const data = await res.json();

                                    // เลเวลซูมตอนกดปุ่ม (ปรับได้ตามชอบ)
                                    setCoord(data.lat, data.lng, 12);
                                } catch (e) {
                                    console.error(e);
                                    alert('ไม่พบพิกัดศูนย์กลางของตำบลนี้ หรือเกิดข้อผิดพลาดในการดึงข้อมูล');
                                }
                            }

                            btnFromSub?.addEventListener('click', moveToSubdistrictCenter);


                            // ช่วยอำนวยความสะดวก: ถ้าเปลี่ยนตำบล และยังไม่มีค่า lat/lng ในฟอร์ม ให้เลื่อนหมุดให้อัตโนมัติ
                            // subdistrictSelect?.addEventListener('change', () => {
                            //     const latEmpty = !latEl.value || isNaN(parseFloat(latEl.value));
                            //     const lngEmpty = !lngEl.value || isNaN(parseFloat(lngEl.value));
                            //     if (latEmpty || lngEmpty) moveToSubdistrictCenter();
                            // });


                        });
                    </script>
                @endpush


                {{-- ===================== วัน-เวลาทำการ ===================== --}}
                @php
                    // รับค่าจาก old() ถ้ามี, ไม่งั้นใช้ของ $unit
                    $whRaw = old('working_hours_json', $unit->org_working_hours_json ?? []);

                    // บังคับให้เป็นสตริง JSON เสมอ (กันเคส array จาก cast)
                    $initWorkingHours = is_string($whRaw) ? $whRaw : json_encode($whRaw, JSON_UNESCAPED_UNICODE);
                @endphp


                <div class="col-12">
                    <label class="form-label fw-semibold">วัน-เวลาทำการ</label>

                    <!-- ตารางเวลาเปิด-ปิดแบบลากเมาส์ -->
                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center justify-content-between py-2">
                            <h6 class="mb-0">กำหนดเวลาเปิด-ปิดของแต่ละวัน</h6>
                            <small class="text-muted">ลากเมาส์เลือกช่วงเวลาเป็นสีเขียว (เปิดทำการ)</small>
                        </div>
                        <div class="card-body p-2">

                            <input type="hidden" id="working_hours_json" name="working_hours_json" value="{{ $initWorkingHours }}">

                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle mb-0" id="working-grid">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="min-width:150px;">วัน</th>
                                            <!-- ส่วนหัวเวลาเติมด้วย JS -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- แถววันเติมด้วย JS -->
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">
                                <div class="alert alert-secondary py-2 small mb-2">
                                    วิธีใช้: คลิกหรือลากเมาส์บนช่องเวลาเพื่อเลือกเปิดทำการ (สีเขียว) |
                                    ดับเบิลคลิกเพื่อเลือก/ยกเลิกทั้งวัน |
                                    ปุ่ม “ล้างวันนี้” จะลบเฉพาะวันนั้น
                                </div>
                                <pre class="bg-light border rounded small p-2 mb-0" id="working-hours-preview" style="white-space:pre-wrap;"></pre>
                            </div>
                        </div>
                    </div>

                    @error('working_hours_json')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                @push('css')
                    <style>
                        .slot.selected {
                            background: var(--bs-success-bg-subtle);
                            outline: 2px solid rgba(var(--bs-success-rgb), .5);
                        }

                        .slot {
                            user-select: none;
                            cursor: crosshair;
                            min-width: 72px;
                            text-align: center;
                            font-variant-numeric: tabular-nums;
                        }

                        #working-grid tbody th.dayname {
                            position: sticky;
                            left: 0;
                            background: var(--bs-body-bg);
                            z-index: 1;
                            width: 150px;
                        }

                        #working-grid thead th {
                            position: sticky;
                            top: 0;
                            z-index: 2;
                        }
                    </style>
                @endpush

                @push('js')
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const startHour = 7,
                                endHour = 22;
                            const hours = Array.from({
                                length: endHour - startHour + 1
                            }, (_, i) => startHour + i);
                            const days = [{
                                    key: 'mon',
                                    label: 'จันทร์'
                                },
                                {
                                    key: 'tue',
                                    label: 'อังคาร'
                                },
                                {
                                    key: 'wed',
                                    label: 'พุธ'
                                },
                                {
                                    key: 'thu',
                                    label: 'พฤหัสบดี'
                                },
                                {
                                    key: 'fri',
                                    label: 'ศุกร์'
                                },
                                {
                                    key: 'sat',
                                    label: 'เสาร์'
                                },
                                {
                                    key: 'sun',
                                    label: 'อาทิตย์'
                                },
                            ];

                            const schedule = Object.fromEntries(days.map(d => [d.key, new Set()]));
                            const table = document.getElementById('working-grid');
                            const theadRow = table.querySelector('thead tr');
                            const tbody = table.querySelector('tbody');

                            // ===== สร้างหัวคอลัมน์เวลา =====
                            for (const h of hours) {
                                const th = document.createElement('th');
                                th.className = 'text-center';
                                th.textContent = h.toString().padStart(2, '0') + ':00';
                                theadRow.appendChild(th);
                            }

                            let dragging = false,
                                dragMode = 'select',
                                dragDayKey = null;

                            const makeSlot = (dayKey, hour) => {
                                const td = document.createElement('td');
                                td.className = 'slot';
                                td.dataset.day = dayKey;
                                td.dataset.hour = String(hour);

                                const updateVisual = () => td.classList.toggle('selected', schedule[dayKey].has(hour));
                                updateVisual();

                                td.addEventListener('mousedown', e => {
                                    e.preventDefault();
                                    dragging = true;
                                    dragDayKey = dayKey;
                                    const selected = schedule[dayKey].has(hour);
                                    dragMode = selected ? 'unselect' : 'select';
                                    if (dragMode === 'select') schedule[dayKey].add(hour);
                                    else schedule[dayKey].delete(hour);
                                    updateVisual();
                                    updateOutputs();
                                });

                                td.addEventListener('mouseenter', () => {
                                    if (!dragging || dragDayKey !== dayKey) return;
                                    if (dragMode === 'select') schedule[dayKey].add(hour);
                                    else schedule[dayKey].delete(hour);
                                    updateVisual();
                                });

                                document.addEventListener('mouseup', () => {
                                    if (dragging) {
                                        dragging = false;
                                        dragDayKey = null;
                                        updateOutputs();
                                    }
                                });

                                td.addEventListener('dblclick', () => {
                                    const allOn = schedule[dayKey].size === hours.length;
                                    schedule[dayKey].clear();
                                    if (!allOn) hours.forEach(h => schedule[dayKey].add(h));
                                    rowUpdateVisual(dayKey);
                                    updateOutputs();
                                });

                                return td;
                            };

                            for (const d of days) {
                                const tr = document.createElement('tr');
                                const th = document.createElement('th');
                                th.className = 'dayname';
                                th.innerHTML = `<div class="d-flex justify-content-between align-items-center">
      <span>${d.label}</span>
      <button type="button" class="btn btn-outline-danger btn-xs py-0 px-1" data-clear-day="${d.key}">ล้างวันนี้</button>
    </div>`;
                                tr.appendChild(th);
                                for (const h of hours) tr.appendChild(makeSlot(d.key, h));
                                tbody.appendChild(tr);
                            }

                            tbody.addEventListener('click', e => {
                                const btn = e.target.closest('button[data-clear-day]');
                                if (!btn) return;
                                const key = btn.dataset.clearDay;
                                schedule[key].clear();
                                rowUpdateVisual(key);
                                updateOutputs();
                            });

                            function rowUpdateVisual(dayKey) {
                                tbody.querySelectorAll(`td.slot[data-day="${dayKey}"]`).forEach(td => {
                                    const h = parseInt(td.dataset.hour);
                                    td.classList.toggle('selected', schedule[dayKey].has(h));
                                });
                            }

                            function compressRanges(setHours) {
                                if (!setHours.size) return [];
                                const list = Array.from(setHours).sort((a, b) => a - b);
                                const ranges = [];
                                let s = list[0],
                                    p = list[0];
                                for (let i = 1; i < list.length; i++) {
                                    const c = list[i];
                                    if (c === p + 1) {
                                        p = c;
                                        continue;
                                    }
                                    ranges.push([s, p + 1]);
                                    s = p = c;
                                }
                                ranges.push([s, p + 1]);
                                return ranges.map(([a, b]) => `${a.toString().padStart(2,'0')}:00-${b.toString().padStart(2,'0')}:00`);
                            }

                            function updateOutputs() {
                                const obj = {};
                                for (const d of days) obj[d.key] = compressRanges(schedule[d.key]);
                                document.getElementById('working_hours_json').value = JSON.stringify(obj);
                                renderPreview(obj);
                            }

                            function renderPreview(obj) {
                                const map = Object.fromEntries(days.map(d => [d.key, d.label]));
                                const lines = Object.keys(obj).map(k => `${map[k]} : ${obj[k].length?obj[k].join(', '):'— ปิดทำการ —'}`);
                                document.getElementById('working-hours-preview').textContent = lines.join('\n');
                            }

                            // ===== ค่าเริ่มต้นจากฐานข้อมูล (ถ้ามี) =====
                            try {
                                const init = JSON.parse(document.getElementById('working_hours_json').value || '{}');
                                for (const d of days) {
                                    const ranges = init?.[d.key] ?? [];
                                    schedule[d.key].clear();
                                    for (const r of ranges) {
                                        const [a, b] = r.split('-');
                                        const ah = parseInt(a);
                                        const bh = parseInt(b);
                                        for (let h = ah; h < bh; h++) schedule[d.key].add(h);
                                    }
                                    rowUpdateVisual(d.key);
                                }
                            } catch (e) {
                                console.warn('init hours parse error', e);
                            }

                            updateOutputs();
                        });
                    </script>
                @endpush


            </div>
            <hr class="my-4">
        </div>
    </div>

    {{-- ====================== ข้อมูลทั่วไปของผู้ลงทะเบียน ====================== --}}
    <h5>3. ข้อมูลทั่วไปของผู้ลงทะเบียน</h5>

    <div class="col-md-6">
        <label for="contact_cid" class="form-label required">เลขบัตรประจำตัวประชาชน (13 หลัก)</label>
        <input type="text" name="contact_cid" id="contact_cid" value="{{ old('contact_cid', $user->contact_cid ?? '') }}" class="form-control" inputmode="numeric" maxlength="13" placeholder="กรอกเฉพาะตัวเลข 13 หลัก">
        @error('contact_cid')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        <div class="form-text" id="cid-preview" style="display:none;"></div>
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const el = document.getElementById('contact_cid');
                const pv = document.getElementById('cid-preview');

                function onlyDigits(v) {
                    return (v || '').replace(/\D/g, '').slice(0, 13);
                }

                function fmtTH(v) {
                    // 1-2345-67890-12-3 (เฉพาะแสดงผล)
                    if (v.length < 13) return '';
                    return `${v[0]}-${v.slice(1,5)}-${v.slice(5,10)}-${v.slice(10,12)}-${v[12]}`;
                }

                function updatePreview() {
                    const v = onlyDigits(el.value);
                    if (v !== el.value) {
                        el.value = v;
                    }
                    const f = fmtTH(v);
                    pv.style.display = f ? 'block' : 'none';
                    pv.textContent = f ? `รูปแบบ: ${f}` : '';
                }

                el.addEventListener('input', updatePreview);
                updatePreview();
            });
        </script>
    @endpush


    <div class="col-md-6">
        <label for="contact_name" class="form-label required">ชื่อ-สกุล</label>
        <input type="text" name="contact_name" id="contact_name" value="{{ old('contact_name', $user->contact_name ?? '') }}" class="form-control">
        @error('contact_name')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="contact_position" class="form-label required">ตำแหน่ง</label>
        <input type="text" name="contact_position" id="contact_position" value="{{ old('contact_position', $user->contact_position ?? '') }}" class="form-control">
        @error('contact_position')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="contact_mobile" class="form-label required">โทรศัพท์มือถือ</label>
        <input type="text" name="contact_mobile" id="contact_mobile" value="{{ old('contact_mobile', $user->contact_mobile ?? '') }}" class="form-control">
        @error('contact_mobile')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label required">อีเมล</label>
        <input type="email" name="email" id="email" value="{{ old('email', $user->email ?? '') }}" class="form-control">
        @error('email')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="officer_doc" class="form-label required">เอกสารยืนยันตัวเจ้าหน้าที่ (เช่น สำเนาบัตรประจำตัวเจ้าหน้าที่ของรัฐ, หนังสือรับรองการเป็นเจ้าหน้าที่)</label>

        {{-- ถ้ามีไฟล์เดิม แสดงลิงก์ + ตัวเลือกลบ --}}
        @if (!empty($user?->officer_doc_path))
            <div class="alert alert-secondary d-flex align-items-center justify-content-between" role="alert">
                <div>
                    <i class="ti ti-file-description me-1"></i>
                    ไฟล์ที่อัปโหลดไว้:
                    <a href="{{ Storage::disk('public')->url($user->officer_doc_path) }}" target="_blank">
                        {{ basename($user->officer_doc_path) }}
                    </a>
                </div>
                <div class="form-check ms-3">
                    <input class="form-check-input" type="checkbox" name="remove_officer_doc" id="remove_officer_doc" value="1">
                    <label class="form-check-label" for="remove_officer_doc">ลบไฟล์นี้</label>
                </div>
            </div>
            <div class="form-text mb-2">
                หากต้องการ “แทนที่ไฟล์” ให้เลือกไฟล์ใหม่ ระบบจะอัปเดตให้โดยอัตโนมัติ
            </div>
        @else
            <div class="form-text mb-2">
                รองรับไฟล์ PDF/JPG/PNG ขนาดสูงสุด 5 MB
            </div>
        @endif

        <input type="file" name="officer_doc" id="officer_doc" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
        @error('officer_doc')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    <hr class="my-4">

    {{-- ====================== ข้อมูลผู้ลงทะเบียน ====================== --}}
    <h5>4. กำหนด Username และ Password สำหรับเข้าใช้งานระบบ</h5>

    <div class="col-md-6">
        <label for="username" class="form-label required">Username</label>
        <input type="text" name="username" id="username" value="{{ old('username', $user->username ?? '') }}" class="form-control">
        @error('username')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="password" class="form-label {{ isset($user) ? '' : 'required' }}">รหัสผ่าน</label>
        <input type="password" name="password" id="password" class="form-control">
        @error('password')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="password_confirmation" class="form-label {{ isset($user) ? '' : 'required' }}">ยืนยันรหัสผ่าน</label>
        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
        @error('password_confirmation')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
        <div class="form-text">
            {{ isset($user) ? 'หากต้องการเปลี่ยนรหัสผ่าน ให้กรอกทั้งรหัสผ่านและยืนยันรหัสผ่าน' : 'กรอกให้ตรงกับรหัสผ่าน' }}
        </div>
    </div>


    <hr class="my-4">

    {{-- ====================== การยืนยันและข้อกำหนด PDPA ====================== --}}
    <h5>5. การยืนยันความถูกต้องและข้อกำหนด PDPA</h5>

    <div class="border border-success rounded p-3 bg-success-subtle">
        <h6 class="text-dark mb-2">
            <i class="ti ti-shield-check me-1"></i> ข้อกำหนดการคุ้มครองข้อมูลส่วนบุคคล (PDPA)
        </h6>
        <p class="mb-2">
            เพื่อความถูกต้องของข้อมูลและการคุ้มครองข้อมูลส่วนบุคคล โปรดอ่าน
            <a href="#" data-bs-toggle="modal" data-bs-target="#pdpaModal" class="fw-bold text-decoration-underline">
                ประกาศความเป็นส่วนตัว (Privacy Notice)
            </a> และติ๊กยอมรับก่อนบันทึก
        </p>
        <ul class="mb-3 small">
            <li>ข้าพเจ้ายืนยันว่าข้อมูลที่กรอกมีความถูกต้อง ครบถ้วน และเป็นปัจจุบัน</li>
            <li>ยินยอมให้กระทรวงสาธารณสุขเก็บ รวบรวม ใช้ และเปิดเผยข้อมูลตามวัตถุประสงค์ของระบบ</li>
            <li>รับทราบสิทธิในการเข้าถึง แก้ไข ลบ ระงับการใช้ หรือคัดค้าน รวมถึงสิทธิถอนความยินยอม</li>
        </ul>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="pdpa_accept" name="pdpa_accept" value="1" {{ old('pdpa_accept', !empty($user->pdpa_version) ? 1 : 0) ? 'checked' : '' }}>
            <label for="pdpa_accept">ยอมรับ PDPA</label>
            <label class="form-check-label fw-semibold" for="pdpa_accept">
                ข้าพเจ้าได้อ่านและยอมรับข้อกำหนดตาม
                <a href="#" data-bs-toggle="modal" data-bs-target="#pdpaModal">Privacy Notice</a>
            </label>
        </div>
    </div>




    @push('modal')
        {{-- Modal: Privacy Notice (ประกาศความเป็นส่วนตัวของ สธ.) --}}
        <div class="modal fade" id="pdpaModal" tabindex="-1" aria-labelledby="pdpaModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title" id="pdpaModalLabel">ประกาศความเป็นส่วนตัว (Privacy Notice) กระทรวงสาธารณสุข</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">ตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 กระทรวงสาธารณสุขดำเนินการดังนี้:</p>
                        <ol>
                            <li><strong>วัตถุประสงค์</strong> : เก็บ รวบรวม ใช้ และเปิดเผยข้อมูลเพื่อการลงทะเบียน การยืนยันตัวตน การให้สิทธิ์ใช้งาน และการบริหารจัดการระบบหน่วยบริการสุขภาพผู้เดินทาง</li>
                            <li><strong>ข้อมูลที่เก็บ</strong> : ชื่อ-สกุล อีเมล โทรศัพท์ ตำแหน่ง หน่วยงาน ที่อยู่ พิกัด และข้อมูลการใช้งานระบบ</li>
                            <li><strong>ฐานทางกฎหมาย</strong> : การปฏิบัติหน้าที่เพื่อประโยชน์สาธารณะ/อำนาจรัฐ และ/หรือความยินยอมของเจ้าของข้อมูล</li>
                            <li><strong>การเปิดเผย</strong> : เฉพาะหน่วยงานในสังกัดกระทรวงสาธารณสุข หรือผู้ประมวลผลข้อมูลที่ได้รับมอบหมาย โดยมีมาตรการคุ้มครองข้อมูลที่เหมาะสม</li>
                            <li><strong>ระยะเวลาเก็บรักษา</strong> : เท่าที่จำเป็นตามวัตถุประสงค์/กฎหมาย หรือจนกว่าจะสิ้นสุดความจำเป็นในการประมวลผล</li>
                            <li><strong>สิทธิของเจ้าของข้อมูล</strong> : เข้าถึง คัดลอก โอนย้าย แก้ไข ลบ ระงับการใช้ หรือคัดค้าน รวมถึงถอนความยินยอมตามที่กฎหมายกำหนด</li>
                            <li><strong>มาตรการความมั่นคงปลอดภัย</strong> : ใช้มาตรการทางเทคนิคและการบริหารจัดการเพื่อป้องกันการเข้าถึงหรือเปิดเผยโดยมิชอบ</li>
                            <li><strong>ช่องทางติดต่อ</strong> : เจ้าหน้าที่คุ้มครองข้อมูลส่วนบุคคล (DPO) ของกระทรวงสาธารณสุข</li>
                        </ol>
                        <p class="small text-muted mb-0">
                            *อ่านรายละเอียดฉบับเต็มได้ที่เว็บไซต์กระทรวงสาธารณสุข:
                            <a href="https://www.ddc.moph.go.th" target="_blank">https://www.ddc.moph.go.th</a>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" data-bs-dismiss="modal">ปิด</button>
                    </div>
                </div>
            </div>
        </div>
    @endpush




    {{-- ====================== การตรวจสอบและอนุมัติ (สำหรับผู้ตรวจสอบ) ====================== --}}
    <hr class="my-4">

    <div class="card admin-review border-2 border-warning shadow-sm position-relative">
        {{-- Ribbon มุมขวาบน --}}
        <div class="position-absolute top-0 start-0 translate-middle-y badge rounded-pill text-bg-warning admin-ribbon ms-3">
            สำหรับผู้ตรวจสอบ
        </div>


        <div class="card-header d-flex align-items-center gap-2 py-3 border-0">
            <i class="ti ti-shield-check fs-4 text-warning"></i>
            <h5 class="mb-0">6. การตรวจสอบและอนุมัติ</h5>
        </div>

        <div class="card-body pt-3">
            <div class="alert alert-warning d-flex align-items-start gap-2 py-2 mb-4">
                <i class="ti ti-alert-triangle fs-5 mt-1"></i>
                <div class="small">
                    ส่วนนี้มีผลต่อสิทธิ์การเข้าถึงและสถานะการใช้งานของผู้ใช้ กรุณาตรวจสอบเอกสารและข้อมูลให้ครบถ้วนก่อนอนุมัติ
                </div>
            </div>

            <div class="row g-3">
                {{-- สถานะการลงทะเบียน --}}
                <div class="col-md-4">
                    <label for="reg_status" class="form-label">สถานะการลงทะเบียน</label>
                    <select name="reg_status" id="reg_status" class="form-select form-select-lg admin-input">
                        @php $status = old('reg_status', $user->reg_status ?? 'รอตรวจสอบ'); @endphp
                        <option value="รอตรวจสอบ" @selected($status === 'รอตรวจสอบ')>รอตรวจสอบ</option>
                        <option value="อนุมัติ" @selected($status === 'อนุมัติ')>อนุมัติ</option>
                        <option value="ไม่อนุมัติ" @selected($status === 'ไม่อนุมัติ')>ไม่อนุมัติ</option>
                    </select>
                </div>

                {{-- หมายเหตุการพิจารณา --}}
                <div class="col-md-8">
                    <label for="reg_review_note" class="form-label">หมายเหตุ/เหตุผลการพิจารณา</label>
                    <textarea name="reg_review_note" id="reg_review_note" class="form-control admin-input" rows="3" placeholder="ระบุเหตุผลประกอบการอนุมัติ/ไม่อนุมัติ (บังคับเมื่อเลือก 'ไม่อนุมัติ')">{{ old('reg_review_note', $user->reg_review_note ?? '') }}</textarea>
                    @error('reg_review_note')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- กำหนดสิทธิ์การใช้งาน --}}
                @php
                    $allRoles = Spatie\Permission\Models\Role::query()
                        ->whereRaw('LOWER(name) not like ?', ['%admin%'])
                        ->where('guard_name', 'web')
                        ->orderBy('ordering', 'asc')
                        ->get(['id', 'name']);
                    $currentRole = isset($user) ? optional($user->getRoleNames()->first()) : null;
                @endphp
                <div class="col-md-4">
                    <label for="role" class="form-label">สิทธิ์การใช้งาน</label>
                    <select name="role_id" id="role" class="form-select admin-input" data-placeholder="--- เลือกสิทธิ์การใช้งาน ---">
                        <option value="">--- เลือกสิทธิ์การใช้งาน ---</option>
                        @foreach ($allRoles as $role)
                            <option value="{{ $role->id }}" {{ $currentRole === $role->name ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- ปุ่มลัดสำหรับผู้ตรวจสอบ --}}
                <div class="col-md-8 d-flex flex-wrap gap-2 align-items-end">
                    <button type="button" class="btn btn-outline-primary" id="btnQuickApprove">
                        <i class="ti ti-check"></i> อนุมัติทันที
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="btnQuickReject">
                        <i class="ti ti-x"></i> ไม่อนุมัติและแจ้งเหตุผล
                    </button>
                    <div class="ms-auto small text-muted">
                        บันทึกจะมีผลทันทีเมื่อกด “บันทึก” ด้านล่าง
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary">
        <i class="ti ti-device-floppy"></i> {{ isset($user) ? 'อัปเดต' : 'บันทึก' }}
    </button>
    <a href="{{ route('backend.application-review.index') }}" class="btn btn-secondary">ยกเลิก</a>
</div>


@push('css')
    <style>
        .admin-review {
            background-color: #fff;
            /* พื้นหลังขาว */
            border: 2px solid #FFE08A;
            /* กรอบเหลืองอ่อน */
            box-shadow: 0 .25rem .5rem rgba(0, 0, 0, .05);
        }

        .admin-review .card-header {
            background: #fff;
            border-bottom: 1px solid #FFE08A;
        }

        .admin-ribbon {
            background: #FFE08A;
            color: #5c3a00;
            padding: .45rem .75rem;
        }

        .admin-input:focus {
            border-color: #ffc10780 !important;
            box-shadow: 0 0 0 .2rem rgba(255, 193, 7, .2) !important;
        }

        .admin-review .alert-warning {
            --bs-alert-bg: #FFF9DB;
            /* กล่องแจ้งเตือนอ่อน ๆ */
            --bs-alert-border-color: #FFE08A;
            --bs-alert-color: #856404;
            padding: .5rem .75rem;
            margin-bottom: 1rem;
        }

        .admin-review::after {
            content: "ADMIN";
            position: absolute;
            right: 1rem;
            bottom: .5rem;
            font-weight: 800;
            letter-spacing: .2rem;
            font-size: clamp(1.25rem, 3vw, 1.75rem);
            color: rgba(0, 0, 0, .05);
            /* watermark จาง ๆ */
            pointer-events: none;
        }
    </style>
@endpush



@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sel = document.getElementById('reg_status');
            const note = document.getElementById('reg_review_note');
            const role = document.getElementById('role'); // สิทธิ์การใช้งาน

            document.getElementById('btnQuickApprove')?.addEventListener('click', () => {
                sel.value = 'อนุมัติ';
                role?.focus(); // เปลี่ยนจาก note เป็น role
            });

            document.getElementById('btnQuickReject')?.addEventListener('click', () => {
                sel.value = 'ไม่อนุมัติ';
                if (!note.value.trim()) note.focus();
            });
        });
    </script>
@endpush


@push('js')
    <script>
        (function() {
            const form = document.getElementById('appReviewForm');
            if (!form) return;

            const statusEl = document.getElementById('reg_status');
            const roleEl = document.getElementById('role');
            const noteEl = document.getElementById('reg_review_note');

            form.addEventListener('submit', async (e) => {
                const status = statusEl?.value || '';

                if (status === 'อนุมัติ' && (!roleEl || !roleEl.value)) {
                    e.preventDefault();
                    await Swal.fire({
                        icon: 'warning',
                        title: 'โปรดเลือกสิทธิ์การใช้งาน',
                        text: 'การอนุมัติผู้ใช้งาน ต้องกำหนดสิทธิ์การใช้งานด้วย',
                        confirmButtonText: 'ตกลง'
                    });
                    roleEl?.focus();
                    return;
                }

                if (status === 'ไม่อนุมัติ' && (!noteEl || !noteEl.value.trim())) {
                    e.preventDefault();
                    await Swal.fire({
                        icon: 'error',
                        title: 'กรุณาระบุเหตุผล',
                        text: 'หากเลือก "ไม่อนุมัติ" จำเป็นต้องกรอกเหตุผลการพิจารณา',
                        confirmButtonText: 'ตกลง'
                    });
                    noteEl?.focus();
                    return;
                }
            });
        })();
    </script>
@endpush
