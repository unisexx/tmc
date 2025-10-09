{{-- resources/views/backend/role/_form.blade.php --}}
@php
    use App\Support\Permissions;

    /** @var array<int,string> $rolePermissions */
    $rolePermissions = (array) old('permissions', $rolePermissions ?? []);
@endphp

{{-- ====== ฟอร์มข้อมูลสิทธิ์ ====== --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="form-label">ชื่อสิทธิ์การใช้งาน <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $role->name ?? '') }}" placeholder="เช่น: ผู้ดูแลระบบ, เจ้าหน้าที่เขต, เจ้าหน้าที่จังหวัด">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check form-switch ms-lg-4">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" @checked(old('is_active', $role->is_active ?? true))>
            <label class="form-check-label" for="is_active">เปิดการใช้งาน</label>
        </div>
    </div>
</div>

<div class="mb-3">
    <button type="button" class="btn btn-primary btn-sm me-2" id="btn-check-all">
        <i class="ti ti-checks me-1"></i> เลือกทั้งหมด
    </button>
    <button type="button" class="btn btn-outline-danger btn-sm" id="btn-uncheck-all">
        <i class="ti ti-x me-1"></i> ไม่เลือกทั้งหมด
    </button>
</div>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th style="width: 280px;">กลุ่มสิทธิ์</th>
                <th>การเข้าถึง/การกระทำ</th>
                <th style="width: 140px;" class="text-end">เลือกทั้งหมด (กลุ่ม)</th>
            </tr>
        </thead>
        <tbody>
            @foreach (Permissions::MODULES as $module => $actions)
                @php
                    $modulePerms = collect($actions)->map(fn($a) => "{$module}.{$a}")->all();
                    $isModuleAllChecked = count(array_intersect($modulePerms, $rolePermissions)) === count($modulePerms);
                @endphp
                <tr>
                    <td class="fw-medium">
                        {{ Permissions::moduleLabel($module) }}
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach ($actions as $action)
                                @php $perm = "{$module}.{$action}"; @endphp
                                <div class="form-check">
                                    <input class="form-check-input perm-checkbox" type="checkbox" id="perm_{{ $module }}_{{ $action }}" name="permissions[]" value="{{ $perm }}" data-module="{{ $module }}" @checked(in_array($perm, $rolePermissions, true))>
                                    <label class="form-check-label" for="perm_{{ $module }}_{{ $action }}">
                                        {{ Permissions::actionLabel($action) }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </td>
                    <td class="text-end">
                        <div class="form-check d-inline-block">
                            <input class="form-check-input module-toggle" type="checkbox" id="module_{{ $module }}_toggle" data-module="{{ $module }}" @checked($isModuleAllChecked)>
                            <label class="form-check-label" for="module_{{ $module }}_toggle">
                                ทั้งกลุ่ม
                            </label>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- ปุ่มบันทึก --}}
<div class="col-12 d-flex gap-2 justify-content-end pt-2">
    <a href="{{ route('backend.role.index') }}" class="btn btn-light">
        <i class="ti ti-arrow-left"></i> ย้อนกลับ
    </a>
    <button type="submit" class="btn btn-primary">
        <i class="ti ti-device-floppy"></i>
        {{ ($mode ?? 'create') === 'edit' ? 'บันทึกการแก้ไข' : 'บันทึก' }}
    </button>
</div>

@push('js')
    <script>
        (function() {
            const allBoxes = () => document.querySelectorAll('.perm-checkbox');

            document.getElementById('btn-check-all')?.addEventListener('click', () => {
                allBoxes().forEach(el => el.checked = true);
                syncModuleToggles();
            });

            document.getElementById('btn-uncheck-all')?.addEventListener('click', () => {
                allBoxes().forEach(el => el.checked = false);
                syncModuleToggles();
            });

            document.querySelectorAll('.module-toggle').forEach(toggle => {
                toggle.addEventListener('change', (e) => {
                    const module = e.target.dataset.module;
                    document.querySelectorAll(`.perm-checkbox[data-module="${module}"]`)
                        .forEach(cb => cb.checked = e.target.checked);
                    syncModuleToggles();
                });
            });

            document.querySelectorAll('.perm-checkbox').forEach(cb => {
                cb.addEventListener('change', syncModuleToggles);
            });

            function syncModuleToggles() {
                document.querySelectorAll('.module-toggle').forEach(toggle => {
                    const module = toggle.dataset.module;
                    const items = document.querySelectorAll(`.perm-checkbox[data-module="${module}"]`);
                    const allChecked = Array.from(items).every(i => i.checked);
                    toggle.checked = allChecked;
                });
            }
        })();
    </script>
@endpush
