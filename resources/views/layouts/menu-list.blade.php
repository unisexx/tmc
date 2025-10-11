{{-- TMC Backend --}}
<li class="pc-item pc-caption">
    <label data-i18n="Backend">Backend</label>
    <i class="ph-duotone ph-chart-pie"></i>
</li>

@can('dashboard.view')
    <li class="pc-item">
        <a href="{{ route('backend.dashboard') }}" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-gauge"></i></span>
            <span class="pc-mtext" data-i18n="แดชบอร์ด">แดชบอร์ด</span>
        </a>
    </li>
@endcan

@can('visitor-stats.view')
    <li class="pc-item">
        <a href="{{ route('backend.stat') }}" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-projector-screen-chart"></i></span>
            <span class="pc-mtext" data-i18n="สถิติผู้เข้าชม">สถิติผู้เข้าชม</span>
        </a>
    </li>
@endcan

@can('service-unit-profile.view')
    <li class="pc-item">
        <a href="{{ route('backend.service-unit-profile.edit') }}" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-hospital"></i></span>
            <span class="pc-mtext">ข้อมูลหน่วยบริการ</span>
        </a>
    </li>
@endcan

@can('assessment.view')
    <li class="pc-item">
        <a href="{{ route('backend.self-assessment-service-unit-level.index') }}" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-clipboard-text"></i></span>
            <span class="pc-mtext">การประเมินตนเอง</span>
        </a>
    </li>
@endcan

@can('review-assessment.view')
    <li class="pc-item">
        <a href="{{ route('backend.review-assessment.index') }}" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-clipboard-text"></i></span>
            <span class="pc-mtext">ตรวจสอบผลการประเมิน</span>
        </a>
    </li>
@endcan

@can('highlights.view')
    <li class="pc-item">
        <a href="{{ route('backend.hilight.index') }}" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-star"></i></span>
            <span class="pc-mtext" data-i18n="ไฮไลท์">ไฮไลท์</span>
        </a>
    </li>
@endcan

@can('news.view')
    <li class="pc-item">
        <a href="{{ route('backend.news.index') }}" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-newspaper"></i></span>
            <span class="pc-mtext" data-i18n="ข่าวประชาสัมพันธ์">ข่าวประชาสัมพันธ์</span>
        </a>
    </li>
@endcan

@can('faqs.view')
    <li class="pc-item">
        <a href="{{ route('backend.faq.index') }}" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-question"></i></span>
            <span class="pc-mtext" data-i18n="คำถามที่พบบ่อย">คำถามที่พบบ่อย</span>
        </a>
    </li>
@endcan

@can('contacts.view')
    <li class="pc-item">
        <a href="{{ route('backend.contact.edit', 1) }}" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-phone"></i></span>
            <span class="pc-mtext" data-i18n="ข้อมูลติดต่อ">ข้อมูลติดต่อ</span>
        </a>
    </li>
@endcan

@can('privacy-policy.view')
    <li class="pc-item">
        <a href="{{ route('backend.privacy.edit', 1) }}" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-fingerprint-simple"></i></span>
            <span class="pc-mtext" data-i18n="นโยบายข้อมูลส่วนบุคคล">นโยบายข้อมูลส่วนบุคคล</span>
        </a>
    </li>
@endcan

@can('cookie-policy.view')
    <li class="pc-item">
        <a href="{{ route('backend.cookie.edit', 1) }}" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-cookie"></i></span>
            <span class="pc-mtext" data-i18n="นโยบายคุกกี้">นโยบายคุกกี้</span>
        </a>
    </li>
@endcan


{{-- เมนูตรวจสอบใบสมัคร --}}
@can('approve-application.view')
    <li class="pc-item">
        <a class="pc-link" href="{{ route('backend.application-review.index') }}">
            <span class="pc-micon"><i class="ph-duotone ph-identification-card"></i></span>
            <span class="pc-mtext" data-i18n="ตรวจสอบใบสมัคร">ตรวจสอบใบสมัคร</span>
            @if (!empty($pendingApplicationCount) && $pendingApplicationCount > 0)
                <span class="pc-badge">{{ $pendingApplicationCount }}</span>
            @endif
        </a>
    </li>
@endcan

@can('users.view')
    {{-- เมนูผู้ใช้งาน --}}
    <li class="pc-item">
        <a class="pc-link" href="{{ route('backend.user.index') }}">
            <span class="pc-micon"><i class="ph-duotone ph-users-three"></i></span>
            <span class="pc-mtext" data-i18n="ผู้ใช้งาน">ผู้ใช้งาน</span>
        </a>
    </li>
@endcan

@can('roles-permissions.view')
    <li class="pc-item">
        <a href="{{ route('backend.role.index') }}" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-shield-check"></i></span>
            <span class="pc-mtext" data-i18n="สิทธิ์การใช้งาน">สิทธิ์การใช้งาน</span>
        </a>
    </li>
@endcan

@can('log.view')
    <li class="pc-item">
        <a href="{{ route('backend.logs.index') }}" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-clock-counter-clockwise"></i></span>
            <span class="pc-mtext" data-i18n="ประวัติการใช้งาน">ประวัติการใช้งาน</span>
        </a>
    </li>
@endcan


<li class="pc-item">
    <a href="{{ route('backend.service-unit.index') }}" class="pc-link">
        <span class="pc-micon"><i class="ph-duotone ph-hospital"></i></span>
        <span class="pc-mtext" data-i18n="จัดการหน่วยบริการ">จัดการหน่วยบริการ</span>
    </a>
</li>
