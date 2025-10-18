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
                            <h5 class="modal-title" id="messageModalTitle" style="color:var(--kc-primary);">ส่งข้อความถึงหน่วยบริการ</h5>
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
