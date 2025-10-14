{{-- resources/views/backend/news/_form.blade.php --}}
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

    {{-- คำอธิบายสั้น (Excerpt) --}}
    <div class="col-12">
        <label for="excerptInput" class="form-label">คำอธิบายสั้น (Excerpt)</label>
        <textarea name="excerpt" id="excerptInput" class="form-control @error('excerpt') is-invalid @enderror" rows="3">{{ old('excerpt', $news->excerpt) }}</textarea>
        @error('excerpt')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- เนื้อหา: ใช้ TinyMCE --}}
    <div class="col-12">
        <label for="editor" class="form-label">เนื้อหา</label>
        <textarea id="editor" name="body" rows="12" class="form-control @error('body') is-invalid @enderror">{{ old('body', $news->body) }}</textarea>
        @error('body')
            <div class="invalid-feedback d-block">{{ $message }}</div>
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

    <div class="col-md-6"></div>

    {{-- สถานะ (แถวล่างสุด) --}}
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

@section('scripts')
    <script src="{{ URL::asset('build/js/plugins/tinymce/tinymce.min.js') }}"></script>
    <script>
        // กัน init ซ้ำ
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
                // แถวที่ 1
                'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor',
                // แถวที่ 2
                'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table',
                // แถวที่ 3
                'code preview fullscreen | removeformat | help'
            ],
            content_style: 'body { font-family: "Inter",system-ui,-apple-system,Segoe UI,Roboto,sans-serif; }',

            // === อัปโหลดภาพผ่าน Laravel ===
            automatic_uploads: true,
            images_upload_url: '{{ route('backend.upload.tinymce') }}',
            images_upload_credentials: true, // ส่งคุกกี้/เซสชันไปด้วย

            // ตั้ง headers เพิ่มเติม (เช่น CSRF) เพื่อให้ Laravel รับได้
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
                        if (!res.ok) {
                            const txt = await res.text();
                            throw new Error(txt || ('HTTP ' + res.status));
                        }
                        return res.json();
                    })
                    .then((json) => {
                        if (!json.location) throw new Error('Invalid JSON: missing "location"');
                        // ต้อง return URL (หรือ { location: url } ก็ได้ ตามเอกสาร v6)
                        return json.location;
                    });
            },


            // (ออปชัน) เปิด file picker เลือกภาพจากเครื่อง
            file_picker_types: 'image',
            file_picker_callback: (cb) => {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                input.onchange = function() {
                    const file = this.files[0];
                    if (!file) return;

                    // ส่งเข้า images_upload_handler โดยแปลงเป็น blobInfo ชั่วคราว
                    const reader = new FileReader();
                    reader.onload = () => {
                        const id = 'blobid' + (new Date()).getTime();
                        const blobCache = tinymce.activeEditor.editorUpload.blobCache;
                        const base64 = reader.result.split(',')[1];
                        const blobInfo = blobCache.create(id, file, base64);
                        blobCache.add(blobInfo);
                        cb(blobInfo.blobUri(), {
                            title: file.name
                        }); // แสดงพรีวิวทันที
                        // แล้ว TinyMCE จะอัปโหลดจริงเมื่อบันทึก/ปรับแต่ง (automatic_uploads)
                    };
                    reader.readAsDataURL(file);
                };
                input.click();
            },
        });
    </script>
@endsection
