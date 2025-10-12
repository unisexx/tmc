@extends('layouts.main')

@section('title', 'ไฮไลท์')
@section('breadcrumb-item', 'เนื้อหา')
@section('breadcrumb-item-active', 'ไฮไลท์')

@section('content')
    <div class="card">
        {{-- <div class="card-header border-0 pb-0">
            <h5 class="mb-0">ไฮไลท์</h5>
        </div> --}}

        <div class="card-body">

            {{-- Filter Bar --}}
            <form method="GET" action="{{ route('backend.hilight.index') }}" class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">

                {{-- ซ้าย: ช่องกรอก + ปุ่มล้าง + ปุ่มค้นหา (ไม่อยู่ใน input-group) --}}
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="input-group" style="width: min(420px, 90vw);">
                        <span class="input-group-text">คำค้น</span>
                        <input id="q" type="text" name="q" value="{{ $q }}" class="form-control" placeholder="หัวข้อ / ลิงก์ / คำอธิบาย">
                    </div>

                    <button class="btn btn-outline-primary" type="submit">
                        <i class="ph-duotone ph-magnifying-glass"></i> ค้นหา
                    </button>
                </div>


                <div class="d-flex justify-content-end mb-2">
                    @if (!($reorder ?? false))
                        <a href="{{ request()->fullUrlWithQuery(['reorder' => 1, 'page' => null]) }}" class="btn btn-outline-secondary">
                            <i class="ti ti-arrows-sort"></i> โหมดจัดเรียง
                        </a>
                    @else
                        <a href="{{ request()->fullUrlWithQuery(['reorder' => 0]) }}" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-back-up"></i> ออกจากโหมดจัดเรียง
                        </a>
                    @endif

                    {{-- ขวา: ปุ่มเพิ่มรายการ --}}
                    <div class="ms-2">
                        <a href="{{ route('backend.hilight.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus"></i> เพิ่มไฮไลท์
                        </a>
                    </div>
                </div>
            </form>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            @if ($reorder ?? false)
                                <th style="width:48px;"></th>
                            @endif
                            <th style="width:72px;">#</th>
                            <th>หัวข้อ</th>
                            <th class="text-center" style="width:120px;">สถานะ</th>
                            <th class="text-center" style="width:120px;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="hilightTbody">
                        @forelse ($rs as $i => $row)
                            <tr data-id="{{ $row->id }}">
                                @if ($reorder ?? false)
                                    <td class="text-muted">
                                        <i class="ti ti-grip-vertical drag-handle"></i>
                                    </td>
                                @endif
                                <td>{{ method_exists($rs, 'firstItem') ? $rs->firstItem() + $i : $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if ($row->image_path)
                                            <div class="flex-shrink-0">
                                                <img src="{{ asset('storage/' . $row->image_path) }}" alt="thumb" class="rounded" style="width:80px;height:48px;object-fit:cover;" />
                                            </div>
                                        @endif
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0">{{ $row->title }}</h6>
                                            @if ($row->link_url)
                                                <small class="text-muted d-block">{{ $row->link_url }}</small>
                                            @endif
                                            @if ($row->description)
                                                <small class="text-muted d-block">{{ Str::limit($row->description, 80) }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if ($row->is_active)
                                        <i class="ph-duotone ph-check-circle text-primary fs-4" data-bs-toggle="tooltip" data-bs-title="เผยแพร่ (Active)"></i>
                                    @else
                                        <i class="ph-duotone ph-x-circle text-danger fs-4" data-bs-toggle="tooltip" data-bs-title="ฉบับร่าง (Inactive)"></i>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex align-items-center gap-2" role="group" aria-label="จัดการข้อมูล">
                                        <a href="{{ route('backend.hilight.edit', $row) }}" class="btn btn-sm btn-light border">
                                            <i class="ti ti-edit me-1"></i> แก้ไข
                                        </a>

                                        <form class="d-inline js-delete-form" method="post" action="{{ route('backend.hilight.destroy', $row) }}" data-title="{{ $row->title }}">
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
                                <td colspan="{{ $reorder ?? false ? 6 : 5 }}" class="text-center text-muted">— ไม่มีข้อมูล —</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if (!($reorder ?? false) && method_exists($rs, 'links'))
                <div class="mt-3 d-flex justify-content-end">
                    {{ $rs->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    @if ($reorder ?? false)
        <style>
            .drag-handle {
                cursor: grab;
            }

            .sortable-ghost {
                opacity: .7;
                background: var(--bs-light-bg-subtle);
            }
        </style>
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
        <script>
            (function() {
                const tbody = document.getElementById('hilightTbody');
                if (!tbody) return;

                new Sortable(tbody, {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onEnd: function() {
                        const ids = Array.from(tbody.querySelectorAll('tr[data-id]')).map(tr => tr.dataset.id);
                        fetch("{{ route('backend.hilight.reorder') }}", {
                                method: "POST",
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    ids
                                })
                            })
                            .then(r => r.json())
                            .then(res => {
                                if (!res.ok) throw new Error(res.message || 'อัปเดตลำดับไม่สำเร็จ');
                                Swal.fire({
                                    icon: 'success',
                                    title: 'บันทึกลำดับใหม่แล้ว',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 1600,
                                    timerProgressBar: true
                                });
                            })
                            .catch(err => {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'อัปเดตลำดับไม่สำเร็จ',
                                    text: err.message || 'กรุณาลองใหม่อีกครั้ง'
                                });
                            });
                    }
                });
            })();
        </script>
    @endif

    <script>
        (function() {
            document.addEventListener('DOMContentLoaded', function() {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
            });

            document.addEventListener('click', e => {
                const t = e.target.closest('[data-bs-toggle="tooltip"]');
                if (t) bootstrap.Tooltip.getInstance(t)?.hide();
            });

            document.addEventListener('submit', e => {
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
                }).then(result => {
                    if (result.isConfirmed) form.submit();
                });
            });
        })();
    </script>
@endsection
