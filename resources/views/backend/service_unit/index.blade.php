@extends('layouts.main')

@section('title', 'จัดการหน่วยบริการ')
@section('breadcrumb-item', 'หน่วยบริการ')
@section('breadcrumb-item-active', 'รายการหน่วยบริการ')

@section('content')
    <div class="card">
        {{-- <div class="card-header d-flex align-items-center justify-content-between py-3">
            <h5 class="mb-0">รายการหน่วยบริการสุขภาพผู้เดินทาง</h5>
            <a href="{{ route('backend.service-unit.create') }}" class="btn btn-primary">
                <i class="ti ti-plus"></i> เพิ่มหน่วยบริการ
            </a>
        </div> --}}

        <div class="card-body pt-3">

            {{-- ฟอร์มค้นหา --}}
            <form method="GET" action="{{ route('backend.service-unit.index') }}" class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">

                {{-- ฝั่งซ้าย: ช่องค้นหาและตัวกรอง --}}
                <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                    <div class="input-group" style="max-width: 300px;">
                        <span class="input-group-text">คำค้น</span>
                        <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="ชื่อหน่วย / ที่อยู่ / โทรศัพท์">
                    </div>

                    @php
                        $affOptions = config('service_unit.affiliations');
                    @endphp
                    <div class="input-group" style="max-width: 220px;">
                        <span class="input-group-text">สังกัด</span>
                        <select name="affiliation" class="form-select">
                            <option value="">— ทั้งหมด —</option>
                            @foreach ($affOptions as $option)
                                <option value="{{ $option }}" @selected(($affiliation ?? '') === $option)>
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div class="input-group" style="max-width: 220px;">
                        <span class="input-group-text">จังหวัด</span>
                        <select name="province" class="form-select">
                            <option value="">— ทั้งหมด —</option>
                            @foreach ($provinces as $code => $name)
                                <option value="{{ $code }}" @selected($code == $provinceCode)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>


                    {{-- ปุ่มค้นหา อยู่ต่อท้ายสังกัด --}}
                    <button class="btn btn-outline-primary">
                        <i class="ti ti-search"></i> ค้นหา
                    </button>
                </div>

                {{-- ฝั่งขวา: ปุ่มเพิ่ม --}}
                <div>
                    <a href="{{ route('backend.service-unit.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus"></i> เพิ่มหน่วยบริการ
                    </a>
                </div>
            </form>




            {{-- ตาราง --}}
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>ชื่อหน่วยบริการ</th>
                            <th>สังกัด</th>
                            <th>จังหวัด</th>
                            <th>เบอร์โทร</th>
                            <th class="text-center" width="120">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($serviceUnits as $unit)
                            <tr>
                                <td>{{ $loop->iteration + ($serviceUnits->firstItem() - 1) }}</td>
                                <td>
                                    <strong>{{ $unit->org_name }}</strong><br>
                                    <small class="text-muted">{{ $unit->org_address }}</small>
                                </td>
                                <td>{{ $unit->org_affiliation ?: '-' }}</td>
                                <td>{{ $unit->province?->title }}</td>
                                <td>{{ $unit->org_tel ?: '-' }}</td>

                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center gap-2" role="group" aria-label="จัดการหน่วยบริการ">
                                        {{-- ปุ่มผู้รับผิดชอบ --}}
                                        <a href="{{ route('backend.service-unit.managers.edit', $unit->id) }}" class="btn btn-sm btn-light border">
                                            <i class="ph-duotone ph-users-three me-1"></i> ผู้รับผิดชอบ
                                        </a>

                                        {{-- ปุ่มแก้ไข --}}
                                        <a href="{{ route('backend.service-unit.edit', $unit) }}" class="btn btn-sm btn-light border">
                                            <i class="ti ti-edit me-1"></i> แก้ไข
                                        </a>

                                        {{-- ปุ่มลบ --}}
                                        <form action="{{ route('backend.service-unit.destroy', $unit) }}" method="POST" class="d-inline js-delete-form" data-title="{{ $unit->org_name }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border">
                                                <i class="ti ti-trash me-1"></i> ลบ
                                            </button>
                                        </form>
                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">ไม่พบข้อมูลหน่วยบริการ</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $serviceUnits->links() }}
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

        // 🗑️ SweetAlert2: ยืนยัน "ลบผู้ใช้"
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (!form.classList.contains('js-delete-form')) return;
            e.preventDefault();
            const title = form.dataset.title || 'หน่วยบริการนี้';
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
