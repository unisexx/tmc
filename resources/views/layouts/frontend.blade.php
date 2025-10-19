{{-- resources/views/layouts/frontend.blade.php --}}
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="alternate icon" type="image/png" href="{{ asset('images/favicon.png') }}">

    <title>@yield('title', 'ระบบประเมินผลการจัดบริการหน่วยบริการสุขภาพผู้เดินทาง')</title>

    {{-- SEO --}}
    <meta name="description" content="@yield('meta_description', 'ระบบประเมินผลการจัดบริการหน่วยบริการสุขภาพผู้เดินทาง กลุ่มโรคติดต่อในผู้เดินทางและแรงงานข้ามชาติ กรมควบคุมโรค กระทรวงสาธารณสุข')">
    <meta name="keywords" content="@yield('meta_keywords', 'กลุ่มโรคติดต่อในผู้เดินทางและแรงงานข้ามชาติ, กรมควบคุมโรค, กระทรวงสาธารณสุข')">
    <link rel="canonical" href="@yield('canonical', url('/'))">

    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('og_title', 'กลุ่มโรคติดต่อในผู้เดินทางและแรงงานข้ามชาติ ประเทศไทย')">
    <meta property="og:description" content="@yield('og_description', 'กลุ่มโรคติดต่อในผู้เดินทางและแรงงานข้ามชาติ ระบบประเมินผลการจัดบริการหน่วยบริการสุขภาพผู้เดินทาง')">
    <meta property="og:image" content="@yield('og_image', asset('images/og-image.jpg'))">
    <meta property="og:url" content="@yield('og_url', url()->current())">
    <meta property="og:type" content="@yield('og_type', 'website')">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Core CSS --}}
    <link href="{{ asset('frontend/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/css/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link href="{{ asset('frontend/css/styles.css') }}" rel="stylesheet">
    @stack('styles')
</head>

<body>

    {{-- ######## HEADER ######## --}}
    @include('frontend.partials.header')

    {{-- ######## OPTIONAL HERO (เช่นสไลด์) ######## --}}
    @yield('hero')

    {{-- ######## PAGE HEADER (หัวข้อ + breadcrumb สำหรับหน้าใน) ######## --}}
    @yield('page_header')

    {{-- ######## MAIN CONTENT ######## --}}
    <main>
        @yield('content')
    </main>

    {{-- ######## FOOTER ######## --}}
    @include('frontend.partials.footer')

    {{-- ######## SCRIPTS ######## --}}
    <script src="{{ asset('frontend/js/bootstrap.bundle.min.js') }}"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{ asset('frontend/js/custom.js') }}" defer></script>

    <script>
        // Global search
        document.querySelectorAll('.search-main-button').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                const m = new bootstrap.Modal(document.getElementById('searchModalMain'));
                m.show();
                setTimeout(() => document.getElementById('searchMainInput')?.focus(), 200);
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if (session('notify'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const n = @json(session('notify'));
                const type = (n.type || 'success').toLowerCase();
                const opts = n.options || {};
                const isConfirm = !!opts.confirm;

                const bgMap = {
                    success: 'bg-success',
                    error: 'bg-danger',
                    danger: 'bg-danger',
                    warning: 'bg-warning',
                    info: 'bg-info',
                    question: 'bg-primary'
                };

                // ค่าพื้นฐานของ Swal
                const baseConfig = {
                    icon: type === 'danger' ? 'error' : type,
                    title: n.message || '',
                    customClass: {
                        popup: 'flash-notify-toast'
                    }
                };

                if (isConfirm) {
                    // โหมดยืนยัน: แสดงปุ่มและไม่ตั้ง timer
                    Swal.fire({
                        ...baseConfig,
                        text: opts.subText || '', // ✅ เพิ่มข้อความรอง
                        showConfirmButton: true,
                        confirmButtonText: opts.confirmText || 'ตกลง',
                        showCancelButton: !!opts.showCancel,
                        cancelButtonText: opts.cancelText || 'ยกเลิก',
                        allowOutsideClick: opts.allowOutsideClick ?? false,
                        focusConfirm: opts.focusConfirm ?? true,
                    });
                } else {
                    // โหมดเดิม (toast อัตโนมัติ)
                    Swal.fire({
                        ...baseConfig,
                        showConfirmButton: false,
                        timer: n.timeout || 3000,
                        timerProgressBar: true,
                        didOpen: () => {
                            const bar = Swal.getTimerProgressBar();
                            if (bar) {
                                bar.classList.add(bgMap[type] || 'bg-primary', 'swal-timer-thick');
                            }
                        }
                    });
                }
            });
        </script>
    @endif

    <style>
        .swal2-timer-progress-bar.swal-timer-thick {
            height: 4px !important;
            border-radius: 999px;
        }

        .swal2-timer-progress-bar.bg-primary {
            background-color: var(--bs-primary) !important;
        }

        .swal2-timer-progress-bar.bg-success {
            background-color: var(--bs-success) !important;
        }

        .swal2-timer-progress-bar.bg-info {
            background-color: var(--bs-info) !important;
        }

        .swal2-timer-progress-bar.bg-warning {
            background-color: var(--bs-warning) !important;
        }

        .swal2-timer-progress-bar.bg-danger {
            background-color: var(--bs-danger) !important;
        }
    </style>

    @stack('scripts')
</body>

</html>
