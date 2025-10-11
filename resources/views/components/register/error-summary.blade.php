@props(['errors'])

@if ($errors->any())
    <div id="error-summary" class="alert alert-danger" role="alert">
        <div class="d-flex align-items-start gap-2">
            <i class="ti ti-alert-circle fs-4 mt-1"></i>
            <div>
                <strong>กรอกข้อมูลไม่ครบหรือไม่ถูกต้อง {{ $errors->count() }} รายการ</strong>
                <div class="small">โปรดตรวจสอบฟิลด์ที่มีเครื่องหมาย <span class="text-danger">*</span> หรือมีกรอบสีแดง</div>
                <ul class="mt-2 mb-0">
                    @foreach ($errors->toArray() as $field => $messages)
                        <li class="small">
                            <a href="#field-{{ \Illuminate\Support\Str::slug($field) }}" class="text-reset text-decoration-underline">
                                {{ str_replace(['working_hours.*.', 'working_hours.'], 'วัน-เวลาทำการ: ', $field) }}
                            </a> : {{ $messages[0] }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const box = document.getElementById('error-summary');
                if (box) box.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });
        </script>
    @endpush
@endif
