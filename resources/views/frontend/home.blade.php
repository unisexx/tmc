{{-- resources/views/contact.blade.php --}}
@extends('layouts.frontend')

@section('title', 'ติดต่อเรา')

@section('page_header')
@endsection

@section('content')
    {{-- resources/views/frontend/home.blade.php --}}
    <!-- #################### Slider #################### -->
    @if ($highlights->count())
        <div id="myCarousel" class="carousel slide highlight" data-bs-ride="carousel">
            <div class="carousel-indicators">
                @foreach ($highlights as $index => $item)
                    <button type="button" data-bs-target="#myCarousel" data-bs-slide-to="{{ $index }}" class="{{ $loop->first ? 'active' : '' }}" aria-label="{{ $item->title ?: 'สไลด์ ' . ($index + 1) }}"></button>
                @endforeach
            </div>

            <div class="carousel-inner">
                @foreach ($highlights as $item)
                    <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                        @php
                            $imgUrl = asset('storage/' . ltrim($item->image_path, '/'));
                            $imgAlt = $item->title ?: 'ไฮไลต์ที่ ' . $loop->iteration;
                        @endphp

                        <figure class="m-0">
                            @if ($item->link_url)
                                <a href="{{ $item->link_url }}" target="_blank" rel="noopener">
                                    <img src="{{ $imgUrl }}" class="d-block w-100" alt="{{ $imgAlt }}" loading="lazy" decoding="async" fetchpriority="{{ $loop->first ? 'high' : 'low' }}">
                                </a>
                            @else
                                <img src="{{ $imgUrl }}" class="d-block w-100" alt="{{ $imgAlt }}" loading="lazy" decoding="async" fetchpriority="{{ $loop->first ? 'high' : 'low' }}">
                            @endif

                            {{-- ซ่อนข้อความเพื่อ SEO แต่ไม่ให้เห็นบนจอ --}}
                            @if ($item->title || $item->description)
                                <figcaption class="visually-hidden">
                                    {{ trim($item->title . ' ' . ($item->description ?? '')) }}
                                </figcaption>
                            @endif
                        </figure>
                    </div>
                @endforeach
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#myCarousel" data-bs-slide="prev" aria-label="ก่อนหน้า">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#myCarousel" data-bs-slide="next" aria-label="ถัดไป">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
            </button>
        </div>
    @endif



    <!-- #################### Banner Register #################### -->
    <div class="banner-register py-3">
        <div class="container m-0">
            <div class="row">
                <div class="col-lg-10 col-xl-10 d-flex flex-row justify-content-between align-items-center mx-auto position-relative flex-wrap">
                    <!-- ข้อความซ้าย -->
                    <div class="fs-4 text-white d-flex align-items-center mb-2 mb-lg-0">
                        สมัครสมาชิกเพื่อเข้าใช้งานระบบ
                        <img src="images/arrow.svg" alt="" width="50" class="ms-3">
                    </div>

                    <!-- กล่องปุ่ม -->
                    <div class="register-box-color">
                        <div class="register-box ms-lg-auto">
                            <a href="login.html" class="btn btn-login me-lg-3">
                                <i class="bi bi-lock fs-5"></i> เข้าสู่ระบบ
                            </a>
                            <a href="register.html" class="btn btn-register">
                                <i class="bi bi-person-plus fs-4"></i> สมัครสมาชิก
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- #################### Health Services Section #################### -->
    <section id="services" class="py-5 bg-light-custom">
        <div class="container pt-lg-2">
            <h2 class="section-title">หน่วยบริการสุขภาพผู้เดินทาง</h2>
            <div class="row mb-4 align-items-end">
                <div class="col-md-6 col-lg-7 mb-3">
                    <div class="input-group">
                        <input type="text" id="searchService" class="form-control form-control-lg" placeholder="ค้นหาชื่อหน่วยบริการหรือจังหวัด">
                    </div>
                </div>
                <div class="col-md-3 col-lg-3 mb-3">
                    <select id="serviceLevel" class="form-select select-lg">
                        <option value="">ระดับทั้งหมด</option>
                        <option value="basic">พื้นฐาน</option>
                        <option value="intermediate">กลาง</option>
                        <option value="advanced">สูง</option>
                    </select>
                </div>
                <div class="col-md-3 col-lg-2 mb-3 text-end">
                    <button class="btn btn-lg btn-search-hs w-100" type="button" id="searchButton">
                        <i class="bi bi-search"></i> ค้นหา
                    </button>
                </div>
            </div>

            <div id="map-section" class="card card-custom p-3 mb-4">
                <div class="row g-3">
                    <div class="col-lg-7">
                        <div id="map"></div>
                    </div>
                    <div class="col-lg-5">
                        <div class="mb-2">
                            <div class="mb-1 namelist">รายการหน่วยบริการ</div>
                            <small class="text-muted">คลิกที่รายการเพื่อดูรายละเอียด หรือกดปุ่มส่งข้อความ</small>
                        </div>
                        <div id="facilityList" class="list-group"></div>
                    </div>
                </div>
            </div>
            <!-- MESSAGE MODAL (ส่งข้อความถึงหน่วยบริการ) -->
            <div class="modal fade" id="messageModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content card-custom p-3">
                        <div class="modal-header">
                            <h5 class="modal-title" id="messageModalTitle" style="color:var(--kc-primary);">ส่งข้อความถึงหน่วยบริการ
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                        </div>
                        <div class="modal-body">
                            <form id="messageForm">
                                <input type="hidden" id="msgFacilityId" />
                                <div class="mb-2">
                                    <label class="form-label">ถึง</label>
                                    <input id="msgTo" class="form-control" readonly />
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">ชื่อ-นามสกุล</label>
                                    <input id="msgName" class="form-control" required />
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">อีเมล</label>
                                    <input id="msgEmail" type="email" class="form-control" required />
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">ข้อความ</label>
                                    <textarea id="msgBody" class="form-control" rows="5" required></textarea>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn" style="background:var(--kc-primary); color:#fff;">ส่งข้อความ</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @push('scripts')
        <script>
            /// #################### MAP leaflet ####################
            document.addEventListener('DOMContentLoaded', () => {
                // ---------- Sample Data ----------
                const sampleFacilities = [{
                        id: 'F001',
                        name: 'ศูนย์บริการสุขภาพผู้เดินทาง กรุงเทพกลาง',
                        province: 'กรุงเทพมหานคร',
                        lat: 13.7563,
                        lon: 100.5018,
                        level: "สูง",
                        services: ['ให้คำปรึกษา/คำแนะนำก่อนเดินทาง', 'วัคซีนสำหรับผู้เดินทาง'],
                        phone: '02-123-4567',
                        email: 'tbcenter@example.go.th',
                        address: 'แขวง/เขต ตัวอย่าง กทม.'
                    },
                    {
                        id: 'F002',
                        name: 'คลินิกผู้เดินทาง เชียงใหม่',
                        province: 'เชียงใหม่',
                        lat: 18.7877,
                        lon: 98.9931,
                        level: "กลาง",
                        services: ['ตรวจคัดกรองโรคติดเชื้อ', 'ให้คำปรึกษา/คำแนะนำก่อนเดินทาง'],
                        phone: '053-234-567',
                        email: 'chiangmai.travelclinic@example.go.th',
                        address: 'อ.เมือง จ.เชียงใหม่'
                    },
                    {
                        id: 'F003',
                        name: 'โรงพยาบาลท่าเรือ ภูเก็ต',
                        province: 'ภูเก็ต',
                        lat: 7.8804,
                        lon: 98.3923,
                        level: "พื้นฐาน",
                        services: ['วัคซีนสำหรับผู้เดินทาง', 'บริการแปล/รับรองภาษา'],
                        phone: '076-345-678',
                        email: 'phuket.porthospital@example.go.th',
                        address: 'ถ.ท่าเรือ จ.ภูเก็ต'
                    },
                    {
                        id: 'F004',
                        name: 'ศูนย์บริการสุขภาพนานาชาติ',
                        province: 'ภูเก็ต',
                        lat: 7.8904,
                        lon: 98.4000,
                        level: "สูง",
                        services: ['ให้คำปรึกษา/คำแนะนำก่อนเดินทาง', 'ตรวจคัดกรองโรคติดเชื้อ'],
                        phone: '076-555-888',
                        email: 'international@example.go.th',
                        address: 'ถ.วิชิตสงคราม จ.ภูเก็ต'
                    },
                    {
                        id: 'F005',
                        name: 'คลินิกนักท่องเที่ยว พัทยา',
                        province: 'ชลบุรี',
                        lat: 12.9274,
                        lon: 100.8778,
                        level: "กลาง",
                        services: ['วัคซีนสำหรับผู้เดินทาง', 'บริการแปล/รับรองภาษา'],
                        phone: '038-111-222',
                        email: 'pattaya.clinic@example.go.th',
                        address: 'อ.บางละมุง จ.ชลบุรี'
                    },
                    {
                        id: 'F006',
                        name: 'โรงพยาบาลสนามบินสุวรรณภูมิ',
                        province: 'สมุทรปราการ',
                        lat: 13.6900,
                        lon: 100.7501,
                        level: "สูง",
                        services: ['ให้คำปรึกษา/คำแนะนำก่อนเดินทาง', 'ตรวจคัดกรองโรคติดเชื้อ', 'วัคซีนสำหรับผู้เดินทาง'],
                        phone: '02-888-9999',
                        email: 'airport.hospital@example.go.th',
                        address: 'สนามบินสุวรรณภูมิ จ.สมุทรปราการ'
                    }
                ];

                // ฟังก์ชันกำหนดสีตามระดับ level
                function getLevelColor(level) {
                    if (level === 'สูง') return '#0dcc93ff';
                    if (level === 'กลาง') return '#f0c419';
                    return '#ef83c2ff';
                }

                // ---------- Initialize Leaflet Map ----------
                const map = L.map('map', {
                    zoomControl: true
                }).setView([13.736717, 100.523186], 6);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                // ไอคอน SVG สีตามระดับ
                function createColoredIcon(color) {
                    return L.icon({
                        iconUrl: 'data:image/svg+xml;utf8,' + encodeURIComponent(`
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="38" viewBox="0 0 28 38" fill="none">
          <path d="M14 1C9 1 4 5.6 4 11.6 4 21.8 14 37 14 37s10-15.2 10-25.4C24 5.6 19 1 14 1z" fill="${color}"/>
          <circle cx="14" cy="11.6" r="3.6" fill="#fff"/>
        </svg>`),
                        iconSize: [28, 38],
                        iconAnchor: [14, 37],
                        popupAnchor: [0, -38],
                    });
                }

                const facilityMarkers = {};

                function addFacilityMarker(f) {
                    const color = getLevelColor(f.level);
                    const icon = createColoredIcon(color);

                    const marker = L.marker([f.lat, f.lon], {
                        icon
                    }).addTo(map);

                    const label = L.divIcon({
                        className: 'facility-label',
                        html: `<div style="
        background: ${color};
        color: black;
        font-weight: 500;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-family: 'Prompt';
        white-space: nowrap;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        cursor: pointer;
        ">
        ${f.name}
      </div>`,
                        iconSize: [f.name.length * 8 + 20, 24],
                        iconAnchor: [-10, 12],
                    });

                    const labelMarker = L.marker([f.lat, f.lon], {
                        icon: label
                    }).addTo(map);

                    labelMarker.on('click', () => {
                        marker.openPopup();
                    });

                    const popupContent = `
      <div style="min-width:220px; font-family: 'Prompt';">
        <h6 style="color:var(--kc-primary);margin-bottom:.25rem">${f.name}</h6>
        <p class="muted-small mb-1">${f.address}<br><strong>จังหวัด:</strong> ${f.province}</p>
        <p class="muted-small mb-1"><strong>บริการ:</strong> ${f.services.join(', ')}</p>
        <p class="muted-small mb-1"><strong>ติดต่อ:</strong> ${f.phone} / ${f.email}</p>
        <div class="d-grid mt-2">
          <button class="btn btn-sm btn-primary" onclick="openMessageModal('${f.id}')"><i class="bi bi-envelope"></i> ส่งข้อความ</button>
        </div>
      </div>
    `;
                    marker.bindPopup(popupContent, {
                        maxWidth: 320
                    });

                    facilityMarkers[f.id] = {
                        main: marker,
                        label: labelMarker
                    };

                    return marker;
                }

                // เพิ่ม marker ทั้งหมด
                sampleFacilities.forEach(f => addFacilityMarker(f));

                // ปรับขอบเขตแผนที่
                const group = new L.featureGroup(Object.values(facilityMarkers).map(obj => obj.main));
                if (group.getLayers().length) map.fitBounds(group.getBounds().pad(0.2));

                // ---------- Render Facility List Based on Page ----------
                function renderFacilityList(list) {
                    const isHealthServicesPage = document.getElementById('facilityListLeft') !== null;

                    if (isHealthServicesPage) {
                        // สำหรับ health_services.html - แสดงสองคอลัมน์
                        renderTwoColumnList(list);
                    } else {
                        // สำหรับ index.html - แสดงแค่ 3 รายการ
                        renderLimitedList(list);
                    }
                }

                function renderLimitedList(list) {
                    const facilityListEl = document.getElementById('facilityList');
                    facilityListEl.innerHTML = '';

                    const limitedList = list.slice(0, 3); // แสดงแค่ 3 รายการแรก

                    if (!limitedList.length) {
                        facilityListEl.innerHTML = '<div class="text-muted">ไม่พบผลลัพธ์</div>';
                        return;
                    }

                    limitedList.forEach(f => {
                        const color = getLevelColor(f.level);
                        const item = document.createElement('div');
                        item.className = 'list-group-item list-group-item-action p-3';
                        item.innerHTML = `
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div style="display:flex; align-items:center; gap:6px; color:var(--kc-primary); font-weight:600; font-size:1.1rem;">
              <div style="width:16px; height:16px; background:${color}; border-radius:3px;"></div>
              <div>${f.name}</div>
            </div>
            <div class="muted-small">${f.address} • ${f.province}</div>
            <div class="muted-small mt-1"><strong>บริการ:</strong> ${f.services.join(', ')}</div>
          </div>
          <div class="text-end">
            <button class="btn btn-sm btn-outline-primary mb-2" onclick="focusOnFacility('${f.id}')" style="width: 103px;"><i class="bi bi-geo-alt"></i> ดูบนแผนที่</button>
            <br>
            <button class="btn btn-sm" style="background:var(--kc-primary);color:#fff;width: 103px;" onclick="openMessageModal('${f.id}')"><i class="bi bi-envelope"></i> ส่งข้อความ</button>
          </div>
        </div>
      `;
                        facilityListEl.appendChild(item);
                    });
                }

                function renderTwoColumnList(list) {
                    const leftColumnEl = document.getElementById('facilityListLeft');
                    const rightColumnEl = document.getElementById('facilityListRight');

                    leftColumnEl.innerHTML = '';
                    rightColumnEl.innerHTML = '';

                    if (!list.length) {
                        leftColumnEl.innerHTML = '<div class="text-muted">ไม่พบผลลัพธ์</div>';
                        return;
                    }

                    // แบ่งรายการเป็นสองคอลัมน์
                    const midIndex = Math.ceil(list.length / 2);
                    const leftList = list.slice(0, midIndex);
                    const rightList = list.slice(midIndex);

                    // เรนเดอร์คอลัมน์ซ้าย
                    leftList.forEach(f => {
                        const color = getLevelColor(f.level);
                        const item = document.createElement('div');
                        item.className = 'list-group-item list-group-item-action p-3 mb-3';
                        item.innerHTML = `
        <div>
          <div style="display:flex; align-items:center; gap:6px; color:var(--kc-primary); font-weight:600; font-size:1.1rem;">
            <div style="width:16px; height:16px; background:${color}; border-radius:3px;"></div>
            <div>${f.name}</div>
          </div>
          <div class="muted-small">${f.address} • ${f.province}</div>
          <div class="muted-small mt-1"><strong>บริการ:</strong> ${f.services.join(', ')}</div>
          <div class="muted-small mt-1"><strong>ติดต่อ:</strong> ${f.phone}</div>
          <div class="d-flex gap-2 mt-3">
            <button class="btn btn-sm btn-outline-primary" onclick="focusOnFacility('${f.id}')"><i class="bi bi-geo-alt"></i> ดูบนแผนที่</button>
            <button class="btn btn-sm" style="background:var(--kc-primary);color:#fff;" onclick="openMessageModal('${f.id}')"><i class="bi bi-envelope"></i> ส่งข้อความ</button>
          </div>
        </div>
      `;
                        leftColumnEl.appendChild(item);
                    });

                    // เรนเดอร์คอลัมน์ขวา
                    rightList.forEach(f => {
                        const color = getLevelColor(f.level);
                        const item = document.createElement('div');
                        item.className = 'list-group-item list-group-item-action p-3 mb-3';
                        item.innerHTML = `
        <div>
          <div style="display:flex; align-items:center; gap:6px; color:var(--kc-primary); font-weight:600; font-size:1.1rem;">
            <div style="width:16px; height:16px; background:${color}; border-radius:3px;"></div>
            <div>${f.name}</div>
          </div>
          <div class="muted-small">${f.address} • ${f.province}</div>
          <div class="muted-small mt-1"><strong>บริการ:</strong> ${f.services.join(', ')}</div>
          <div class="muted-small mt-1"><strong>ติดต่อ:</strong> ${f.phone}</div>
          <div class="d-flex gap-2 mt-3">
            <button class="btn btn-sm btn-outline-primary" onclick="focusOnFacility('${f.id}')"><i class="bi bi-geo-alt"></i> ดูบนแผนที่</button>
            <button class="btn btn-sm" style="background:var(--kc-primary);color:#fff;" onclick="openMessageModal('${f.id}')"><i class="bi bi-envelope"></i> ส่งข้อความ</button>
          </div>
        </div>
      `;
                        rightColumnEl.appendChild(item);
                    });
                }

                // เรนเดอร์รายการเริ่มต้น
                renderFacilityList(sampleFacilities);

                // ---------- Search ----------
                const searchInput = document.getElementById('searchService');
                const levelSelect = document.getElementById('serviceLevel');
                const searchButton = document.getElementById('searchButton');

                if (searchButton) {
                    searchButton.addEventListener('click', () => {
                        const query = searchInput.value.trim().toLowerCase();
                        const level = levelSelect.value;

                        let filtered = sampleFacilities.filter(f =>
                            (f.name + ' ' + f.province).toLowerCase().includes(query)
                        );

                        if (level && level !== 'ทั้งหมด') {
                            filtered = filtered.filter(f => f.level === level);
                        }

                        renderFacilityList(filtered);

                        // Update markers
                        Object.entries(facilityMarkers).forEach(([id, markerObj]) => {
                            const found = filtered.find(f => f.id === id);
                            if (found) {
                                markerObj.main.addTo(map);
                                markerObj.label.addTo(map);
                            } else {
                                map.removeLayer(markerObj.main);
                                map.removeLayer(markerObj.label);
                            }
                        });

                        if (filtered.length) {
                            const fgroup = new L.featureGroup(filtered.map(f => facilityMarkers[f.id].main));
                            map.fitBounds(fgroup.getBounds().pad(0.25));
                        }
                    });
                }

                // ---------- Focus on facility ----------
                function focusOnFacility(id) {
                    const f = sampleFacilities.find(x => x.id === id);
                    if (!f) return;
                    const mk = facilityMarkers[id];
                    if (!mk) return;
                    map.setView([f.lat, f.lon], 13, {
                        animate: true
                    });
                    mk.main.openPopup();
                }
                window.focusOnFacility = focusOnFacility;

                // ---------- Message modal ----------
                function openMessageModal(id) {
                    const f = sampleFacilities.find(x => x.id === id);
                    if (!f) return;
                    const modalEl = document.getElementById('messageModal');
                    const bsModal = new bootstrap.Modal(modalEl);
                    document.getElementById('msgFacilityId').value = f.id;
                    document.getElementById('msgTo').value = `${f.name} (${f.email})`;
                    document.getElementById('msgBody').value = '';
                    document.getElementById('msgName').value = '';
                    document.getElementById('msgEmail').value = '';
                    bsModal.show();
                }
                window.openMessageModal = openMessageModal;

                // ---------- Handle message form ----------
                const messageForm = document.getElementById('messageForm');
                if (messageForm) {
                    messageForm.addEventListener('submit', (e) => {
                        e.preventDefault();
                        const facilityId = document.getElementById('msgFacilityId').value;
                        const to = document.getElementById('msgTo').value;
                        const name = document.getElementById('msgName').value;
                        alert(`ส่งข้อความเรียบร้อยถึง ${to}\nจาก: ${name}\n(Facility ID: ${facilityId})`);
                        bootstrap.Modal.getInstance(document.getElementById('messageModal')).hide();
                    });
                }
            });
        </script>
    @endpush

    <!-- #################### News Section #################### -->
    <section id="news" class="py-5 bg-light-custom-even">
        <div class="container pt-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="section-title">ข่าวประชาสัมพันธ์</h2>
                <a href="{{ route('frontend.news.index') }}" class="btn-viewall">ดูทั้งหมด</a>
            </div>

            <div class="row news-list">
                @forelse($latestNews as $n)
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <img src="{{ $n->image_url }}" class="img-fluid" alt="{{ $n->title }}">
                            <div class="card-body">
                                @php
                                    $dt = \Carbon\Carbon::parse($n->created_at)->locale('th');
                                    $thDate = $dt->translatedFormat('j F ') . ($dt->year + 543);
                                @endphp
                                <p class="meta-info text-muted small">
                                    <i class="bi bi-calendar"></i>
                                    <time datetime="{{ $n->created_at->toDateString() }}">{{ $thDate }}</time>
                                    <span class="ms-3">
                                        <i class="bi bi-eye-fill"></i>
                                        <span>{{ number_format((int) ($n->views ?? 0)) }}</span>
                                    </span>
                                </p>

                                <h5 class="card-title line-clamp-3 mb-2">{{ $n->title }}</h5>

                                @if ($n->excerpt)
                                    <p class="card-text text-secondary line-clamp-3 mb-0">{{ $n->excerpt }}</p>
                                @endif

                                <!-- ลิงก์คลุมทั้งการ์ด -->
                                <a href="{{ route('frontend.news.show', $n->id) }}" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info mb-0">ยังไม่มีข่าวประชาสัมพันธ์</div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>


    <!-- #################### FAQ Section #################### -->
    @if ($faqs->count())
        <section id="faq" class="py-5 bg-light-custom">
            <div class="container pt-3 pb-5">
                <h2 class="section-title">คำถามที่พบบ่อย</h2>
                <div class="accordion" id="faqAccordion">
                    @foreach ($faqs as $faq)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading{{ $loop->index }}">
                                <button class="accordion-button {{ !$loop->first ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $loop->index }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="collapse{{ $loop->index }}">
                                    {{ $faq->question }}
                                </button>
                            </h2>
                            <div id="collapse{{ $loop->index }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    {!! nl2br(e($faq->answer)) !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

@endsection
