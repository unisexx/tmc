<?php

// app/Support/Permissions.php
namespace App\Support;

class Permissions
{
    public const MODULES = [
        'dashboard'          => ['view'],
        'visitor-stats'      => ['view', 'export'],
        'assessment'         => ['view', 'create', 'update', 'delete'],
        'approve-assessment' => ['view', 'create', 'update', 'delete'],
        'highlights'         => ['view', 'create', 'update', 'delete'],
        'news'               => ['view', 'create', 'update', 'delete'],
        'faqs'               => ['view', 'create', 'update', 'delete'],
        'contacts'           => ['view', 'update'],
        'privacy-policy'     => ['view', 'update'],
        'cookie-policy'      => ['view', 'update'],
        'users'              => ['view', 'create', 'update', 'delete'],
        'roles-permissions'  => ['view', 'create', 'update', 'delete'],
    ];

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
}
