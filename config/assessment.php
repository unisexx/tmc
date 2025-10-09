<?php

return [

    /*
    |--------------------------------------------------------------------------
    | แผนที่ข้อความ/สี : ระดับหน่วยบริการ
    |--------------------------------------------------------------------------
    | ใช้คู่กับ <x-level-badge> หรือ $model->level_text / $model->level_badge_class
     */
    'level_text'                => [
        'basic'    => 'ระดับพื้นฐาน',
        'medium'   => 'ระดับกลาง',
        'advanced' => 'ระดับสูง',
    ],

    // คลาสสีพื้นของ badge (Light Able utility classes)
    'level_badge_class'         => [
        'basic'    => 'pink-100',   // ชมพูอ่อน
        'medium'   => 'yellow-100', // เหลืองอ่อน
        'advanced' => 'green-100',  // เขียวอ่อน
    ],

    // สีตัวอักษรบน badge (ควรเข้มกว่าโทนพื้น)
    'level_badge_text_color'    => [
        'basic'    => 'pink-900',
        'medium'   => 'yellow-900',
        'advanced' => 'teal-900',
        'default'  => '#212529', // เผื่อไว้เป็น fallback
    ],

    /*
    |--------------------------------------------------------------------------
    | แผนที่ข้อความ/สี : สถานะแบบประเมิน (การกรอกฟอร์ม)
    |--------------------------------------------------------------------------
    | ใช้คู่กับ <x-status-badge> หรือ $model->status_text / $model->status_badge_class
     */
    'status_text'               => [
        'draft'     => 'แบบร่าง',
        'completed' => 'ส่งตรวจสอบแล้ว',
    ],

    // สีพื้น badge ของสถานะแบบประเมิน
    'status_badge_class'        => [
        'draft'     => 'secondary', // น้ำเงินอ่อน (กำลังจัดทำ)
        'completed' => 'secondary', // เขียวอ่อน (ส่งแล้ว)
    ],

    // สีตัวอักษรของสถานะแบบประเมิน (ถ้าต้องใช้)
    'status_badge_text_color'   => [
        'draft'     => 'text-white',
        'completed' => 'text-white',
        'default'   => '#212529',
    ],

    /*
    |--------------------------------------------------------------------------
    | แผนที่ข้อความ/สี : สถานะการอนุมัติ
    |--------------------------------------------------------------------------
    | ใช้คู่กับ <x-approval-badge> หรือ $model->approval_text / $model->approval_badge_class
     */
    'approval_text'             => [
        'pending'   => 'รอดำเนินการ',
        'reviewing' => 'อยู่ระหว่างการพิจารณา',
        'returned'  => 'ส่งกลับแก้ไข',
        'approved'  => 'อนุมัติ',
        'rejected'  => 'ไม่อนุมัติ',
    ],

    // สีพื้น badge ของการอนุมัติ
    'approval_badge_class'      => [
        'pending'   => 'gray-100',   // รอ
        'reviewing' => 'blue-100',   // ตรวจอยู่
        'returned'  => 'yellow-100', // ส่งกลับแก้
        'approved'  => 'green-100',  // ผ่าน
        'rejected'  => 'red-100',    // ไม่ผ่าน
    ],

    // สีตัวอักษรของการอนุมัติ (ถ้าต้องใช้)
    'approval_badge_text_color' => [
        'pending'   => 'gray-900',
        'reviewing' => 'blue-900',
        'returned'  => 'yellow-900',
        'approved'  => 'green-900',
        'rejected'  => 'red-900',
        'default'   => '#212529',
    ],

];
