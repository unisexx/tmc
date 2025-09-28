<?php
// database/seeders/PermissionSeeder.php
namespace Database\Seeders;

use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // สร้าง permissions
        foreach (Permissions::all() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // สร้าง roles ตัวอย่าง
        $super        = Role::firstOrCreate(['name' => 'Super Admin']);
        $contentAdmin = Role::firstOrCreate(['name' => 'Content Admin']);
        $editor       = Role::firstOrCreate(['name' => 'Editor']);
        $viewer       = Role::firstOrCreate(['name' => 'Viewer']);
        $analyst      = Role::firstOrCreate(['name' => 'Analyst']);

        // กำหนดสิทธิ์ให้ role
        $super->syncPermissions(Permission::all());

        $contentAdmin->syncPermissions([
            // บริหารเนื้อหาหลัก
            'highlights.view', 'highlights.create', 'highlights.update', 'highlights.delete', 'highlights.publish',
            'news.view', 'news.create', 'news.update', 'news.delete', 'news.publish',
            'faqs.view', 'faqs.create', 'faqs.update', 'faqs.delete',
            'privacy-policy.view', 'privacy-policy.update', 'privacy-policy.publish',
            'cookie-policy.view', 'cookie-policy.update', 'cookie-policy.publish',
        ]);

        $editor->syncPermissions([
            'highlights.view', 'highlights.create', 'highlights.update',
            'news.view', 'news.create', 'news.update',
            'faqs.view', 'faqs.create', 'faqs.update',
        ]);

        $viewer->syncPermissions([
            'dashboard.view',
            'visitor-stats.view',
            'highlights.view', 'news.view', 'faqs.view',
            'contacts.view', 'privacy-policy.view', 'cookie-policy.view',
        ]);

        $analyst->syncPermissions([
            'dashboard.view',
            'visitor-stats.view', 'visitor-stats.export',
        ]);
    }
}
