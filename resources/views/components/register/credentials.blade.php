@props(['user' => null])

<h5>4. กำหนด Username และ Password สำหรับเข้าใช้งานระบบ</h5>
<div class="row g-3">
    <div class="col-md-6">
        <label for="username" class="form-label required">Username</label>
        <input type="text" name="username" id="username" value="{{ old('username', $user->username ?? '') }}" class="form-control">
        @error('username')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
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
        <div class="form-text">{{ isset($user) ? 'หากต้องการเปลี่ยนรหัสผ่าน ให้กรอกทั้งรหัสผ่านและยืนยันรหัสผ่าน' : 'กรอกให้ตรงกับรหัสผ่าน' }}</div>
    </div>
</div>
