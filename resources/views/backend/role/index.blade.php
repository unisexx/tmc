{{-- resources/views/backend/role/index.blade.php --}}
@extends('layouts.main')

@section('title', 'สิทธิ์การใช้งาน')
@section('breadcrumb-item', 'จัดการผู้ใช้งาน')
@section('breadcrumb-item-active', 'สิทธิ์การใช้งาน')

@section('css')
    <link rel="stylesheet" href="{{ URL::asset('build/css/plugins/style.css') }}">
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card table-card">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <h5 class="mb-0">สิทธิ์การใช้งาน</h5>
                    <div>
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
                                    <th style="width: 80px;">#</th>
                                    <th>สิทธิ์การใช้งาน</th>
                                    <th class="text-center" style="width: 120px;">สถานะ</th>
                                    <th class="text-end" style="width: 120px;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($roles as $i => $role)
                                    <tr>
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
                                            <a href="{{ route('backend.role.edit', $role) }}" class="avtar avtar-xs btn-link-secondary">
                                                <i class="ti ti-edit f-20"></i>
                                            </a>
                                            <form class="d-inline" method="post" action="{{ route('backend.role.destroy', $role) }}" onsubmit="return confirm('ยืนยันการลบ?')">
                                                @csrf @method('delete')
                                                <button class="avtar avtar-xs btn-link-secondary" type="submit">
                                                    <i class="ti ti-trash f-20"></i>
                                                </button>
                                            </form>
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

                    @if (method_exists($roles, 'links'))
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
    <script type="module">
        import {
            DataTable
        } from "/build/js/plugins/module.js";
        if (document.querySelector('#pc-dt-simple')) {
            window.dt = new DataTable("#pc-dt-simple");
        }
    </script>
@endsection
