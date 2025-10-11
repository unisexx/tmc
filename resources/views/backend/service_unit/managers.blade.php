{{-- resources/views/backend/service_unit/managers.blade.php --}}

@extends('layouts.main')

@section('title', 'ผู้รับผิดชอบหน่วยงาน: ' . $unit->org_name)
@section('breadcrumb-item', 'หน่วยบริการ')
@section('breadcrumb-item-active', 'ผู้รับผิดชอบหน่วยงาน')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between py-3">
            <h5 class="mb-0">
                <i class="ph-duotone ph-users-three me-1"></i>
                ตั้งค่าผู้รับผิดชอบหน่วยงาน
                <span class="text-muted">— {{ $unit->org_name }}</span>
            </h5>
            <div class="d-flex gap-2">
                <a href="{{ route('backend.service-unit.edit', $unit->id) }}" class="btn btn-light">
                    <i class="ph-duotone ph-arrow-left"></i> กลับไปแก้ไขหน่วยบริการ
                </a>
                <a href="{{ route('backend.service-unit.index') }}" class="btn btn-light">
                    <i class="ph-duotone ph-list-bullets"></i> รายการหน่วยบริการ
                </a>
            </div>
        </div>

        <form method="post" action="{{ route('backend.service-unit.managers.update', $unit->id) }}">
            @csrf
            @method('put')

            <div class="card-body pt-3">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <i class="ti ti-alert-circle me-1"></i>
                        พบข้อผิดพลาด {{ $errors->count() }} รายการ โปรดตรวจสอบ
                    </div>
                @endif

                {{-- เพิ่มผู้รับผิดชอบ --}}
                <div class="border rounded p-3 mb-3 bg-body-tertiary">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-10">
                            <label class="form-label required">ค้นหาผู้ใช้</label>
                            <select id="userPicker" class="form-select" data-placeholder="พิมพ์ค้นหา ชื่อหรืออีเมล">
                                <option></option>
                                @foreach ($allUsers as $u)
                                    <option value="{{ $u->id }}" data-cid="{{ $u->contact_cid }}" data-position="{{ $u->contact_position }}" data-mobile="{{ $u->contact_mobile }}" data-role="{{ optional($u->role)->name ?? 'ไม่มีสิทธิ์' }}">
                                        {{ $u->name }} <{{ $u->email }}>
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 text-end">
                            <button type="button" id="btnAddRow" class="btn btn-primary w-100">
                                <i class="ph-duotone ph-plus"></i> เพิ่ม
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ตารางรายการ --}}
                <div class="table-responsive table-fixed">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>ชื่อผู้ใช้</th>
                                <th>เลขบัตรประชาชน</th>
                                <th>ตำแหน่ง</th>
                                <th>โทรศัพท์มือถือ</th>
                                <th>สิทธิ์การใช้งาน</th>
                                <th class="text-center" style="width: 8%">หลัก</th>
                            </tr>
                        </thead>
                        <tbody id="managerRows">
                            @forelse ($currentItems as $i => $row)
                                <tr>
                                    <td>
                                        <input type="hidden" name="managers[{{ $i }}][user_id]" value="{{ $row['user_id'] }}">
                                        <input type="hidden" name="managers[{{ $i }}][role]" value="manager">
                                        <input type="hidden" name="managers[{{ $i }}][start_date]" value="{{ now()->format('Y-m-d') }}">
                                        <input type="hidden" name="managers[{{ $i }}][end_date]" value="">
                                        <div class="fw-semibold">{{ $row['name'] }}</div>
                                        <div class="small text-muted">{{ $row['email'] }}</div>
                                    </td>
                                    <td>{{ $row['cid'] ?? '-' }}</td>
                                    <td>{{ $row['position_name'] ?? '-' }}</td>
                                    <td>{{ $row['mobile'] ?? '-' }}</td>
                                    <td>{{ $row['role_name'] ?? 'manager' }}</td>
                                    <td class="text-center">
                                        <input type="hidden" name="managers[{{ $i }}][is_primary]" value="0">
                                        <input type="checkbox" class="form-check-input set-primary" name="managers[{{ $i }}][is_primary]" value="1" @checked($row['is_primary'])>
                                    </td>
                                </tr>
                            @empty
                                <tr class="emptyRow">
                                    <td colspan="6" class="text-center text-muted py-4">ยังไม่มีผู้รับผิดชอบ</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('backend.service-unit.edit', $unit->id) }}" class="btn btn-light">
                    <i class="ph-duotone ph-arrow-left"></i> ย้อนกลับ
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="ph-duotone ph-device-floppy"></i> บันทึก
                </button>
            </div>
        </form>
    </div>
@endsection

@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        .table-fixed thead th {
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 2
        }

        .select2-container--bootstrap-5 .select2-selection--single {
            min-height: 38px;
            padding: .375rem .75rem
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
            padding-left: 0
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: 38px;
            right: .5rem
        }
    </style>
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
    <script>
        (function() {
            let idx = {{ count($currentItems) }};

            function ensureSinglePrimary(chk) {
                document.querySelectorAll('#managerRows .set-primary').forEach(el => {
                    if (el !== chk) el.checked = false;
                });
            }

            document.addEventListener('change', e => {
                if (e.target.classList.contains('set-primary')) ensureSinglePrimary(e.target);
            });

            const $userPicker = $('#userPicker').select2({
                theme: 'bootstrap-5',
                placeholder: $('#userPicker').data('placeholder') || 'พิมพ์ค้นหา ชื่อหรืออีเมล',
                allowClear: true,
                width: '100%'
            });

            document.getElementById('btnAddRow').addEventListener('click', function() {
                const sel = $userPicker.select2('data');
                if (!sel || !sel.length) return;

                const id = sel[0].id;
                const label = sel[0].text;
                const m = label.match(/^(.*)\s<(.+)>$/);
                const name = m ? m[1] : label;
                const email = m ? m[2] : '';

                const opt = $('#userPicker option[value="' + id + '"]');
                const cid = opt.data('cid') || '-';
                const pos = opt.data('position') || '-';
                const mobile = opt.data('mobile') || '-';
                const roleName = opt.data('role') || 'manager';

                const empty = document.querySelector('#managerRows .emptyRow');
                if (empty) empty.remove();

                const today = new Date().toISOString().split('T')[0];

                const tr = document.createElement('tr');
                tr.innerHTML = `
            <td>
                <input type="hidden" name="managers[${idx}][user_id]" value="${id}">
                <input type="hidden" name="managers[${idx}][role]" value="manager">
                <input type="hidden" name="managers[${idx}][start_date]" value="${today}">
                <input type="hidden" name="managers[${idx}][end_date]" value="">
                <div class="fw-semibold">${name}</div>
                <div class="small text-muted">${email}</div>
            </td>
            <td>${cid}</td>
            <td>${pos}</td>
            <td>${mobile}</td>
            <td>${roleName}</td>
            <td class="text-center">
                <input type="hidden" name="managers[${idx}][is_primary]" value="0">
                <input type="checkbox" class="form-check-input set-primary"
                       name="managers[${idx}][is_primary]" value="1">
            </td>
        `;
                document.getElementById('managerRows').appendChild(tr);

                $userPicker.val(null).trigger('change');
                idx++;
            }, false);
        })();
    </script>
@endpush
