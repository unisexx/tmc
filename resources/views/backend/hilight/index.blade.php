{{-- resources/views/backend/hilight/index.blade.php --}}
@extends('layouts.main')

@section('title', 'ไฮไลท์')
@section('breadcrumb-item', 'เนื้อหา')
@section('breadcrumb-item-active', 'ไฮไลท์')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card table-card">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <h5 class="mb-0">ไฮไลท์</h5>

                    <div class="d-flex gap-2">
                        {{-- ปุ่มเข้า/ออกโหมดจัดเรียง --}}
                        @if (!($reorder ?? false))
                            <a href="{{ request()->fullUrlWithQuery(['reorder' => 1, 'page' => null]) }}" class="btn btn-outline-secondary">
                                <i class="ti ti-arrows-sort"></i> โหมดจัดเรียง
                            </a>
                        @else
                            <a href="{{ request()->fullUrlWithQuery(['reorder' => 0]) }}" class="btn btn-outline-secondary">
                                <i class="ti ti-arrow-back-up"></i> ออกจากโหมดจัดเรียง
                            </a>
                        @endif

                        <a href="{{ route('backend.hilight.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus"></i> เพิ่มรายการ
                        </a>
                    </div>
                </div>

                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="pc-dt-simple">
                            <thead>
                                <tr>
                                    @if ($reorder ?? false)
                                        <th style="width:48px;"></th> {{-- คอลัมน์จับลาก --}}
                                    @endif
                                    <th>#</th>
                                    <th>หัวข้อ</th>
                                    <th class="text-center">สถานะ</th>
                                    <th class="text-end">จัดการ</th>
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
                                                        <img src="{{ asset('storage/' . $row->image_path) }}" alt="thumb" class="wid-80" />
                                                    </div>
                                                @endif
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-0">{{ $row->title }}</h6>
                                                    @if ($row->image_path)
                                                        <small class="text-muted d-block">{{ $row->image_path }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if ($row->is_active)
                                                <i class="ph-duotone ph-check-circle text-primary f-24" data-bs-toggle="tooltip" data-bs-title="เผยแพร่ (Active)"></i>
                                            @else
                                                <i class="ph-duotone ph-x-circle text-danger f-24" data-bs-toggle="tooltip" data-bs-title="ฉบับร่าง (Inactive)"></i>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('backend.hilight.edit', $row) }}" class="avtar avtar-xs btn-link-secondary">
                                                <i class="ti ti-edit f-20"></i>
                                            </a>
                                            <form class="d-inline" method="post" action="{{ route('backend.hilight.destroy', $row) }}" onsubmit="return confirm('ยืนยันการลบ?')">
                                                @csrf @method('delete')
                                                <button class="avtar avtar-xs btn-link-secondary" type="submit">
                                                    <i class="ti ti-trash f-20"></i>
                                                </button>
                                            </form>
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

                    {{-- ถ้าไม่ใช่โหมดจัดเรียง ค่อยแสดง paginate --}}
                    @if (!($reorder ?? false) && method_exists($rs, 'links'))
                        <div class="mt-3">
                            {{ $rs->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    {{-- โหมดปกติ: ใช้ DataTable ได้ตามเดิม --}}
    @if (!($reorder ?? false))
        <script type="module">
            import {
                DataTable
            } from "/build/js/plugins/module.js";
            if (document.querySelector('#pc-dt-simple')) {
                window.dt = new DataTable("#pc-dt-simple");
            }
        </script>
    @else
        {{-- โหมดจัดเรียง: ปิด DataTable และเปิด SortableJS --}}
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
                        // เก็บ id ตามลำดับปัจจุบัน
                        const ids = Array.from(tbody.querySelectorAll('tr[data-id]')).map(tr => tr.dataset.id);

                        // ยิงไปอัปเดต
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
                                // แจ้งเตือนสั้นๆ (ถ้ามีระบบ toast อยู่แล้วเรียกใช้แทนได้)
                                console.log(res.message);
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
