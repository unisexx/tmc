@props(['user' => null, 'roles' => null])

<div class="card admin-review border-2 border-warning shadow-sm position-relative">
    <div class="position-absolute top-0 start-0 translate-middle-y badge rounded-pill text-bg-warning admin-ribbon ms-3">สำหรับผู้ตรวจสอบ</div>

    <div class="card-header d-flex align-items-center gap-2 py-3 border-0">
        <i class="ti ti-shield-check fs-4 text-warning"></i>
        <h5 class="mb-0">6. การตรวจสอบและอนุมัติ</h5>
    </div>

    <div class="card-body pt-3">
        <div class="alert alert-warning d-flex align-items-start gap-2 py-2 mb-4">
            <i class="ti ti-alert-triangle fs-5 mt-1"></i>
            <div class="small">ส่วนนี้มีผลต่อสิทธิ์การเข้าถึงและสถานะการใช้งานของผู้ใช้ กรุณาตรวจสอบเอกสารและข้อมูลให้ครบถ้วนก่อนอนุมัติ</div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label for="reg_status" class="form-label">สถานะการลงทะเบียน</label>
                @php $status = old('reg_status', $user->reg_status ?? 'รอตรวจสอบ'); @endphp
                <select name="reg_status" id="reg_status" class="form-select form-select-lg admin-input">
                    <option value="รอตรวจสอบ" @selected($status === 'รอตรวจสอบ')>รอตรวจสอบ</option>
                    <option value="อนุมัติ" @selected($status === 'อนุมัติ')>อนุมัติ</option>
                    <option value="ไม่อนุมัติ" @selected($status === 'ไม่อนุมัติ')>ไม่อนุมัติ</option>
                </select>
            </div>

            <div class="col-md-8">
                <label for="reg_review_note" class="form-label">หมายเหตุ/เหตุผลการพิจารณา</label>
                <textarea name="reg_review_note" id="reg_review_note" class="form-control admin-input" rows="3" placeholder="ระบุเหตุผลประกอบการอนุมัติ/ไม่อนุมัติ (บังคับเมื่อเลือก 'ไม่อนุมัติ')">{{ old('reg_review_note', $user->reg_review_note ?? '') }}</textarea>
                @error('reg_review_note')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            @php
                $allRoles =
                    $roles ??
                    Spatie\Permission\Models\Role::query()
                        ->whereRaw('LOWER(name) not like ?', ['%admin%'])
                        ->where('guard_name', 'web')
                        ->orderBy('ordering', 'asc')
                        ->get(['id', 'name']);
                $currentRole = isset($user) ? optional($user->getRoleNames()->first()) : null;
            @endphp

            <div class="col-md-4">
                <label for="role" class="form-label">สิทธิ์การใช้งาน</label>
                <select name="role_id" id="role" class="form-select admin-input" data-placeholder="--- เลือกสิทธิ์การใช้งาน ---">
                    <option value="">--- เลือกสิทธิ์การใช้งาน ---</option>
                    @foreach ($allRoles as $role)
                        <option value="{{ $role->id }}" {{ $currentRole === $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
                @error('role_id')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-8 d-flex flex-wrap gap-2 align-items-end">
                <button type="button" class="btn btn-outline-primary" id="btnQuickApprove"><i class="ti ti-check"></i> อนุมัติทันที</button>
                <button type="button" class="btn btn-outline-danger" id="btnQuickReject"><i class="ti ti-x"></i> ไม่อนุมัติและแจ้งเหตุผล</button>
                <div class="ms-auto small text-muted">บันทึกจะมีผลทันทีเมื่อกด “บันทึก” ด้านล่าง</div>
            </div>
        </div>
    </div>
</div>

@push('css')
    <style>
        .admin-review {
            background: #fff;
            border: 2px solid #FFE08A;
            box-shadow: 0 .25rem .5rem rgba(0, 0, 0, .05);
        }

        .admin-review .card-header {
            background: #fff;
            border-bottom: 1px solid #FFE08A;
        }

        .admin-ribbon {
            background: #FFE08A;
            color: #5c3a00;
            padding: .45rem .75rem;
        }

        .admin-input:focus {
            border-color: #ffc10780 !important;
            box-shadow: 0 0 0 .2rem rgba(255, 193, 7, .2) !important;
        }

        .admin-review .alert-warning {
            --bs-alert-bg: #FFF9DB;
            --bs-alert-border-color: #FFE08A;
            --bs-alert-color: #856404;
            padding: .5rem .75rem;
            margin-bottom: 1rem;
        }

        .admin-review::after {
            content: "ADMIN";
            position: absolute;
            right: 1rem;
            bottom: .5rem;
            font-weight: 800;
            letter-spacing: .2rem;
            font-size: clamp(1.25rem, 3vw, 1.75rem);
            color: rgba(0, 0, 0, .05);
            pointer-events: none;
        }
    </style>
@endpush

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sel = document.getElementById('reg_status');
            const note = document.getElementById('reg_review_note');
            const role = document.getElementById('role');

            document.getElementById('btnQuickApprove')?.addEventListener('click', () => {
                sel.value = 'อนุมัติ';
                role?.focus();
            });
            document.getElementById('btnQuickReject')?.addEventListener('click', () => {
                sel.value = 'ไม่อนุมัติ';
                if (!note.value.trim()) note.focus();
            });

            const form = document.getElementById('appReviewForm');
            if (!form) return;
            form.addEventListener('submit', async (e) => {
                const status = sel?.value || '';
                if (status === 'อนุมัติ' && (!role || !role.value)) {
                    e.preventDefault();
                    await Swal.fire({
                        icon: 'warning',
                        title: 'โปรดเลือกสิทธิ์การใช้งาน',
                        text: 'การอนุมัติผู้ใช้งาน ต้องกำหนดสิทธิ์การใช้งานด้วย',
                        confirmButtonText: 'ตกลง'
                    });
                    role?.focus();
                    return;
                }
                if (status === 'ไม่อนุมัติ' && (!note || !note.value.trim())) {
                    e.preventDefault();
                    await Swal.fire({
                        icon: 'error',
                        title: 'กรุณาระบุเหตุผล',
                        text: 'หากเลือก "ไม่อนุมัติ" จำเป็นต้องกรอกเหตุผลการพิจารณา',
                        confirmButtonText: 'ตกลง'
                    });
                    note?.focus();
                    return;
                }
            });
        });
    </script>
@endpush
