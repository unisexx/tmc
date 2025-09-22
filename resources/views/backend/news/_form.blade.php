@php
    // ใช้ $news และ $mode ('create' | 'edit')
    $img = old('image_path', $news->image_path ?? null);
@endphp

<div class="row g-4">
    {{-- ชื่อเรื่อง --}}
    <div class="col-12">
        <label for="titleInput" class="form-label">ชื่อเรื่อง <span class="text-danger">*</span></label>
        <input type="text" name="title" id="titleInput" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $news->title) }}" required>
        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Slug --}}
    <div class="col-md-6">
        <label for="slugInput" class="form-label">Slug (ว่างไว้ให้ระบบสร้างอัตโนมัติ)</label>
        <input type="text" name="slug" id="slugInput" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $news->slug) }}">
        @error('slug')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- หมวดหมู่ --}}
    <div class="col-md-6">
        <label for="categoryInput" class="form-label">หมวดหมู่</label>
        <input type="text" name="category" id="categoryInput" class="form-control @error('category') is-invalid @enderror" value="{{ old('category', $news->category) }}">
        @error('category')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- คำอธิบายสั้น (Excerpt) --}}
    <div class="col-12">
        <label for="excerptInput" class="form-label">คำอธิบายสั้น (Excerpt)</label>
        <textarea name="excerpt" id="excerptInput" class="form-control @error('excerpt') is-invalid @enderror" rows="3">{{ old('excerpt', $news->excerpt) }}</textarea>
        @error('excerpt')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- เนื้อหา --}}
    <div class="col-12">
        <label for="bodyInput" class="form-label">เนื้อหา</label>
        <textarea name="body" id="bodyInput" class="form-control @error('body') is-invalid @enderror" rows="6">{{ old('body', $news->body) }}</textarea>
        @error('body')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- สถานะการเผยแพร่ --}}
    <div class="col-md-6">
        <label for="isActiveSelect" class="form-label">สถานะ</label>
        <select name="is_active" id="isActiveSelect" class="form-select @error('is_active') is-invalid @enderror">
            <option value="1" @selected(old('is_active', $news->is_active ?? false) == 1)>เผยแพร่</option>
            <option value="0" @selected(old('is_active', $news->is_active ?? false) == 0)>ฉบับร่าง</option>
        </select>
        @error('is_active')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- อัปโหลดรูปภาพ --}}
    <div class="col-md-6">
        <label class="form-label" for="imageInput">รูปภาพปก</label>
        <input type="file" name="image" id="imageInput" class="form-control @error('image') is-invalid @enderror" accept="image/*" onchange="previewNewsImage(event)">
        @error('image')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror

        @if ($img)
            <div class="mt-2">
                <img id="preview-img" src="{{ asset('storage/' . $img) }}" alt="preview" class="img-thumbnail" style="max-height:140px">
            </div>
        @else
            <img id="preview-img" alt="preview" class="img-thumbnail d-none mt-2" style="max-height:140px">
        @endif
    </div>

    {{-- ปุ่มบันทึก/ย้อนกลับ --}}
    <div class="col-12 d-flex gap-2 justify-content-end pt-2">
        <a href="{{ route('backend.news.index') }}" class="btn btn-light">
            <i class="ti ti-arrow-left"></i> ย้อนกลับ
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy"></i>
            {{ ($mode ?? 'create') === 'edit' ? 'บันทึกการแก้ไข' : 'บันทึก' }}
        </button>
    </div>
</div>

@push('js')
    <script>
        function previewNewsImage(e) {
            const img = document.getElementById('preview-img');
            const file = e.target.files?.[0];
            if (!file) {
                img.classList.add('d-none');
                return;
            }
            const url = URL.createObjectURL(file);
            img.src = url;
            img.classList.remove('d-none');
        }
    </script>
@endpush
