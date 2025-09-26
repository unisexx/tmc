{{-- resources/views/backend/user/index.blade.php --}}
@extends('layouts.main')

@section('title', 'จัดการผู้ใช้งาน')
@section('breadcrumb-item', 'ผู้ใช้งาน')
@section('breadcrumb-item-active', 'รายการผู้ใช้งาน')

@section('css')
    <link rel="stylesheet" href="{{ URL::asset('build/css/plugins/style.css') }}">
    <style>
        .avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eef2f7;
            color: #6c757d;
            font-weight: 700;
        }

        .wid-80 {
            width: 80px;
            height: auto;
        }
    </style>
@endsection

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
                                    <th class="d-none d-md-table-cell">Username</th>
                                    <th class="d-none d-lg-table-cell">โทรศัพท์</th>
                                    <th class="text-center">สถานะ</th>
                                    <th class="text-end">การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $i => $u)
                                    <tr>
                                        <td>{{ method_exists($users, 'firstItem') ? $users->firstItem() + $i : $loop->iteration }}</td>

                                        {{-- ผู้ใช้งาน (รูป/ชื่อ/อีเมล) --}}
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    @php $initial = mb_substr($u->name ?? 'U', 0, 1); @endphp
                                                    @if (!empty($u->avatar_path))
                                                        <img src="{{ asset('storage/' . $u->avatar_path) }}" alt="avatar" class="wid-80 rounded">
                                                    @else
                                                        <div class="avatar">{{ $initial }}</div>
                                                    @endif
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-0">{{ $u->name ?? '-' }}</h6>
                                                    <small class="text-muted d-block">{{ $u->email ?? '-' }}</small>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="d-none d-md-table-cell">{{ $u->username ?? '-' }}</td>
                                        <td class="d-none d-lg-table-cell">{{ $u->phone ?? '-' }}</td>

                                        <td class="text-center">
                                            @if ($u->is_active ?? true)
                                                <i class="ph-duotone ph-check-circle text-primary f-24" data-bs-toggle="tooltip" data-bs-title="Active"></i>
                                            @else
                                                <i class="ph-duotone ph-x-circle text-danger f-24" data-bs-toggle="tooltip" data-bs-title="Inactive"></i>
                                            @endif
                                        </td>

                                        <td class="text-end">
                                            <a href="{{ route('backend.user.edit', $u) }}" class="avtar avtar-xs btn-link-secondary" title="แก้ไข">
                                                <i class="ti ti-edit f-20"></i>
                                            </a>
                                            <form action="{{ route('backend.user.destroy', $u) }}" method="POST" class="d-inline" onsubmit="return confirm('ยืนยันการลบผู้ใช้งานนี้?')">
                                                @csrf @method('DELETE')
                                                <button class="avtar avtar-xs btn-link-secondary" type="submit" title="ลบ">
                                                    <i class="ti ti-trash f-20"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">— ไม่พบข้อมูล —</td>
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
