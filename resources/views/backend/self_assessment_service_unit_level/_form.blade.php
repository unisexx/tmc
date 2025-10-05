@php
    $isEdit = isset($mode) && $mode === 'edit' && isset($row);

    // ปี/รอบ: ถ้า edit ใช้ค่าจาก $row, ถ้า create อิงวันปัจจุบัน
    $yearCE = $isEdit ? (int) $row->assess_year : fiscalYearCE();
    $round = $isEdit ? (int) $row->assess_round : fiscalRound();
    $yearBE = $yearCE + 543;
    $roundTxt = fiscalRoundText($round);

    // ค่าเดิมของคำตอบแต่ละข้อ (รองรับ old() จาก validation)
    $oldQ1 = old('q1', $isEdit ? $row->q1 : null);
    $oldQ2 = old('q2', $isEdit ? $row->q2 : null);
    $oldQ31 = old('q31', $isEdit ? $row->q31 : null);
    $oldQ32 = old('q32', $isEdit ? $row->q32 : null);
    $oldQ4 = old('q4', $isEdit ? $row->q4 : null);
    $oldLv = old('level', $isEdit ? $row->level : null);

    // คำนวณว่า section ไหนควรเปิดไว้ตั้งแต่โหลดหน้า
    $showQ2 = $oldQ1 === 'have';
    $showQ31 = $oldQ1 === 'have' && $oldQ2 === 'tm';
    $showQ32 = $oldQ1 === 'have' && $oldQ2 === 'other';
    $showQ4 = $oldQ31 === 'yes';

    $mapLvTxt = ['basic' => 'ระดับพื้นฐาน', 'medium' => 'ระดับกลาง', 'advanced' => 'ระดับสูง'];
    $mapLvBg = ['basic' => 'info', 'medium' => 'warning', 'advanced' => 'danger'];
@endphp

