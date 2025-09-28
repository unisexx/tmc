<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Support\Permissions as Perms;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::query()
            ->when($request->filled('name'), fn($q) =>
                $q->where('name', 'like', '%' . $request->name . '%')
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('backend.role.index', compact('roles'));
    }

    public function create()
    {
        // view ฟอร์มจะดึงรายการ permission จาก App\Support\Permissions เอง
        return view('backend.role.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255|unique:roles,name',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string',
            'is_active'     => 'nullable', // checkbox
        ]);

        // sanitize ชุด permissions ให้ตรง whitelist เท่านั้น
        $permsFromForm = (array) $request->input('permissions', []);
        $perms         = $this->sanitizePermissions($permsFromForm);

        // สร้าง role
        $role = Role::create([
            'name'       => $request->string('name'),
            'guard_name' => 'web',
            // ถ้าตารางมีคอลัมน์ is_active จะบันทึกให้ด้วย
            // (ไม่มีคอลัมน์ก็ไม่ error เพราะเป็น attribute เฉย ๆ)
            'is_active'  => $request->boolean('is_active'),
        ]);

        if (!empty($perms)) {
            $this->ensurePermissionsExist($perms, 'web');
            $role->syncPermissions($perms);
        }

        flash_notify('เพิ่มสิทธิ์การใช้งานเรียบร้อยแล้ว', 'success');
        return redirect()->route('backend.role.index');
    }

    public function edit($id)
    {
        $role            = Role::findOrFail($id);
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('backend.role.edit', compact('role', 'rolePermissions'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name'          => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string',
            'is_active'     => 'nullable',
        ]);

        // อัปเดตชื่อ + สถานะ
        $role->fill([
            'name' => $request->string('name'),
        ]);

        // เฉพาะกรณีมีคอลัมน์ is_active ในตาราง roles
        if (Arr::has($role->getAttributes(), 'is_active') || array_key_exists('is_active', $role->getAttributes())) {
            $role->is_active = $request->boolean('is_active');
        }

        $role->save();

        // จัดการสิทธิ์
        $permsFromForm = (array) $request->input('permissions', []);
        $perms         = $this->sanitizePermissions($permsFromForm);

        if (empty($perms)) {
            $role->syncPermissions([]); // ล้างสิทธิ์ทั้งหมด
        } else {
            $this->ensurePermissionsExist($perms, 'web');
            $role->syncPermissions($perms);
        }

        flash_notify('แก้ไขสิทธิ์การใช้งานเรียบร้อยแล้ว', 'success');
        return redirect()->route('backend.role.index');
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        flash_notify('ลบสิทธิ์การใช้งานเรียบร้อยแล้ว', 'success');
        return redirect()->route('backend.role.index');
    }

    /**
     * กรอง permission ให้ตรงกับ whitelist จาก App\Support\Permissions
     */
    private function sanitizePermissions(array $requested): array
    {
        $whitelist = Perms::all(); // ['dashboard.view', 'users.create', ...]
        $requested = array_filter($requested, fn($v) => is_string($v) && trim($v) !== '');
        $requested = array_values(array_unique($requested));

        // เก็บเฉพาะที่อยู่ใน whitelist
        return array_values(array_intersect($requested, $whitelist));
    }

    /**
     * ถ้า permission ไหนยังไม่มีใน DB ให้สร้างก่อน (Spatie helper)
     */
    private function ensurePermissionsExist(array $names, string $guard = 'web'): void
    {
        foreach ($names as $name) {
            Permission::findOrCreate($name, $guard);
        }
    }
}
