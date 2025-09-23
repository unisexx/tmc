@extends('layouts.main')

@section('title', 'นโยบายข้อมูลส่วนบุคคล')
@section('breadcrumb-item', 'เนื้อหา')
@section('breadcrumb-item-active', 'นโยบายข้อมูลส่วนบุคคล')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">นโยบายข้อมูลส่วนบุคคล</h5>
                    <span class="text-muted small">บันทึกนี้มีเพียง 1 รายการ (id = 1)</span>
                </div>

                <div class="card-body">
                    <form action="{{ route('backend.privacy.update') }}" method="POST" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="row g-4">
                            {{-- ชื่อเรื่อง --}}
                            <div class="col-12">
                                <label for="titleInput" class="form-label">ชื่อเรื่อง <span class="text-danger">*</span></label>
                                <input type="text" id="titleInput" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $policy->title) }}" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="editor" class="form-label">รายละเอียด</label>
                                <textarea id="editor" name="description" rows="12" class="form-control @error('description') is-invalid @enderror">{{ old('description', $policy->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-device-floppy"></i> บันทึก
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
@endsection

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
