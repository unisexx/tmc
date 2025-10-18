{{-- resources/views/layouts/partials/sidebar.blade.php --}}
{{-- ติดตั้ง active ให้ครอบคลุมทุก action ด้วย Route::is() --}}
@php
    $isAppReviewActive = Route::is('backend.application-review.*');
    $isReviewActive = Route::is('backend.review-assessment.*');
    $isServiceUnitActive = Route::is('backend.service-unit.*');
    $isUserActive = Route::is('backend.user.*');
    $isRoleActive = Route::is('backend.role.*');
    $isProfileActive = Route::is('backend.service-unit-profile.*');
    $isSelfAssessActive = Route::is('backend.self-assessment-service-unit-level.*');

    $isHighlightsActive = Route::is('backend.hilight.*');
    $isNewsActive = Route::is('backend.news.*');
    $isFaqsActive = Route::is('backend.faq.*');
    $isContactsActive = Route::is('backend.contact.*');
    $isPrivacyActive = Route::is('backend.privacy.*');
    $isCookieActive = Route::is('backend.cookie.*');
    $isStHealthServiceActive = Route::is('backend.st-health-services.*');

    // เพิ่มตัวตรวจเมนูกล่องข้อความ
    $isContactMessagesActive = Route::is('backend.contact-messages.*');

    $isHomeMgmtGroupActive = $isHighlightsActive || $isNewsActive || $isFaqsActive || $isContactsActive || $isPrivacyActive || $isCookieActive || $isContactMessagesActive; // รวมกล่องข้อความเข้า group นี้

    $isSettingGroupActive = $isUserActive || $isRoleActive || $isServiceUnitActive || $isStHealthServiceActive;
@endphp

{{-- TMC Backend --}}
<li class="pc-item pc-caption">
    <label data-i18n="เมนู">เมนู</label>
</li>

@can('dashboard.view')
    <li class="pc-item {{ Route::is('backend.dashboard') ? 'active' : '' }}">
        <a href="{{ route('backend.dashboard') }}" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-gauge"></i></span>
            <span class="pc-mtext" data-i18n="แดชบอร์ด">แดชบอร์ด</span>
        </a>
    </li>
@endcan

@can('visitor-stats.view')
    <li class="pc-item {{ Route::is('backend.stat*') ? 'active' : '' }}">
        <a href="{{ route('backend.stat') }}" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-projector-screen-chart"></i></span>
            <span class="pc-mtext" data-i18n="สถิติผู้เข้าชม">สถิติผู้เข้าชม</span>
        </a>
    </li>
@endcan

@can('approve-application.view')
    <li class="pc-item {{ $isAppReviewActive ? 'active' : '' }}">
        <a class="pc-link" href="{{ route('backend.application-review.index') }}">
            <span class="pc-micon"><i class="ph-duotone ph-identification-card"></i></span>
            <span class="pc-mtext" data-i18n="ตรวจสอบใบสมัคร">ตรวจสอบใบสมัคร</span>
            @if (!empty($pendingApplicationCount) && $pendingApplicationCount > 0)
                <span class="pc-badge">{{ $pendingApplicationCount }}</span>
            @endif
        </a>
    </li>
@endcan

@can('service-unit-profile.view')
    <li class="pc-item {{ $isProfileActive ? 'active' : '' }}">
        <a href="{{ route('backend.service-unit-profile.edit') }}" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-hospital"></i></span>
            <span class="pc-mtext">ข้อมูลหน่วยบริการ</span>
        </a>
    </li>
@endcan

@can('assessment.view')
    <li class="pc-item {{ $isSelfAssessActive ? 'active' : '' }}">
        <a href="{{ route('backend.self-assessment-service-unit-level.index') }}" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-clipboard-text"></i></span>
            <span class="pc-mtext">การประเมินตนเอง</span>
        </a>
    </li>
@endcan

@can('review-assessment.view')
    <li class="pc-item {{ $isReviewActive ? 'active' : '' }}">
        <a href="{{ route('backend.review-assessment.index') }}" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-clipboard-text"></i></span>
            <span class="pc-mtext">ตรวจสอบผลการประเมิน</span>
            @if (!empty($pendingReviewCount) && $pendingReviewCount > 0)
                <span class="pc-badge">{{ $pendingReviewCount }}</span>
            @endif
        </a>
    </li>
@endcan

