{{-- resources/views/backend/service_unit_profile/_form.blade.php --}}


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

    {{-- =================== ข้อมูลหน่วยบริการพื้นฐาน =================== --}}
    <div class="col-12">
        <label for="org_name" class="form-label required">ชื่อหน่วยบริการ/หน่วยงาน</label>
        <input type="text" name="org_name" id="org_name" value="{{ old('org_name', $unit->org_name ?? '') }}" class="form-control @error('org_name') is-invalid @enderror" required>
        @error('org_name')
            <div class="invalid-feedback">{{ $message }}</div>
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
        <label for="org_tel" class="form-label">หมายเลขโทรศัพท์</label>
        <input type="text" name="org_tel" id="org_tel" value="{{ old('org_tel', $unit->org_tel ?? ($unit->org_phone ?? '')) }}" class="form-control @error('org_tel') is-invalid @enderror">
        @error('org_tel')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="org_address" class="form-label">ที่อยู่หน่วยบริการ</label>
        <textarea name="org_address" id="org_address" rows="2" class="form-control @error('org_address') is-invalid @enderror" placeholder="พิมพ์ที่อยู่ให้ละเอียด แล้วกด “ค้นหาพิกัดจากที่อยู่”">{{ old('org_address', $unit->org_address ?? '') }}</textarea>
        @error('org_address')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Chain select จังหวัด/อำเภอ/ตำบล/รหัสไปรษณีย์ (ใช้ partial เดิมของคุณ) --}}
    {!! thGeoSelect('org_', [
        'province_code' => old('org_province_code', $unit->org_province_code ?? ''),
        'district_code' => old('org_district_code', $unit->org_district_code ?? ''),
        'subdistrict_code' => old('org_subdistrict_code', $unit->org_subdistrict_code ?? ''),
        'postcode' => old('org_postcode', $unit->org_postcode ?? ''),
        'required' => true,
    ]) !!}

    {{-- =================== แผนที่ (Leaflet) =================== --}}
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

    <div class="col-md-3">
        <label for="org_lat" class="form-label">Latitude</label>
        <input type="text" name="org_lat" id="org_lat" value="{{ old('org_lat', $unit->org_lat ?? '') }}" class="form-control @error('org_lat') is-invalid @enderror" placeholder="จะถูกกรอกอัตโนมัติเมื่อเลือกพิกัด">
        @error('org_lat')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-3">
        <label for="org_lng" class="form-label">Longitude</label>
        <input type="text" name="org_lng" id="org_lng" value="{{ old('org_lng', $unit->org_lng ?? '') }}" class="form-control @error('org_lng') is-invalid @enderror" placeholder="จะถูกกรอกอัตโนมัติเมื่อเลือกพิกัด">
        @error('org_lng')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
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

                const hasLatLng = latEl.value && lngEl.value &&
                    !isNaN(parseFloat(latEl.value)) && !isNaN(parseFloat(lngEl.value));

                const TH_CENTER = [13.853450, 100.527171]; // กรมควบคุมโรค
                const initLatLng = hasLatLng ? [parseFloat(latEl.value), parseFloat(lngEl.value)] : TH_CENTER;
                const initZoom = hasLatLng ? 17 : 6;

                // สร้างแผนที่
                const map = L.map('map', {
                    scrollWheelZoom: true
                }).setView(initLatLng, initZoom);
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

                marker.on('dragend', e => {
                    const {
                        lat,
                        lng
                    } = e.target.getLatLng();
                    setCoord(lat, lng, map.getZoom(), false);
                });

                map.on('click', e => setCoord(e.latlng.lat, e.latlng.lng));

                // ค้นหาพิกัดจากข้อความที่อยู่
                async function doGeocode() {
                    const q = (addrEl.value || '').trim();
                    if (!q) {
                        addrEl.focus();
                        return;
                    }
                    const url = new URL('https://nominatim.openstreetmap.org/search');
                    url.searchParams.set('q', q + ', Thailand');
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
                            alert('ไม่พบพิกัดจากที่อยู่นี้ กรุณาระบุให้ละเอียดขึ้น');
                        }
                    } catch (err) {
                        console.error(err);
                        alert('เกิดข้อผิดพลาดในการค้นหาพิกัด');
                    }
                }

                addrEl.addEventListener('keydown', (ev) => {
                    if (ev.key === 'Enter') {
                        ev.preventDefault();
                        doGeocode();
                    }
                });
                document.getElementById('btn-geocode').addEventListener('click', doGeocode);

                // ศูนย์กลางตำบลจากรหัสที่เลือกใน chain select
                const subdistrictSelect =
                    document.querySelector('select[name="org_subdistrict_code"]') ||
                    document.querySelector('[id^="geo_"] select[id$="_subdistrict"]');

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
                        setCoord(data.lat, data.lng, 12); // ซูมออกหน่อยตามที่คุณต้องการ
                    } catch (e) {
                        console.error(e);
                        alert('ไม่พบพิกัดศูนย์กลางของตำบลนี้ หรือเกิดข้อผิดพลาด');
                    }
                }
                document.getElementById('btn-from-subdistrict').addEventListener('click', moveToSubdistrictCenter);

                // ปุ่มอำนวยความสะดวก
                const initialState = {
                    lat: initLatLng[0],
                    lng: initLatLng[1],
                    zoom: initZoom
                };
                document.getElementById('btn-center-marker').addEventListener('click', () => {
                    const {
                        lat,
                        lng
                    } = marker.getLatLng();
                    map.flyTo([lat, lng], Math.max(map.getZoom(), 16));
                });
                document.getElementById('btn-reset-initial').addEventListener('click', () => {
                    setCoord(initialState.lat, initialState.lng, initialState.zoom, true);
                });

                // guard ค่าที่พิมพ์เองในช่อง lat/lng
                function sanitizeCoordInput(el, min, max) {
                    el.addEventListener('input', () => {
                        el.value = el.value.replace(/[^0-9.\-]/g, '');
                    });
                    el.addEventListener('change', () => {
                        let n = parseFloat(el.value);
                        if (isNaN(n)) return;
                        n = Math.max(min, Math.min(max, n));
                        el.value = n.toFixed(6);
                        setCoord(parseFloat(latEl.value || TH_CENTER[0]),
                            parseFloat(lngEl.value || TH_CENTER[1]),
                            map.getZoom());
                    });
                }
                sanitizeCoordInput(latEl, -90, 90);
                sanitizeCoordInput(lngEl, -180, 180);

                if (hasLatLng) {
                    badgeEl.textContent = `Lat: ${parseFloat(latEl.value).toFixed(6)} , Lng: ${parseFloat(lngEl.value).toFixed(6)}`;
                    badgeEl.classList.remove('d-none');
                }
            });
        </script>
    @endpush

    {{-- =================== วัน-เวลาทำการ (ลากเมาส์เลือกช่วงเวลา) =================== --}}
    @php
        $whRaw = old('working_hours_json', $unit->org_working_hours_json ?? []);
        $initWorkingHours = is_string($whRaw) ? $whRaw : json_encode($whRaw, JSON_UNESCAPED_UNICODE);
    @endphp

    <div class="col-12">
        <label class="form-label fw-semibold">วัน-เวลาทำการ</label>
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
                                {{-- หัวคอลัมน์เวลา เติมด้วย JS --}}
                            </tr>
                        </thead>
                        <tbody>
                            {{-- แถววัน เติมด้วย JS --}}
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <div class="alert alert-secondary py-2 small mb-2">
                        วิธีใช้: คลิกหรือลากเมาส์บนช่องเวลาเพื่อเลือกเปิดทำการ (สีเขียว) • ดับเบิลคลิกเพื่อเลือก/ยกเลิกทั้งวัน • ปุ่ม “ล้างวันนี้” จะลบเฉพาะวันนั้น
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
            .slot {
                user-select: none;
                cursor: crosshair;
                min-width: 72px;
                text-align: center;
                font-variant-numeric: tabular-nums;
            }

            .slot.selected {
                background: var(--bs-success-bg-subtle);
                outline: 2px solid rgba(var(--bs-success-rgb), .5);
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

                // สร้างหัวคอลัมน์เวลา
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
                        (dragMode === 'select') ? schedule[dayKey].add(hour): schedule[dayKey].delete(hour);
                        updateVisual();
                        updateOutputs();
                    });

                    td.addEventListener('mouseenter', () => {
                        if (!dragging || dragDayKey !== dayKey) return;
                        (dragMode === 'select') ? schedule[dayKey].add(hour): schedule[dayKey].delete(hour);
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
                    const lines = Object.keys(obj).map(k => `${map[k]} : ${obj[k].length ? obj[k].join(', ') : '— ปิดทำการ —'}`);
                    document.getElementById('working-hours-preview').textContent = lines.join('\n');
                }

                // โหลดค่าเริ่มต้นจากฐานข้อมูล (ถ้ามี)
                try {
                    const init = JSON.parse(document.getElementById('working_hours_json').value || '{}');
                    for (const d of days) {
                        const ranges = init?.[d.key] ?? [];
                        schedule[d.key].clear();
                        for (const r of ranges) {
                            const [a, b] = r.split('-');
                            const ah = parseInt(a),
                                bh = parseInt(b);
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

    {{-- =================== ปุ่มบันทึก =================== --}}
    <div class="col-12">
        <div class="d-flex gap-2 justify-content-end">
            <a href="{{ url()->previous() }}" class="btn btn-light">
                <i class="ph-duotone ph-arrow-left"></i> ย้อนกลับ
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="ph-duotone ph-floppy-disk"></i> บันทึกข้อมูล
            </button>
        </div>
    </div>
</div>
