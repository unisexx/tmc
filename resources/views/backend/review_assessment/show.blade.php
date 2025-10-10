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
    </style>
@endpush

@section('content')
    <div class="card shadow-sm print-area">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">สรุปผลการประเมินตนเองของหน่วยบริการสุขภาพผู้เดินทาง</h5>
        </div>

        <div class="card-body">
            {{-- ใช้ partial เดียวกันกับฝั่งหน่วยบริการ แต่ส่ง suggestions แทน form --}}
            @include('backend.self_assessment_service_unit_level._summary', [
                'row' => $row,
                'yearBE' => $yearBE,
                'components' => $components,
                'suggestions' => $suggestions ?? collect(),
            ])

            {{-- ฟอร์มอัปเดตสถานะ (ใส่ไว้ในหน้าเดียว) --}}
            <hr class="my-4">
            <h6 class="mb-3">อัปเดตสถานะการพิจารณา</h6>
            <form method="post" action="{{ route('backend.review-assessment.status', $row->id) }}" class="row g-2" id="statusForm">
                @csrf
                @method('PUT')

                <div class="col-12">
                    <div class="col-12">
                        <span class="me-2 text-muted">สถานะปัจจุบัน:</span>
                        <x-approval-badge :status="$row->approval_status ?? 'pending'" />
                    </div>
                </div>


                <div class="col-12">
                    <label class="form-label">หมายเหตุ/ข้อเสนอแนะ (กรณีส่งกลับ/ไม่อนุมัติ)</label>
                    <textarea name="remark" id="remark" class="form-control" rows="3">{{ old('remark', $row->approval_remark) }}</textarea>
                    @error('remark')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                    <div class="invalid-feedback d-block" id="remarkFeedback" style="display:none;"></div> {{-- ✅ feedback ฝั่ง client --}}
                </div>

                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="btn btn-warning" name="action" value="review">กำลังตรวจ</button>
                    <button class="btn btn-info" name="action" value="return" data-requires-remark="1">ส่งกลับแก้ไข</button>
                    <button class="btn btn-success" name="action" value="approve">อนุมัติ</button>
                    <button class="btn btn-danger" name="action" value="reject" data-requires-remark="1">ไม่อนุมัติ</button>
                </div>
            </form>
        </div>

        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('backend.review-assessment.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left"></i> กลับรายการ
            </a>
            <a href="{{ route('backend.self-assessment-service-unit-level.export-pdf', $row->id) }}" target="_blank" class="btn btn-danger">
                <i class="ti ti-file-type-pdf"></i> ดาวน์โหลด PDF
            </a>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('statusForm');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                // รองรับทั้ง e.submitter (ใหม่) และ activeElement (เผื่อบางบราวเซอร์/ปลั๊กอิน)
                const submitter = e.submitter || document.activeElement;
                const action = submitter && submitter.value ? submitter.value : '';
                const remarkEl = document.getElementById('remark');
                const remark = remarkEl ? remarkEl.value.trim() : '';

                const needsRemark = (action === 'return' || action === 'reject') ||
                    (submitter && submitter.dataset && submitter.dataset.requiresRemark === '1');

                if (needsRemark && remark === '') {
                    e.preventDefault();

                    // แสดง error แบบ Bootstrap + โฟกัสช่อง
                    if (remarkEl) {
                        remarkEl.classList.add('is-invalid');
                        remarkEl.focus();
                    }
                    const fb = document.getElementById('remarkFeedback');
                    if (fb) {
                        fb.textContent = 'กรุณากรอกหมายเหตุเมื่อทำการส่งกลับแก้ไขหรือไม่อนุมัติ';
                        fb.style.display = 'block';
                    } else {
                        alert('กรุณากรอกหมายเหตุเมื่อทำการส่งกลับแก้ไขหรือไม่อนุมัติ');
                    }
                } else {
                    // เคลียร์สถานะ error ถ้ามี
                    if (remarkEl) remarkEl.classList.remove('is-invalid');
                    const fb = document.getElementById('remarkFeedback');
                    if (fb) fb.style.display = 'none';
                }
            });
        });
    </script>
@endpush
