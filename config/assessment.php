<?php
// \config\assessment.php

return [

    /*
    |--------------------------------------------------------------------------
    | แผนที่ข้อความ/สี : ระดับหน่วยบริการ
    |--------------------------------------------------------------------------
    | ใช้คู่กับ <x-level-badge> หรือ $model->level_text / $model->level_badge_class
     */
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

    // สีตัวอักษรบน badge (ควรเข้มกว่าโทนพื้น)
    'level_badge_text_color'    => [
        'basic'      => 'pink-900',
        'medium'     => 'yellow-900',
        'advanced'   => 'teal-900',
        'unassessed' => 'gray-900',
        'default'    => '#212529',
    ],

    // เสริม: คลาส border (เฉพาะกรณีที่อยากให้ “ยังไม่ได้ประเมิน” เด่นขึ้น)
    'level_badge_border_class'  => [
        'basic'      => '',
        'medium'     => '',
        'advanced'   => '',
        'unassessed' => 'border border-gray-400',
    ],

    /*
    |--------------------------------------------------------------------------
    | แผนที่ข้อความ/สี : สถานะแบบประเมิน (การกรอกฟอร์ม)
    |--------------------------------------------------------------------------
     */
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

    /*
    |--------------------------------------------------------------------------
    | แผนที่ข้อความ/สี : สถานะการอนุมัติ
    |--------------------------------------------------------------------------
     */
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

];