{{-- =========================================
| STEP 1 : ประเมินระดับ (ทำทีละข้อ)
========================================= --}}
<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="ph-duotone ph-clipboard-text fs-4"></i>
        <h5 class="mb-0">ขั้นที่ 1 : พิจารณาสถานะหน่วยบริการ</h5>
    </div>

    <div class="card-body">

        {{-- Error Summary --}}
        @if ($errors->any())
            <div id="formErrorSummary" class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <div class="d-flex align-items-start gap-2">
                    <i class="ph-duotone ph-warning-circle fs-4 mt-1"></i>
                    <div>
                        <strong>กรอกข้อมูลไม่ครบหรือไม่ถูกต้อง {{ $errors->count() }} รายการ</strong>
                        <ul class="mb-0 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="alert alert-secondary mb-4">
            ตอบทีละข้อ ระบบจะเปิดข้อถัดไปให้โดยอัตโนมัติ หรือสรุปผลระดับตามเงื่อนไขที่กำหนด
        </div>

        {{-- FY / รอบ --}}
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">ปีงบประมาณ</label>
                <div class="form-control-plaintext fw-semibold">{{ $yearBE }}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">รอบการประเมิน</label>
                <div class="form-control-plaintext fw-semibold">{{ $roundTxt }}</div>
            </div>
        </div>
        <input type="hidden" name="assess_year" value="{{ $yearCE }}">
        <input type="hidden" name="assess_round" value="{{ $round }}">

        {{-- ===================== ข้อ 1 ===================== --}}
        <div id="secQ1" class="mb-4">
            <label class="form-label fw-semibold">1) มีแพทย์ประจำ/หมุนเวียนหรือไม่?</label>
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <input class="btn-check" type="radio" name="q1" id="q1_have" value="have" {{ $oldQ1 === 'have' ? 'checked' : '' }}>
                    <label class="btn btn-outline-primary btn-lg w-100 py-3 rounded-3" for="q1_have">
                        <i class="ph-duotone ph-stethoscope me-2"></i> ตัวเลือก 1: มีแพทย์ประจำ/หมุนเวียน
                    </label>
                </div>
                <div class="col-12 col-md-6">
                    <input class="btn-check" type="radio" name="q1" id="q1_none" value="none" {{ $oldQ1 === 'none' ? 'checked' : '' }}>
                    <label class="btn btn-outline-secondary btn-lg w-100 py-3 rounded-3" for="q1_none">
                        <i class="ph-duotone ph-x-circle me-2"></i> ตัวเลือก 2: ไม่มีแพทย์
                    </label>
                </div>
            </div>
        </div>

        {{-- ===================== ข้อ 2 ===================== --}}
        <div id="secQ2" class="mb-4 collapse {{ $showQ2 ? 'show' : '' }}">
            <label class="form-label fw-semibold">2) ประเภทของแพทย์</label>
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <input class="btn-check" type="radio" name="q2" id="q2_tm" value="tm" {{ $oldQ2 === 'tm' ? 'checked' : '' }}>
                    <label class="btn btn-outline-primary btn-lg w-100 py-3 rounded-3" for="q2_tm">
                        <i class="ph-duotone ph-airplane-in-flight me-2"></i> ตัวเลือก 1: แพทย์เฉพาะทาง TM
                    </label>
                </div>
                <div class="col-12 col-md-6">
                    <input class="btn-check" type="radio" name="q2" id="q2_other" value="other" {{ $oldQ2 === 'other' ? 'checked' : '' }}>
                    <label class="btn btn-outline-secondary btn-lg w-100 py-3 rounded-3" for="q2_other">
                        <i class="ph-duotone ph-user-circle me-2"></i> ตัวเลือก 2: แพทย์สาขาอื่น
                    </label>
                </div>
            </div>
        </div>

        {{-- ===================== ข้อ 3.1 (ทาง TM) ===================== --}}
        <div id="secQ31" class="mb-4 collapse {{ $showQ31 ? 'show' : '' }}">
            <label class="form-label fw-semibold">3.1) บริการฉีดวัคซีน ให้ยา และหัตถการทางการแพทย์อื่นๆ</label>
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <input class="btn-check" type="radio" name="q31" id="q31_yes" value="yes" {{ $oldQ31 === 'yes' ? 'checked' : '' }}>
                    <label class="btn btn-outline-primary btn-lg w-100 py-3 rounded-3" for="q31_yes">
                        <i class="ph-duotone ph-syringe me-2"></i> ตัวเลือก 1: มี
                    </label>
                </div>
                <div class="col-12 col-md-6">
                    <input class="btn-check" type="radio" name="q31" id="q31_no" value="no" {{ $oldQ31 === 'no' ? 'checked' : '' }}>
                    <label class="btn btn-outline-secondary btn-lg w-100 py-3 rounded-3" for="q31_no">
                        <i class="ph-duotone ph-x-circle me-2"></i> ตัวเลือก 2: ไม่มี
                    </label>
                </div>
            </div>
        </div>

        {{-- ===================== ข้อ 3.2 (ทางสาขาอื่น) ===================== --}}
        <div id="secQ32" class="mb-4 collapse {{ $showQ32 ? 'show' : '' }}">
            <label class="form-label fw-semibold">3.2) บริการฉีดวัคซีน ให้ยา และหัตถการทางการแพทย์อื่นๆ</label>
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <input class="btn-check" type="radio" name="q32" id="q32_yes" value="yes" {{ $oldQ32 === 'yes' ? 'checked' : '' }}>
                    <label class="btn btn-outline-primary btn-lg w-100 py-3 rounded-3" for="q32_yes">
                        <i class="ph-duotone ph-syringe me-2"></i> ตัวเลือก 1: มี
                    </label>
                </div>
                <div class="col-12 col-md-6">
                    <input class="btn-check" type="radio" name="q32" id="q32_no" value="no" {{ $oldQ32 === 'no' ? 'checked' : '' }}>
                    <label class="btn btn-outline-secondary btn-lg w-100 py-3 rounded-3" for="q32_no">
                        <i class="ph-duotone ph-x-circle me-2"></i> ตัวเลือก 2: ไม่มี
                    </label>
                </div>
            </div>
        </div>

        {{-- ===================== ข้อ 4 ===================== --}}
        <div id="secQ4" class="mb-4 collapse {{ $showQ4 ? 'show' : '' }}">
            <label class="form-label fw-semibold">4) ความสามารถในการให้บริการกลุ่มที่มีปัญหา</label>
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <input class="btn-check" type="radio" name="q4" id="q4_can" value="can" {{ $oldQ4 === 'can' ? 'checked' : '' }}>
                    <label class="btn btn-outline-primary btn-lg w-100 py-3 rounded-3" for="q4_can">
                        <i class="ph-duotone ph-thumbs-up me-2"></i> ตัวเลือก 1: สามารถให้บริการได้
                    </label>
                </div>
                <div class="col-12 col-md-6">
                    <input class="btn-check" type="radio" name="q4" id="q4_cannot" value="cannot" {{ $oldQ4 === 'cannot' ? 'checked' : '' }}>
                    <label class="btn btn-outline-secondary btn-lg w-100 py-3 rounded-3" for="q4_cannot">
                        <i class="ph-duotone ph-thumbs-down me-2"></i> ตัวเลือก 2: ไม่สามารถให้บริการได้
                    </label>
                </div>
            </div>
        </div>

        {{-- ===================== สรุปผล & ปุ่มถัดไป ===================== --}}
        <div class="row g-3 align-items-center mt-2">
            <div class="col-lg-8">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted mb-1">ผลการพิจารณาสถานะหน่วยสุขภาพผู้เดินทาง จัดอยู่ในระดับ:</div>
                    <h4 id="levelLabel" class="mb-0">
                        @if ($oldLv)
                            <span class="badge bg-{{ $mapLvBg[$oldLv] ?? 'secondary' }}">{{ $mapLvTxt[$oldLv] ?? '—' }}</span>
                        @else
                            <span class="badge bg-secondary">—</span>
                        @endif
                    </h4>
                    <input type="hidden" name="level" id="levelInput" value="{{ $oldLv }}">
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <button id="btnNext" type="submit" class="btn btn-primary btn-lg px-4 fw-semibold" {{ $oldLv ? '' : 'disabled' }}>
                    ไปขั้นถัดไป ประเมินตนเองตามเกณฑ์องค์ประกอบ 6 ด้าน <i class="ph-duotone ph-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .btn.btn-lg.w-100.py-3 {
            line-height: 1.4;
            min-height: 56px;
        }

        .btn-check:focus+.btn,
        .btn:focus {
            box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .25);
        }
    </style>
