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
                                {{-- ก่อนตาราง หรือภายใน @foreach ก็ได้ แต่แนะนำทำใน loop เพื่อลด scope --}}
                                @forelse($users as $i => $u)
                                    @php
                                        $rowNo = method_exists($users, 'firstItem') ? $users->firstItem() + $i : $loop->iteration;
                                        $unit = $u->serviceUnits()->wherePivot('is_primary', true)->first() ?? $u->serviceUnits()->first();
                                        $isActive = (bool) ($u->is_active ?? false);
                                        $purposes = $u->reg_purpose_labels_with_color ?? [];
                                    @endphp
                                    <tr>
                                        <td>{{ $rowNo }}</td>
                                        {{-- ผู้ใช้งาน (รูป/ชื่อ/อีเมล) --}}
                                        <td>
                                            <h6 class="mb-0 truncate-1" title="{{ $u->contact_name ?? '-' }}">{{ $u->contact_name ?? '-' }}</h6>
                                            <small class="text-muted d-block truncate-1" title="{{ $u->email ?? '-' }}">{{ $u->email ?? '-' }}</small>
                                        </td>

                                        {{-- สังกัด / บทบาท จาก service_units --}}
                                        <td class="d-none d-xl-table-cell">
                                            {{-- วัตถุประสงค์ที่ลงทะเบียน --}}
                                            <div class="mt-1 d-flex flex-wrap gap-1">
                                                @forelse($purposes as $pp)
                                                    <span class="badge {{ $pp['class'] }}">{{ $pp['label'] }}</span>
                                                @empty
                                                    <span class="text-muted small">-</span>
                                                @endforelse
                                            </div>


                                            {{-- สังกัดหน่วยบริการ (ถ้ามี) --}}
                                            @if (!empty($unit?->org_affiliation))
                                                <div class="small text-muted truncate-1 mt-1" title="{{ $unit->org_affiliation }}">
                                                    <i class="ti ti-building"></i>
                                                    {{ $unit->org_affiliation }}
                                                </div>
                                            @endif

                                            {{-- จังหวัด / สคร. ตามวัตถุประสงค์ --}}
                                            @php
                                                $hasP = $u->hasPurpose('P');
                                                $hasR = $u->hasPurpose('R');
                                            @endphp

                                            {{-- จังหวัดที่สังกัด (ผู้กำกับฯ ระดับจังหวัด: P) --}}
                                            @if ($hasP && $u->superviseProvince)
                                                <div class="small text-muted mt-1 truncate-1" title="จังหวัดที่สังกัด: {{ $u->superviseProvince->title }}">
                                                    <i class="ti ti-map-pin"></i>
                                                    จังหวัดที่สังกัด: {{ $u->superviseProvince->title }}
                                                </div>
                                            @endif

                                            {{-- สคร. ที่สังกัด (ผู้กำกับฯ ระดับเขต: R) --}}
                                            @if ($hasR && $u->superviseRegion)
                                                <div class="small text-muted mt-1 truncate-1" title="สคร.: {{ $u->superviseRegion->short_title }}">
                                                    <i class="ti ti-building-community"></i>
                                                    สคร.: {{ $u->superviseRegion->short_title }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="d-none d-md-table-cell">{{ $u->username ?? '-' }}</td>
                                        <td class="d-none d-lg-table-cell">{{ $u->contact_mobile ?? '-' }}</td>
                                        {{-- สถานะลงทะเบียน --}}
                                        <td class="text-center d-none d-lg-table-cell">
                                            <span class="badge {{ $u->reg_status_badge_class }}">
                                                {{ $u->reg_status_text }}
                                            </span>
                                        </td>
                                        {{-- สถานะระบบ --}}
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
