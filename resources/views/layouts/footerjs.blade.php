<!-- Required Js -->
<script src="{{ URL::asset('build/js/plugins/popper.min.js') }}"></script>
<script src="{{ URL::asset('build/js/plugins/simplebar.min.js') }}"></script>
<script src="{{ URL::asset('build/js/plugins/bootstrap.min.js') }}"></script>
<script src="{{ URL::asset('build/js/plugins/i18next.min.js') }}"></script>
<script src="{{ URL::asset('build/js/plugins/i18nextHttpBackend.min.js') }}"></script>
<script src="{{ URL::asset('build/js/icon/custom-font.js') }}"></script>
<script src="{{ URL::asset('build/js/script.js') }}"></script>
<script src="{{ URL::asset('build/js/theme.js') }}"></script>
<script src="{{ URL::asset('build/js/multi-lang.js') }}"></script>
<script src="{{ URL::asset('build/js/plugins/feather.min.js') }}"></script>

@if (env('APP_DARK_LAYOUT') == 'default')
    <script>
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            dark_layout = 'true';
        } else {
            dark_layout = 'false';
        }
        layout_change_default();
        if (dark_layout == 'true') {
            layout_change('dark');
        } else {
            layout_change('light');
        }
    </script>
@endif

@if (env('APP_DARK_LAYOUT') != 'default')
    @if (env('APP_DARK_LAYOUT') == 'true')
        <script>
            layout_change('dark');
        </script>
    @endif
    @if (env('APP_DARK_LAYOUT') == false)
        <script>
            layout_change('light');
        </script>
    @endif
@endif


@if (env('APP_DARK_NAVBAR') == 'true')
    <script>
        layout_sidebar_change('dark');
    </script>
@endif

@if (env('APP_DARK_NAVBAR') == false)
    <script>
        layout_sidebar_change('light');
    </script>
@endif

@if (env('APP_BOX_CONTAINER') == false)
    <script>
        change_box_container('true');
    </script>
@endif

@if (env('APP_BOX_CONTAINER') == false)
    <script>
        change_box_container('false');
    </script>
@endif

@if (env('APP_CAPTION_SHOW') == 'true')
    <script>
        layout_caption_change('true');
    </script>
@endif

@if (env('APP_CAPTION_SHOW') == false)
    <script>
        layout_caption_change('false');
    </script>
@endif

@if (env('APP_RTL_LAYOUT') == 'true')
    <script>
        layout_rtl_change('true');
    </script>
@endif

@if (env('APP_RTL_LAYOUT') == false)
    <script>
        layout_rtl_change('false');
    </script>
@endif

@if (env('APP_PRESET_THEME') != '')
    <script>
        preset_change("{{ env('APP_PRESET_THEME') }}");
    </script>
@endif


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@if (session('notify'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const n = @json(session('notify'));
            const type = (n.type || 'success').toLowerCase();

            // map ประเภท → คลาสสีของ Bootstrap
            const bgMap = {
                success: 'bg-success',
                error: 'bg-danger',
                danger: 'bg-danger',
                warning: 'bg-warning',
                info: 'bg-info',
                question: 'bg-primary'
            };

            Swal.fire({
                icon: type === 'danger' ? 'error' : type,
                title: n.message || '',
                showConfirmButton: false,
                timer: n.timeout || 3000,
                timerProgressBar: true,
                customClass: {
                    popup: 'flash-notify-toast'
                },

                // ใส่คลาสสีของ Bootstrap ให้ progress bar เมื่อ alert เปิด
                didOpen: () => {
                    const bar = Swal.getTimerProgressBar();
                    if (bar) {
                        bar.classList.add(bgMap[type] || 'bg-primary', 'swal-timer-thick');
                    }
                }
            });
        });
    </script>
@endif

<style>
    /* ปรับความหนา/ทรง ของ progress bar (เลือกได้) */
    .swal2-timer-progress-bar.swal-timer-thick {
        height: 4px !important;
        border-radius: 999px;
    }

    /* ให้แน่ใจว่าค่าสีมาจากตัวแปรธีมของ Bootstrap/Light Able */
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
