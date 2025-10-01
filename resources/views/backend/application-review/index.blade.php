{{-- resources/views/backend/user/index.blade.php --}}
@extends('layouts.main')

@section('title', 'จัดการผู้ใช้งาน')
@section('breadcrumb-item', 'จัดการผู้ใช้งาน')
@section('breadcrumb-item-active', 'ตรวจสอบใบสมัคร')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card table-card">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <h5 class="mb-0">ตรวจสอบใบสมัคร</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('backend.application-review.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus"></i> เพิ่มใบสมัคร
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
                                        $isActive = (bool) ($u->is_active ?? false);
                                        $regStatus = $u->reg_status ?? 'pending';
                                        $docVerified = !empty($u->officer_doc_verified_at);
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

                                        {{-- สังกัด / บทบาท --}}
                                        <td class="d-none d-xl-table-cell">
                                            <div class="truncate-1" title="{{ $u->org_affiliation ?? '-' }}">
                                                <i class="ti ti-building"></i> {{ $u->org_affiliation ?? '-' }}
                                            </div>

                                            {{-- วัตถุประสงค์ที่ลงทะเบียน (purpose) --}}
                                            <div class="mt-1 d-flex flex-wrap gap-1">
                                                @forelse($purposes as $pp)
                                                    <span class="badge bg-light text-dark border">{{ $pp }}</span>
                                                @empty
                                                    <span class="text-muted small">-</span>
                                                @endforelse
                                            </div>

                                            {{-- สิทธิ์การใช้งาน (Roles) --}}
                                            <div class="mt-1 d-flex flex-wrap gap-1">
                                                <span class="badge text-bg-primary">
                                                    {{ optional($u->role)->name ?? '-' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell">{{ $u->username ?? '-' }}</td>
                                        <td class="d-none d-lg-table-cell">{{ $u->contact_mobile ?? '-' }}</td>

                                        {{-- สถานะลงทะเบียน --}}
                                        <td class="text-center d-none d-lg-table-cell">
                                            @switch($regStatus)
                                                @case('approved')
                                                    <span class="badge text-bg-primary">อนุมัติ</span>
                                                @break

                                                @case('rejected')
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
                                            <form action="{{ route('backend.impersonate.start', $u->id) }}" method="POST" onsubmit="return confirm('จำลองเป็น {{ $u->contact_name }} ?')">
                                                @csrf
                                                <button type="submit" class="avtar avtar-xs btn-link-danger" title="จำลองผู้ใช้">
                                                    <i class="ti ti-user-exclamation f-20"></i>
                                                </button>
                                            </form>

                                            <a href="{{ route('backend.application-review.edit', $u) }}" class="avtar avtar-xs btn-link-secondary" title="แก้ไข">
                                                <i class="ti ti-edit f-20"></i>
                                            </a>

                                            <form action="{{ route('backend.application-review.destroy', $u) }}" method="POST" class="d-inline" onsubmit="return confirm('ยืนยันการลบผู้ใช้งานนี้?')">
                                                @csrf @method('DELETE')
                                                <button class="avtar avtar-xs btn-link-secondary" type="submit" title="ลบ">
                                                    <i class="ti ti-trash f-20"></i>
                                                </button>
                                            </form>
                                        </td>

                                    </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">— ไม่พบข้อมูล —</td>
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
    @endsection
