@php
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

    <div class="col-md-12">
        <label class="form-label d-block required" id="field-{{ \Illuminate\Support\Str::slug('reg_purpose') }}">ในฐานะ</label>
        @php
            $purposes = ['หน่วยบริการสุขภาพผู้เดินทาง', 'ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับจังหวัด (สสจ.)', 'ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับเขต (สคร.)'];
            $selectedPurposes = old('reg_purpose', $user->reg_purpose ?? []);
            if (is_string($selectedPurposes)) {
                $selectedPurposes = json_decode($selectedPurposes, true) ?? explode(',', $selectedPurposes);
            }

            $oldProvince = old('reg_supervise_province_code', $user->reg_supervise_province_code ?? null);
            $oldRegion = old('reg_supervise_region_id', $user->reg_supervise_region_id ?? null);
        @endphp

        @foreach ($purposes as $option)
            <div class="form-check">
                <input class="form-check-input reg-purpose" type="checkbox" name="reg_purpose[]" id="reg_purpose_{{ $loop->index }}" value="{{ $option }}" {{ in_array($option, $selectedPurposes) ? 'checked' : '' }}>
                <label class="form-check-label" for="reg_purpose_{{ $loop->index }}">{{ $option }}</label>
            </div>
        @endforeach
        @error('reg_purpose')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- ========== สสจ. → เลือกจังหวัด ========== --}}
    <div class="col-md-6 d-none" id="wrap_supervise_province">
        <label for="reg_supervise_province_code" class="form-label required">
            เลือกจังหวัด (สำหรับบทบาทผู้กำกับดูแลระดับจังหวัด - สสจ.)
        </label>
        <select id="reg_supervise_province_code" name="reg_supervise_province_code" class="form-select" data-placeholder="--- เลือกจังหวัดของท่าน ---">
            @php
                // ดึงจาก Blade (ถ้ายังไม่ได้ดึงมาก่อน)
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
    <div class="col-md-6 d-none" id="wrap_supervise_region">
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
                // --- init Choices ปกติ ---
                const provinceChoices = new Choices('#reg_supervise_province_code', {
                    searchEnabled: true,
                    itemSelectText: '',
                    shouldSort: false
                    // อย่าใส่ classNames.containerOuter เป็น string ที่มีช่องว่าง
                });

                const regionChoices = new Choices('#reg_supervise_region_id', {
                    searchEnabled: true,
                    itemSelectText: '',
                    shouldSort: false
                });

                // --- เติมคลาสเสริมให้ container ภายหลัง ---
                // วิธี 1: อิงจาก input id
                document.querySelector('#reg_supervise_province_code')
                    .closest('.choices')
                    ?.classList.add('choices--lightable');

                document.querySelector('#reg_supervise_region_id')
                    .closest('.choices')
                    ?.classList.add('choices--lightable');

                // ===== Checkbox สสจ./สคร. (exclusive) + โชว์/ซ่อน select =====
                const cbProv = document.getElementById('reg_purpose_1'); // สสจ.
                const cbRegion = document.getElementById('reg_purpose_2'); // สคร.
                const wrapProv = document.getElementById('wrap_supervise_province');
                const wrapReg = document.getElementById('wrap_supervise_region');

                function show(el) {
                    el.classList.remove('d-none');
                }

                function hide(el) {
                    el.classList.add('d-none');
                }

                function clearChoices(instance) {
                    // ล้างค่าเลือก (ไม่ลบ options)
                    instance.removeActiveItems();
                    instance.setChoiceByValue(''); // เผื่อมี option ว่าง
                }

                function toggleExclusive() {
                    // กันเลือกซ้อน
                    if (cbProv.checked) {
                        cbRegion.checked = false;
                        cbRegion.disabled = true;
                        show(wrapProv);
                        hide(wrapReg);
                        clearChoices(regionChoices);
                    } else {
                        cbRegion.disabled = false;
                        hide(wrapProv);
                        clearChoices(provinceChoices);
                    }

                    if (cbRegion.checked) {
                        cbProv.checked = false;
                        cbProv.disabled = true;
                        show(wrapReg);
                        hide(wrapProv);
                        clearChoices(provinceChoices);
                    } else {
                        cbProv.disabled = false;
                        if (!cbProv.checked) {
                            hide(wrapReg);
                            clearChoices(regionChoices);
                        }
                    }
                }

                toggleExclusive();
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
                    <input type="text" name="org_name" id="org_name" value="{{ old('org_name', $user->org_name ?? '') }}" class="form-control">
                    @error('org_name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="org_affiliation" class="form-label required">สังกัด</label>
                    <select name="org_affiliation" id="org_affiliation" class="form-select">
                        <option value="">--- เลือก ---</option>
                        @foreach (['สำนักงานปลัดกระทรวงสาธารณสุข', 'กรมควบคุมโรค', 'กรมการแพทย์', 'กรมสุขภาพจิต', 'สภากาชาดไทย', 'สำนักการแพทย์ กรุงเทพมหานคร', 'กระทรวงอุดมศึกษา วิทยาศาสตร์ วิจัยและนวัตกรรม', 'กระทรวงกลาโหม', 'องค์กรปกครองส่วนท้องถิ่น', 'องค์การมหาชน', 'เอกชน', 'อื่น ๆ'] as $option)
                            <option value="{{ $option }}" @selected(old('org_affiliation', $user->org_affiliation ?? '') == $option)>
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
                    <input type="text" name="org_affiliation_other" id="org_affiliation_other" class="form-control" value="{{ old('org_affiliation_other', $user->org_affiliation_other ?? '') }}">
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
                    <input type="text" name="org_tel" id="org_tel" value="{{ old('org_tel', $user->org_tel ?? '') }}" class="form-control">
                    @error('org_tel')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- ========== Address + Map (Leaflet + Nominatim) ========== --}}
                <div class="col-12">
                    <label for="org_address" class="form-label required">ที่อยู่หน่วยบริการ</label>
                    <textarea name="org_address" id="org_address" rows="2" class="form-control" placeholder="พิมพ์ที่อยู่ให้ละเอียด แล้วกด “ค้นหาพิกัดจากที่อยู่”">{{ old('org_address', $user->org_address ?? '') }}</textarea>
                    @error('org_address')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <div class="d-flex align-items-center gap-2 my-2">
                        <button type="button" id="btn-geocode" class="btn btn-outline-primary btn-sm">
                            ค้นหาพิกัดจากที่อยู่
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
                    <input type="text" name="org_lat" id="org_lat" value="{{ old('org_lat', $user->org_lat ?? '') }}" class="form-control" placeholder="จะถูกกรอกอัตโนมัติเมื่อเลือกพิกัด">
                </div>

                <div class="col-md-6">
                    <label for="org_lng" class="form-label">Longitude</label>
                    <input type="text" name="org_lng" id="org_lng" value="{{ old('org_lng', $user->org_lng ?? '') }}" class="form-control" placeholder="จะถูกกรอกอัตโนมัติเมื่อเลือกพิกัด">
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
                            const initZoom = hasLatLng ? 14 : 6;

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

                        });
                    </script>
                @endpush





                @php
                    $dayOptions = [
                        'mon' => 'จันทร์',
                        'tue' => 'อังคาร',
                        'wed' => 'พุธ',
                        'thu' => 'พฤหัสบดี',
                        'fri' => 'ศุกร์',
                        'sat' => 'เสาร์',
                        'sun' => 'อาทิตย์',
                    ];
                    $existing = old('working_hours', $user->org_working_hours ?? []);
                @endphp

                <div class="col-12">
                    <label class="form-label">วัน-เวลาทำการ</label>

                    {{-- รายการช่วงวัน–เวลา (จะถูกเติมด้วย JS) --}}
                    <div id="wh-rows" class="vstack gap-2"></div>

                    <div class="mt-2 d-flex flex-wrap gap-2">
                        <button type="button" id="btnAddWh" class="btn btn-outline-primary btn-sm">
                            <i class="ti ti-plus"></i> เพิ่มช่วงวัน–เวลา
                        </button>
                    </div>

                    {{-- ตัวช่วยกรอกเร็ว (Batch) --}}
                    <div class="mt-3 border border-info rounded p-3 bg-info-subtle">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 mb-2">
                                <h6 class="text-dark mb-0">
                                    <i class="ti ti-bolt me-1"></i> ตัวช่วยกรอกแบบรวดเร็ว
                                </h6>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-0">เริ่ม</label>
                                <input type="text" id="batchStart" class="form-control" placeholder="เช่น 08:30">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-0">สิ้นสุด</label>
                                <input type="text" id="batchEnd" class="form-control" placeholder="เช่น 16:30">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-0">หมายเหตุ</label>
                                <input type="text" id="batchNote" class="form-control" placeholder="ถ้ามี">
                            </div>
                            <div class="col-md-2">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="batchClosed">
                                    <label class="form-check-label" for="batchClosed">ปิดให้บริการทั้งวัน</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="batchClear">
                                    <label class="form-check-label" for="batchClear">ล้างรายการเดิมก่อนเติม</label>
                                </div>
                                <div class="d-grid d-md-flex gap-2 mt-2">
                                    <button type="button" id="btnFillAllDays" class="btn btn-secondary btn-sm">เติมทุกวัน</button>
                                    <button type="button" id="btnFillWeekdays" class="btn btn-secondary btn-sm">เติมเฉพาะจันทร์–ศุกร์</button>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2">
                            เลือกช่วงเวลา/หมายเหตุครั้งเดียวแล้วกดปุ่ม ระบบจะสร้างแถวอัตโนมัติ (ถ้าเลือก “ปิดให้บริการทั้งวัน” จะไม่ใช้เวลา)
                        </small>
                    </div>

                    <small class="text-muted d-block mt-2">
                        เพิ่มหลายแถวได้ (วันเดียวกันได้หลายช่วง) • ใช้ช่อง “ปิดให้บริการ” สำหรับวันที่ปิดทั้งวัน
                    </small>

                    {{-- ข้อความอิสระเพิ่มเติม (ถ้าต้องการเก็บบันทึก/คำอธิบาย) --}}
                    <div class="mt-3">
                        <label for="org_working_hours" class="form-label">คำอธิบายเพิ่มเติม (ไม่บังคับ)</label>
                        <textarea name="org_working_hours" id="org_working_hours" rows="2" class="form-control">{{ old('org_working_hours', $user->org_working_hours_text ?? '') }}</textarea>
                    </div>
                </div>

                {{-- Template แถวเดี่ยว --}}
                <template id="wh-row-tpl">
                    <div class="card p-2 border rounded wh-row">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label mb-0">วัน</label>
                                <select class="form-select wh-day" name="">
                                    @foreach ($dayOptions as $k => $v)
                                        <option value="{{ $k }}">{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-0">เริ่ม</label>
                                {{-- เปลี่ยนเป็น text เพื่อใช้ Flatpickr --}}
                                <input type="text" class="form-control wh-start" name="">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-0">สิ้นสุด</label>
                                <input type="text" class="form-control wh-end" name="">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-0">หมายเหตุ</label>
                                <input type="text" class="form-control wh-note" name="" placeholder="ถ้ามี">
                            </div>
                            <div class="col-md-2">
                                <div class="form-check mt-4">
                                    <input class="form-check-input wh-closed" type="checkbox" name="">
                                    <label class="form-check-label">ปิดให้บริการ</label>
                                </div>
                            </div>

                            <div class="col-12 d-flex justify-content-end">
                                <button type="button" class="btn btn-outline-danger btn-sm wh-remove">
                                    <i class="ti ti-x"></i> ลบช่วงนี้
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                @push('js')
                    <script>
                        (function() {
                            const container = document.getElementById('wh-rows');
                            const tpl = document.getElementById('wh-row-tpl').content;
                            const addBtn = document.getElementById('btnAddWh');

                            // Batch controls
                            const batchStart = document.getElementById('batchStart');
                            const batchEnd = document.getElementById('batchEnd');
                            const batchNote = document.getElementById('batchNote');
                            const batchClosed = document.getElementById('batchClosed');
                            const batchClear = document.getElementById('batchClear');
                            const btnFillAll = document.getElementById('btnFillAllDays');
                            const btnFillWeek = document.getElementById('btnFillWeekdays');

                            const DAYS_ALL = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
                            const DAYS_WEEK = ['mon', 'tue', 'wed', 'thu', 'fri'];

                            let idx = 0;

                            // ===== ตัวเลือก timepicker กลาง (24 ชม.) =====
                            const timeOpts = {
                                enableTime: true,
                                noCalendar: true,
                                dateFormat: 'H:i', // แสดง/ส่งค่าเป็น HH:mm
                                time_24hr: true,
                                minuteIncrement: 5,
                                allowInput: true,
                                disableMobile: true
                            };

                            // ผูก timepicker กับ Batch
                            flatpickr(batchStart, timeOpts);
                            flatpickr(batchEnd, timeOpts);

                            // ฟังก์ชันผูก timepicker กับแถวใหม่
                            window.initWhTimePickers = function(rowEl) {
                                flatpickr(rowEl.querySelector('.wh-start'), timeOpts);
                                flatpickr(rowEl.querySelector('.wh-end'), timeOpts);
                            };

                            function addRow(rowData = null) {
                                const node = document.importNode(tpl, true);
                                const row = node.querySelector('.wh-row');

                                const day = row.querySelector('.wh-day');
                                const start = row.querySelector('.wh-start');
                                const end = row.querySelector('.wh-end');
                                const note = row.querySelector('.wh-note');
                                const closed = row.querySelector('.wh-closed');

                                day.name = `working_hours[${idx}][day]`;
                                start.name = `working_hours[${idx}][start]`;
                                end.name = `working_hours[${idx}][end]`;
                                note.name = `working_hours[${idx}][note]`;
                                closed.name = `working_hours[${idx}][closed]`;

                                if (rowData) {
                                    if (rowData.day) day.value = rowData.day;
                                    if (rowData.start) start.value = rowData.start; // ควรเป็นรูปแบบ HH:mm
                                    if (rowData.end) end.value = rowData.end;
                                    if (rowData.note) note.value = rowData.note;
                                    if (rowData.closed) {
                                        closed.checked = true;
                                    }
                                }

                                // แปะลง DOM ก่อน แล้วค่อยผูก Flatpickr
                                container.appendChild(row);
                                window.initWhTimePickers(row);

                                // ถ้า closed ให้ disable และเคลียร์ด้วย
                                toggleClosed(row, closed.checked);

                                closed.addEventListener('change', (e) => toggleClosed(row, e.target.checked));
                                row.querySelector('.wh-remove').addEventListener('click', () => row.remove());

                                idx++;
                            }

                            function toggleClosed(row, isClosed) {
                                const start = row.querySelector('.wh-start');
                                const end = row.querySelector('.wh-end');

                                start.disabled = end.disabled = isClosed;

                                // เคลียร์ค่าทั้ง input และอินสแตนซ์ flatpickr ถ้ามี
                                const fps = start._flatpickr;
                                const fpe = end._flatpickr;
                                if (isClosed) {
                                    if (fps) fps.clear();
                                    else start.value = '';
                                    if (fpe) fpe.clear();
                                    else end.value = '';
                                }
                            }

                            addBtn.addEventListener('click', () => addRow());

                            // โหลดข้อมูลเดิมจาก PHP
                            const existing = @json($existing);
                            if (Array.isArray(existing) && existing.length) {
                                existing.forEach(r => addRow(r));
                            } else {
                                addRow();
                            }

                            // ===== Batch Fill =====
                            function fillDays(days) {
                                const isClosed = batchClosed.checked;
                                const startVal = batchStart.value || '';
                                const endVal = batchEnd.value || '';
                                const noteVal = batchNote.value || '';

                                if (!isClosed) {
                                    // เทียบรูปแบบ HH:mm ได้ตรง ๆ
                                    if (!startVal || !endVal || endVal <= startVal) {
                                        alert('กรุณาใส่เวลาเริ่ม/สิ้นสุดให้ถูกต้อง (สิ้นสุดต้องมากกว่าเริ่ม)');
                                        return;
                                    }
                                }

                                if (batchClear && batchClear.checked) {
                                    container.innerHTML = '';
                                    idx = 0;
                                }

                                days.forEach(d => {
                                    addRow({
                                        day: d,
                                        closed: isClosed,
                                        start: isClosed ? null : startVal,
                                        end: isClosed ? null : endVal,
                                        note: noteVal || null
                                    });
                                });
                            }

                            btnFillAll?.addEventListener('click', () => fillDays(DAYS_ALL));
                            btnFillWeek?.addEventListener('click', () => fillDays(DAYS_WEEK));
                        })();
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


    {{-- ====================== การตรวจสอบและอนุมัติ ====================== --}}
    <hr class="my-4">
    <h5>6. การตรวจสอบและอนุมัติ (สำหรับผู้ตรวจสอบ)</h5>

    <div class="col-12">
        <div class="row g-3">

            {{-- สถานะการลงทะเบียน --}}
            <div class="col-md-4">
                <label for="reg_status" class="form-label">สถานะการลงทะเบียน</label>
                <select name="reg_status" id="reg_status" class="form-select">
                    @php
                        $status = old('reg_status', $user->reg_status ?? 'รอตรวจสอบ');
                    @endphp
                    <option value="รอตรวจสอบ" @selected($status === 'รอตรวจสอบ')>รอตรวจสอบ</option>
                    <option value="อนุมัติ" @selected($status === 'อนุมัติ')>อนุมัติ</option>
                    <option value="ไม่อนุมัติ" @selected($status === 'ไม่อนุมัติ')>ไม่อนุมัติ</option>
                </select>
                @error('reg_status')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror

                {{-- แสดงประวัติอนุมัติ --}}
                {{-- <div class="form-text mt-1">
                    @if ($user?->approved_at)
                        อนุมัติเมื่อ: {{ $user->approved_at->format('d/m/Y H:i') }}
                        โดย: {{ optional(\App\Models\User::find($user->approved_by))->name ?? 'System' }}
                    @else
                        ยังไม่อนุมัติ
                    @endif
                </div> --}}
            </div>

            {{-- ตรวจสอบไฟล์เอกสารเจ้าหน้าที่ --}}
            {{-- <div class="col-md-4">
                <label class="form-label d-block">การตรวจสอบเอกสารเจ้าหน้าที่</label>
                @php
                    $verified = old('officer_doc_verified', !empty($user?->officer_doc_verified_at) ? '1' : '0');
                @endphp
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="officer_doc_verified" id="doc_verified_1" value="1" @checked($verified === '1')>
                    <label class="form-check-label" for="doc_verified_1">ตรวจสอบแล้ว</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="officer_doc_verified" id="doc_verified_0" value="0" @checked($verified === '0')>
                    <label class="form-check-label" for="doc_verified_0">ยังไม่ตรวจสอบ</label>
                </div>
                @error('officer_doc_verified')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror

                <div class="form-text mt-1">
                    @if ($user?->officer_doc_verified_at)
                        ตรวจแล้วเมื่อ: {{ $user->officer_doc_verified_at->format('d/m/Y H:i') }}
                        โดย: {{ optional(\App\Models\User::find($user->officer_doc_verified_by))->name ?? 'System' }}
                    @else
                        ยังไม่ตรวจเอกสาร
                    @endif
                </div>
            </div> --}}

            {{-- หมายเหตุการพิจารณา --}}
            <div class="col-md-12">
                <label for="reg_review_note" class="form-label">หมายเหตุ/เหตุผลการพิจารณา</label>
                <textarea name="reg_review_note" id="reg_review_note" class="form-control" rows="3" placeholder="ระบุหมายเหตุสำหรับการอนุมัติ/ไม่อนุมัติ (ถ้ามี)">{{ old('reg_review_note', $user->reg_review_note ?? '') }}</textarea>
                @error('reg_review_note')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>


            {{-- กำหนดสิทธิ์การใช้งาน ไม่นับสิทธิ์ admin --}}
            @php
                $allRoles = Spatie\Permission\Models\Role::query()
                    ->whereRaw('LOWER(name) not like ?', ['%admin%'])
                    ->where('guard_name', 'web')
                    ->orderBy('name')
                    ->get(['id', 'name']);

                // ชื่อ role ปัจจุบัน (ถ้ามี)
                $currentRole = isset($user) ? optional($user->getRoleNames()->first()) : null;
            @endphp
            <div class="col-md-4">
                <label for="role" class="form-label">สิทธิ์การใช้งาน</label>
                <select name="role_id" id="role" class="form-select" data-placeholder="--- เลือกสิทธิ์การใช้งาน ---">
                    <option value="">--- เลือกสิทธิ์การใช้งาน ---</option>
                    @foreach ($allRoles ?? [] as $role)
                        <option value="{{ $role->id }}" {{ $currentRole === $role->name ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
                @error('role_id')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
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
