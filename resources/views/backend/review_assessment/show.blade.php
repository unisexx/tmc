@extends('layouts.main')

@push('css')
    <style>
        .table-summary th,
        .table-summary td {
            vertical-align: top !important
        }

        .table-summary ul {
            margin: 0;
            padding-left: 1.25rem
        }

        .print-area {
            background: var(--bs-body-bg)
        }

        @media print {

            .pc-header,
            .pc-sidebar,
            .btn-toolbar,
            .card-footer {
                display: none !important
            }

            .card {
                box-shadow: none !important;
                border: 0 !important
            }

            .print-area {
                margin: 0
            }

            .vr {
                width: 1px !important;
                min-height: 1.25rem !important;
                background-color: #000 !important;
                opacity: .2 !important;
                margin: 0 .5rem !important
            }
        }

        /* ===== สไตล์ฟอร์มผู้พิจารณา (ให้โดดเด่น) ===== */
        .admin-review {
            background: #fff;
            border: 2px solid #FFE08A;
            box-shadow: 0 .25rem .5rem rgba(0, 0, 0, .05)
        }

        .admin-review .card-header {
            background: #fff;
            border-bottom: 1px solid #FFE08A
        }

        .admin-ribbon {
            background: #FFE08A;
            color: #5c3a00;
            padding: .45rem .75rem
        }

        .admin-input:focus {
            border-color: #ffc10780 !important;
            box-shadow: 0 0 0 .2rem rgba(255, 193, 7, .2) !important
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

@section('content')
    {{-- ===== Card สรุปผลการประเมิน (เดิม) ===== --}}
    <div class="card shadow-sm print-area">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">สรุปผลการประเมินตนเองของหน่วยบริการสุขภาพผู้เดินทาง</h5>
        </div>

        <div class="card-body">
            {{-- partial สรุปหัวกระดาษ --}}
            @include('backend.self_assessment_service_unit_level._summary', [
                'row' => $row,
                'yearBE' => $yearBE,
                'components' => $components,
                'suggestions' => $suggestions ?? collect(),
            ])

            {{-- ตารางองค์ประกอบ 1–6 + ข้อเสนอ/แผนพัฒนา
                 หมายเหตุ: ปุ่ม "ดาวน์โหลดผลการประเมิน" ถูกย้ายไปแสดงหลังหัวข้อ 2 ใน partial นี้แล้ว --}}
            @include('backend.self_assessment_service_unit_level._summary_table', [
                'components' => $components,
                'form' => $form,
                'row' => $row,
                'suggestions' => $suggestions ?? collect(),
            ])
        </div>

        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('backend.review-assessment.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left"></i> กลับรายการ
            </a>
        </div>
    </div>

    {{-- ===== Card ฟอร์มอัปเดตสถานะ (แยกใบ/โดดเด่น) ===== --}}
    <div class="card admin-review border-2 border-warning shadow-sm position-relative mt-3">
        <div class="position-absolute top-0 start-0 translate-middle-y badge rounded-pill text-bg-warning admin-ribbon ms-3">
            สำหรับผู้พิจารณา
        </div>

        <div class="card-header d-flex align-items-center gap-2 py-3 border-0">
            <i class="ti ti-shield-check fs-4 text-warning"></i>
            <h5 class="mb-0">การพิจารณาและอนุมัติผลการประเมิน</h5>
        </div>

        <div class="card-body pt-3">
            <div class="alert alert-warning d-flex align-items-start gap-2 py-2 mb-4">
                <i class="ti ti-alert-triangle fs-5 mt-1"></i>
                <div class="small">
                    การเปลี่ยนสถานะมีผลต่อกระบวนการประเมิน โปรดตรวจสอบข้อมูลให้ครบถ้วนก่อนดำเนินการ
                </div>
            </div>

            <form method="post" action="{{ route('backend.review-assessment.status', $row->id) }}" class="row g-3" id="statusForm">
                @csrf
                @method('PUT')

                <div class="col-12">
                    <span class="me-2 text-muted">สถานะปัจจุบัน:</span>
                    <x-approval-badge :status="$row->approval_status ?? 'pending'" />
                </div>

                <div class="col-12">
                    <label class="form-label">หมายเหตุ/ข้อเสนอแนะ (กรณีส่งกลับ/ไม่อนุมัติ)</label>
                    <textarea name="remark" id="remark" class="form-control admin-input" rows="3">{{ old('remark', $row->approval_remark) }}</textarea>
                    @error('remark')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                    <div class="invalid-feedback d-block" id="remarkFeedback" style="display:none;"></div>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2">
                    {{-- ปุ่มลัดกำหนดค่า --}}
                    <button type="button" class="btn btn-outline-primary" id="btnQuickReview">
                        <i class="ti ti-eye-check"></i> ตั้งเป็นกำลังตรวจ
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="btnQuickNeedFix">
                        <i class="ti ti-arrow-back-up"></i> ตั้งเป็นส่งกลับแก้ไข
                    </button>
                    <button type="button" class="btn btn-outline-success" id="btnQuickApprove">
                        <i class="ti ti-check"></i> ตั้งเป็นอนุมัติ
                    </button>

                    <div class="vr"></div>

                    {{-- ปุ่มยืนยันดำเนินการจริง --}}
                    <button class="btn btn-warning" name="action" value="review">กำลังตรวจ</button>
                    <button class="btn btn-info" name="action" value="return" data-requires-remark="1">ส่งกลับแก้ไข</button>
                    <button class="btn btn-success" name="action" value="approve">อนุมัติ</button>
                    <button class="btn btn-danger" name="action" value="reject" data-requires-remark="1">ไม่อนุมัติ</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('statusForm');
            const remarkEl = document.getElementById('remark');

            // ปุ่มลัด
            document.getElementById('btnQuickReview')?.addEventListener('click', () => {
                remarkEl?.focus();
            });
            document.getElementById('btnQuickNeedFix')?.addEventListener('click', () => {
                if (remarkEl && !remarkEl.value.trim()) remarkEl.focus();
            });
            document.getElementById('btnQuickApprove')?.addEventListener('click', () => {
                // ไม่มีเงื่อนไข remark
            });

            // ตรวจ remark เมื่อกดส่งกลับ/ไม่อนุมัติ
            form?.addEventListener('submit', function(e) {
                const submitter = e.submitter || document.activeElement;
                const action = submitter && submitter.value ? submitter.value : '';
                const remark = remarkEl ? remarkEl.value.trim() : '';

                const needsRemark = (action === 'return' || action === 'reject') ||
                    (submitter && submitter.dataset && submitter.dataset.requiresRemark === '1');

                if (needsRemark && remark === '') {
                    e.preventDefault();
                    if (remarkEl) {
                        remarkEl.classList.add('is-invalid');
                        remarkEl.focus();
                    }
                    const fb = document.getElementById('remarkFeedback');
                    if (fb) {
                        fb.textContent = 'กรุณากรอกหมายเหตุเมื่อทำการส่งกลับแก้ไขหรือไม่อนุมัติ';
                        fb.style.display = 'block';
                    }
                } else {
                    if (remarkEl) remarkEl.classList.remove('is-invalid');
                    const fb = document.getElementById('remarkFeedback');
                    if (fb) fb.style.display = 'none';
                }
            });
        });
    </script>
@endpush
