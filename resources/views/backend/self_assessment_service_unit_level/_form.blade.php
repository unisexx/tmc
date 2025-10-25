@php
    $isEdit = isset($mode) && $mode === 'edit' && isset($row);

    // ปี/รอบ
    $yearCE = $isEdit ? (int) $row->assess_year : fiscalYearCE();
    $round = $isEdit ? (int) $row->assess_round : fiscalRound();
    $yearBE = $yearCE + 543;
    $roundTxt = fiscalRoundText($round);

    // ค่าเดิมของคำตอบ
    $oldQ1 = old('q1', $isEdit ? $row->q1 : null);
    $oldQ2 = old('q2', $isEdit ? $row->q2 : null);
    $oldQ31 = old('q31', $isEdit ? $row->q31 : null);
    $oldQ32 = old('q32', $isEdit ? $row->q32 : null);
    $oldQ4 = old('q4', $isEdit ? $row->q4 : null);
    $oldLv = old('level', $isEdit ? $row->level : null);

    // เปิด/ปิด section
    $showQ2 = $oldQ1 === 'have';
    $showQ31 = $oldQ1 === 'have' && $oldQ2 === 'tm';
    $showQ32 = $oldQ1 === 'have' && $oldQ2 === 'other';
    $showQ4 = $oldQ31 === 'yes';
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

        @php
            $currentUnitId = session('current_service_unit_id');

            // 1) ถ้ามีตัวแปร $serviceUnit จาก Controller ใช้เลย
            $currentUnitName =
                isset($serviceUnit) && $serviceUnit?->id === $currentUnitId
                    ? $serviceUnit->org_name ?? null
                    : // 2) ลองหาในหน่วยของผู้ใช้ (กรณีไม่ใช่แอดมิน หรือแอดมินมีผูกบางหน่วย)
                        optional(Auth::user()?->serviceUnits?->firstWhere('id', $currentUnitId))->org_name ??
                        // 3) สุดท้ายค่อย query ตรงตาม id (สำหรับแอดมินที่เลือกหน่วยใดก็ได้)
                        App\Models\ServiceUnit::whereKey($currentUnitId)->value('org_name');
            $currentUnitName = $currentUnitName ?: '-';
        @endphp


        <div class="border rounded p-2 mb-3 bg-body-tertiary d-flex flex-wrap align-items-center gap-3">
            {{-- หน่วยบริการ --}}
            <div class="d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-hospital fs-5"></i>
                <span class="text-muted">หน่วยบริการ</span>
                <span class="fw-semibold">{{ $currentUnitName }}</span>
            </div>

            <div class="vr"></div>

            {{-- ระดับ (อัปเดตแบบไดนามิกผ่าน JS) --}}
            <div class="d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-medal fs-5"></i>
                <span class="text-muted">ระดับ</span>
                <span id="summaryLevelBadge">
                    @if ($oldLv)
                        <x-level-badge :level="$oldLv" class="ms-1" />
                    @else
                        <span class="badge bg-secondary ms-1">—</span>
                    @endif
                </span>
            </div>

            <div class="vr"></div>

            {{-- ปีงบประมาณ --}}
            <div class="d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-calendar-blank fs-5"></i>
                <span class="text-muted">ปีงบประมาณ</span>
                <span class="fw-semibold">{{ $yearBE }}</span>
            </div>

            <div class="vr"></div>

            {{-- รอบ --}}
            <div class="d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-number-circle-one fs-5"></i>
                <span class="text-muted">รอบ</span>
                <span class="fw-semibold">{{ $roundTxt }}</span>
            </div>
        </div>


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

        <div class="alert alert-primary d-flex align-items-center mb-4 shadow-sm border-0 rounded-3">
            <i class="ph-duotone ph-clipboard-text fs-4 me-3"></i>
            <div>
                กรุณาตอบทีละข้อ ระบบจะเปิดข้อถัดไปให้โดยอัตโนมัติ
                <br class="d-sm-none">
                หรือสรุปผลระดับตามเงื่อนไขที่กำหนด
            </div>
        </div>



        {{-- FY / รอบ --}}
        <input type="hidden" name="assess_year" value="{{ $yearCE }}">
        <input type="hidden" name="assess_round" value="{{ $round }}">

        {{-- ===================== ข้อ 1 ===================== --}}
        <div id="secQ1" class="mb-4">
            <label class="form-label fw-semibold d-block">1) มีแพทย์ประจำ/หมุนเวียนหรือไม่?</label>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="q1" id="q1_have" value="have" {{ $oldQ1 === 'have' ? 'checked' : '' }}>
                <label class="form-check-label" for="q1_have">
                    มีแพทย์ประจำ/หมุนเวียน
                </label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="q1" id="q1_none" value="none" {{ $oldQ1 === 'none' ? 'checked' : '' }}>
                <label class="form-check-label" for="q1_none">
                    ไม่มีแพทย์
                </label>
            </div>
        </div>

        {{-- ===================== ข้อ 2 ===================== --}}
        <div id="secQ2" class="mb-4 collapse {{ $showQ2 ? 'show' : '' }}">
            <label class="form-label fw-semibold d-block">2) ประเภทของแพทย์</label>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="q2" id="q2_tm" value="tm" {{ $oldQ2 === 'tm' ? 'checked' : '' }}>
                <label class="form-check-label" for="q2_tm">
                    แพทย์เฉพาะทาง TM
                </label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="q2" id="q2_other" value="other" {{ $oldQ2 === 'other' ? 'checked' : '' }}>
                <label class="form-check-label" for="q2_other">
                    แพทย์สาขาอื่น
                </label>
            </div>
        </div>

        {{-- ===================== ข้อ 3.1 (ทาง TM) ===================== --}}
        <div id="secQ31" class="mb-4 collapse {{ $showQ31 ? 'show' : '' }}">
            <label class="form-label fw-semibold d-block">3.1) บริการฉีดวัคซีน ให้ยา และหัตถการทางการแพทย์อื่นๆ</label>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="q31" id="q31_yes" value="yes" {{ $oldQ31 === 'yes' ? 'checked' : '' }}>
                <label class="form-check-label" for="q31_yes">มี</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="q31" id="q31_no" value="no" {{ $oldQ31 === 'no' ? 'checked' : '' }}>
                <label class="form-check-label" for="q31_no">ไม่มี</label>
            </div>
        </div>


        {{-- ===================== ข้อ 3.2 (ทางสาขาอื่น) ===================== --}}
        <div id="secQ32" class="mb-4 collapse {{ $showQ32 ? 'show' : '' }}">
            <label class="form-label fw-semibold d-block">3.2) บริการฉีดวัคซีน ให้ยา และหัตถการทางการแพทย์อื่นๆ</label>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="q32" id="q32_yes" value="yes" {{ $oldQ32 === 'yes' ? 'checked' : '' }}>
                <label class="form-check-label" for="q32_yes">มี</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="q32" id="q32_no" value="no" {{ $oldQ32 === 'no' ? 'checked' : '' }}>
                <label class="form-check-label" for="q32_no">ไม่มี</label>
            </div>
        </div>


        {{-- ===================== ข้อ 4 ===================== --}}
        <div id="secQ4" class="mb-4 collapse {{ $showQ4 ? 'show' : '' }}">
            <label class="form-label fw-semibold d-block">4) ความสามารถในการให้บริการกลุ่มที่มีปัญหา</label>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="q4" id="q4_can" value="can" {{ $oldQ4 === 'can' ? 'checked' : '' }}>
                <label class="form-check-label" for="q4_can">สามารถให้บริการได้</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="q4" id="q4_cannot" value="cannot" {{ $oldQ4 === 'cannot' ? 'checked' : '' }}>
                <label class="form-check-label" for="q4_cannot">ไม่สามารถให้บริการได้</label>
            </div>
        </div>


        {{-- ===================== สรุปผล & ปุ่มถัดไป ===================== --}}
        <div class="mt-4">
            {{-- กล่องสรุปผล --}}
            <div class="p-3 bg-light rounded border mb-3">
                <div class="text-muted mb-1">
                    ผลการพิจารณาสถานะหน่วยสุขภาพผู้เดินทาง จัดอยู่ในระดับ:
                </div>
                <h4 id="levelLabel" class="mb-0">
                    @if ($oldLv)
                        <span class="badge bg-{{ $levelBadgeMap[$oldLv] ?? 'secondary' }} text-{{ $levelBadgeTextColors[$oldLv] ?? 'white' }}">
                            {{ $levelTextMap[$oldLv] ?? '—' }}
                        </span>
                    @else
                        <span class="badge bg-secondary">—</span>
                    @endif
                </h4>
                <input type="hidden" name="level" id="levelInput" value="{{ $oldLv }}">
            </div>

            {{-- ปุ่มควบคุมการทำงาน --}}
            <div class="d-flex flex-column flex-sm-row justify-content-between gap-2">
                <a href="{{ route('backend.self-assessment-service-unit-level.index') }}" class="btn btn-outline-secondary px-4 fw-semibold">
                    <i class="ph-duotone ph-arrow-left"></i> กลับหน้าแรก
                </a>
                <button id="btnNext" type="submit" class="btn btn-primary px-4 fw-semibold" {{ $oldLv ? '' : 'disabled' }}>
                    ไปขั้นถัดไป ประเมินตนเองตามเกณฑ์องค์ประกอบ 6 ด้าน
                    <i class="ph-duotone ph-arrow-right"></i>
                </button>
            </div>
        </div>

    </div>
