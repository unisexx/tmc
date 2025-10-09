<?php

if (!function_exists('flash_notify')) {
    /**
     * Flash message สำหรับ SweetAlert2
     *
     * @param  string       $message   ข้อความ
     * @param  string       $type      default|info|success|warning|danger|error|question
     * @param  int          $timeout   เวลาแสดงผล (ms) ใช้เมื่อเป็นโหมด toast
     * @param  string|null  $icon      ไม่ใช้ก็ได้ (เผื่ออนาคต)
     * @param  array        $options   ตัวเลือกเพิ่มเติม:
     *                                 - 'confirm'            => bool   เปิดโหมดยืนยัน (default: false)
     *                                 - 'confirmText'        => string ข้อความปุ่มยืนยัน (default: 'ตกลง')
     *                                 - 'showCancel'         => bool   แสดงปุ่มยกเลิก (default: false)
     *                                 - 'cancelText'         => string ข้อความปุ่มยกเลิก (default: 'ยกเลิก')
     *                                 - 'allowOutsideClick'  => bool   คลิกนอกกล่องเพื่อปิด (default: false เมื่อ confirm)
     *                                 - 'focusConfirm'       => bool   โฟกัสปุ่มยืนยัน (default: true เมื่อ confirm)
     * @return void
     */
    function flash_notify(
        string  $message,
        string  $type = 'success',
        int     $timeout = 2000,
        ?string $icon = null,
        array   $options = []
    ): void {
        session()->flash('notify', [
            'message' => $message,
            'type'    => $type,
            'timeout' => $timeout,
            'icon'    => $icon,

            // ตัวเลือกเสริมสำหรับโหมดยืนยัน
            'options' => $options,
        ]);
    }
}

/**
 * ช็อตคัตสำหรับ “ยืนยัน” (ปุ่มตกลงอย่างเดียว)
 */
if (!function_exists('flash_confirm')) {
    function flash_confirm(string $message, string $type = 'warning', array $options = []): void
    {
        $opts = array_merge([
            'confirm'           => true,
            'confirmText'       => 'ตกลง',
            'showCancel'        => false,
            'cancelText'        => 'ยกเลิก',
            'allowOutsideClick' => false,
            'focusConfirm'      => true,
            'subText'           => null,
        ], $options);

        flash_notify($message, $type, 0, null, $opts);
    }
}
