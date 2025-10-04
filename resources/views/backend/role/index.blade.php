{{-- resources/views/backend/role/index.blade.php --}}
@extends('layouts.main')

@section('title', 'สิทธิ์การใช้งาน')
@section('breadcrumb-item', 'จัดการผู้ใช้งาน')
@section('breadcrumb-item-active', 'สิทธิ์การใช้งาน')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card table-card">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <h5 class="mb-0">สิทธิ์การใช้งาน</h5>

                    <div class="d-flex gap-2">
                        {{-- ปุ่มเข้า/ออกโหมดจัดเรียง --}}
                        @if (empty($reorder))
                            <a href="{{ request()->fullUrlWithQuery(['reorder' => 1, 'page' => null]) }}" class="btn btn-outline-secondary">
                                <i class="ti ti-arrows-sort"></i> โหมดจัดเรียง
                            </a>
                        @else
                            <a href="{{ request()->fullUrlWithQuery(['reorder' => 0]) }}" class="btn btn-outline-secondary">
                                <i class="ti ti-arrow-back-up"></i> ออกจากโหมดจัดเรียง
                            </a>
                        @endif

                        <a href="{{ route('backend.role.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus"></i> เพิ่มรายการ
                        </a>
                    </div>
                </div>

                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="pc-dt-simple">
                            <thead>
                                <tr>
                                    @if (!empty($reorder))
                                        <th style="width:48px;"></th> {{-- คอลัมน์จับลาก --}}
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
                                            <td class="text-muted">
                                                <i class="ti ti-grip-vertical drag-handle"></i>
                                            </td>
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
                                            <a href="{{ route('backend.role.edit', $role) }}" class="avtar avtar-xs btn-link-secondary" title="แก้ไข">
                                                <i class="ti ti-edit f-20"></i>
                                            </a>
                                            <form class="d-inline" method="post" action="{{ route('backend.role.destroy', $role) }}" onsubmit="return confirm('ยืนยันการลบ?')">
                                                @csrf @method('delete')
                                                <button class="avtar avtar-xs btn-link-secondary" type="submit" title="ลบ">
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

                    {{-- โหมดปกติเท่านั้นที่แสดง paginate --}}
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
    @if (empty($reorder))
        <script type="module">
            import {
                DataTable
            } from "/build/js/plugins/module.js";
            if (document.querySelector('#pc-dt-simple')) {
                window.dt = new DataTable("#pc-dt-simple");
            }
        </script>
    @else
        <style>
            .sortable-ghost {
                opacity: .6;
                background: #f6f7fb;
            }

            .drag-handle {
                cursor: grab;
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
                            })
                            .catch(err => {
                                alert(err.message || 'เกิดข้อผิดพลาดในการอัปเดตลำดับ');
                            });
                    }
                });
            })();
        </script>
    @endif
@endsection
