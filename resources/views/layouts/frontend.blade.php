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

    @stack('scripts')
</body>

</html>
