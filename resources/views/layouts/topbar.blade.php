<!-- [ Header Topbar ] start -->
<header class="pc-header">
    <div class="header-wrapper">
        <!-- [Mobile Media Block] start -->
        <div class="me-auto pc-mob-drp">
            <ul class="list-unstyled">
                <!-- ======= Menu collapse Icon ===== -->
                <li class="pc-h-item pc-sidebar-collapse">
                    <a href="#" class="pc-head-link ms-0" id="sidebar-hide">
                        <i class="ti ti-menu-2"></i>
                    </a>
                </li>
                <li class="pc-h-item pc-sidebar-popup">
                    <a href="#" class="pc-head-link ms-0" id="mobile-collapse">
                        <i class="ti ti-menu-2"></i>
                    </a>
                </li>

                @php
                    // ผู้ใช้ + หน่วยงานที่ดูแล
                    $user = Auth::user();
                    $units = $user?->serviceUnits ?? collect();

                    // หน่วยปัจจุบัน + ระดับล่าสุดของหน่วยนั้น
                    $currentUnitId = session('current_service_unit_id');
                    $latest = $currentUnitId ? \App\Models\AssessmentServiceUnitLevel::where('service_unit_id', $currentUnitId)->orderByDesc('assess_year')->orderByDesc('assess_round')->first() : null;
                @endphp

                @if ($units->isNotEmpty())
                    <li class="pc-h-item d-none d-md-inline-flex align-items-center ms-3">
                        {{-- สลับหน่วยงาน --}}
                        <form action="{{ route('backend.service-unit.switch') }}" method="POST" id="formSwitchUnit">
                            @csrf
                            <select name="service_unit_id" class="form-select" onchange="document.getElementById('formSwitchUnit').submit();">
                                @foreach ($units as $su)
                                    <option value="{{ $su->id }}" @selected(session('current_service_unit_id') == $su->id)>
                                        {{ $su->org_name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>

                        {{-- แสดง badge ระดับ + ปี/รอบ ล่าสุดของหน่วยที่เลือก --}}
                        @if ($latest)
                            <x-level-badge :level="$latest->level" class="ms-2" />

                            <span class="ms-1 text-muted small">
                                ปี {{ $latest->assess_year ? $latest->assess_year + 543 : '-' }}
                                รอบ {{ $latest->assess_round ?? '-' }}
                            </span>
                        @endif
                    </li>
                @endif




            </ul>
        </div>
        <!-- [Mobile Media Block end] -->
        <div class="ms-auto">
            <ul class="list-unstyled">

                <li class="dropdown pc-h-item header-user-profile">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" data-bs-auto-close="outside" aria-expanded="false">
                        สวัสดี, {{ Auth::user()->contact_name }}
                    </a>
                    <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown">
                        <div class="dropdown-header d-flex align-items-center justify-content-between">
                            <h5 class="m-0">โปรไฟล์</h5>
                        </div>
                        <div class="dropdown-body">
                            <div class="profile-notification-scroll position-relative" style="max-height: calc(100vh - 225px)">
                                <ul class="list-group list-group-flush w-100">
                                    <li class="list-group-item">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1 mx-3">
                                                <h5 class="mb-0">{{ Auth::user()->contact_name }}</h5>
                                                <div>{{ @Auth::user()->org_name }}</div>
                                                {{-- <a class="link-primary" href="mailto:carson.darrin@company.io">carson.darrin@company.io</a> --}}
                                                <div class="badge bg-primary">{{ optional(Auth::user()->role)->name ?? '-' }}</div>
                                                @php
                                                    $primaryUnit = Auth::user()->serviceUnits()->wherePivot('is_primary', 1)->first();
                                                @endphp
                                                @if ($primaryUnit)
                                                    <div class="mt-1 small text-muted">
                                                        หน่วยหลัก: {{ $primaryUnit->org_name }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </li>

                                    <li class="list-group-item">
                                        @if (session('is_impersonating'))
                                            <a href="{{ route('backend.impersonate.stop') }}" class="dropdown-item">
                                                <span class="d-flex align-items-center text-danger">
                                                    <i class="ph-duotone ph-user-switch"></i>
                                                    <span>หยุดจำลอง</span>
                                                </span>
                                            </a>
                                        @endif
                                        <a href="{{ route('backend.profile.edit', Auth::user()->id) }}" class="dropdown-item">
                                            <span class="d-flex align-items-center">
                                                <i class="ph-duotone ph-user-circle"></i>
                                                <span>แก้ไขข้อมูลส่วนตัว</span>
                                            </span>
                                        </a>
                                        <a href="{{ route('logout') }}" onclick="event.preventDefault();
                                            document.getElementById('logout-form').submit();" class="dropdown-item">
                                            <span class="d-flex align-items-center">
                                                <i class="ph-duotone ph-power"></i>
                                                <span>ออกจากระบบ</span>
                                            </span>
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</header>
<!-- [ Header ] end -->
