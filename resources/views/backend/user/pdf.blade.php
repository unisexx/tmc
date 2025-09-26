<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ใบลงทะเบียนผู้ใช้งาน</title>
    <style>
        body {
            font-family: "TH Sarabun New", DejaVu Sans, sans-serif;
            font-size: 16px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        ul {
            margin: 0;
            padding: 0 20px;
        }
    </style>
</head>

<body>
    <h2>ใบลงทะเบียนผู้ใช้งานระบบ</h2>

    <h4>1. วัตถุประสงค์การลงทะเบียน</h4>
    <p>{{ $u->reg_purpose ?? '-' }}</p>

    <h4>2. ข้อมูลหน่วยบริการ/หน่วยงาน</h4>
    <ul>
        <li>ชื่อหน่วยบริการ: {{ $u->org_name }}</li>
        <li>สังกัด: {{ $u->org_affiliation }}</li>
        <li>ที่อยู่: {{ $u->org_address }}</li>
        <li>เบอร์โทร: {{ $u->org_tel }}</li>
        <li>พิกัด: {{ $u->org_lat }}, {{ $u->org_lng }}</li>
        <li>วันเวลาทำการ: {{ $u->org_working_hours }}</li>
    </ul>

    <h4>3. ข้อมูลผู้ลงทะเบียน</h4>
    <ul>
        <li>ชื่อ-สกุล: {{ $u->contact_name }}</li>
        <li>ตำแหน่ง: {{ $u->contact_position }}</li>
        <li>โทรศัพท์: {{ $u->contact_mobile }}</li>
        <li>อีเมล: {{ $u->email }}</li>
    </ul>

    <h4>4. การบันทึกข้อมูลล่าสุด</h4>
    <p>{{ $lastSavedAt?->format('d/m/Y H:i') ?? '-' }}</p>
</body>

</html>
