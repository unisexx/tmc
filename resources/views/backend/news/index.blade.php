@extends('layouts.main')

@section('title', 'ข่าวประชาสัมพันธ์')
@section('breadcrumb-item', 'เนื้อหา')
@section('breadcrumb-item-active', 'ข่าวประชาสัมพันธ์')

@section('content')
    <!-- [ Main Content ] start -->
    <div class="row">
        <div class="col-12">
            <div class="card table-card">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <h5 class="mb-0">ข่าวประชาสัมพันธ์</h5>
                    <a href="{{ route('backend.news.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus"></i> เพิ่มข่าว
                    </a>
                </div>

                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover" id="pc-dt-simple">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>หัวข้อ</th>
                                    {{-- <th>สร้างเมื่อ</th> --}}
                                    <th class="text-center">สถานะ</th>
                                    <th class="text-end">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rs as $i => $row)
                                    <tr>
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
                                                    @if ($row->excerpt)
                                                        <small class="text-muted d-block">{{ \Illuminate\Support\Str::limit($row->excerpt, 120) }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        {{-- <td class="text-nowrap">{{ optional($row->created_at)->format('d/m/Y H:i') }}</td> --}}
                                        <td class="text-center">
                                            @if ($row->is_active)
                                                <i class="ph-duotone ph-check-circle text-primary f-24" data-bs-toggle="tooltip" data-bs-title="เผยแพร่ (Active)"></i>
                                            @else
                                                <i class="ph-duotone ph-x-circle text-danger f-24" data-bs-toggle="tooltip" data-bs-title="ฉบับร่าง (Inactive)"></i>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('backend.news.edit', $row) }}" class="avtar avtar-xs btn-link-secondary" data-bs-toggle="tooltip" data-bs-title="แก้ไข">
                                                <i class="ti ti-edit f-20"></i>
                                            </a>

                                            <form class="d-inline js-delete-form" method="post" action="{{ route('backend.news.destroy', $row) }}" data-title="{{ $row->title }}">
                                                @csrf @method('delete')
                                                <button class="avtar avtar-xs btn-link-secondary" type="submit" data-bs-toggle="tooltip" data-bs-title="ลบ">
                                                    <i class="ti ti-trash f-20"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">— ไม่มีข้อมูล —</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- แสดงลิงก์ paginate แบบเดียวกับไฮไลท์ --}}
                    @if (method_exists($rs, 'links'))
                        <div class="mt-3">
                            {{ $rs->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
@endsection

@section('scripts')
    {{-- DataTable (เดิม) --}}
    <script type="module">
        import {
            DataTable
        } from "/build/js/plugins/module.js";
        if (document.querySelector('#pc-dt-simple')) {
            window.dt = new DataTable("#pc-dt-simple");
        }
    </script>

    {{-- Tooltip + SweetAlert2 confirm delete --}}
    <script>
        (function() {
            // ✅ เปิดใช้งาน Bootstrap Tooltip
            document.addEventListener('DOMContentLoaded', function() {
                const list = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                list.forEach(function(el) {
                    new bootstrap.Tooltip(el);
                });
            });

            // 🧹 ปิด tooltip อัตโนมัติเมื่อกดปุ่ม (กัน tooltip ค้าง)
            document.addEventListener('click', function(e) {
                const t = e.target.closest('[data-bs-toggle="tooltip"]');
                if (t) {
                    const inst = bootstrap.Tooltip.getInstance(t);
                    inst && inst.hide();
                }
            });

            // 🛑 SweetAlert2: ยืนยันลบ
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
