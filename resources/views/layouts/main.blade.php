    <!DOCTYPE html>
    <html lang="en">

    <head>
        <title>@yield('title') | Light Able Laravel 11 Admin & Dashboard Template</title>
        <!-- [Meta] -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="description" content="Light Able admin and dashboard template offer a variety of UI elements and pages, ensuring your admin panel is both fast and effective." />
        <meta name="author" content="phoenixcoded" />
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- [Favicon] icon -->
        <link rel="icon" href="{{ URL::asset('build/images/favicon.svg') }}" type="image/x-icon">
        @yield('css')
        @stack('css')
        @stack('styles')
        @include('layouts.head-css')
        <style>
            .bg-basic {
                background-color: #FF4560 !important;
            }

            /* ชมพูแดง */
            .bg-medium {
                background-color: #FEB019 !important;
            }

            /* ส้มทอง */
            .bg-advanced {
                background-color: #00E396 !important;
            }

            /* เขียวมรกต */
            .bg-unassessed {
                background-color: #A8A8A8 !important;
            }
        </style>
        <style>
            /* สไตล์ badge ระดับหน่วยบริการ แบบเดียวกับ label บนแผนที่ */
            .level-badge-map {
                /* var(--c) จะถูก inject ผ่าน inline style="--c: #xxxxxx" */
                background: color-mix(in srgb, var(--c) 18%, white);
                border: 1px solid color-mix(in srgb, var(--c) 45%, transparent);
                color: #0b2e13;
                font-size: .9rem;
                line-height: 1.1;
                font-weight: 500;

                border-radius: 999px;
                padding: .35rem .75rem;
                box-shadow: 0 4px 12px rgba(0, 0, 0, .12);

                gap: .5rem;
                white-space: nowrap;
            }

            .level-badge-map .dot {
                width: .66rem;
                height: .66rem;
                border-radius: 4px;
                background: var(--c);
                box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .08);
                flex-shrink: 0;
            }

            .level-badge-map .name {
                font-weight: 600;
                color: #000;
                /* ใน map เราไม่ได้ทำข้อความเป็นสีของระดับ แต่ใช้ดำ/น้ำเงินเข้มอ่านง่าย */
                line-height: 1.1;
            }

            /* ถ้าอยากให้ข้อความเป็นโทนน้ำเงินแบบ popup title ก็เปลี่ยนเป็น #0d6efd ตรงนี้ได้ */
            /* .level-badge-map .name { color:#0d6efd; } */
        </style>
    </head>

    <body data-pc-preset="preset-1" data-pc-sidebar-theme="light" data-pc-sidebar-caption="true" data-pc-direction="ltr" data-pc-theme="light">

        @include('layouts.loader')
        @include('layouts.sidebar')
        @include('layouts.topbar')

        <!-- [ Main Content ] start -->
        <div class="pc-container">
            <div class="pc-content">
                @if (View::hasSection('breadcrumb-item'))
                    @include('layouts.breadcrumb')
                @endif
                <!-- [ Main Content ] start -->
                @yield('content')
                <!-- [ Main Content ] end -->
            </div>
        </div>
        <!-- [ Main Content ] end -->

        @include('layouts.footer')
        @include('layouts.customizer')

        @stack('modal')

        @include('layouts.footerjs')
        @yield('scripts')
        @stack('js')
        @stack('scripts')
    </body>
    <!-- [Body] end -->

    </html>
