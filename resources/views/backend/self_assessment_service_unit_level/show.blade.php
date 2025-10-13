@extends('layouts.main')

@push('css')
    <style>
        .table-summary th,
        .table-summary td {
            vertical-align: top !important;
        }


        .table-summary ul {
            margin: 0;
            padding-left: 1.25rem;
        }

        .print-area {
            background: var(--bs-body-bg);
        }

        @media print {

            .pc-header,
            .pc-sidebar,
            .btn-toolbar,
            .card-footer {
                display: none !important;
            }

            .card {
                box-shadow: none !important;
                border: 0 !important;
            }

            .print-area {
                margin: 0;
            }
        }

        /* ให้เส้นกั้น .vr พิมพ์ออกมาได้ชัดเจน */
        @media print {
            .vr {
                width: 1px !important;
                min-height: 1.25rem !important;
                background-color: #000 !important;
                opacity: .2 !important;
                margin: 0 .5rem !important;
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

            {{-- ==== แจ้งสถานะการพิจารณา (ใช้ <x-approval-badge> เหมือนหน้า index) ==== --}}
            @php
                $status = $row->approval_status ?? 'pending';

                // mapping สำหรับ alert + ไอคอน (ไว้แค่เรื่องสกินของกล่องแจ้งเตือน)
                $alertCtxMap = [
                    'pending' => 'secondary',
                    'reviewing' => 'warning',
                    'returned' => 'info',
                    'approved' => 'success',
                    'rejected' => 'danger',
                ];
                $iconMap = [
                    'pending' => 'ti-info-circle',
                    'reviewing' => 'ti-hourglass',
                    'returned' => 'ti-arrow-back-up',
                    'approved' => 'ti-badge-check',
                    'rejected' => 'ti-circle-x',
                ];
                $alertCtx = $alertCtxMap[$status] ?? 'secondary';
                $icon = $iconMap[$status] ?? 'ti-info-circle';
            @endphp

            @if (in_array($status, ['returned', 'rejected', 'reviewing', 'approved', 'pending'], true))
                <div class="alert alert-{{ $alertCtx }} d-flex align-items-start" role="alert">
                    <div class="me-2">
                        <i class="ti {{ $icon }} fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">
                            สถานะปัจจุบัน:
                            {{-- ✅ ใช้ component เดียวกับหน้า index --}}
                            <x-approval-badge :status="$status" />
                        </div>

                        {{-- ข้อความอธิบายตามสถานะ --}}
                        @switch($status)
                            @case('returned')
                                <div class="mt-1">
                                    แบบประเมินถูก<strong>ส่งกลับเพื่อแก้ไข</strong> กรุณาตรวจสอบรายการที่ขอให้ปรับปรุงด้านล่าง จากนั้นแก้ไขและส่งใหม่อีกครั้ง
                                </div>
                            @break

                            @case('rejected')
                                <div class="mt-1">
                                    แบบประเมิน<strong>ไม่อนุมัติ</strong> โปรดอ่านเหตุผลประกอบและปรับปรุงก่อนส่งใหม่ในรอบถัดไป
                                </div>
                            @break

                            @case('reviewing')
                                <div class="mt-1">อยู่ระหว่างการตรวจสอบโดยเจ้าหน้าที่ ขณะนี้ไม่สามารถแก้ไขได้</div>
                            @break

                            @case('approved')
                                <div class="mt-1">แบบประเมินได้รับการ<strong>อนุมัติ</strong>แล้ว</div>
                            @break

                            @default
                                <div class="mt-1">แบบประเมินอยู่ในสถานะเริ่มต้น</div>
                        @endswitch

                        {{-- เหตุผล/หมายเหตุจากผู้ตรวจ --}}
                        @if (!empty($row->approval_remark))
                            <div class="mt-2">
                                <div class="small text-muted">หมายเหตุจากผู้พิจารณา</div>
                                <div class="border rounded p-2 bg-body-tertiary">{!! nl2br(e($row->approval_remark)) !!}</div>
                            </div>
                        @endif

                        {{-- ผู้พิจารณาและเวลา (ถ้ามี) --}}
                        @if ($row->approved_at || $row->approver)
                            <div class="mt-2 small text-muted">
                                @if ($row->approver)
                                    ผู้พิจารณา: <span class="fw-semibold">{{ $row->approver->name }}</span>
                                @endif
                                @if ($row->approved_at)
                                    <span class="vr"></span> เวลา: {{ \Carbon\Carbon::parse($row->approved_at)->format('d/m/Y H:i') }}
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- แถบสรุป หน่วยบริการ, ระดับ, ปีงบ, รอบการประเมิน --}}
            @include('backend.self_assessment_service_unit_level._summary', compact('row', 'yearBE', 'form', 'components'))
            {{-- ตารางองค์ประกอบ 1–6 + ข้อเสนอ/แผนพัฒนา (ยกมาเหมือนเดิม) --}}
            @include('backend.self_assessment_service_unit_level._summary_table', ['components' => $components, 'form' => $form])
        </div>

        <div class="card-footer d-flex justify-content-end">
            <div class="btn-toolbar gap-2">
                <a href="{{ route('backend.self-assessment-service-unit-level.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left"></i> ย้อนกลับ
                </a>
            </div>
        </div>
    </div>
@endsection
