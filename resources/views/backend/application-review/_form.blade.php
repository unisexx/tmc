{{-- resources\views\backend\application-review\_form.blade.php --}}

@php
    $unit = $unit ?? (optional($user)->serviceUnits()->wherePivot('is_primary', true)->first() ?? optional($user)->serviceUnits()->first());
    $provinces = Cache::remember(
        'blade.provinces.all',
        now()->addDay(),
        fn() => \App\Models\Province::select(['CODE', 'TITLE'])
            ->orderBy('TITLE')
            ->get(),
    );
    $healthRegions = Cache::remember(
        'blade.health_regions.all',
        now()->addDay(),
        fn() => \App\Models\HealthRegion::select(['id', 'code', 'title', 'short_title'])
            ->orderBy('code')
            ->get(),
    );
@endphp

<x-register.error-summary :errors="$errors" />

<div class="row g-3">
    {{-- หัวข้อ 1: ในฐานะ + จังหวัด/เขต --}}
    <x-register.purpose :user="$user" :provinces="$provinces" :healthRegions="$healthRegions" id="reg" />

    <hr class="my-4">

    {{-- หัวข้อ 2: หน่วยบริการ (พร้อม collapse) --}}
    <div class="col-12">
        <!-- กล่องที่มีเอฟเฟกต์สไลด์ -->
        <div id="section2Body" class="collapse show">
            <div class="row g-3">
                <h5 class="col-12">2. ข้อมูลทั่วไปของหน่วยบริการ/หน่วยงาน</h5>
                <x-service-unit.fields :unit="$unit ?? new \App\Models\ServiceUnit()" mode="create" :withAssets="true" />
            </div>
            <hr class="my-4">
        </div>
        <div id="section2Hint" class="form-text text-muted"></div>
    </div>

    {{-- หัวข้อ 3: ผู้ลงทะเบียน --}}
    <x-register.user-info :user="$user" />

    <hr class="my-4">

    {{-- หัวข้อ 4: บัญชีเข้าใช้งาน --}}
    <x-register.credentials :user="$user" />

    <hr class="my-4">

    {{-- หัวข้อ 5: PDPA --}}
    <x-register.pdpa :user="$user" />

    <hr class="my-4">

    {{-- หัวข้อ 6: เฉพาะผู้ตรวจสอบ --}}
    <x-register.admin-review :user="$user" />

    <div class="mt-3">
        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> {{ isset($user) ? 'อัปเดต' : 'บันทึก' }}</button>
        <a href="{{ route('backend.application-review.index') }}" class="btn btn-secondary">ยกเลิก</a>
    </div>
</div>


@push('js')
    {{-- ตัวจัดการ collapse Section 2 --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const box = document.getElementById('section2Body');
            const coll = new bootstrap.Collapse(box, {
                toggle: false
            });
            const hint = document.getElementById('section2Hint');

            function setDisabledInside(disabled) {
                box.querySelectorAll('input,select,textarea,button').forEach(el => {
                    if (['btn-center-marker', 'btn-reset-initial', 'btn-geocode'].includes(el.id)) return;
                    if (disabled) {
                        el.setAttribute('data-was-disabled', el.disabled ? '1' : '0');
                        el.disabled = true;
                    } else {
                        if (el.getAttribute('data-was-disabled') === '0') el.disabled = false;
                        el.removeAttribute('data-was-disabled');
                    }
                });
            }

            function updateHint(isHidden) {
                if (!hint) return;
                hint.textContent = isHidden ? 'พับเก็บ “ส่วนที่ 2” และจะไม่ถูกบันทึก' : 'กำลังบันทึกข้อมูลส่วนที่ 2';
                hint.classList.toggle('text-danger', isHidden);
                hint.classList.toggle('text-muted', !isHidden);
            }

            box.addEventListener('shown.bs.collapse', () => {
                setDisabledInside(false);
                updateHint(false);
            });
            box.addEventListener('hidden.bs.collapse', () => {
                setDisabledInside(true);
                updateHint(true);
            });

            document.addEventListener('register:supervisor-only', (e) => {
                e.detail.hideSection2 ? coll.hide() : coll.show();
            });
        });
    </script>
@endpush
