{{-- resources/views/backend/self_assessment_service_unit_level/_summary_table.blade.php --}}
@php
    $defaultNames = [
        1 => 'การบริหารจัดการ',
        2 => 'กระบวนงาน',
        3 => 'บุคลากร',
        4 => 'อาคาร สถานที่',
        5 => 'เครื่องมือฯ เอกสารการแพทย์',
        6 => 'ระบบเทคโนโลยี และแลกเปลี่ยนข้อมูล',
    ];
@endphp

@push('css')
    <style>
        /* 1) ล็อกตารางในหน้า */
        .table-summary {
            table-layout: fixed;
            width: 100%;
            border-collapse: collapse;
        }

        /* 2) ยอมตัดคำทุกกรณีใน cell */
        .table-summary th,
        .table-summary td {
            white-space: normal !important;
            word-break: break-word;
            /* ใช้ break-word เป็น base */
            overflow-wrap: anywhere;
            /* ตัดได้ทุกที่เวลาจำเป็น */
            vertical-align: top;
        }

        /* 3) กันคลาสจากที่อื่นมาล็อก nowrap */
        .table-summary .text-nowrap {
            white-space: normal !important;
        }

        /* 4) ตัวห่อข้างในเซลล์: ไม่ให้ขยายเกิน, ยอมตัดคำ, เคารพความกว้างเซลล์ */
        .table-summary .cell-wrap {
            max-width: 100%;
            min-width: 0;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        /* 5) element ลูกทั้งหมดในเซลล์ต้องไม่ดันกรอบ */
        .table-summary td * {
            max-width: 100%;
            min-width: 0;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        /* 6) รายการ bullet ให้คอมแพค ไม่ดันกว้าง */
        .table-summary ul {
            margin: 0;
            padding-left: 1.25rem;
        }

        /* 7) ปริ้นท์ไม่ให้ row หักครึ่ง */
        @media print {
            .table-summary tr {
                page-break-inside: avoid;
            }
        }

        /* 8) จอเล็ก ลดฟอนต์เล็กน้อย */
        @media (max-width: 1366px) {

            .table-summary th,
            .table-summary td {
                font-size: .9375rem;
            }
        }
    </style>
@endpush

<div class="summary-table-wrap"><!-- ไม่มี .table-responsive -->
    <table class="table table-bordered table-summary align-middle">
        <thead class="table-light">
            <tr>
                <th style="width:28%">องค์ประกอบ</th>
                <th style="width:36%">คุณสมบัติ/ศักยภาพที่มี</th>
                <th style="width:36%">ช่องว่างในการพัฒนา</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 1; $i <= 6; $i++)
                @php
                    $cmp = $components[$i] ?? ['name' => $defaultNames[$i] ?? "องค์ประกอบที่ $i", 'has' => [], 'gaps' => []];
                @endphp
                <tr>
                    <td>
                        <div class="cell-wrap fw-semibold">{{ $i }}. {{ $cmp['name'] }}</div>
                    </td>
                    <td>
                        <div class="cell-wrap">
                            @if (count($cmp['has']))
                                <ul class="mb-0">
                                    @foreach ($cmp['has'] as $t)
                                        <li>{{ $t }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="text-muted">- ไม่มีรายการ -</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="cell-wrap">
                            @if (count($cmp['gaps']))
                                <ul class="mb-0">
                                    @foreach ($cmp['gaps'] as $t)
                                        <li>{{ $t }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="text-muted">- ไม่มีช่องว่าง -</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @endfor
        </tbody>
    </table>
</div>

<div class="mt-4">
    <div class="fw-semibold mb-2">2. หน่วยบริการฯ มีข้อเสนอ/แผนพัฒนา</div>
    @if ($form && $form->suggestions->isNotEmpty())
        <ol class="mb-0">
            @foreach ($form->suggestions->sortBy('id') as $sg)
                <li class="mb-1">
                    {{ $sg->text ?? '—' }}
                    @if (!empty($sg->attachment_path))
                        <a class="ms-2 link-primary" target="_blank" href="{{ Storage::disk('public')->url($sg->attachment_path) }}">ไฟล์แนบ</a>
                    @endif
                </li>
            @endforeach
        </ol>
    @else
        <div class="text-muted">- ไม่มีข้อเสนอ/แผนพัฒนา -</div>
    @endif
</div>

{{-- ✅ ปุ่มดาวน์โหลดผลการประเมิน --}}
<div class="mt-4">
    <a href="{{ route('backend.self-assessment-service-unit-level.export-pdf', $row->id) }}" target="_blank" class="btn btn-danger">
        <i class="ph-duotone ph-file-pdf me-1"></i>
        ดาวน์โหลดผลการประเมิน
    </a>
</div>