</div>



@push('css')
    <style>
        /* ขยาย hit-area ของ radio ให้คลิกง่าย */
        .form-check .form-check-input {
            cursor: pointer;
        }

        .form-check .form-check-label {
            cursor: pointer;
            user-select: none;
        }

        .form-check+.form-check {
            margin-top: .25rem;
        }
    </style>
@endpush


@push('js')
    <script>
        // Single Source of Truth จาก PHP -> JS
        const LEVEL_TEXT = @json(config('tmc.level_text', []));
        const LEVEL_COLORS = @json(config('tmc.level_colors', []));
        const FALLBACK_LEVEL_KEY = 'unassessed';
        const FALLBACK_COLOR = LEVEL_COLORS[FALLBACK_LEVEL_KEY] || '#A8A8A8';
    </script>

    <script>
        function renderLevelBadgeHtml(levelKey) {
            var k = (levelKey && String(levelKey).trim() !== '') ?
                String(levelKey).trim() :
                FALLBACK_LEVEL_KEY;

            var color = LEVEL_COLORS[k] || FALLBACK_COLOR;
            var text = LEVEL_TEXT[k] || '—';

            return '' +
                '<span class="level-badge-map d-inline-flex align-items-center" style="--c: ' + color + ';">' +
                '<span class="dot"></span>' +
                '<span class="name">' + text + '</span>' +
                '</span>';
        }

        function renderEmptyBadgeHtml() {
            return '' +
                '<span class="level-badge-map d-inline-flex align-items-center" style="--c: ' + FALLBACK_COLOR + ';">' +
                '<span class="dot"></span>' +
                '<span class="name">—</span>' +
                '</span>';
        }

        (function() {
            var secQ2 = document.getElementById('secQ2');
            var secQ31 = document.getElementById('secQ31');
            var secQ32 = document.getElementById('secQ32');
            var secQ4 = document.getElementById('secQ4');

            var levelLabel = document.getElementById('levelLabel');
            var levelInput = document.getElementById('levelInput');
            var btnNext = document.getElementById('btnNext');
            var summaryLevelBadge = document.getElementById('summaryLevelBadge');

            function collapseShow(el) {
                if (!el) return;
                el.classList.add('show');
            }

            function collapseHide(el) {
                if (!el) return;
                el.classList.remove('show');
            }

            function clearRadios(name) {
                document.querySelectorAll('input[name="' + name + '"]').forEach(function(i) {
                    i.checked = false;
                });
            }

            // setResult(): ใส่ค่า level ลง hidden + update badge + enable ปุ่ม
            function setResult(levelKey) {
                var key = levelKey || '';
                levelInput.value = key;
                levelLabel.innerHTML = renderLevelBadgeHtml(key);

                if (summaryLevelBadge) {
                    summaryLevelBadge.innerHTML = renderLevelBadgeHtml(key);
                }

                btnNext.disabled = !key;
            }

            // resetResult(): ล้าง level และ badge เป็น "—"
            function resetResult() {
                levelInput.value = '';
                levelLabel.innerHTML = renderEmptyBadgeHtml();

                if (summaryLevelBadge) {
                    summaryLevelBadge.innerHTML = renderEmptyBadgeHtml();
                }

                btnNext.disabled = true;
            }

            /**
             * hideBelow(fromStep)
             * fromStep:
             *   1  = เปลี่ยน q1 -> ล้าง q2,q31,q32,q4
             *   2  = เปลี่ยน q2 -> ล้าง q31,q32,q4
             *   31 = เปลี่ยน q31 -> ล้าง q4
             *   32 = เปลี่ยน q32 -> ล้าง q4
             */
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
                } else if (from === 31 || from === 32) {
                    collapseHide(secQ4);
                    clearRadios('q4');
                }
            }

            // -----------------------------
            // Event listeners
            // -----------------------------

            // Q1
            document.querySelectorAll('input[name="q1"]').forEach(function(el) {
                el.addEventListener('change', function() {
                    var v = el.value;
                    resetResult();

                    if (v === 'have') {
                        collapseShow(secQ2);
                        hideBelow(2);
                    }
                    if (v === 'none') {
                        hideBelow(1);
                        setResult('basic'); // ไม่มีแพทย์ = พื้นฐาน
                    }
                });
            });

            // Q2
            document.querySelectorAll('input[name="q2"]').forEach(function(el) {
                el.addEventListener('change', function() {
                    var v = el.value;
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

            // Q31 (สาย TM)
            document.querySelectorAll('input[name="q31"]').forEach(function(el) {
                el.addEventListener('change', function() {
                    var v = el.value;
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

            // Q32 (สายอื่น)
            document.querySelectorAll('input[name="q32"]').forEach(function(el) {
                el.addEventListener('change', function() {
                    var v = el.value;
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

            // Q4 (ศักยภาพกลุ่มซับซ้อน)
            document.querySelectorAll('input[name="q4"]').forEach(function(el) {
                el.addEventListener('change', function() {
                    var v = el.value;
                    if (v === 'can') {
                        setResult('advanced');
                    }
                    if (v === 'cannot') {
                        setResult('medium');
                    }
                });
            });

            // -----------------------------
            // Restore state ตอนโหลดหน้า
            // -----------------------------
            (function restore() {
                var q1 = document.querySelector('input[name="q1"]:checked');
                var q2 = document.querySelector('input[name="q2"]:checked');
                var q31 = document.querySelector('input[name="q31"]:checked');
                var q32 = document.querySelector('input[name="q32"]:checked');
                var lv = levelInput ? levelInput.value : '';

                if (q1 && q1.value === 'have') {
                    collapseShow(secQ2);

                    if (q2 && q2.value === 'tm') {
                        collapseShow(secQ31);
                    }
                    if (q2 && q2.value === 'other') {
                        collapseShow(secQ32);
                    }

                    if (q31 && q31.value === 'yes') {
                        collapseShow(secQ4);
                    }
                }

                if (lv && lv.trim() !== '') {
                    levelLabel.innerHTML = renderLevelBadgeHtml(lv);

                    if (summaryLevelBadge) {
                        summaryLevelBadge.innerHTML = renderLevelBadgeHtml(lv);
                    }

                    btnNext.disabled = false;
                } else {
                    levelLabel.innerHTML = renderEmptyBadgeHtml();

                    if (summaryLevelBadge) {
                        summaryLevelBadge.innerHTML = renderEmptyBadgeHtml();
                    }

                    btnNext.disabled = true;
                }
            })();
        })();
    </script>
@endpush