{{-- จัดการข้อมูลหน้าแรก --}}
@canany(['highlights.view', 'news.view', 'faqs.view', 'contacts.view', 'privacy-policy.view', 'cookie-policy.view', 'contact-messages.view'])
    <li class="pc-item pc-hasmenu {{ $isHomeMgmtGroupActive ? 'active pc-trigger' : '' }}">
        <a href="#!" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-desktop"></i></span>
            <span class="pc-mtext" data-i18n="จัดการข้อมูลหน้าแรก">จัดการข้อมูลหน้าแรก</span>
            <span class="pc-arrow"><i data-feather="chevron-right"></i></span>
        </a>
        <ul class="pc-submenu">
            @can('highlights.view')
                <li class="pc-item {{ $isHighlightsActive ? 'active' : '' }}">
                    <a class="pc-link" href="{{ route('backend.hilight.index') }}" data-i18n="ไฮไลท์">ไฮไลท์</a>
                </li>
            @endcan

            @can('news.view')
                <li class="pc-item {{ $isNewsActive ? 'active' : '' }}">
                    <a class="pc-link" href="{{ route('backend.news.index') }}" data-i18n="ข่าวประชาสัมพันธ์">ข่าวประชาสัมพันธ์</a>
                </li>
            @endcan

            @can('faqs.view')
                <li class="pc-item {{ $isFaqsActive ? 'active' : '' }}">
                    <a class="pc-link" href="{{ route('backend.faq.index') }}" data-i18n="คำถามที่พบบ่อย">คำถามที่พบบ่อย</a>
                </li>
            @endcan

            @can('contacts.view')
                <li class="pc-item {{ $isContactsActive ? 'active' : '' }}">
                    <a class="pc-link" href="{{ route('backend.contact.edit', 1) }}" data-i18n="ข้อมูลติดต่อ">ข้อมูลติดต่อ</a>
                </li>
            @endcan

            {{-- เมนูใหม่: กล่องข้อความ --}}
            {{-- @can('contact-messages.view') --}}
            <li class="pc-item {{ $isContactMessagesActive ? 'active' : '' }}">
                <a class="pc-link" href="{{ route('backend.contact-messages.index') }}" data-i18n="กล่องข้อความ">
                    กล่องข้อความ
                </a>
            </li>
            {{-- @endcan --}}

            @can('privacy-policy.view')
                <li class="pc-item {{ $isPrivacyActive ? 'active' : '' }}">
                    <a class="pc-link" href="{{ route('backend.privacy.edit', 1) }}" data-i18n="นโยบายข้อมูลส่วนบุคคล">นโยบายข้อมูลส่วนบุคคล</a>
                </li>
            @endcan

            @can('cookie-policy.view')
                <li class="pc-item {{ $isCookieActive ? 'active' : '' }}">
                    <a class="pc-link" href="{{ route('backend.cookie.edit', 1) }}" data-i18n="นโยบายคุกกี้">นโยบายคุกกี้</a>
                </li>
            @endcan
        </ul>
    </li>
@endcanany

{{-- ตั้งค่า --}}
@canany(['users.view', 'roles-permissions.view', 'service-unit.view'])
    <li class="pc-item pc-hasmenu {{ $isSettingGroupActive ? 'active pc-trigger' : '' }}">
        <a href="#!" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-gear-six"></i></span>
            <span class="pc-mtext" data-i18n="ตั้งค่า">ตั้งค่า</span>
            <span class="pc-arrow"><i data-feather="chevron-right"></i></span>
        </a>
        <ul class="pc-submenu">
            @can('users.view')
                <li class="pc-item {{ $isUserActive ? 'active' : '' }}">
                    <a class="pc-link" href="{{ route('backend.user.index') }}" data-i18n="ผู้ใช้งาน">ผู้ใช้งาน</a>
                </li>
            @endcan

            @can('roles-permissions.view')
                <li class="pc-item {{ $isRoleActive ? 'active' : '' }}">
                    <a class="pc-link" href="{{ route('backend.role.index') }}" data-i18n="สิทธิ์การใช้งาน">สิทธิ์การใช้งาน</a>
                </li>
            @endcan

            @can('service-unit.view')
                <li class="pc-item {{ $isServiceUnitActive ? 'active' : '' }}">
                    <a class="pc-link" href="{{ route('backend.service-unit.index') }}" data-i18n="จัดการหน่วยบริการ">จัดการหน่วยบริการ</a>
                </li>
            @endcan

            <li class="pc-item {{ $isStHealthServiceActive ? 'active' : '' }}">
                <a class="pc-link" href="{{ route('backend.st-health-services.index') }}" data-i18n="การให้บริการ">การให้บริการ</a>
            </li>
        </ul>
    </li>
@endcanany

@can('log.view')
    <li class="pc-item {{ Route::is('backend.logs.*') ? 'active' : '' }}">
        <a href="{{ route('backend.logs.index') }}" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-clock-counter-clockwise"></i></span>
            <span class="pc-mtext" data-i18n="ประวัติการใช้งาน">ประวัติการใช้งาน</span>
        </a>
    </li>
@endcan
