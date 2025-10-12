{{-- resources/views/backend/user/index.blade.php --}}
@extends('layouts.main')

@section('title', 'จัดการผู้ใช้งาน')
@section('breadcrumb-item', 'ผู้ใช้งาน')
@section('breadcrumb-item-active', 'รายการผู้ใช้งาน')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                {{-- <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <h5 class="mb-0">รายการผู้ใช้งาน</h5>
                </div> --}}

                <div class="card-body">
                    <form method="GET" action="{{ route('backend.user.index') }}" class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">

                        {{-- ซ้าย: ช่องกรอก + ตัวเลือก --}}
                        <div class="d-flex flex-wrap align-items-center gap-2">

                            {{-- คำค้น --}}
                            <div class="input-group" style="width: min(400px, 90vw);">
                                <span class="input-group-text">คำค้น</span>
                                <input id="q" type="text" name="q" value="{{ $q }}" class="form-control" placeholder="ชื่อ / อีเมล / Username">
                            </div>

                            {{-- วัตถุประสงค์ (reg_purpose) --}}
                            <div class="input-group" style="width: 300px;">
                                <span class="input-group-text">วัตถุประสงค์</span>
                                <select name="purpose" class="form-select">
                                    <option value="">— ทั้งหมด —</option>
                                    <option value="T" @selected($purpose === 'T')>หน่วยบริการสุขภาพผู้เดินทาง</option>
                                    <option value="P" @selected($purpose === 'P')>ผู้กำกับดูแลระดับจังหวัด (สสจ.)</option>
                                    <option value="R" @selected($purpose === 'R')>ผู้กำกับดูแลระดับเขต (สคร.)</option>
                                </select>
                            </div>

                            {{-- บทบาท (Role) --}}
                            <div class="input-group" style="width: 300px;">
                                <span class="input-group-text">สิทธิ์การใช้งาน</span>
                                <select name="role_id" class="form-select">
                                    <option value="">— ทั้งหมด —</option>
                                    @foreach ($roles as $id => $name)
                                        <option value="{{ $id }}" @selected((string) $id === (string) $roleId)>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button class="btn btn-outline-primary" type="submit">
                                <i class="ph-duotone ph-magnifying-glass"></i> ค้นหา
                            </button>
                        </div>

                        {{-- ขวา: ปุ่มเพิ่มรายการ --}}
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('backend.user.create') }}" class="btn btn-primary">
                                <i class="ti ti-plus"></i> เพิ่มผู้ใช้งาน
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-nowrap">
                                    <th style="width:60px">#</th>
                                    <th>ผู้ใช้งาน</th>
                                    <th class="d-none d-xl-table-cell">สังกัด / บทบาท</th>
                                    <th class="d-none d-md-table-cell">Username</th>
                                    <th class="d-none d-lg-table-cell">โทรศัพท์</th>
                                    <th class="text-center d-none d-lg-table-cell">สิทธิ์การใช้งาน</th>
                                    <th class="text-center">สถานะระบบ</th>
                                    <th style="width:120px">การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $i => $u)
                                    <tr>
                                        @php
                                            // เลขลำดับ
                                            $rowNo = method_exists($users, 'firstItem') ? $users->firstItem() + $i : $loop->iteration;

                                            // หน่วยบริการหลัก (primary -> ตัวแรก)
                                            $unit = $u->serviceUnits->firstWhere('pivot.is_primary', true) ?? $u->serviceUnits->first();

                                            // สิทธิ์/บทบาท/วัตถุประสงค์
                                            $isActive = (bool) ($u->is_active ?? false);
                                            $purposes = $u->reg_purpose_labels_with_color ?? [];
                                            $badgeT = collect($purposes)->firstWhere('label', 'หน่วยบริการสุขภาพผู้เดินทาง');
                                            $otherBadges = collect($purposes)->reject(fn($pp) => ($pp['label'] ?? '') === 'หน่วยบริการสุขภาพผู้เดินทาง');
                                            $hasP = $u->hasPurpose('P');
                                            $hasR = $u->hasPurpose('R');
                                        @endphp

                                        <td>{{ $rowNo }}</td>

                                        {{-- ผู้ใช้งาน --}}
                                        <td>
                                            <h6 class="mb-0 text-truncate" title="{{ $u->contact_name ?? '-' }}">{{ $u->contact_name ?? '-' }}</h6>
                                            <small class="text-muted d-block text-truncate" title="{{ $u->email ?? '-' }}">{{ $u->email ?? '-' }}</small>
                                        </td>

                                        {{-- สังกัด / บทบาท --}}
                                        <td class="d-none d-xl-table-cell">
                                            <div class="row align-items-start g-2">
                                                {{-- ไอคอนตาม purpose --}}
                                                @if (!empty($purposes))
                                                    <div class="col-auto pe-0 d-flex align-items-start gap-1 flex-wrap">
                                                        @foreach ($purposes as $pp)
                                                            @php
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
                                                    {{-- Badge: หน่วยบริการสุขภาพผู้เดินทาง (T) --}}
                                                    @if ($badgeT)
                                                        <div class="mb-1">
                                                            <span class="badge {{ $badgeT['class'] }}">{{ $badgeT['label'] }}</span>
                                                        </div>
                                                    @endif

                                                    {{-- ชื่อหน่วยบริการ + จังหวัด/อำเภอ/ตำบล --}}
                                                    @if ($unit && filled($unit->org_name))
                                                        @php
                                                            $prov = $unit->province->title ?? null;
                                                            $dist = $unit->district->title ?? null;
                                                            $subd = $unit->subdistrict->title ?? null;
                                                            $geo = collect([$prov, $dist, $subd])
                                                                ->filter()
                                                                ->implode(' / ');
                                                        @endphp

                                                        <div class="fw-semibold text-truncate" title="{{ $unit->org_name }}">
                                                            {{ $unit->org_name }}
                                                        </div>

                                                        @if ($geo !== '' || filled($unit->org_postcode))
                                                            <div class="small text-muted text-truncate" title="{{ trim($geo . ' ' . ($unit->org_postcode ? '· ' . $unit->org_postcode : '')) }}">
                                                                {{ $geo }} @if ($unit->org_postcode)
                                                                    · {{ $unit->org_postcode }}
                                                                @endif
                                                            </div>
                                                        @endif
                                                    @endif

                                                    {{-- Badge อื่น ๆ --}}
                                                    <div class="mt-1 d-flex flex-wrap gap-1">
                                                        @foreach ($otherBadges as $pp)
                                                            <span class="badge {{ $pp['class'] }}">{{ $pp['label'] }}</span>
                                                        @endforeach
                                                    </div>

                                                    {{-- จังหวัดที่สังกัด / สคร. --}}
                                                    @if ($hasP && $u->superviseProvince)
                                                        <div class="small text-muted text-truncate" title="จังหวัดที่สังกัด: {{ $u->superviseProvince->title }}">
                                                            จังหวัดที่สังกัด: {{ $u->superviseProvince->title }}
                                                        </div>
                                                    @endif
                                                    @if ($hasR && $u->superviseRegion)
                                                        <div class="small text-muted text-truncate" title="สคร.: {{ $u->superviseRegion->short_title }}">
                                                            สคร.: {{ $u->superviseRegion->short_title }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        <td class="d-none d-md-table-cell">{{ $u->username ?? '-' }}</td>
                                        <td class="d-none d-lg-table-cell">{{ $u->contact_mobile ?? '-' }}</td>

                                        <td class="text-center d-none d-lg-table-cell">
                                            <span class="badge {{ $u->role_badge_class }}">{{ $u->role->name ?? '-' }}</span>
                                        </td>

                                        <td class="text-center">
                                            @if ($isActive)
                                                <i class="ph-duotone ph-check-circle text-primary f-24" data-bs-toggle="tooltip" data-bs-title="Active"></i>
                                            @else
                                                <i class="ph-duotone ph-x-circle text-danger f-24" data-bs-toggle="tooltip" data-bs-title="Inactive"></i>
                                            @endif
                                        </td>

                                        <td class="text-center">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-2" role="group" aria-label="จัดการผู้ใช้">
                                                <form action="{{ route('backend.impersonate.start', $u->id) }}" method="POST" class="d-inline js-impersonate-form" data-title="{{ $u->contact_name ?? ($u->username ?? 'ผู้ใช้') }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-light border" data-bs-toggle="tooltip" data-bs-title="จำลองผู้ใช้">
                                                        <i class="ti ti-user-exclamation me-1"></i> จำลอง
                                                    </button>
                                                </form>

                                                <a href="{{ route('backend.user.edit', $u) }}" class="btn btn-sm btn-light border" data-bs-toggle="tooltip" data-bs-title="แก้ไข">
                                                    <i class="ti ti-edit me-1"></i> แก้ไข
                                                </a>

                                                <form action="{{ route('backend.user.destroy', $u) }}" method="POST" class="d-inline js-delete-form" data-title="{{ $u->contact_name ?? ($u->username ?? 'ผู้ใช้') }}">
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
    <script>
        // Bootstrap Tooltip
        document.addEventListener('DOMContentLoaded', function() {
            const list = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            list.forEach(function(el) {
                new bootstrap.Tooltip(el);
            });
        });

        // ซ่อน tooltip เมื่อคลิก
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
    </script>
@endsection
