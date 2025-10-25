<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Document')</title>
    <style>
        /* ฟอนต์ไทยสำหรับ dompdf (สำคัญมาก) */
        @font-face {
            font-family: 'Sarabun';
            font-style: normal;
            font-weight: normal;
            src: url('{{ public_path('fonts/sarabun/THSarabunNew.ttf') }}') format('truetype');
        }

        @font-face {
            font-family: 'Sarabun';
            font-style: normal;
            font-weight: bold;
            src: url('{{ public_path('fonts/sarabun/THSarabunNew_Bold.ttf') }}') format('truetype');
        }

        @font-face {
            font-family: 'Sarabun';
            font-style: italic;
            font-weight: normal;
            src: url('{{ public_path('fonts/sarabun/THSarabunNew_Italic.ttf') }}') format('truetype');
        }

        @font-face {
            font-family: 'Sarabun';
            font-style: italic;
            font-weight: bold;
            src: url('{{ public_path('fonts/sarabun/THSarabunNew_Bold_Italic.ttf') }}') format('truetype');
        }

        html,
        body {
            font-family: 'Sarabun';
            font-size: 11pt;
            color: #000;
        }

        /* กรอบหน้า A4 แบบ margin สวย ๆ */
        .page {
            padding: 8mm 15mm 16mm 15mm;
            /* top right bottom left */
        }


        h1,
        h2,
        h3,
        h4,
        h5 {
            margin: 0 0 .6rem 0;
        }

        .muted {
            color: #6c757d;
        }

        .meta-bar {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px 10px;
            background: #f8fafc;
            display: flex;
            flex-wrap: wrap;
            gap: 10px 16px;
            align-items: center;
            margin-bottom: 12px;
        }

        .meta-item {
            display: inline-flex;
            gap: 6px;
            align-items: center;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10pt;
            line-height: 1.2;
        }

        .bg-secondary {
            background: #e9ecef;
        }

        .bg-success {
            background: #d1fae5;
        }

        .bg-warning {
            background: #fff7d6;
        }

        .bg-danger {
            background: #ffe4e6;
        }

        .bg-info {
            background: #e0f2fe;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .table {
            border: 1px solid #555;
            /* ✅ เส้นนอกเข้มขึ้น */
            /* border-radius: 6px; */
            overflow: hidden;
            margin-bottom: 14px;
        }

        .table th,
        .table td {
            border: 1px solid #555;
            /* ✅ เส้นในเข้มขึ้น */
            padding: 6px 8px;
            vertical-align: top;
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .table thead th {
            background: #f0f0f0;
            /* ทำหัวตารางให้ดูตัดกับเส้นเข้ม */
            font-weight: bold;
        }

        /* ลิงก์ใน PDF ไม่ต้องขีดเส้น/เปลี่ยนสี เพื่อลดการขยายเนื้อหา */
        a {
            color: #0d6efd;
            text-decoration: underline;
        }

        /* แก้ list ให้เลข/ข้อความอยู่แนวเดียวกัน */
        ul {
            margin: 0;
            padding-left: 0;
            list-style: none;
        }

        ul li {
            display: flex;
            align-items: flex-start;
            margin-bottom: 2px;
        }

        ul li::before {
            margin-right: 6px;
            font-size: 11pt;
            line-height: 1.2;
        }

        ul li span,
        ul li p {
            display: inline-block;
            margin: 0;
            padding: 0;
        }

        li {
            word-break: break-word;
            overflow-wrap: anywhere;
            line-height: 1.4;
        }



        /* กันล้นหน้า / ตัดหน้า */
        .page-break {
            page-break-after: always;
        }

        /* เส้นบรรทัดแบบฟอร์มราชการ (ดอท/เส้นประใต้ข้อความ) */
        .dotline {
            display: inline-block;
            border-bottom: 1px dotted #000;
            /* dompdf พิมพ์เส้นประได้ดี */
            min-width: 120px;
            /* ปรับความกว้างมาตรฐาน */
            padding: 0 6px;
            /* เว้นขอบเล็กน้อย */
            line-height: 1.2;
            text-align: center;
            /* ให้ข้อความอยู่กลางเส้น */
            vertical-align: baseline;
        }

        /* ยูทิลิตี้ตัวช่วยเว้นระยะนิดหน่อย (เหมือน Bootstrap) */
        .ms-1 {
            margin-left: .25rem;
        }

        .mb-1 {
            margin-bottom: .25rem;
        }

        .mb-2 {
            margin-bottom: .5rem;
        }

        .mb-3 {
            margin-bottom: .75rem;
        }

        .text-center {
            text-align: center;
        }

        /* (ถ้าข้อความยาวมาก ต้องการเส้นยาวขึ้นเฉพาะที่)
   สร้างคลาสเฉพาะจุด เช่น .dotline-lg */
        .dotline-lg {
            min-width: 220px;
        }
    </style>

    @yield('pdf-css') {{-- เผื่อหน้าไหนอยากเพิ่ม CSS เฉพาะ --}}
</head>

<body>
    <div class="page">
        @yield('content')
    </div>
</body>

</html>
