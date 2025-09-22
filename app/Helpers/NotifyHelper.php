<?php

if (!function_exists('flash_notify')) {
    /**
     * สร้าง flash message สำหรับแจ้งเตือน (ใช้กับ notifier.js / ac-notification)
     *
     * @param  string  $message   ข้อความที่จะแสดง
     * @param  string  $type      default|info|success|warning|danger
     * @param  int     $timeout   เวลาแสดงผล (มิลลิวินาที)
     * @param  string|null $icon  ไอคอน (optional เช่น 'fa fa-check-circle')
     * @return void
     */
    function flash_notify(string $message, string $type = 'success', int $timeout = 2000, ?string $icon = null): void
    {
        session()->flash('notify', [
            'message' => $message,
            'type'    => $type,
            'timeout' => $timeout,
            'icon'    => $icon,
        ]);
    }
}
