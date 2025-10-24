<table>
    <thead>
        <tr>
            <th>#</th>
            <th>ชื่อหน่วยบริการ</th>
            <th>จังหวัด</th>
            <th>ระดับล่าสุด</th>
            <th>สถานะอนุมัติ</th>
        </tr>
    </thead>
    <tbody>
        @php
            $mapLevel = fn($v) => match (strtolower((string) $v)) {
                'พื้นฐาน', 'ระดับพื้นฐาน', 'basic' => 'ระดับพื้นฐาน',
                'กลาง', 'ระดับกลาง', 'medium' => 'ระดับกลาง',
                'สูง', 'ระดับสูง', 'advanced' => 'ระดับสูง',
                default => '-',
            };
            $latest = function ($su) {
                return optional(optional($su)->assessmentLevels)->last();
            };
        @endphp

        @foreach ($serviceUnits as $i => $su)
            @php
                $row = $latest($su);
                $levelText = $mapLevel(optional($row)->level);
                $approval = strtolower((string) optional($row)->approval_status);
                $approvalText = match ($approval) {
                    'approved' => 'อนุมัติ',
                    'reviewing' => 'อยู่ระหว่างการพิจารณา',
                    'returned' => 'ส่งกลับแก้ไข',
                    'pending', '', null => 'รอดำเนินการ',
                    'rejected' => 'ไม่อนุมัติ',
                    default => '-',
                };
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $su->org_name }}</td>
                <td>{{ optional($su->province)->title ?: '-' }}</td>
                <td>{{ $levelText }}</td>
                <td>{{ $approvalText }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
