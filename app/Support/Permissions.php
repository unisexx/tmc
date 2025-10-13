<?php

namespace App\Support;

class Permissions
{
    /**
     * รายการสิทธิ์แยกตามโมดูล -> actions
     */
    public const MODULES = [
        'dashboard'            => ['view'],
        'visitor-stats'        => ['view', 'export'],
        'assessment'           => ['view', 'create', 'update', 'delete'],
        'service-unit-profile' => ['view', 'create', 'update', 'delete'],
        'review-assessment'    => ['view', 'create', 'update', 'delete'],
        'highlights'           => ['view', 'create', 'update', 'delete'],
        'news'                 => ['view', 'create', 'update', 'delete'],
        'faqs'                 => ['view', 'create', 'update', 'delete'],
        'contacts'             => ['view', 'update'],
        'privacy-policy'       => ['view', 'update'],
        'cookie-policy'        => ['view', 'update'],
        'approve-application'  => ['view', 'create', 'update', 'delete'],
        'users'                => ['view', 'create', 'update', 'delete'],
        'roles-permissions'    => ['view', 'create', 'update', 'delete'],
        'service-unit'         => ['view', 'create', 'update', 'delete'],
        'log'                  => ['view'],
    ];

    /**
     * ป้ายชื่อ (label) ของโมดูล (ภาษาไทย)
     */
    public const MODULE_LABELS = [
        'dashboard'            => 'แดชบอร์ด',
        'visitor-stats'        => 'สถิติผู้เข้าชม',
        'assessment'           => 'การประเมินตนเอง',
        'service-unit-profile' => 'ข้อมูลหน่วยบริการ',
        'review-assessment'    => 'ตรวจสอบผลการประเมิน',
        'highlights'           => 'ไฮไลท์',
        'news'                 => 'ข่าว',
        'faqs'                 => 'คำถามที่พบบ่อย',
        'contacts'             => 'ติดต่อ/ช่องทาง',
        'privacy-policy'       => 'นโยบายความเป็นส่วนตัว (PDPA)',
        'cookie-policy'        => 'นโยบายคุกกี้',
        'approve-application'  => 'ตรวจสอบใบสมัคร',
        'users'                => 'ผู้ใช้งาน',
        'roles-permissions'    => 'สิทธิ์การใช้งาน',
        'service-unit'         => 'หน่วยบริการ',
        'log'                  => 'ประวัติการใช้งาน',
    ];

    /**
     * ป้ายชื่อ (label) ของ action (ภาษาไทย)
     */
    public const ACTION_LABELS = [
        'view'           => 'ดู',
        'create'         => 'เพิ่ม',
        'update'         => 'แก้ไข',
        'delete'         => 'ลบ',
        'publish'        => 'เผยแพร่',
        'export'         => 'ส่งออก',
        'reset-password' => 'รีเซ็ตรหัสผ่าน',
        'assign'         => 'กำหนดสิทธิ์',
    ];

    /**
     * รวม permission ทั้งหมดเป็นรูปแบบ module.action
     */
    public static function all(): array
    {
        $perms = [];
        foreach (self::MODULES as $module => $actions) {
            foreach ($actions as $action) {
                $perms[] = "{$module}.{$action}";
            }
        }
        return $perms;
    }

    /**
     * คืนค่า label ของโมดูล (fallback เป็น key เดิมถ้าไม่พบ)
     */
    public static function moduleLabel(string $module): string
    {
        return self::MODULE_LABELS[$module] ?? $module;
    }

    /**
     * คืนค่า label ของ action (fallback เป็น key เดิมถ้าไม่พบ)
     */
    public static function actionLabel(string $action): string
    {
        return self::ACTION_LABELS[$action] ?? $action;
    }
}
