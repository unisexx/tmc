{{-- resources/views/backend/user/index.blade.php --}}
@extends('layouts.main')

@section('title', 'จัดการผู้ใช้งาน')
@section('breadcrumb-item', 'ผู้ใช้งาน')
@section('breadcrumb-item-active', 'รายการผู้ใช้งาน')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card table-card">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <h5 class="mb-0">รายการผู้ใช้งาน</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('backend.user.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus"></i> เพิ่มผู้ใช้งาน
                        </a>
                        <a href="{{ route('backend.user.export') }}" class="btn btn-outline-secondary">
                            <i class="ti ti-download"></i> ส่งออก CSV
                        </a>
                    </div>
                </div>

                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="pc-dt-simple">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ผู้ใช้งาน</th>
                                    <th class="d-none d-xl-table-cell">สังกัด / บทบาท</th>
                                    <th class="d-none d-md-table-cell">Username</th>
                                    <th class="d-none d-lg-table-cell">โทรศัพท์</th>
                                    <th class="text-center d-none d-lg-table-cell">สถานะลงทะเบียน</th>
                                    <th class="text-center">สถานะระบบ</th>
                                    <th class="text-end">การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $i => $u)
                                    @php
                                        $rowNo = method_exists($users, 'firstItem') ? $users->firstItem() + $i : $loop->iteration;

                                        // หน่วยบริการหลักของผู้ใช้
                                        $unit = $u->serviceUnits()->wherePivot('is_primary', true)->first() ?? $u->serviceUnits()->first();

                                        $isActive = (bool) ($u->is_active ?? false);

                                        $regStatusRaw = $u->reg_status ?? 'รอตรวจสอบ';
                                        $regStatus = match ($regStatusRaw) {
                                            'อนุมัติ' => 'อนุมัติ',
                                            'ไม่อนุมัติ' => 'ไม่อนุมัติ',
                                            'รอตรวจสอบ' => 'รอตรวจสอบ',
                                            default => 'รอตรวจสอบ',
                                        };

                                        $purposes = is_array($u->reg_purpose) ? $u->reg_purpose : (is_string($u->reg_purpose) && $u->reg_purpose !== '' ? json_decode($u->reg_purpose, true) ?? explode(',', $u->reg_purpose) : []);
                                    @endphp
                                    <tr>
                                        <td>{{ $rowNo }}</td>

                                        {{-- ผู้ใช้งาน (รูป/ชื่อ/อีเมล) --}}
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    @php $initial = mb_substr($u->contact_name ?: ($u->name ?? 'U'), 0, 1); @endphp
                                                    @if (!empty($u->avatar_path))
                                                        <img src="{{ asset('storage/' . $u->avatar_path) }}" alt="avatar" class="wid-80 rounded">
                                                    @else
                                                        <div class="avatar">{{ $initial }}</div>
                                                    @endif
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-0 truncate-1" title="{{ $u->contact_name ?? '-' }}">{{ $u->contact_name ?? '-' }}</h6>
                                                    <small class="text-muted d-block truncate-1" title="{{ $u->email ?? '-' }}">{{ $u->email ?? '-' }}</small>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- สังกัด / บทบาท จาก service_units --}}
                                        <td class="d-none d-xl-table-cell">
                                            {{-- สังกัด / บทบาท --}}
                                            @if (!empty($unit?->org_affiliation))
                                                <div class="truncate-1" title="{{ $unit->org_affiliation }}">
                                                    <i class="ti ti-building"></i>
                                                    {{ $unit->org_affiliation }}
                                                </div>
                                            @endif

                                            {{-- วัตถุประสงค์ที่ลงทะเบียน --}}
                                            @if (!empty($purposes) && count($purposes))
                                                <div class="mt-1 d-flex flex-wrap gap-1">
                                                    @foreach ($purposes as $pp)
                                                        <span class="badge bg-light text-dark border">{{ $pp }}</span>
                                                    @endforeach
                                                </div>
                                            @endif

                                            {{-- สิทธิ์การใช้งาน --}}
                                            <div class="mt-1 d-flex flex-wrap gap-1">
                                                @php
                                                    $role = optional($u->role);
                                                    $color = match ($role->id ?? null) {
                                                        2 => 'danger',
                                                        3 => 'primary',
                                                        4, 5 => 'warning',
                                                        default => 'secondary',
                                                    };
                                                @endphp
                                                <span class="badge text-bg-{{ $color }}">
                                                    {{ $role->name ?? '-' }}
                                                </span>
                                            </div>
                                        </td>

                                        <td class="d-none d-md-table-cell">{{ $u->username ?? '-' }}</td>
                                        <td class="d-none d-lg-table-cell">{{ $u->contact_mobile ?? '-' }}</td>

                                        {{-- สถานะลงทะเบียน --}}
                                        <td class="text-center d-none d-lg-table-cell">
                                            @switch($regStatus)
                                                @case('อนุมัติ')
                                                    <span class="badge text-bg-primary">อนุมัติ</span>
                                                @break

                                                @case('ไม่อนุมัติ')
                                                    <span class="badge text-bg-danger">ไม่อนุมัติ</span>
                                                @break

                                                @default
                                                    <span class="badge bg-secondary-subtle text-secondary border">รอตรวจสอบ</span>
                                            @endswitch
                                        </td>

                                        {{-- สถานะระบบ (is_active) --}}
                                        <td class="text-center">
                                            @if ($isActive)
                                                <i class="ph-duotone ph-check-circle text-primary f-24" data-bs-toggle="tooltip" data-bs-title="Active"></i>
                                            @else
                                                <i class="ph-duotone ph-x-circle text-danger f-24" data-bs-toggle="tooltip" data-bs-title="Inactive"></i>
                                            @endif
                                        </td>

                                        {{-- การจัดการ --}}
                                        <td class="text-end d-flex justify-content-end gap-1">
                                            {{-- Impersonate --}}
                                            <form action="{{ route('backend.impersonate.start', $u->id) }}" method="POST" class="d-inline js-impersonate-form" data-title="{{ $u->contact_name ?? ($u->username ?? 'ผู้ใช้') }}">
                                                @csrf
                                                <button type="submit" class="avtar avtar-xs btn-link-danger" data-bs-toggle="tooltip" data-bs-title="จำลองผู้ใช้" aria-label="จำลองเป็น {{ $u->contact_name ?? ($u->username ?? 'ผู้ใช้') }}">
                                                    <i class="ti ti-user-exclamation f-20"></i>
                                                </button>
                                            </form>

                                            {{-- Edit --}}
                                            <a href="{{ route('backend.user.edit', $u) }}" class="avtar avtar-xs btn-link-secondary" data-bs-toggle="tooltip" data-bs-title="แก้ไข" aria-label="แก้ไข: {{ $u->contact_name ?? ($u->username ?? 'ผู้ใช้') }}">
                                                <i class="ti ti-edit f-20"></i>
                                            </a>

                                            {{-- Delete --}}
                                            <form action="{{ route('backend.user.destroy', $u) }}" method="POST" class="d-inline js-delete-form" data-title="{{ $u->contact_name ?? ($u->username ?? 'ผู้ใช้') }}">
                                                @csrf @method('DELETE')
                                                <button class="avtar avtar-xs btn-link-secondary" type="submit" data-bs-toggle="tooltip" data-bs-title="ลบ" aria-label="ลบ: {{ $u->contact_name ?? ($u->username ?? 'ผู้ใช้') }}">
                                                    <i class="ti ti-trash f-20"></i>
                                                </button>
                                            </form>
                                        </td>


                                    </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">— ไม่พบข้อมูล —</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $users->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection

    @section('scripts')
        <script type="module">
            import {
                DataTable
            } from "/build/js/plugins/module.js";
            if (document.querySelector('#pc-dt-simple')) {
                window.dt = new DataTable("#pc-dt-simple");
            }
        </script>

        <script>
            (function() {
                // ✅ เปิดใช้งาน Bootstrap Tooltip
                document.addEventListener('DOMContentLoaded', function() {
                    const list = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    list.forEach(function(el) {
                        new bootstrap.Tooltip(el);
                    });
                });

                // 🧹 ปิด tooltip เมื่อคลิกปุ่ม (กันค้าง)
                document.addEventListener('click', function(e) {
                    const t = e.target.closest('[data-bs-toggle="tooltip"]');
                    if (t) {
                        const inst = bootstrap.Tooltip.getInstance(t);
                        inst && inst.hide();
                    }
                });

                // 🧑‍🚀 SweetAlert2: ยืนยัน "จำลองผู้ใช้"
                document.addEventListener('submit', function(e) {
                    const form = e.target;
                    if (!form.classList.contains('js-impersonate-form')) return;

                    e.preventDefault();
                    const title = form.dataset.title || 'ผู้ใช้รายนี้';

                    Swal.fire({
                        icon: 'question',
                        title: 'ยืนยันการจำลองผู้ใช้?',
                        html: `คุณต้องการ <b>จำลองเป็น ${title}</b> ใช่ไหม`,
                        showCancelButton: true,
                        confirmButtonText: 'ยืนยัน',
                        cancelButtonText: 'ยกเลิก',
                        reverseButtons: true,
                        focusCancel: true
                    }).then(res => {
                        if (res.isConfirmed) form.submit();
                    });
                });

                // 🗑️ SweetAlert2: ยืนยัน "ลบผู้ใช้"
                document.addEventListener('submit', function(e) {
                    const form = e.target;
                    if (!form.classList.contains('js-delete-form')) return;

                    e.preventDefault();
                    const title = form.dataset.title || 'ผู้ใช้รายนี้';

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
            })();
        </script>
    @endsection
