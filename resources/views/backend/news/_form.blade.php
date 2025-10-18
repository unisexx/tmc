{{-- resources/views/backend/news/_form.blade.php --}}
@php
    $img = old('image_path', $news->image_path ?? null);
@endphp

<div class="row g-4">
    {{-- ชื่อเรื่อง --}}
    <div class="col-12">
        <label for="titleInput" class="form-label">ชื่อเรื่อง <span class="text-danger">*</span></label>
        <input type="text" name="title" id="titleInput" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $news->title) }}" placeholder="กรอกชื่อข่าว เช่น การประชุมประจำปีหน่วยบริการสุขภาพผู้เดินทาง" required>
        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- เนื้อหา --}}
    <div class="col-12">
        <label for="editor" class="form-label">เนื้อหา</label>
        <textarea id="editor" name="body" rows="12" class="form-control @error('body') is-invalid @enderror" placeholder="พิมพ์รายละเอียดข่าวที่ต้องการเผยแพร่">{{ old('body', $news->body) }}</textarea>
        @error('body')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- อัปโหลดรูปภาพ --}}
    <div class="col-md-6">
        <label class="form-label" for="imageInput">รูปภาพปก <small class="text-muted">(ขนาดแนะนำ 448 × 276 px)</small></label>
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

    <div class="col-md-6"></div>

    {{-- สถานะ --}}
    <div class="col-12">
        <label class="form-label d-block mb-1" for="is_active">สถานะ</label>
        <div class="form-check form-switch">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $news->is_active ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">เผยแพร่</label>
        </div>
        @error('is_active')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- ปุ่มบันทึก --}}
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

@section('scripts')
    <script src="{{ URL::asset('build/js/plugins/tinymce/tinymce.min.js') }}"></script>
    <script>
        if (tinymce?.editors?.length) tinymce.remove();

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        tinymce.init({
            selector: '#editor',
            height: 500,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: [
                'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor',
                'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table',
                'code preview fullscreen | removeformat | help'
            ],
            content_style: 'body { font-family: "Inter",system-ui,-apple-system,Segoe UI,Roboto,sans-serif; }',
            automatic_uploads: true,
            images_upload_url: '{{ route('backend.upload.tinymce') }}',
            images_upload_credentials: true,
            images_upload_handler: (blobInfo, progress) => {
                const formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());
                return fetch('{{ route('backend.upload.tinymce') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        credentials: 'same-origin',
                    })
                    .then(async (res) => {
                        if (!res.ok) throw new Error(await res.text());
                        return res.json();
                    })
                    .then((json) => {
                        if (!json.location) throw new Error('Invalid JSON: missing "location"');
                        return json.location;
                    });
            },
            file_picker_types: 'image',
            file_picker_callback: (cb) => {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                input.onchange = function() {
                    const file = this.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = () => {
                        const id = 'blobid' + (new Date()).getTime();
                        const blobCache = tinymce.activeEditor.editorUpload.blobCache;
                        const base64 = reader.result.split(',')[1];
                        const blobInfo = blobCache.create(id, file, base64);
                        blobCache.add(blobInfo);
                        cb(blobInfo.blobUri(), {
                            title: file.name
                        });
                    };
                    reader.readAsDataURL(file);
                };
                input.click();
            },
        });

        function previewNewsImage(event) {
            const img = document.getElementById('preview-img');
            img.src = URL.createObjectURL(event.target.files[0]);
            img.classList.remove('d-none');
        }
    </script>
@endsection
