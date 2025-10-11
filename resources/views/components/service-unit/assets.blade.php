@once
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
@endonce

@once
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
@endonce

@once
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
@endonce

@once
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
                        (dragMode === 'select') ?
                        schedule[dayKey].add(hour): schedule[dayKey].delete(hour);
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
@endonce
