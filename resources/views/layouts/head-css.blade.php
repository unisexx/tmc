<!-- [Google Font : Public Sans] icon -->
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- [phosphor Icons] https://phosphoricons.com/ -->
<link rel="stylesheet" href="{{ URL::asset('build/fonts/phosphor/duotone/style.css') }}" />
<!-- [Tabler Icons] https://tablericons.com -->
<link rel="stylesheet" href="{{ URL::asset('build/fonts/tabler-icons.min.css') }}">
<!-- [Feather Icons] https://feathericons.com -->
<link rel="stylesheet" href="{{ URL::asset('build/fonts/feather.css') }}">
<!-- [Font Awesome Icons] https://fontawesome.com/icons -->
<link rel="stylesheet" href="{{ URL::asset('build/fonts/fontawesome.css') }}">
<!-- [Material Icons] https://fonts.google.com/icons -->
<link rel="stylesheet" href="{{ URL::asset('build/fonts/material.css') }}">
<!-- [Template CSS Files] -->
<link rel="stylesheet" href="{{ URL::asset('build/css/style.css') }}" id="main-style-link">
<link rel="stylesheet" href="{{ URL::asset('build/css/style-preset.css') }}">

<link rel="stylesheet" href="{{ asset('build/css/plugins/notifier.css') }}">
<link rel="stylesheet" href="{{ asset('build/css/plugins/animate.min.css') }}">

{{-- Choice JS --}}
<style>
    /* กล่องหลักของ Choices ให้ดูเหมือน .form-select ของธีม */
    .choices--lightable .choices__inner {
        padding: .8rem 2rem .8rem .75rem;
        font-size: .875rem;
        line-height: 1.5;
        color: #5b6b79;
        background-color: #fff;
        border: 1px solid #dbe0e5;
        border-radius: 8px;
        min-height: calc(1.5em + 1.6rem + 2px);
    }

    .choices--lightable .choices__list--single .choices__item {
        padding: 0;
    }

    .choices--lightable .choices__placeholder {
        opacity: .6;
    }

    /* ไอคอนลูกศรขวาให้ตำแหน่งคล้าย form-select */
    .choices--lightable .choices__list--single {
        padding-right: 2rem;
    }

    .choices--lightable .choices__list--dropdown {
        z-index: 2000;
        border-radius: 8px;
    }

    /* โฟกัส */
    .choices--lightable.is-focused .choices__inner {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 .25rem rgba(var(--bs-primary-rgb), .25);
    }

    /* invalid/valid state (ใช้ร่วมกับ BS) */
    .is-invalid+.choices .choices__inner {
        border-color: var(--bs-danger) !important;
    }

    .is-valid+.choices .choices__inner {
        border-color: var(--bs-success) !important;
    }
</style>
