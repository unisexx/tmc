{{-- resources\views\components\service-unit\fields.blade.php --}}

@props(['unit', 'mode' => 'create', 'withAssets' => true])

{{-- โหลด asset ของฟอร์มนี้ (กันซ้ำด้วย pushOnce ในไฟล์ assets) --}}
@if ($withAssets)
    @include('components.service-unit.assets')
@endif

{{-- ====================== Error Summary ====================== --}}
@if ($errors->any())
    <div id="error-summary" class="alert alert-danger" role="alert">
        <div class="d-flex align-items-start gap-2">
            <i class="ti ti-alert-circle fs-4 mt-1"></i>
            <div>
                <strong>กรอกข้อมูลไม่ครบหรือไม่ถูกต้อง {{ $errors->count() }} รายการ</strong>
                <div class="small">โปรดตรวจสอบฟิลด์ที่มีเครื่องหมาย <span class="text-danger">*</span> หรือมีกรอบสีแดง</div>
                <ul class="mt-2 mb-0">
                    @foreach ($errors->toArray() as $field => $messages)
                        <li class="small">
                            {{-- แปลงชื่อ field ที่เป็น array ให้อ่านง่าย --}}
                            <a href="#field-{{ \Illuminate\Support\Str::slug($field) }}" class="text-reset text-decoration-underline">
                                {{ str_replace(['working_hours.*.', 'working_hours.'], 'วัน-เวลาทำการ: ', $field) }}
                            </a>
                            : {{ $messages[0] }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @push('js')
        <script>
            // เลื่อนขึ้นไปดูสรุป error อัตโนมัติ
            document.addEventListener('DOMContentLoaded', () => {
                const box = document.getElementById('error-summary');
                if (box) box.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });
        </script>
    @endpush
@endif

<div class="row g-3">

    {{-- =================== ข้อมูลหน่วยบริการพื้นฐาน =================== --}}
    <div class="col-12">
        <label for="org_name" class="form-label required">ชื่อหน่วยบริการ/หน่วยงาน</label>
        <input type="text" name="org_name" id="org_name" value="{{ old('org_name', $unit->org_name ?? '') }}" class="form-control @error('org_name') is-invalid @enderror" required>
        @error('org_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="org_affiliation" class="form-label required">สังกัด</label>
        <select name="org_affiliation" id="org_affiliation" class="form-select">
            <option value="">--- เลือก ---</option>
            @foreach (['สำนักงานปลัดกระทรวงสาธารณสุข', 'กรมควบคุมโรค', 'กรมการแพทย์', 'กรมสุขภาพจิต', 'สภากาชาดไทย', 'สำนักการแพทย์ กรุงเทพมหานคร', 'กระทรวงอุดมศึกษา วิทยาศาสตร์ วิจัยและนวัตกรรม', 'กระทรวงกลาโหม', 'องค์กรปกครองส่วนท้องถิ่น', 'องค์การมหาชน', 'เอกชน', 'อื่น ๆ'] as $option)
                <option value="{{ $option }}" @selected(old('org_affiliation', $unit->org_affiliation ?? '') == $option)>
                    {{ $option }}
                </option>
            @endforeach
        </select>
        @error('org_affiliation')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6" id="org_affiliation_other_box" style="display: none;">
        <label for="org_affiliation_other" class="form-label required">โปรดระบุ</label>
        <input type="text" name="org_affiliation_other" id="org_affiliation_other" class="form-control" value="{{ old('org_affiliation_other', $unit->org_affiliation_other ?? '') }}">
        @error('org_affiliation_other')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="org_tel" class="form-label">หมายเลขโทรศัพท์</label>
        <input type="text" name="org_tel" id="org_tel" value="{{ old('org_tel', $unit->org_tel ?? ($unit->org_phone ?? '')) }}" class="form-control @error('org_tel') is-invalid @enderror">
        @error('org_tel')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="org_address" class="form-label">ที่อยู่หน่วยบริการ</label>
        <textarea name="org_address" id="org_address" rows="2" class="form-control @error('org_address') is-invalid @enderror" placeholder="พิมพ์ที่อยู่ให้ละเอียด แล้วกด “ค้นหาพิกัดจากที่อยู่”">{{ old('org_address', $unit->org_address ?? '') }}</textarea>
        @error('org_address')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Chain select จังหวัด/อำเภอ/ตำบล/รหัสไปรษณีย์ (ใช้ partial เดิมของคุณ) --}}
    {!! thGeoSelect('org_', [
        'province_code' => old('org_province_code', $unit->org_province_code ?? ''),
        'district_code' => old('org_district_code', $unit->org_district_code ?? ''),
        'subdistrict_code' => old('org_subdistrict_code', $unit->org_subdistrict_code ?? ''),
        'postcode' => old('org_postcode', $unit->org_postcode ?? ''),
        'required' => true,
    ]) !!}

    {{-- =================== แผนที่ (Leaflet) =================== --}}
    <div class="col-12">
        <div class="d-flex align-items-center gap-2 my-2">
            <button type="button" id="btn-geocode" class="btn btn-outline-primary btn-sm">
                ค้นหาพิกัดจากที่อยู่
            </button>
            <button type="button" id="btn-from-subdistrict" class="btn btn-outline-success btn-sm">
                ใช้พิกัดจากตำบล
            </button>
            <button type="button" id="btn-center-marker" class="btn btn-outline-secondary btn-sm">
                จัดกึ่งกลางที่หมุด
            </button>
            <button type="button" id="btn-reset-initial" class="btn btn-outline-danger btn-sm">
                รีเซ็ตจุดเริ่มต้น
            </button>
            <small class="text-muted">หรือคลิกบนแผนที่/ลากหมุดเพื่อกำหนดพิกัดเอง</small>
            <span id="coord-badge" class="badge bg-secondary ms-auto d-none"></span>
        </div>
        <div id="map" style="height: 360px; border-radius: .5rem; overflow: hidden;"></div>
    </div>

    <div class="col-md-3">
        <label for="org_lat" class="form-label">Latitude</label>
        <input type="text" name="org_lat" id="org_lat" value="{{ old('org_lat', $unit->org_lat ?? '') }}" class="form-control @error('org_lat') is-invalid @enderror" placeholder="จะถูกกรอกอัตโนมัติเมื่อเลือกพิกัด">
        @error('org_lat')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-3">
        <label for="org_lng" class="form-label">Longitude</label>
        <input type="text" name="org_lng" id="org_lng" value="{{ old('org_lng', $unit->org_lng ?? '') }}" class="form-control @error('org_lng') is-invalid @enderror" placeholder="จะถูกกรอกอัตโนมัติเมื่อเลือกพิกัด">
        @error('org_lng')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- =================== วัน-เวลาทำการ (ลากเมาส์เลือกช่วงเวลา) =================== --}}
    @php
        $whRaw = old('working_hours_json', $unit->org_working_hours_json ?? []);
        $initWorkingHours = is_string($whRaw) ? $whRaw : json_encode($whRaw, JSON_UNESCAPED_UNICODE);
    @endphp

    <div class="col-12">
        <label class="form-label fw-semibold">วัน-เวลาทำการ</label>
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between py-2">
                <h6 class="mb-0">กำหนดเวลาเปิด-ปิดของแต่ละวัน</h6>
                <small class="text-muted">ลากเมาส์เลือกช่วงเวลาเป็นสีเขียว (เปิดทำการ)</small>
            </div>
            <div class="card-body p-2">
                <input type="hidden" id="working_hours_json" name="working_hours_json" value="{{ $initWorkingHours }}">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0" id="working-grid">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width:150px;">วัน</th>
                                {{-- หัวคอลัมน์เวลา เติมด้วย JS --}}
                            </tr>
                        </thead>
                        <tbody>
                            {{-- แถววัน เติมด้วย JS --}}
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <div class="alert alert-secondary py-2 small mb-2">
                        วิธีใช้: คลิกหรือลากเมาส์บนช่องเวลาเพื่อเลือกเปิดทำการ (สีเขียว) • ดับเบิลคลิกเพื่อเลือก/ยกเลิกทั้งวัน • ปุ่ม “ล้างวันนี้” จะลบเฉพาะวันนั้น
                    </div>
                    <pre class="bg-light border rounded small p-2 mb-0" id="working-hours-preview" style="white-space:pre-wrap;"></pre>
                </div>
            </div>
        </div>
        @error('working_hours_json')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>
</div>
