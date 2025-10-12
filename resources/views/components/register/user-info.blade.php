{{-- <x-register.user-info --}}

@props(['user' => null])

<h5>3. ข้อมูลทั่วไปของผู้ลงทะเบียน</h5>
<div class="row g-3">
    <div class="col-md-6">
        <label for="contact_cid" class="form-label required">เลขบัตรประจำตัวประชาชน (13 หลัก)</label>
        <input type="text" name="contact_cid" id="contact_cid" value="{{ old('contact_cid', $user->contact_cid ?? '') }}" class="form-control" inputmode="numeric" maxlength="13" placeholder="กรอกเฉพาะตัวเลข 13 หลัก">
        @error('contact_cid')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        <div class="form-text" id="cid-preview" style="display:none;"></div>
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
        <input type="email" name="email" id="email" value="{{ old('email', $user->email ?? '') }}" class="form-control">
        @error('email')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="officer_doc" class="form-label required">เอกสารยืนยันตัวเจ้าหน้าที่</label>
        @if (!empty($user?->officer_doc_path))
            <div class="alert alert-secondary d-flex align-items-center justify-content-between" role="alert">
                <div>
                    <i class="ti ti-file-description me-1"></i>
                    ไฟล์ที่อัปโหลดไว้:
                    <a href="{{ Storage::disk('public')->url($user->officer_doc_path) }}" target="_blank">
                        {{ basename($user->officer_doc_path) }}
                    </a>
                </div>
                <div class="form-check ms-3">
                    <input class="form-check-input" type="checkbox" name="remove_officer_doc" id="remove_officer_doc" value="1">
                    <label class="form-check-label" for="remove_officer_doc">ลบไฟล์นี้</label>
                </div>
            </div>
            <div class="form-text mb-2">หากต้องการ “แทนที่ไฟล์” ให้เลือกไฟล์ใหม่ ระบบจะอัปเดตให้โดยอัตโนมัติ</div>
        @else
            <div class="form-text mb-2">รองรับไฟล์ PDF/JPG/PNG ขนาดสูงสุด 5 MB</div>
        @endif
        <input type="file" name="officer_doc" id="officer_doc" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
        @error('officer_doc')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('contact_cid');
            const pv = document.getElementById('cid-preview');

            function onlyDigits(v) {
                return (v || '').replace(/\D/g, '').slice(0, 13);
            }

            function fmtTH(v) {
                if (v.length < 13) return '';
                return `${v[0]}-${v.slice(1,5)}-${v.slice(5,10)}-${v.slice(10,12)}-${v[12]}`;
            }

            function updatePreview() {
                const v = onlyDigits(el.value);
                if (v !== el.value) {
                    el.value = v;
                }
                const f = fmtTH(v);
                pv.style.display = f ? 'block' : 'none';
                pv.textContent = f ? `รูปแบบ: ${f}` : '';
            }
            el?.addEventListener('input', updatePreview);
            updatePreview();
        });
    </script>
@endpush
