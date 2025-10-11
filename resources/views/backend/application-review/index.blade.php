{{-- resources/views/backend/application_review/index.blade.php --}}
@extends('layouts.main')

@section('title', 'จัดการผู้ใช้งาน')
@section('breadcrumb-item', 'จัดการผู้ใช้งาน')
@section('breadcrumb-item-active', 'ตรวจสอบใบสมัคร')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">

                <div class="card-body">
                    {{-- Filter Bar --}}
                    <form method="GET" action="{{ route('backend.application-review.index') }}" class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        {{-- ซ้าย: ช่องกรอก + ตัวเลือก --}}
                        <div class="d-flex flex-wrap align-items-center gap-2">

                            {{-- คำค้น --}}
                            <div class="input-group" style="width: min(400px, 90vw);">
                                <span class="input-group-text">คำค้น</span>
                                <input id="q" type="text" name="q" value="{{ $q ?? '' }}" class="form-control" placeholder="ชื่อ / อีเมล / Username">
                            </div>

                            {{-- วัตถุประสงค์ --}}
                            <div class="input-group" style="width: 300px;">
                                <span class="input-group-text">วัตถุประสงค์</span>
                                <select name="purpose" class="form-select">
                                    @php $purpose = $purpose ?? ''; @endphp
                                    <option value="">— ทั้งหมด —</option>
                                    <option value="T" @selected($purpose === 'T')>หน่วยบริการสุขภาพผู้เดินทาง</option>
                                    <option value="P" @selected($purpose === 'P')>ผู้กำกับดูแลระดับจังหวัด (สสจ.)</option>
                                    <option value="R" @selected($purpose === 'R')>ผู้กำกับดูแลระดับเขต (สคร.)</option>
                                </select>
                            </div>

                            <button class="btn btn-outline-primary" type="submit">
                                <i class="ph-duotone ph-magnifying-glass"></i> ค้นหา
                            </button>
                        </div>

                        {{-- ขวา: ปุ่มเพิ่มใบสมัคร --}}
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('backend.application-review.create') }}" class="btn btn-primary">
                                <i class="ti ti-plus"></i> เพิ่มใบสมัคร
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
                                    <th class="text-center d-none d-lg-table-cell">สถานะลงทะเบียน</th>
                                    <th class="text-center">สถานะระบบ</th>
                                    <th class="text-end" style="width:120px">การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $i => $u)
                                    @php
                                        $rowNo = method_exists($users, 'firstItem') ? $users->firstItem() + $i : $loop->iteration;
                                        $unit = $u->serviceUnits()->wherePivot('is_primary', true)->first() ?? $u->serviceUnits()->first();
                                        $isActive = (bool) ($u->is_active ?? false);
                                        $purposes = $u->reg_purpose_labels_with_color ?? [];
                                        $badgeT = collect($purposes)->firstWhere('label', 'หน่วยบริการสุขภาพผู้เดินทาง');
                                        $otherBadges = collect($purposes)->reject(fn($pp) => ($pp['label'] ?? '') === 'หน่วยบริการสุขภาพผู้เดินทาง');
                                        $hasP = $u->hasPurpose('P');
                                        $hasR = $u->hasPurpose('R');
                                    @endphp
                                    <tr>
                                        <td>{{ $rowNo }}</td>

                                        {{-- ผู้ใช้งาน --}}
                                        <td>
                                            <h6 class="mb-0 text-truncate" title="{{ $u->contact_name ?? '-' }}">{{ $u->contact_name ?? '-' }}</h6>
                                            <small class="text-muted d-block text-truncate" title="{{ $u->email ?? '-' }}">{{ $u->email ?? '-' }}</small>
                                        </td>

                                        {{-- สังกัด / บทบาท --}}
                                        <td class="d-none d-xl-table-cell">
                                            <div class="row align-items-start g-2">
                                                {{-- ไอคอน purpose --}}
                                                @if (!empty($purposes))
                                                    <div class="col-auto pe-0 d-flex flex-column align-items-center gap-1">
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

                                                <div class="col">
                                                    {{-- Badge T --}}
                                                    @if ($badgeT)
                                                        <div class="mb-1">
                                                            <span class="badge {{ $badgeT['class'] }}">{{ $badgeT['label'] }}</span>
                                                        </div>
                                                    @endif

                                                    {{-- หน่วยบริการ + ที่ตั้ง --}}
                                                    @if ($unit && filled($unit->org_name))
                                                        @php
                                                            $prov = $unit->province->title ?? null;
                                                            $dist = $unit->district->title ?? null;
                                                            $subd = $unit->subdistrict->title ?? null;
                                                            $geo = collect([$prov, $dist, $subd])
                                                                ->filter()
                                                                ->implode(' / ');
                                                        @endphp
                                                        <div class="fw-semibold text-truncate" title="{{ $unit->org_name }}">{{ $unit->org_name }}</div>
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

                                                    {{-- จังหวัด / สคร. ตามวัตถุประสงค์ --}}
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

                                        {{-- สถานะลงทะเบียน --}}
                                        <td class="text-center d-none d-lg-table-cell">
                                            <span class="badge {{ $u->reg_status_badge_class }}">{{ $u->reg_status_text }}</span>
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
                                        <td class="text-end">
                                            <div class="d-inline-flex justify-content-end gap-1">
                                                <form action="{{ route('backend.impersonate.start', $u->id) }}" method="POST" class="d-inline js-impersonate-form" data-title="{{ $u->contact_name ?? ($u->username ?? 'ผู้ใช้') }}">
                                                    @csrf
                                                    <button type="submit" class="avtar avtar-xs btn-link-danger" data-bs-toggle="tooltip" data-bs-title="จำลองผู้ใช้">
                                                        <i class="ti ti-user-exclamation f-20"></i>
                                                    </button>
                                                </form>

                                                <a href="{{ route('backend.application-review.edit', $u) }}" class="avtar avtar-xs btn-link-secondary" data-bs-toggle="tooltip" data-bs-title="แก้ไข">
                                                    <i class="ti ti-edit f-20"></i>
                                                </a>

                                                <form action="{{ route('backend.application-review.destroy', $u) }}" method="POST" class="d-inline js-delete-form" data-title="{{ $u->contact_name ?? ($u->username ?? 'ผู้ใช้') }}">
                                                    @csrf @method('DELETE')
                                                    <button class="avtar avtar-xs btn-link-secondary" type="submit" data-bs-toggle="tooltip" data-bs-title="ลบ">
                                                        <i class="ti ti-trash f-20"></i>
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
            if (!t) return;
            const inst = bootstrap.Tooltip.getInstance(t);
            inst && inst.hide();
        });

        // SweetAlert2: จำลองผู้ใช้
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

        // SweetAlert2: ลบผู้ใช้
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
