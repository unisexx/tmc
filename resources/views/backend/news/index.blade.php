@extends('layouts.main')

@section('title', 'ข่าวประชาสัมพันธ์')
@section('breadcrumb-item', 'จัดการข้อมูลหน้าแรก')
@section('breadcrumb-item-active', 'ข่าวประชาสัมพันธ์')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('backend.news.index') }}" class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="input-group" style="width:min(420px,90vw);">
                                <span class="input-group-text">คำค้น</span>
                                <input id="q" type="text" name="q" value="{{ $q ?? ($filters['q'] ?? '') }}" class="form-control" placeholder="หัวข้อ / เนื้อหา">
                            </div>
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="ph-duotone ph-magnifying-glass"></i> ค้นหา
                            </button>
                        </div>
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('backend.news.create') }}" class="btn btn-primary">
                                <i class="ti ti-plus"></i> เพิ่มข่าว
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:72px;">#</th>
                                    <th>รูป</th>
                                    <th>หัวข้อ</th>
                                    <th class="text-center" style="width:140px;">สถานะ</th>
                                    <th class="text-center" style="width:120px;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rs as $i => $row)
                                    <tr>
                                        <td>{{ method_exists($rs, 'firstItem') ? $rs->firstItem() + $i : $loop->iteration }}</td>
                                        <td>
                                            @if ($row->image_path)
                                                <div class="flex-shrink-0">
                                                    <img src="{{ asset('storage/' . $row->image_path) }}" alt="thumb" class="rounded" style="width:80px;height:48px;object-fit:cover;">
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="text-wrap" style="max-width:380px; white-space:normal; line-height:1.4;">
                                                {{ $row->title }}
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if ($row->is_active)
                                                <i class="ph-duotone ph-check-circle text-primary fs-4" data-bs-toggle="tooltip" data-bs-title="เผยแพร่ (Active)"></i>
                                            @else
                                                <i class="ph-duotone ph-x-circle text-danger fs-4" data-bs-toggle="tooltip" data-bs-title="ฉบับร่าง (Inactive)"></i>
                                            @endif
                                        </td>

                                        <td class="text-center">
                                            <div class="d-inline-flex align-items-center gap-2" role="group" aria-label="จัดการข่าว">
                                                <a href="{{ route('backend.news.edit', $row) }}" class="btn btn-sm btn-light border">
                                                    <i class="ti ti-edit me-1"></i> แก้ไข
                                                </a>
                                                <form class="d-inline js-delete-form" method="post" action="{{ route('backend.news.destroy', $row) }}" data-title="{{ $row->title }}">
                                                    @csrf @method('delete')
                                                    <button type="submit" class="btn btn-sm btn-light border">
                                                        <i class="ti ti-trash me-1"></i> ลบ
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">— ไม่มีข้อมูล —</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if (method_exists($rs, 'links'))
                        <div class="mt-3 d-flex justify-content-end">
                            {{ $rs->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            document.addEventListener('DOMContentLoaded', function() {
                const list = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                list.forEach(el => new bootstrap.Tooltip(el));
            });
            document.addEventListener('click', function(e) {
                const t = e.target.closest('[data-bs-toggle="tooltip"]');
                if (t) bootstrap.Tooltip.getInstance(t)?.hide();
            });
            document.addEventListener('submit', function(e) {
                const form = e.target;
                if (!form.classList.contains('js-delete-form')) return;
                e.preventDefault();
                const title = form.dataset.title || 'รายการนี้';
                Swal.fire({
                    icon: 'warning',
                    title: 'ยืนยันการลบ?',
                    html: `ต้องการลบ <b>${title}</b> หรือไม่?<br>การลบเป็นแบบถาวร ไม่สามารถกู้คืนได้`,
                    showCancelButton: true,
                    confirmButtonText: 'ลบ',
                    cancelButtonText: 'ยกเลิก',
                    reverseButtons: true,
                    focusCancel: true
                }).then(r => {
                    if (r.isConfirmed) form.submit();
                });
            });
        })();
    </script>
@endsection
