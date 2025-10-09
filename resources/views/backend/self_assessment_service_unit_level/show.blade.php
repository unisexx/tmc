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
            @php
                // หน่วยบริการ (ยึดตาม record ถ้ามี, fallback ไปที่หน่วยปัจจุบันใน session)
                $currentUnitId = session('current_service_unit_id');
                $currentUnitName = $row->serviceUnit->org_name ?? (optional(Auth::user()?->serviceUnits?->firstWhere('id', $currentUnitId))->org_name ?? '-');

                // ระดับเดิมจาก record
                $oldLv = $row->level ?? null;

                // ปีงบประมาณ / รอบ (ใช้ค่าที่คุณมีอยู่แล้ว)
                $yearBE = $yearBE ?? ($row->assess_year ?? fiscalYearCE()) + 543;
                $roundTxt = isset($row->assess_round) ? fiscalRoundText((int) $row->assess_round) : '-';
            @endphp

            <div class="border rounded p-2 mb-3 bg-body-tertiary d-flex flex-wrap align-items-center gap-3">
                <!-- หน่วยบริการ -->
                <div class="d-inline-flex align-items-center gap-2">
                    <i class="ph-duotone ph-hospital fs-5"></i>
                    <span class="text-muted">หน่วยบริการ</span>
                    <span class="fw-semibold">{{ $currentUnitName }}</span>
                </div>

                <div class="vr"></div>

                <!-- ระดับ (ใช้ x-level-badge แบบเดียวกับฟอร์ม) -->
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

                <!-- ปีงบประมาณ -->
                <div class="d-inline-flex align-items-center gap-2">
                    <i class="ph-duotone ph-calendar-blank fs-5"></i>
                    <span class="text-muted">ปีงบประมาณ</span>
                    <span class="fw-semibold">{{ $yearBE }}</span>
                </div>

                <div class="vr"></div>

                <!-- รอบ -->
                <div class="d-inline-flex align-items-center gap-2">
                    <i class="ph-duotone ph-number-circle-one fs-5"></i>
                    <span class="text-muted">รอบ</span>
                    <span class="fw-semibold">{{ $roundTxt }}</span>
                </div>
            </div>



            {{-- ตารางสรุป 6 องค์ประกอบ (3 คอลัมน์) --}}
            <div class="table-responsive">
                <table class="table table-bordered table-summary align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 28%">องค์ประกอบ</th>
                            <th style="width: 36%">คุณสมบัติและศักยภาพการให้บริการที่มี<br><small class="text-muted">List ข้อที่มี</small></th>
                            <th style="width: 36%">ช่องว่างในการพัฒนา<br><small class="text-muted">List ข้อที่ไม่มี</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // เผื่อไม่มีข้อมูล ส่งหัวเรื่อง 1..6 ไว้ก่อน
                            $defaultNames = [
                                1 => 'การบริหารจัดการ',
                                2 => 'กระบวนงาน',
                                3 => 'บุคลากร',
                                4 => 'อาคาร สถานที่',
                                5 => 'เครื่องมือ เครื่องใช้ วัสดุเวชภัณฑ์และเอกสารทางการแพทย์',
                                6 => 'ระบบเทคโนโลยี และแลกเปลี่ยนข้อมูล',
                            ];
                        @endphp

                        @for ($i = 1; $i <= 6; $i++)
                            @php
                                $cmp = $components[$i] ?? ['name' => $defaultNames[$i] ?? "องค์ประกอบที่ $i", 'has' => [], 'gaps' => []];
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $i }}. {{ $cmp['name'] }}</div>
                                </td>
                                <td>
                                    @if (count($cmp['has']))
                                        <ul>
                                            @foreach ($cmp['has'] as $txt)
                                                <li>{{ $txt }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-muted">- ไม่มีรายการ -</span>
                                    @endif
                                </td>
                                <td>
                                    @if (count($cmp['gaps']))
                                        <ul>
                                            @foreach ($cmp['gaps'] as $txt)
                                                <li>{{ $txt }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-muted">- ไม่มีช่องว่าง -</span>
                                    @endif
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            {{-- ข้อเสนอเพื่อการพัฒนา / แผนพัฒนา --}}
            <div class="mt-4">
                <div class="fw-semibold mb-2">
                    2. หน่วยบริการฯ มีข้อเสนอเพื่อการพัฒนา และ/หรือ แผนพัฒนา
                </div>

                @if ($form && $form->suggestions->isNotEmpty())
                    <ol class="mb-0">
                        @foreach ($form->suggestions->sortBy('id') as $sg)
                            <li class="mb-1">
                                {{ $sg->text ?? '—' }}
                                @if (!empty($sg->attachment_path))
                                    <a class="ms-2 link-primary" target="_blank" href="{{ Storage::disk('public')->url($sg->attachment_path) }}">
                                        ไฟล์แนบ
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @else
                    <div class="text-muted">- ไม่มีข้อเสนอ/แผนพัฒนา -</div>
                @endif
            </div>
        </div>

        <div class="card-footer d-flex justify-content-end">
            <div class="btn-toolbar gap-2">
                <a href="{{ route('backend.self-assessment-service-unit-level.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left"></i> ย้อนกลับ
                </a>
                <a href="{{ route('backend.self-assessment-service-unit-level.export-pdf', $row->id) }}" target="_blank" class="btn btn-danger">
                    <i class="ti ti-file-type-pdf"></i> ดาวน์โหลด PDF
                </a>
            </div>
        </div>

    </div>
@endsection
