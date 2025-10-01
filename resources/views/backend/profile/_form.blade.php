@php
    // provinces: CODE, TITLE
    $provinces = \Illuminate\Support\Facades\Cache::remember('blade.provinces.all', now()->addDay(), function () {
        return \App\Models\Province::query()
            ->select(['CODE', 'TITLE'])
            ->orderBy('TITLE')
            ->get();
    });

    // health regions: id, code, title, short_title
    $healthRegions = \Illuminate\Support\Facades\Cache::remember('blade.health_regions.all', now()->addDay(), function () {
        return \App\Models\HealthRegion::query()
            ->select(['id', 'code', 'title', 'short_title'])
            ->orderBy('code')
            ->get();
    });
@endphp

{{-- ====================== Error Summary ====================== --}}
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
                            {{-- แปลงชื่อ field ที่เป็น array ให้อ่านง่าย --}}
                            <a href="#field-{{ \Illuminate\Support\Str::slug($field) }}" class="text-reset text-decoration-underline">
                                {{ str_replace(['working_hours.*.', 'working_hours.'], 'วัน-เวลาทำการ: ', $field) }}
                            </a>
                            : {{ $messages[0] }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @push('js')
        <script>
            // เลื่อนขึ้นไปดูสรุป error อัตโนมัติ
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


<div class="row g-3">

    <div class="col-md-6">
        <label for="contact_cid" class="form-label required">เลขบัตรประจำตัวประชาชน (13 หลัก)</label>
        <input type="text" name="contact_cid" id="contact_cid" value="{{ old('contact_cid', $user->contact_cid ?? '') }}" class="form-control" readonly>
        <div class="form-text text-muted">ไม่สามารถเปลี่ยนแปลงได้</div>
    </div>

    <div class="col-md-6">
        <label for="contact_name" class="form-label required">ชื่อ-สกุล</label>
        <input type="text" name="contact_name" id="contact_name" value="{{ old('contact_name', $user->contact_name ?? '') }}" class="form-control">
        @error('contact_name')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="contact_position" class="form-label required">ตำแหน่ง</label>
        <input type="text" name="contact_position" id="contact_position" value="{{ old('contact_position', $user->contact_position ?? '') }}" class="form-control">
        @error('contact_position')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="contact_mobile" class="form-label required">โทรศัพท์มือถือ</label>
        <input type="text" name="contact_mobile" id="contact_mobile" value="{{ old('contact_mobile', $user->contact_mobile ?? '') }}" class="form-control">
        @error('contact_mobile')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label required">อีเมล</label>
        <input type="email" name="email" id="email" value="{{ old('email', $user->email ?? '') }}" class="form-control" readonly>
        <div class="form-text text-muted">ไม่สามารถเปลี่ยนแปลงได้</div>
    </div>

    <hr class="my-4">

    <div class="col-md-6">
        <label for="username" class="form-label required">Username</label>
        <input type="text" name="username" id="username" value="{{ old('username', $user->username ?? '') }}" class="form-control" readonly>
        <div class="form-text text-muted">ไม่สามารถเปลี่ยนแปลงได้</div>
    </div>

    <div class="col-md-6">
        <label for="password" class="form-label {{ isset($user) ? '' : 'required' }}">รหัสผ่าน</label>
        <input type="password" name="password" id="password" class="form-control">
        @error('password')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="password_confirmation" class="form-label {{ isset($user) ? '' : 'required' }}">ยืนยันรหัสผ่าน</label>
        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
        @error('password_confirmation')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
        <div class="form-text">
            {{ isset($user) ? 'หากต้องการเปลี่ยนรหัสผ่าน ให้กรอกทั้งรหัสผ่านและยืนยันรหัสผ่าน' : 'กรอกให้ตรงกับรหัสผ่าน' }}
        </div>
    </div>
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary">
        <i class="ti ti-device-floppy"></i> {{ isset($user) ? 'อัปเดต' : 'บันทึก' }}
    </button>
    <a href="{{ route('backend.dashboard') }}" class="btn btn-secondary">ยกเลิก</a>
</div>
