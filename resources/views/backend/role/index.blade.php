{{-- resources/views/backend/role/index.blade.php --}}
@extends('layouts.main')

@section('title', 'สิทธิ์การใช้งาน')
@section('breadcrumb-item', 'จัดการผู้ใช้งาน')
@section('breadcrumb-item-active', 'สิทธิ์การใช้งาน')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                {{-- <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <h5 class="mb-0">สิทธิ์การใช้งาน</h5>
                </div> --}}

                <div class="card-body pt-3">

                    {{-- Filter Bar (ย้ายมาอยู่ card-body) --}}
                    @php $q = request('q'); @endphp
                    <form method="GET" action="{{ route('backend.role.index') }}" class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">

                        {{-- ซ้าย: ค้นหา (ไม่ใช้ input-group) --}}
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="input-group" style="width: min(420px, 90vw);">
                                <span class="input-group-text">คำค้น</span>
                                <input id="q" type="text" name="q" value="{{ $q }}" class="form-control" placeholder="ชื่อสิทธิ์">
                            </div>


                            <button class="btn btn-outline-primary" type="submit">
                                <i class="ph-duotone ph-magnifying-glass"></i> ค้นหา
                            </button>
                        </div>

                        {{-- ขวา: ปุ่มโหมดจัดเรียง + เพิ่มรายการ --}}
                        <div class="d-flex justify-content-end">
                            @if (empty($reorder))
                                <a href="{{ request()->fullUrlWithQuery(['reorder' => 1, 'page' => null]) }}" class="btn btn-outline-secondary">
                                    <i class="ti ti-arrows-sort"></i> โหมดจัดเรียง
                                </a>
                            @else
                                <a href="{{ request()->fullUrlWithQuery(['reorder' => 0]) }}" class="btn btn-outline-secondary">
                                    <i class="ti ti-arrow-back-up"></i> ออกจากโหมดจัดเรียง
                                </a>
                            @endif

                            <a href="{{ route('backend.role.create') }}" class="btn btn-primary ms-2">
                                <i class="ti ti-plus"></i> เพิ่มรายการ
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    @if (!empty($reorder))
                                        <th style="width:48px;"></th>
                                    @endif
                                    <th style="width:80px;">#</th>
                                    <th>สิทธิ์การใช้งาน</th>
                                    <th class="text-center" style="width:120px;">สถานะ</th>
                                    <th class="text-end" style="width:120px;">จัดการ</th>
                                </tr>
                            </thead>

                            <tbody id="roleTbody">
                                @forelse ($roles as $i => $role)
                                    <tr data-id="{{ $role->id }}">
                                        @if (!empty($reorder))
                                            <td class="text-muted"><i class="ti ti-grip-vertical drag-handle"></i></td>
                                        @endif

                                        <td>{{ method_exists($roles, 'firstItem') ? $roles->firstItem() + $i : $loop->iteration }}</td>
                                        <td>{{ $role->name }}</td>

                                        <td class="text-center">
                                            @if ($role->is_active)
                                                <i class="ph-duotone ph-check-circle text-primary f-24" data-bs-toggle="tooltip" data-bs-title="ใช้งานอยู่ (Active)"></i>
                                            @else
                                                <i class="ph-duotone ph-x-circle text-danger f-24" data-bs-toggle="tooltip" data-bs-title="ปิดการใช้งาน (Inactive)"></i>
                                            @endif
                                        </td>

                                        <td class="text-end">
                                            <a href="{{ route('backend.role.edit', $role) }}" class="avtar avtar-xs btn-link-secondary" data-bs-toggle="tooltip" data-bs-title="แก้ไข" aria-label="แก้ไข: {{ $role->name }}">
                                                <i class="ti ti-edit f-20"></i>
                                            </a>

                                            <form class="d-inline js-delete-form" method="post" action="{{ route('backend.role.destroy', $role) }}" data-title="{{ $role->name }}">
                                                @csrf @method('delete')
                                                <button class="avtar avtar-xs btn-link-secondary" type="submit" data-bs-toggle="tooltip" data-bs-title="ลบ" aria-label="ลบ: {{ $role->name }}">
                                                    <i class="ti ti-trash f-20"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ !empty($reorder) ? 5 : 4 }}" class="text-center text-muted">— ไม่มีข้อมูล —</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- แสดง paginate เฉพาะโหมดปกติ --}}
                    @if (empty($reorder) && method_exists($roles, 'links'))
                        <div class="mt-3">
                            {{ $roles->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @if (!empty($reorder))
        {{-- โหมดจัดเรียง: SortableJS --}}
        <style>
            .drag-handle {
                cursor: grab;
            }

            .sortable-ghost {
                opacity: .6;
                background: #f6f7fb;
            }

            .table-responsive thead th {
                position: sticky;
                top: 0;
                z-index: 1;
            }
        </style>
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
        <script>
            (function() {
                const tbody = document.getElementById('roleTbody');
                if (!tbody) return;

                new Sortable(tbody, {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onEnd: function() {
                        const ids = Array.from(tbody.querySelectorAll('tr[data-id]')).map(tr => tr.dataset.id);
                        fetch("{{ route('backend.role.reorder') }}", {
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

    {{-- ใช้ได้ทั้งสองโหมด: Tooltip + SweetAlert ลบ --}}
    <script>
        (function() {
            // Bootstrap Tooltip
            document.addEventListener('DOMContentLoaded', function() {
                const list = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                list.forEach(function(el) {
                    new bootstrap.Tooltip(el);
                });
            });

            // ปิด tooltip เมื่อคลิกปุ่ม (กันค้าง)
            document.addEventListener('click', function(e) {
                const t = e.target.closest('[data-bs-toggle="tooltip"]');
                if (t) {
                    const inst = bootstrap.Tooltip.getInstance(t);
                    inst && inst.hide();
                }
            });

            // SweetAlert2: ยืนยันลบ
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
                }).then(result => {
                    if (result.isConfirmed) form.submit();
                });
            });
        })();
    </script>
@endsection
