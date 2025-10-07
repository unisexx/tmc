@extends('layouts.pdf')

@section('title', 'สรุปผลการประเมินตนเอง')

@section('content')
    @php
        $levelMap = ['basic' => 'ระดับพื้นฐาน', 'medium' => 'ระดับกลาง', 'advanced' => 'ระดับสูง'];
        $st = $row->approval_status;
        $badgeClass = $st === 'approved' ? 'bg-success' : ($st === 'rejected' ? 'bg-danger' : 'bg-warning');

        // ชื่อหน่วย + ปี/รอบ
        $currentUnitName = $row->serviceUnit->org_name ?? '-';
        $yearBE = $yearBE ?? ($row->assess_year ? $row->assess_year + 543 : null);
        $roundTxt = isset($row->assess_round) ? fiscalRoundText((int) $row->assess_round) : '-';
    @endphp

    {{-- =========================
    | หัวรายงานแบบข้อความ
    |========================= --}}
    @php
        $orgName = $row->serviceUnit->org_name ?? '-';
        $aff = trim($row->serviceUnit->org_affiliation ?? '');
    @endphp

    <h3 class="text-center mb-1">
        สรุปผลการประเมินตนเองของหน่วยบริการสุขภาพผู้เดินทาง
        <span class="dotline ms-1 dotline-lg">{{ $orgName }}</span>
        @if ($aff !== '')
            <br>สังกัด <span class="dotline dotline-lg">{{ $aff }}</span>
        @endif
    </h3>


    <div class="mb-2" style="text-align: right;">
        วันที่ประเมิน <span class="dotline">{{ isset($row->created_at) ? thFullDate($row->created_at) : thFullDate(now()) }}</span>
    </div>


    <p class="mb-3">
        <strong>1.</strong>
        หน่วยบริการสุขภาพผู้เดินทาง <span class="dotline">{{ $row->serviceUnit->org_name ?? '-' }}</span>
        มีผลประเมินอยู่ในระดับ <span class="dotline">{{ $levelMap[$row->level] ?? '-' }}</span>
        โดยมีคุณสมบัติและศักยภาพในการให้บริการ และช่องว่างในการพัฒนาแต่ละองค์ประกอบ ดังนี้
    </p>


    {{-- ตารางสรุป 6 องค์ประกอบ --}}
    <table class="table">
        <thead>
            <tr>
                <th style="width: 28%">องค์ประกอบ</th>
                <th style="width: 36%">คุณสมบัติและศักยภาพที่มี<br><span class="muted">รายการที่มี</span></th>
                <th style="width: 36%">ช่องว่างในการพัฒนา<br><span class="muted">รายการที่ไม่มี</span></th>
            </tr>
        </thead>
        <tbody>
            @php
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
                    <td><strong>{{ $i }}. {{ $cmp['name'] }}</strong></td>
                    <td>
                        @if (count($cmp['has']))
                            <ul>
                                @foreach ($cmp['has'] as $txt)
                                    <li>{!! preg_replace('/^(\d+\))\s*/u', '$1&nbsp;', e($txt)) !!}</li>
                                @endforeach
                            </ul>
                        @else
                            <span class="muted">- ไม่มีรายการ -</span>
                        @endif
                    </td>
                    <td>
                        @if (count($cmp['gaps']))
                            <ul>
                                @foreach ($cmp['gaps'] as $txt)
                                    <li>{!! preg_replace('/^(\d+\))\s*/u', '$1&nbsp;', e($txt)) !!}</li>
                                @endforeach
                            </ul>
                        @else
                            <span class="muted">- ไม่มีช่องว่าง -</span>
                        @endif
                    </td>
                </tr>
            @endfor
        </tbody>
    </table>

    <h4 style="margin-top:12px;">2. ข้อเสนอเพื่อการพัฒนา / แผนพัฒนา</h4>

    @if ($form && $form->suggestions->isNotEmpty())
        <ol style="margin-top:6px;">
            @foreach ($form->suggestions->sortBy('id') as $sg)
                <li style="margin-bottom:4px;">
                    {{ $sg->text ?? '—' }}

                    @if (!empty($sg->attachment_path))
                        @php
                            $fileName = basename($sg->attachment_path);
                            // dompdf ต้องการลิงก์เต็ม เช่น http://tmc.test/attachments/5/download
                            $link = url(route('backend.attachments.download', $sg->id));
                        @endphp

                        <span style="color:#0d6efd;">
                            (ไฟล์แนบ:
                            <a href="{{ $link }}" style="color:#0d6efd; text-decoration: underline;" target="_blank">
                                {{ $fileName }}
                            </a>)
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    @else
        <div class="muted">- ไม่มีข้อเสนอ/แผนพัฒนา -</div>
    @endif



@endsection
