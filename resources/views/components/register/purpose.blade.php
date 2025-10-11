@props([
    'user' => null,
    'provinces' => collect(),
    'healthRegions' => collect(),
    'id' => 'reg', // prefix กันชน
])

@php
    $purposeLabels = ['หน่วยบริการสุขภาพผู้เดินทาง', 'ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับจังหวัด (สสจ.)', 'ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับเขต (สคร.)'];
    $purposeCodeToLabel = ['T' => $purposeLabels[0], 'P' => $purposeLabels[1], 'R' => $purposeLabels[2]];
    $selectedPurposes = old('reg_purpose', $user->reg_purpose ?? []);
    if (is_string($selectedPurposes)) {
        $selectedPurposes = json_decode($selectedPurposes, true) ?? explode(',', $selectedPurposes);
    }
    $selectedLabels = collect((array) $selectedPurposes)->map(fn($v) => $purposeCodeToLabel[trim((string) $v)] ?? $v)->all();

    $oldProvince = old('reg_supervise_province_code', $user->reg_supervise_province_code ?? null);
    $oldRegion = old('reg_supervise_region_id', $user->reg_supervise_region_id ?? null);

    $isProv = in_array($purposeLabels[1], $selectedLabels, true);
    $isReg = in_array($purposeLabels[2], $selectedLabels, true);
@endphp

<h5>1. วัตถุประสงค์การลงทะเบียน</h5>

<div class="col-md-12">
    <label class="form-label d-block required" id="field-{{ \Illuminate\Support\Str::slug('reg_purpose') }}">ในฐานะ</label>
    @foreach ($purposeLabels as $i => $option)
        <div class="form-check">
            <input class="form-check-input {{ $id }}-purpose" type="checkbox" name="reg_purpose[]" id="{{ $id }}_purpose_{{ $i }}" value="{{ $option }}" {{ in_array($option, $selectedLabels, true) ? 'checked' : '' }}>
            <label class="form-check-label" for="{{ $id }}_purpose_{{ $i }}">{{ $option }}</label>
        </div>
    @endforeach
    @error('reg_purpose')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<div class="row g-3">
    {{-- เลือกจังหวัด (สสจ.) --}}
    <div class="col-md-6 {{ $isProv ? '' : 'd-none' }}" id="{{ $id }}_wrap_prov">
        <label for="{{ $id }}_prov" class="form-label required">เลือกจังหวัด (สำหรับบทบาทผู้กำกับดูแลระดับจังหวัด - สสจ.)</label>
        <select id="{{ $id }}_prov" name="reg_supervise_province_code" class="form-select" data-placeholder="--- เลือกจังหวัดของท่าน ---">
            <option value="">--- เลือกจังหวัดของท่าน ---</option>
            @foreach ($provinces as $p)
                <option value="{{ $p->CODE }}" @selected((string) $oldProvince === (string) $p->CODE)>{{ $p->TITLE }}</option>
            @endforeach
        </select>
        @error('reg_supervise_province_code')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- เลือกเขตสุขภาพ (สคร.) --}}
    <div class="col-md-6 {{ $isReg ? '' : 'd-none' }}" id="{{ $id }}_wrap_region">
        <label for="{{ $id }}_region" class="form-label required">เลือกเขตสุขภาพ (สำหรับบทบาทผู้กำกับดูแลระดับเขต - สคร.)</label>
        <select id="{{ $id }}_region" name="reg_supervise_region_id" class="form-select" data-placeholder="--- เลือกเขตสุขภาพ ---">
            <option value="">--- เลือกเขตสุขภาพ ---</option>
            @foreach ($healthRegions as $r)
                <option value="{{ $r->id }}" @selected((string) $oldRegion === (string) $r->id)>{{ $r->short_title ? $r->short_title . ' - ' : '' }}{{ $r->title }}</option>
            @endforeach
        </select>
        @error('reg_supervise_region_id')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const id = @json($id);
            const cbService = document.getElementById(`${id}_purpose_0`);
            const cbProv = document.getElementById(`${id}_purpose_1`);
            const cbRegion = document.getElementById(`${id}_purpose_2`);
            const wrapProv = document.getElementById(`${id}_wrap_prov`);
            const wrapReg = document.getElementById(`${id}_wrap_region`);
            const form = document.querySelector('form');

            function show(el) {
                el?.classList.remove('d-none');
            }

            function hide(el) {
                el?.classList.add('d-none');
            }

            function toggleExclusive() {
                if (cbProv?.checked) {
                    cbRegion.checked = false;
                    cbRegion.disabled = true;
                    show(wrapProv);
                    hide(wrapReg);
                } else {
                    cbRegion.disabled = false;
                    hide(wrapProv);
                }

                if (cbRegion?.checked) {
                    cbProv.checked = false;
                    cbProv.disabled = true;
                    show(wrapReg);
                    hide(wrapProv);
                } else {
                    cbProv.disabled = false;
                    hide(wrapReg);
                }
            }

            function isSupervisorOnly() {
                const isService = !!cbService?.checked;
                return !isService && (!!cbProv?.checked || !!cbRegion?.checked);
            }

            // แจ้ง parent ว่าจะซ่อน/แสดงส่วนที่ 2
            function dispatchCollapseEvent() {
                document.dispatchEvent(new CustomEvent('register:supervisor-only', {
                    detail: {
                        hideSection2: isSupervisorOnly()
                    }
                }));
            }

            [cbService, cbProv, cbRegion].forEach(el => el?.addEventListener('change', () => {
                toggleExclusive();
                dispatchCollapseEvent();
            }));
            toggleExclusive();
            dispatchCollapseEvent();

            // กันค่าหลุดตอน submit
            form?.addEventListener('submit', () => {
                if (isSupervisorOnly()) {
                    // ถ้าต้องการเคลียร์ค่าใน section2 เพิ่มเติม ทำที่นี่
                }
            });
        });
    </script>
@endpush
