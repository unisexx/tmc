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
    </style>
@endpush

@section('content')
    <div class="card shadow-sm print-area">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">สรุปผลการประเมินตนเองของหน่วยบริการสุขภาพผู้เดินทาง</h5>
            <div class="btn-toolbar gap-2">
                <a href="{{ route('backend.self-assessment-service-unit-level.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left"></i> ย้อนกลับ
                </a>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="ti ti-printer"></i> พิมพ์
                </button>
            </div>
        </div>

        <div class="card-body">
            {{-- บรรทัดหัวแบบฟอร์ม --}}
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="fw-semibold">
                        สังกัด: <span class="text-muted">{{ $row->serviceUnit->org_name ?? '-' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div>ปีงบประมาณ: <span class="text-muted">{{ $yearBE ?? '-' }}</span></div>
                </div>
                <div class="col-md-6">
                    <div>รอบการประเมิน: <span class="text-muted">{{ $row->assess_round ?? '-' }}</span></div>
                </div>
                <div class="col-md-6">
                    <div>ระดับที่ประเมินได้:
                        @php
                            $levelMap = ['basic' => 'ระดับพื้นฐาน', 'medium' => 'ระดับกลาง', 'advanced' => 'ระดับสูง'];
                        @endphp
                        <span class="badge bg-info">{{ $levelMap[$row->level] ?? strtoupper($row->level) }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div>สถานะ:
                        @php $st = $row->approval_status; @endphp
                        <span class="badge {{ $st === 'approved' ? 'bg-success' : ($st === 'rejected' ? 'bg-danger' : 'bg-warning') }}">
                            {{ $st ?? '-' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- ตารางสรุป 3 คอลัมน์ --}}
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

        <div class="card-footer text-end">
            <a href="{{ route('backend.self-assessment-service-unit-level.edit', $row->id) }}" class="btn btn-warning">
                <i class="ti ti-edit"></i> แก้ไขขั้นที่ 1
            </a>
            <a href="{{ route('backend.self-assessment-component.create', $row->id) }}" class="btn btn-primary">
                <i class="ti ti-list-details"></i> ประเมิน 6 องค์ประกอบ
            </a>
        </div>
    </div>
@endsection
