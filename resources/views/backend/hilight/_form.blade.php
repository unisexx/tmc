{{-- resources/views/backend/hilight/_form.blade.php --}}
@php
    // ใช้ $hilight จากหน้า create/edit
@endphp

<div class="row g-4">
    {{-- ชื่อเรื่อง --}}
    <div class="col-12">
        <label for="titleInput" class="form-label">ชื่อเรื่อง <span class="text-danger">*</span></label>
        <input type="text" name="title" id="titleInput" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $hilight->title) }}" required>
        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- ลิงก์ปลายทาง --}}
    <div class="col-md-6">
        <label for="linkUrlInput" class="form-label">ลิงก์ปลายทาง (ถ้ามี)</label>
        <input type="url" name="link_url" id="linkUrlInput" class="form-control @error('link_url') is-invalid @enderror" value="{{ old('link_url', $hilight->link_url) }}">
        @error('link_url')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- ลำดับแสดงผล --}}
    <div class="col-md-3">
        <label for="orderingInput" class="form-label">ลำดับแสดงผล</label>
        <input type="number" name="ordering" id="orderingInput" class="form-control @error('ordering') is-invalid @enderror" min="0" value="{{ old('ordering', $hilight->ordering ?? 0) }}">
        @error('ordering')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- สถานะ (เลือกเปิด/ปิด) --}}
    <div class="col-md-3">
        <label for="isActiveSelect" class="form-label">สถานะ</label>
        <select name="is_active" id="isActiveSelect" class="form-select @error('is_active') is-invalid @enderror">
            <option value="1" {{ old('is_active', $hilight->is_active ?? true) ? 'selected' : '' }}>เปิดใช้งาน</option>
            <option value="0" {{ old('is_active', $hilight->is_active ?? true) ? '' : 'selected' }}>ปิดใช้งาน</option>
        </select>
        @error('is_active')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- คำอธิบาย --}}
    <div class="col-12">
        <label for="descriptionInput" class="form-label">คำอธิบาย</label>
        <textarea name="description" id="descriptionInput" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $hilight->description) }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- อัปโหลดรูปภาพ --}}
    <div class="col-md-6">
        <label class="form-label" for="imageInput">รูปภาพ</label>
        <input type="file" name="image" id="imageInput" class="form-control @error('image') is-invalid @enderror" accept="image/*">
        @error('image')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror

        @if (!empty($hilight->image_path))
            <div class="mt-2">
                <img src="{{ asset('storage/' . $hilight->image_path) }}" alt="preview" class="img-thumbnail" style="max-height:120px">
            </div>
        @endif
    </div>

    {{-- ปุ่ม --}}
    <div class="col-12 d-flex gap-2 justify-content-end pt-2">
        <a href="{{ route('backend.hilight.index') }}" class="btn btn-light">
            <i class="ti ti-arrow-left"></i> ย้อนกลับ
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy"></i>
            {{ ($mode ?? 'create') === 'edit' ? 'บันทึกการแก้ไข' : 'บันทึก' }}
        </button>
    </div>
</div>
