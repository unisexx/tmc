{{-- <x-register.pdpa --}}

@props(['user' => null])

<h5>5. การยืนยันความถูกต้องและข้อกำหนด PDPA</h5>
<div class="border border-success rounded p-3 bg-success-subtle">
    <h6 class="text-dark mb-2"><i class="ti ti-shield-check me-1"></i> ข้อกำหนดการคุ้มครองข้อมูลส่วนบุคคล (PDPA)</h6>
    <p class="mb-2">
        เพื่อความถูกต้องของข้อมูลและการคุ้มครองข้อมูลส่วนบุคคล โปรดอ่าน
        <a href="#" data-bs-toggle="modal" data-bs-target="#pdpaModal" class="fw-bold text-decoration-underline">ประกาศความเป็นส่วนตัว (Privacy Notice)</a>
        และติ๊กยอมรับก่อนบันทึก
    </p>
    <ul class="mb-3 small">
        <li>ข้าพเจ้ายืนยันว่าข้อมูลที่กรอกมีความถูกต้อง ครบถ้วน และเป็นปัจจุบัน</li>
        <li>ยินยอมให้กระทรวงสาธารณสุขเก็บ รวบรวม ใช้ และเปิดเผยข้อมูลตามวัตถุประสงค์ของระบบ</li>
        <li>รับทราบสิทธิในการเข้าถึง แก้ไข ลบ ระงับการใช้ หรือคัดค้าน รวมถึงสิทธิถอนความยินยอม</li>
    </ul>

    <div class="form-check">
        <input class="form-check-input @error('pdpa_accept') is-invalid @enderror" type="checkbox" id="pdpa_accept" name="pdpa_accept" value="1" {{ old('pdpa_accept', !empty($user->pdpa_version) ? 1 : 0) ? 'checked' : '' }}>
        <label class="form-check-label fw-semibold" for="pdpa_accept">
            ข้าพเจ้าได้อ่านและยอมรับข้อกำหนดตาม
            <a href="#" data-bs-toggle="modal" data-bs-target="#pdpaModal">Privacy Notice</a>
        </label>
        @error('pdpa_accept')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>

@push('modal')
    {{-- Modal: Privacy Notice (ประกาศความเป็นส่วนตัวของ สธ.) --}}
    <div class="modal fade" id="pdpaModal" tabindex="-1" aria-labelledby="pdpaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="pdpaModalLabel">ประกาศความเป็นส่วนตัว (Privacy Notice) กระทรวงสาธารณสุข</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">ตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 กระทรวงสาธารณสุขดำเนินการดังนี้:</p>
                    <ol>
                        <li><strong>วัตถุประสงค์</strong> : เก็บ รวบรวม ใช้ และเปิดเผยข้อมูลเพื่อการลงทะเบียน การยืนยันตัวตน การให้สิทธิ์ใช้งาน และการบริหารจัดการระบบหน่วยบริการสุขภาพผู้เดินทาง</li>
                        <li><strong>ข้อมูลที่เก็บ</strong> : ชื่อ-สกุล อีเมล โทรศัพท์ ตำแหน่ง หน่วยงาน ที่อยู่ พิกัด และข้อมูลการใช้งานระบบ</li>
                        <li><strong>ฐานทางกฎหมาย</strong> : การปฏิบัติหน้าที่เพื่อประโยชน์สาธารณะ/อำนาจรัฐ และ/หรือความยินยอมของเจ้าของข้อมูล</li>
                        <li><strong>การเปิดเผย</strong> : เฉพาะหน่วยงานในสังกัดกระทรวงสาธารณสุข หรือผู้ประมวลผลข้อมูลที่ได้รับมอบหมาย โดยมีมาตรการคุ้มครองข้อมูลที่เหมาะสม</li>
                        <li><strong>ระยะเวลาเก็บรักษา</strong> : เท่าที่จำเป็นตามวัตถุประสงค์/กฎหมาย หรือจนกว่าจะสิ้นสุดความจำเป็นในการประมวลผล</li>
                        <li><strong>สิทธิของเจ้าของข้อมูล</strong> : เข้าถึง คัดลอก โอนย้าย แก้ไข ลบ ระงับการใช้ หรือคัดค้าน รวมถึงถอนความยินยอมตามที่กฎหมายกำหนด</li>
                        <li><strong>มาตรการความมั่นคงปลอดภัย</strong> : ใช้มาตรการทางเทคนิคและการบริหารจัดการเพื่อป้องกันการเข้าถึงหรือเปิดเผยโดยมิชอบ</li>
                        <li><strong>ช่องทางติดต่อ</strong> : เจ้าหน้าที่คุ้มครองข้อมูลส่วนบุคคล (DPO) ของกระทรวงสาธารณสุข</li>
                    </ol>
                    <p class="small text-muted mb-0">
                        *อ่านรายละเอียดฉบับเต็มได้ที่เว็บไซต์กระทรวงสาธารณสุข:
                        <a href="https://www.ddc.moph.go.th" target="_blank">https://www.ddc.moph.go.th</a>
                    </p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>
@endpush
