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
                                    <th class="text-center d-none d-lg-table-cell">สิทธิ์การใช้งาน</th>
                                    <th class="text-center">สถานะระบบ</th>
                                    <th class="text-end">การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $i => $u)
                                    @php
                                        $rowNo = method_exists($users, 'firstItem') ? $users->firstItem() + $i : $loop->iteration;
                                        $unit = $u->serviceUnits()->wherePivot('is_primary', true)->first() ?? $u->serviceUnits()->first();
                                        $isActive = (bool) ($u->is_active ?? false);
                                        $purposes = $u->reg_purpose_labels_with_color ?? [];
                                        $hasP = $u->hasPurpose('P');
                                        $hasR = $u->hasPurpose('R');
                                    @endphp
                                    <tr>
                                        <td>{{ $rowNo }}</td>

                                        {{-- ผู้ใช้งาน --}}
                                        <td>
                                            <h6 class="mb-0 truncate-1" title="{{ $u->contact_name ?? '-' }}">{{ $u->contact_name ?? '-' }}</h6>
                                            <small class="text-muted d-block truncate-1" title="{{ $u->email ?? '-' }}">{{ $u->email ?? '-' }}</small>
                                        </td>

                                        {{-- สังกัด / บทบาท --}}
                                        <td class="d-none d-xl-table-cell">
                                            <div class="row align-items-start">
                                                {{-- ถ้ามี purposes ให้แสดงไอคอนเรียงต่อกัน --}}
                                                @if (!empty($purposes))
                                                    <div class="col-auto pe-0 d-flex align-items-start gap-1 flex-wrap">
                                                        @foreach ($purposes as $pp)
                                                            @php
                                                                // กำหนด icon และสีพื้นหลังตามประเภท
                                                                $label = $pp['label'] ?? '';
                                                                [$icon, $bgClass] = match (true) {
                                                                    str_contains($label, 'สคร') => ['ph-map-pin-area', 'btn-light-success'],
                                                                    str_contains($label, 'สสจ') => ['ph-map-pin', 'btn-light-warning'],
                                                                    str_contains($label, 'หน่วยบริการ') => ['ph-hospital', 'btn-light-primary'],
                                                                    default => ['ph-user', 'btn-light-secondary'],
                                                                };
                                                            @endphp
                                                            <div class="avtar avtar-s {{ $bgClass }}">
                                                                <i class="ph-duotone {{ $icon }} f-18"></i>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                {{-- เนื้อหาทางขวา --}}
                                                <div class="col">
                                                    {{-- วัตถุประสงค์ที่ลงทะเบียน --}}
                                                    <div class="mb-1 d-flex flex-wrap gap-1">
                                                        @forelse($purposes as $pp)
                                                            <span class="badge {{ $pp['class'] }}">{{ $pp['label'] }}</span>
                                                        @empty
                                                            <span class="text-muted small">-</span>
                                                        @endforelse
                                                    </div>

                                                    {{-- สังกัดหน่วยงาน --}}
                                                    @if (!empty($unit?->org_affiliation))
                                                        <div class="small text-muted truncate-1" title="{{ $unit->org_affiliation }}">
                                                            {{ $unit->org_affiliation }}
                                                        </div>
                                                    @endif

                                                    {{-- จังหวัด/สคร. --}}
                                                    @if ($hasP && $u->superviseProvince)
                                                        <div class="small text-muted truncate-1" title="จังหวัดที่สังกัด: {{ $u->superviseProvince->title }}">
                                                            จังหวัดที่สังกัด: {{ $u->superviseProvince->title }}
                                                        </div>
                                                    @endif

                                                    @if ($hasR && $u->superviseRegion)
                                                        <div class="small text-muted truncate-1" title="สคร.: {{ $u->superviseRegion->short_title }}">
                                                            สคร.: {{ $u->superviseRegion->short_title }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>


                                        <td class="d-none d-md-table-cell">{{ $u->username ?? '-' }}</td>
                                        <td class="d-none d-lg-table-cell">{{ $u->contact_mobile ?? '-' }}</td>

                                        {{-- ✅ เปลี่ยนเป็นสิทธิ์การใช้งาน --}}
                                        <td class="text-center d-none d-lg-table-cell">
                                            <span class="badge {{ $u->role_badge_class }}">
                                                {{ $u->role->name ?? '-' }}
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
                                            <form action="{{ route('backend.impersonate.start', $u->id) }}" method="POST" class="d-inline js-impersonate-form" data-title="{{ $u->contact_name ?? ($u->username ?? 'ผู้ใช้') }}">
                                                @csrf
                                                <button type="submit" class="avtar avtar-xs btn-link-danger" data-bs-toggle="tooltip" data-bs-title="จำลองผู้ใช้">
                                                    <i class="ti ti-user-exclamation f-20"></i>
                                                </button>
                                            </form>

                                            <a href="{{ route('backend.user.edit', $u) }}" class="avtar avtar-xs btn-link-secondary" data-bs-toggle="tooltip" data-bs-title="แก้ไข">
                                                <i class="ti ti-edit f-20"></i>
                                            </a>

                                            <form action="{{ route('backend.user.destroy', $u) }}" method="POST" class="d-inline js-delete-form" data-title="{{ $u->contact_name ?? ($u->username ?? 'ผู้ใช้') }}">
                                                @csrf @method('DELETE')
                                                <button class="avtar avtar-xs btn-link-secondary" type="submit" data-bs-toggle="tooltip" data-bs-title="ลบ">
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
            document.addEventListener('DOMContentLoaded', function() {
                const list = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                list.forEach(function(el) {
                    new bootstrap.Tooltip(el);
                });
            });

            document.addEventListener('click', function(e) {
                const t = e.target.closest('[data-bs-toggle="tooltip"]');
                if (t) {
                    const inst = bootstrap.Tooltip.getInstance(t);
                    inst && inst.hide();
                }
            });

            // ✅ SweetAlert2: จำลองผู้ใช้
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