@endpush

@push('js')
    <script>
        (function() {
            const secQ2 = document.getElementById('secQ2');
            const secQ31 = document.getElementById('secQ31');
            const secQ32 = document.getElementById('secQ32');
            const secQ4 = document.getElementById('secQ4');

            const levelLabel = document.getElementById('levelLabel');
            const levelInput = document.getElementById('levelInput');
            const btnNext = document.getElementById('btnNext');

            function collapseShow(el) {
                el.classList.add('show');
            }

            function collapseHide(el) {
                el.classList.remove('show');
            }

            function clearRadios(name) {
                document.querySelectorAll(`input[name="${name}"]`).forEach(i => i.checked = false);
            }

            function setResult(levelKey) {
                const map = {
                    basic: ['ระดับพื้นฐาน', 'info'],
                    medium: ['ระดับกลาง', 'warning'],
                    advanced: ['ระดับสูง', 'danger']
                };
                const [txt, theme] = map[levelKey] || ['—', 'secondary'];
                levelInput.value = levelKey || '';
                levelLabel.innerHTML = `<span class="badge bg-${theme}">${txt}</span>`;
                btnNext.disabled = !levelKey;
            }

            function resetResult() {
                levelInput.value = '';
                levelLabel.innerHTML = `<span class="badge bg-secondary">—</span>`;
                btnNext.disabled = true;
            }

            function hideBelow(from) {
                if (from <= 1) {
                    collapseHide(secQ2);
                    clearRadios('q2');
                    collapseHide(secQ31);
                    clearRadios('q31');
                    collapseHide(secQ32);
                    clearRadios('q32');
                    collapseHide(secQ4);
                    clearRadios('q4');
                } else if (from === 2) {
                    collapseHide(secQ31);
                    clearRadios('q31');
                    collapseHide(secQ32);
                    clearRadios('q32');
                    collapseHide(secQ4);
                    clearRadios('q4');
                } else if (from === 31) {
                    collapseHide(secQ4);
                    clearRadios('q4');
                } else if (from === 32) {
                    collapseHide(secQ4);
                    clearRadios('q4');
                }
            }

            // Q1
            document.querySelectorAll('input[name="q1"]').forEach(el => {
                el.addEventListener('change', () => {
                    const v = el.value;
                    resetResult();
                    if (v === 'have') {
                        collapseShow(secQ2);
                        hideBelow(2);
                    }
                    if (v === 'none') {
                        hideBelow(1);
                        setResult('basic');
                    }
                });
            });

            // Q2
            document.querySelectorAll('input[name="q2"]').forEach(el => {
                el.addEventListener('change', () => {
                    const v = el.value;
                    resetResult();
                    hideBelow(2);
                    if (v === 'tm') {
                        collapseShow(secQ31);
                    }
                    if (v === 'other') {
                        collapseShow(secQ32);
                    }
                });
            });

            // Q31
            document.querySelectorAll('input[name="q31"]').forEach(el => {
                el.addEventListener('change', () => {
                    const v = el.value;
                    resetResult();
                    hideBelow(31);
                    if (v === 'yes') {
                        collapseShow(secQ4);
                    }
                    if (v === 'no') {
                        setResult('basic');
                    }
                });
            });

            // Q32
            document.querySelectorAll('input[name="q32"]').forEach(el => {
                el.addEventListener('change', () => {
                    const v = el.value;
                    resetResult();
                    hideBelow(32);
                    if (v === 'yes') {
                        setResult('medium');
                    }
                    if (v === 'no') {
                        setResult('basic');
                    }
                });
            });

            // Q4
            document.querySelectorAll('input[name="q4"]').forEach(el => {
                el.addEventListener('change', () => {
                    const v = el.value;
                    if (v === 'can') setResult('advanced');
                    if (v === 'cannot') setResult('medium');
                });
            });

            // Restore (เปิดส่วนที่เกี่ยว & ตั้ง badge ถ้ามีค่าเดิม)
            function restore() {
                const q1 = document.querySelector('input[name="q1"]:checked')?.value;
                const q2 = document.querySelector('input[name="q2"]:checked')?.value;
                const q31 = document.querySelector('input[name="q31"]:checked')?.value;
                const q32 = document.querySelector('input[name="q32"]:checked')?.value;
                const lv = document.getElementById('levelInput')?.value;

                if (q1 === 'have') collapseShow(secQ2);
                if (q1 === 'have' && q2 === 'tm') collapseShow(secQ31);
                if (q1 === 'have' && q2 === 'other') collapseShow(secQ32);
                if (q31 === 'yes') collapseShow(secQ4);

                if (lv) {
                    const m = {
                        basic: 'ระดับพื้นฐาน',
                        medium: 'ระดับกลาง',
                        advanced: 'ระดับสูง'
                    };
                    const theme = {
                        basic: 'info',
                        medium: 'warning',
                        advanced: 'danger'
                    } [lv] || 'secondary';
                    levelLabel.innerHTML = `<span class="badge bg-${theme}">${m[lv]||'—'}</span>`;
                    btnNext.disabled = false;
                }
            }
            restore();
        })();
    </script>
@endpush
