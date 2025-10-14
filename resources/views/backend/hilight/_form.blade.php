{{-- resources/views/backend/hilight/_form.blade.php --}}


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
    <div class="col-12">
        <label for="linkUrlInput" class="form-label">ลิงก์ปลายทาง (ถ้ามี)</label>
        <input type="url" name="link_url" id="linkUrlInput" class="form-control @error('link_url') is-invalid @enderror" value="{{ old('link_url', $hilight->link_url) }}">
        @error('link_url')
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

    {{-- สถานะ: ย้ายมาแถวล่างสุด และเปลี่ยนเป็นสวิตช์ --}}
    <div class="col-12">
        <label class="form-label d-block mb-2" for="is_active">สถานะ</label>
        <div class="form-check form-switch">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $hilight->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">เปิดการใช้งานรายการนี้</label>
        </div>
        @error('is_active')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
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
