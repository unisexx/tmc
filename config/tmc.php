<?php
// config/tmc.php

return [

    /*
    |--------------------------------------------------------------------------
    | การตั้งค่าเกี่ยวกับการประเมินหน่วยบริการ (Assessment)
    |--------------------------------------------------------------------------
    | ย้ายจาก config/assessment.php มาไว้รวมที่นี่
    | การเรียกใช้งาน:
    |   config('tmc.assessment.level_text.basic')
    |   config('tmc.assessment.approval_badge_class.approved')
     */

    'assessment'   => [

        // แผนที่ข้อความ: ระดับหน่วยบริการ
        'level_text'                => [
            'basic'      => 'ระดับพื้นฐาน',
            'medium'     => 'ระดับกลาง',
            'advanced'   => 'ระดับสูง',
            'unassessed' => 'ยังไม่ได้ประเมิน', // เพิ่ม
        ],

        // คลาสสีพื้นของ badge (Light Able utility classes)
        'level_badge_class'         => [
            'basic'      => 'pink-100',   // ชมพูอ่อน
            'medium'     => 'yellow-100', // เหลืองอ่อน
            'advanced'   => 'green-100',  // เขียวอ่อน
            'unassessed' => 'gray-100',   // เทาอ่อน
        ],

        // สีตัวอักษรบน badge
        'level_badge_text_color'    => [
            'basic'      => 'pink-900',
            'medium'     => 'yellow-900',
            'advanced'   => 'teal-900',
            'unassessed' => 'gray-900',
            'default'    => '#212529',
        ],

        // คลาสเสริมของ border สำหรับ badge
        'level_badge_border_class'  => [
            'basic'      => '',
            'medium'     => '',
            'advanced'   => '',
            'unassessed' => 'border border-gray-400',
        ],

        // แผนที่ข้อความ: สถานะแบบประเมิน (การกรอกฟอร์ม)
        'status_text'               => [
            'draft'     => 'แบบร่าง',
            'completed' => 'ส่งตรวจสอบแล้ว',
        ],

        'status_badge_class'        => [
            'draft'     => 'secondary',
            'completed' => 'secondary',
        ],

        'status_badge_text_color'   => [
            'draft'     => 'text-white',
            'completed' => 'text-white',
            'default'   => '#212529',
        ],

        // แผนที่ข้อความ: สถานะการอนุมัติ
        'approval_text'             => [
            'pending'   => 'รอดำเนินการ',
            'reviewing' => 'อยู่ระหว่างการพิจารณา',
            'returned'  => 'ส่งกลับแก้ไข',
            'approved'  => 'อนุมัติ',
            'rejected'  => 'ไม่อนุมัติ',
        ],

        'approval_badge_class'      => [
            'pending'   => 'gray-100',
            'reviewing' => 'blue-100',
            'returned'  => 'yellow-100',
            'approved'  => 'green-100',
            'rejected'  => 'red-100',
        ],

        'approval_badge_text_color' => [
            'pending'   => 'gray-900',
            'reviewing' => 'blue-900',
            'returned'  => 'yellow-900',
            'approved'  => 'green-900',
            'rejected'  => 'red-900',
            'default'   => '#212529',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | สังกัดหน่วยบริการ (Affiliations)
    |--------------------------------------------------------------------------
    | ย้ายจาก config เดิมมาไว้รวมที่นี่
    | การเรียกใช้งาน:
    |   config('tmc.affiliations')  // ได้เป็น array ทั้งชุด
     */

    'affiliations' => [
        'สำนักงานปลัดกระทรวงสาธารณสุข',
        'กรมควบคุมโรค',
        'กรมการแพทย์',
        'กรมสุขภาพจิต',
        'สภากาชาดไทย',
        'สำนักการแพทย์ กรุงเทพมหานคร',
        'กระทรวงอุดมศึกษา วิทยาศาสตร์ วิจัยและนวัตกรรม',
        'กระทรวงกลาโหม',
        'องค์กรปกครองส่วนท้องถิ่น',
        'องค์การมหาชน',
        'เอกชน',
        'อื่น ๆ',
    ],

];
