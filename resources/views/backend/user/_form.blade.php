@php
    // provinces: CODE, TITLE
    $provinces = \Illuminate\Support\Facades\Cache::remember('blade.provinces.all', now()->addDay(), function () {
        return \App\Models\Province::query()
            ->select(['CODE', 'TITLE'])
            ->orderBy('TITLE')
            ->get();
    });

    // health regions: id, code, title, short_title
    $healthRegions = \Illuminate\Support\Facades\Cache::remember('blade.health_regions.all', now()->addDay(), function () {
        return \App\Models\HealthRegion::query()
            ->select(['id', 'code', 'title', 'short_title'])
            ->orderBy('code')
            ->get();
    });
@endphp


<div class="row g-3">

    {{-- ====================== ข้อมูลการลงทะเบียน (TOR) ====================== --}}
    <h5>1. วัตถุประสงค์การลงทะเบียน</h5>

    <div class="col-md-12">
        <label class="form-label d-block">ในฐานะ</label>
        @php
            $purposes = ['หน่วยบริการสุขภาพผู้เดินทาง', 'ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับจังหวัด (สสจ.)', 'ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับเขต (สคร.)'];
            $selectedPurposes = old('reg_purpose', $user->reg_purpose ?? []);
            if (is_string($selectedPurposes)) {
                $selectedPurposes = json_decode($selectedPurposes, true) ?? explode(',', $selectedPurposes);
            }

            $oldProvince = old('reg_supervise_province_code', $user->reg_supervise_province_code ?? null);
            $oldRegion = old('reg_supervise_region_id', $user->reg_supervise_region_id ?? null);
        @endphp

        @foreach ($purposes as $option)
            <div class="form-check">
                <input class="form-check-input reg-purpose" type="checkbox" name="reg_purpose[]" id="reg_purpose_{{ $loop->index }}" value="{{ $option }}" {{ in_array($option, $selectedPurposes) ? 'checked' : '' }}>
                <label class="form-check-label" for="reg_purpose_{{ $loop->index }}">
                    {{ $option }}
                </label>
            </div>
        @endforeach
    </div>

    {{-- ========== สสจ. → เลือกจังหวัด ========== --}}
    <div class="col-md-6 d-none" id="wrap_supervise_province">
        <label for="reg_supervise_province_code" class="form-label">
            เลือกจังหวัด (สำหรับบทบาทผู้กำกับดูแลระดับจังหวัด - สสจ.)
        </label>
        <select id="reg_supervise_province_code" name="reg_supervise_province_code" class="form-select" data-placeholder="--- เลือกจังหวัดของท่าน ---">
            @php
                // ดึงจาก Blade (ถ้ายังไม่ได้ดึงมาก่อน)
                $provinces = $provinces ?? \App\Models\Province::select('CODE', 'TITLE')->orderBy('TITLE')->get();
                $oldProvince = old('reg_supervise_province_code', $user->reg_supervise_province_code ?? null);
            @endphp
            @foreach ($provinces as $p)
                <option value="{{ $p->CODE }}" {{ (string) $oldProvince === (string) $p->CODE ? 'selected' : '' }}>
                    {{ $p->TITLE }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- ========== สคร. → เลือกเขตสุขภาพ ========== --}}
    <div class="col-md-6 d-none" id="wrap_supervise_region">
        <label for="reg_supervise_region_id" class="form-label">
            เลือกเขตสุขภาพ (สำหรับบทบาทผู้กำกับดูแลระดับเขต - สคร.)
        </label>
        <select id="reg_supervise_region_id" name="reg_supervise_region_id" class="form-select" data-placeholder="--- เลือกเขตสุขภาพ ---">
            @php
                $healthRegions = $healthRegions ?? \App\Models\HealthRegion::select('id', 'code', 'title', 'short_title')->orderBy('code')->get();
                $oldRegion = old('reg_supervise_region_id', $user->reg_supervise_region_id ?? null);
            @endphp
            @foreach ($healthRegions as $r)
                <option value="{{ $r->id }}" {{ (string) $oldRegion === (string) $r->id ? 'selected' : '' }}>
                    {{ $r->short_title ? $r->short_title . ' - ' : '' }}{{ $r->title }}
                </option>
            @endforeach
        </select>
    </div>
    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // --- init Choices ปกติ ---
                const provinceChoices = new Choices('#reg_supervise_province_code', {
                    searchEnabled: true,
                    itemSelectText: '',
                    shouldSort: false
                    // อย่าใส่ classNames.containerOuter เป็น string ที่มีช่องว่าง
                });

                const regionChoices = new Choices('#reg_supervise_region_id', {
                    searchEnabled: true,
                    itemSelectText: '',
                    shouldSort: false
                });

                // --- เติมคลาสเสริมให้ container ภายหลัง ---
                // วิธี 1: อิงจาก input id
                document.querySelector('#reg_supervise_province_code')
                    .closest('.choices')
                    ?.classList.add('choices--lightable');

                document.querySelector('#reg_supervise_region_id')
                    .closest('.choices')
                    ?.classList.add('choices--lightable');

                // ===== Checkbox สสจ./สคร. (exclusive) + โชว์/ซ่อน select =====
                const cbProv = document.getElementById('reg_purpose_1'); // สสจ.
                const cbRegion = document.getElementById('reg_purpose_2'); // สคร.
                const wrapProv = document.getElementById('wrap_supervise_province');
                const wrapReg = document.getElementById('wrap_supervise_region');

                function show(el) {
                    el.classList.remove('d-none');
                }

                function hide(el) {
                    el.classList.add('d-none');
                }

                function clearChoices(instance) {
                    // ล้างค่าเลือก (ไม่ลบ options)
                    instance.removeActiveItems();
                    instance.setChoiceByValue(''); // เผื่อมี option ว่าง
                }

                function toggleExclusive() {
                    // กันเลือกซ้อน
                    if (cbProv.checked) {
                        cbRegion.checked = false;
                        cbRegion.disabled = true;
                        show(wrapProv);
                        hide(wrapReg);
                        clearChoices(regionChoices);
                    } else {
                        cbRegion.disabled = false;
                        hide(wrapProv);
                        clearChoices(provinceChoices);
                    }

                    if (cbRegion.checked) {
                        cbProv.checked = false;
                        cbProv.disabled = true;
                        show(wrapReg);
                        hide(wrapProv);
                        clearChoices(provinceChoices);
                    } else {
                        cbProv.disabled = false;
                        if (!cbProv.checked) {
                            hide(wrapReg);
                            clearChoices(regionChoices);
                        }
                    }
                }

                toggleExclusive();
                [cbProv, cbRegion].forEach(el => el && el.addEventListener('change', toggleExclusive));
            });
        </script>
    @endpush


    <hr class="my-4">
    <h5>2. ข้อมูลทั่วไปของหน่วยบริการ/หน่วยงาน</h5>

    {{-- ชื่อหน่วยบริการ/หน่วยงาน (ขึ้นแถวใหม่แบบเต็มความกว้าง) --}}
    <div class="col-12">
        <label for="org_name" class="form-label">ชื่อหน่วยบริการ/หน่วยงาน</label>
        <input type="text" name="org_name" id="org_name" value="{{ old('org_name', $user->org_name ?? '') }}" class="form-control">
    </div>


    <div class="col-md-6">
        <label for="org_affiliation" class="form-label">สังกัด</label>
        <select name="org_affiliation" id="org_affiliation" class="form-select">
            <option value="">--- เลือก ---</option>
            @foreach (['สำนักงานปลัดกระทรวงสาธารณสุข', 'กรมควบคุมโรค', 'กรมการแพทย์', 'กรมสุขภาพจิต', 'สภากาชาดไทย', 'สำนักการแพทย์ กรุงเทพมหานคร', 'กระทรวงอุดมศึกษา วิทยาศาสตร์ วิจัยและนวัตกรรม', 'กระทรวงกลาโหม', 'องค์กรปกครองส่วนท้องถิ่น', 'องค์การมหาชน', 'เอกชน', 'อื่น ๆ'] as $option)
                <option value="{{ $option }}" @selected(old('org_affiliation', $user->org_affiliation ?? '') == $option)>
                    {{ $option }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label for="org_tel" class="form-label">หมายเลขโทรศัพท์</label>
        <input type="text" name="org_tel" id="org_tel" value="{{ old('org_tel', $user->org_tel ?? '') }}" class="form-control">
    </div>

    <div class="col-12">
        <label for="org_address" class="form-label">ที่อยู่หน่วยงาน</label>
        <textarea name="org_address" id="org_address" rows="2" class="form-control">{{ old('org_address', $user->org_address ?? '') }}</textarea>
    </div>

    <div class="col-md-6">
        <label for="org_lat" class="form-label">Latitude</label>
        <input type="text" name="org_lat" id="org_lat" value="{{ old('org_lat', $user->org_lat ?? '') }}" class="form-control">
    </div>

    <div class="col-md-6">
        <label for="org_lng" class="form-label">Longitude</label>
        <input type="text" name="org_lng" id="org_lng" value="{{ old('org_lng', $user->org_lng ?? '') }}" class="form-control">
    </div>

    <div class="col-12">
        <label for="org_working_hours" class="form-label">วัน-เวลาทำการ</label>
        <textarea name="org_working_hours" id="org_working_hours" rows="2" class="form-control">{{ old('org_working_hours', $user->org_working_hours ?? '') }}</textarea>
        <small class="text-muted">สามารถระบุเป็นข้อความ หรือ JSON เช่น [{"วัน":"จันทร์","เวลา":"08:30-16:30"}]</small>
    </div>

    <hr class="my-4">

    {{-- ====================== ข้อมูลผู้ใช้งานระบบ ====================== --}}
    <h5>ข้อมูลผู้ใช้งานระบบ</h5>

    <div class="col-md-6">
        <label for="name" class="form-label">ชื่อ - สกุล</label>
        <input type="text" name="name" id="name" value="{{ old('name', $user->name ?? '') }}" class="form-control" required>
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">อีเมล</label>
        <input type="email" name="email" id="email" value="{{ old('email', $user->email ?? '') }}" class="form-control" required>
    </div>

    <div class="col-md-6">
        <label for="username" class="form-label">Username</label>
        <input type="text" name="username" id="username" value="{{ old('username', $user->username ?? '') }}" class="form-control" required>
    </div>

    <div class="col-md-6">
        <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
        <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone ?? '') }}" class="form-control">
    </div>

    <div class="col-md-6">
        <label for="position" class="form-label">ตำแหน่ง</label>
        <input type="text" name="position" id="position" value="{{ old('position', $user->position ?? '') }}" class="form-control">
    </div>

    <div class="col-md-6">
        <label for="password" class="form-label">รหัสผ่าน</label>
        <input type="password" name="password" id="password" class="form-control">
        <small class="text-muted">ถ้าเว้นว่าง ระบบจะสร้างอัตโนมัติ</small>
    </div>

    <hr class="my-4">

    {{-- ====================== ข้อมูลผู้ลงทะเบียน ====================== --}}
    <h5>ข้อมูลผู้ลงทะเบียน</h5>

    <div class="col-md-6">
        <label for="contact_name" class="form-label">ชื่อ-สกุล</label>
        <input type="text" name="contact_name" id="contact_name" value="{{ old('contact_name', $user->contact_name ?? '') }}" class="form-control">
    </div>

    <div class="col-md-6">
        <label for="contact_position" class="form-label">ตำแหน่ง</label>
        <input type="text" name="contact_position" id="contact_position" value="{{ old('contact_position', $user->contact_position ?? '') }}" class="form-control">
    </div>

    <div class="col-md-6">
        <label for="contact_mobile" class="form-label">โทรศัพท์มือถือ</label>
        <input type="text" name="contact_mobile" id="contact_mobile" value="{{ old('contact_mobile', $user->contact_mobile ?? '') }}" class="form-control">
    </div>
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary">
        <i class="ti ti-device-floppy"></i> {{ isset($user) ? 'อัปเดต' : 'บันทึก' }}
    </button>
    <a href="{{ route('backend.user.index') }}" class="btn btn-secondary">ยกเลิก</a>
</div>
