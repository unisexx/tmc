{{-- resources/views/backend/st_health_services/index.blade.php --}}
@extends('layouts.main')

@section('title', 'การให้บริการ')
@section('breadcrumb-item', 'ตั้งค่า')
@section('breadcrumb-item-active', 'การให้บริการ')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @php
                        $q = request('q');
                        $level = request('level');
                        $active = request('active');
                        $reorder = (bool) request('reorder', false);
                        $levels = ['basic' => 'พื้นฐาน', 'medium' => 'กลาง', 'advanced' => 'สูง'];
                    @endphp

                    <form method="GET" action="{{ route('backend.st-health-services.index') }}" class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        {{-- ซ้าย: ช่องกรอก + ตัวเลือก --}}
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            {{-- คำค้น --}}
                            <div class="input-group" style="width: min(360px, 90vw);">
                                <span class="input-group-text">คำค้น</span>
                                <input id="q" type="text" name="q" value="{{ $q }}" class="form-control" placeholder="ชื่อบริการ">
                            </div>

                            {{-- ระดับ --}}
                            <div class="input-group" style="width: 260px;">
                                <span class="input-group-text">ระดับ</span>
                                <select name="level" class="form-select">
                                    <option value="">— ทั้งหมด —</option>
                                    @foreach ($levels as $k => $v)
                                        <option value="{{ $k }}" @selected($level === $k)>{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- สถานะใช้งาน --}}
                            <div class="input-group" style="width: 220px;">
                                <span class="input-group-text">สถานะ</span>
                                <select name="active" class="form-select">
                                    <option value="">— ทั้งหมด —</option>
                                    <option value="1" @selected($active === '1')>ใช้งาน</option>
                                    <option value="0" @selected($active === '0')>ปิดการใช้งาน</option>
                                </select>
                            </div>

                            <button class="btn btn-outline-primary" type="submit">
                                <i class="ph-duotone ph-magnifying-glass"></i> ค้นหา
                            </button>
                        </div>

                        {{-- ขวา: โหมดจัดเรียง + เพิ่มรายการ --}}
                        <div class="d-flex justify-content-end">
                            @if (!$reorder)
                                <a href="{{ request()->fullUrlWithQuery(['reorder' => 1, 'page' => null]) }}" class="btn btn-outline-secondary">
                                    <i class="ti ti-arrows-sort"></i> โหมดจัดเรียง
                                </a>
                            @else
                                <a href="{{ request()->fullUrlWithQuery(['reorder' => 0]) }}" class="btn btn-outline-secondary">
                                    <i class="ti ti-arrow-back-up"></i> ออกจากโหมดจัดเรียง
                                </a>
                            @endif

                            <a href="{{ route('backend.st-health-services.create') }}" class="btn btn-primary ms-2">
                                <i class="ti ti-plus"></i> เพิ่มบริการ
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-nowrap">
                                    @if ($reorder)
                                        <th style="width:48px;"></th>
                                    @endif
                                    <th style="width:70px">#</th>
                                    <th>ชื่อบริการ</th>
                                    <th class="text-center">ระดับ</th>
                                    <th class="text-center">สถานะ</th>
                                    <th class="text-center" style="width:120px">การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="healthServiceTbody">
                                @forelse($items as $i => $it)
                                    @php
                                        $levelMap = ['basic' => 'พื้นฐาน', 'medium' => 'กลาง', 'advanced' => 'สูง'];
                                    @endphp
                                    <tr @if ($reorder) data-id="{{ $it->id }}" @endif>
                                        @if ($reorder)
                                            <td class="text-muted"><i class="ti ti-grip-vertical drag-handle"></i></td>
                                        @endif

                                        <td>{{ $it->ordering ?? '-' }}</td>

                                        <td>
                                            <div class="mb-0 text-truncate" title="{{ $it->name }}">{{ $it->name }}</div>
                                            @if ($it->description)
                                                <small class="text-muted d-block text-truncate" title="{{ $it->description }}">{{ $it->description }}</small>
                                            @endif
                                        </td>

                                        <td class="text-center">
                                            <x-level-badge :level="$it->level_code" class="ms-2" />
                                        </td>

                                        <td class="text-center">
                                            @if ($it->is_active)
                                                <i class="ph-duotone ph-check-circle text-primary f-20" data-bs-toggle="tooltip" data-bs-title="ใช้งาน"></i>
                                            @else
                                                <i class="ph-duotone ph-x-circle text-danger f-20" data-bs-toggle="tooltip" data-bs-title="ปิดการใช้งาน"></i>
                                            @endif
                                        </td>

                                        <td class="text-center">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2" role="group" aria-label="จัดการบริการ">
                                                <a href="{{ route('backend.st-health-services.edit', $it) }}" class="btn btn-sm btn-light border" data-bs-toggle="tooltip" data-bs-title="แก้ไข">
                                                    <i class="ti ti-edit me-1"></i> แก้ไข
                                                </a>
                                                <form action="{{ route('backend.st-health-services.destroy', $it) }}" method="POST" class="d-inline js-delete-form" data-title="{{ $it->name }}">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light border" data-bs-toggle="tooltip" data-bs-title="ลบ">
                                                        <i class="ti ti-trash me-1"></i> ลบ
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $reorder ? 8 : 7 }}" class="text-center text-muted">— ไม่พบข้อมูล —</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @unless ($reorder)
                        <div class="mt-3">
                            {{ method_exists($items, 'links') ? $items->links() : '' }}
                        </div>
                    @endunless
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @if (request('reorder'))
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
                const tbody = document.getElementById('healthServiceTbody');
                if (!tbody) return;

                new Sortable(tbody, {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onEnd: function() {
                        const ids = Array.from(tbody.querySelectorAll('tr[data-id]')).map(tr => tr.dataset.id);
                        fetch("{{ route('backend.st-health-services.reorder') }}", {
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
                                // อัปเดตเลข ordering บนจอให้ตรงกับลำดับใหม่
                                const rows = tbody.querySelectorAll('tr[data-id]');
                                rows.forEach((tr, idx) => tr.querySelector('td:nth-child({{ request('reorder') ? 2 : 1 }})').textContent = (idx + 1).toString());
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
        document.addEventListener('DOMContentLoaded', function() {
            const list = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            list.forEach(function(el) {
                new bootstrap.Tooltip(el);
            });
        });

        // 🗑️ SweetAlert2: ยืนยันการลบ
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
            }).then(res => {
                if (res.isConfirmed) form.submit();
            });
        });

        // ซ่อน tooltip เมื่อคลิก
        document.addEventListener('click', function(e) {
            const t = e.target.closest('[data-bs-toggle="tooltip"]');
            if (t) bootstrap.Tooltip.getInstance(t)?.hide();
        });
    </script>
@endsection
