<div class="header-wrapper">
    <div class="topbar"></div>

    <div class="container custom-container">
        <div class="d-flex flex-wrap justify-content-start head-mobile">

            {{-- Logo --}}
            <div class="flex-shrink-0 logo-bg-white" style="max-width:220px;">
                <div class="logo-bg-white">
                    <div class="logo-wrap">
                        <a href="{{ url('/') }}">
                            <img src="{{ asset('frontend/images/logoDDC.webp') }}" class="logo" alt="กรมควบคุมโรค">
                        </a>
                    </div>
                </div>
            </div>

            {{-- Navbar + Search --}}
            <div class="d-flex align-items-center justify-content-start position-relative navsearch-mobile">
                <nav class="navbar navbar-expand-lg">
                    <div class="d-flex w-md-100 justify-content-start align-items-center">
                        <button class="navbar-toggler me-auto d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="d-lg-none">
                            <div class="position-relative">
                                <a href="#" class="search-button search-main-button">
                                    <i class="bi bi-search text-white"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- เมนูหลัก --}}
                    <div class="collapse navbar-collapse custom-navbar-overlay" id="mainNav">
                        <ul class="navbar-nav ms-xl-5 d-flex align-items-start align-items-lg-center">
                            <li class="nav-item"><a class="nav-link {{ Request::is('/') ? 'active' : '' }}" href="{{ url('/') }}">หน้าแรก</a></li>
                            <li class="nav-item"><a class="nav-link {{ Request::is('service-units*') ? 'active' : '' }}" href="{{ route('frontend.service-units.index') }}">ค้นหาหน่วยบริการสุขภาพผู้เดินทาง</a></li>
                            <li class="nav-item"><a class="nav-link {{ Request::is('news*') ? 'active' : '' }}" href="{{ route('frontend.news.index') }}">ข่าวประชาสัมพันธ์</a></li>
                            <li class="nav-item"><a class="nav-link {{ Request::is('faq*') ? 'active' : '' }}" href="{{ route('frontend.faq.index') }}">คำถามที่พบบ่อย</a></li>
                            <li class="nav-item"><a class="nav-link {{ Request::is('contact*') ? 'active' : '' }}" href="{{ route('frontend.contact.index') }}">ติดต่อเรา</a></li>
                        </ul>
                    </div>
                </nav>

                {{-- ปุ่มค้นหา Desktop --}}
                <div class="d-none d-lg-block search-wrap">
                    <div class="position-relative">
                        <a href="#" class="search-button search-main-button">
                            <i class="bi bi-search text-white"></i>
                        </a>
                    </div>
                </div>

                {{-- Modal ค้นหา --}}
                <div class="modal fade" id="searchModalMain" tabindex="-1" aria-labelledby="searchModalMainLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content p-3">
                            <div class="modal-header border-0">
                                <h5 class="modal-title" id="searchModalMainLabel">ค้นหา</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                            </div>
                            <div class="modal-body">
                                <input type="text" class="form-control" id="searchMainInput" placeholder="พิมพ์คำค้นหา...">
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-primary" id="searchMainSubmit">ค้นหา</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
