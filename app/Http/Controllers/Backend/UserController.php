<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        // $this->middleware('permission:ผู้ใช้งาน-จัดการผู้ใช้งาน-ดู|ผู้ใช้งาน-จัดการผู้ใช้งาน-เพิ่ม|ผู้ใช้งาน-จัดการผู้ใช้งาน-แก้ไข|ผู้ใช้งาน-จัดการผู้ใช้งาน-ลบ', ['only' => ['index', 'show']]);
    }

    /* =========================================================================
    | 1) รายชื่อผู้ใช้
     * =========================================================================*/
    public function index(Request $request)
    {
        $q     = trim($request->get('q', ''));
        $users = User::query()
            ->with(['role', 'serviceUnits' => fn($q) => $q->withPivot('is_primary'), 'superviseProvince', 'superviseRegion'])
            ->whereNotNull('role_id')
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($x) use ($q) {
                    $x->where('name', 'like', "%{$q}%")
                        ->orWhere('contact_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('backend.user.index', compact('users', 'q'));
    }

    /* =========================================================================
    | 2) เพิ่มผู้ใช้
     * =========================================================================*/
    public function create()
    {
        return view('backend.user.create');
    }

    public function store(Request $request)
    {
        [$data, $pwdPlain] = $this->validatedPayload($request, null);

        // สร้างผู้ใช้
        $user = User::create($data);

        // กำหนดสิทธิ์
        if ($request->filled('role_id')) {
            $role = Role::find($request->role_id);
            if ($role) {
                $user->assignRole($role->name);
            }
        }

        // ส่งอีเมลแจ้ง credential
        $this->notifyCredentials($user->email, $user->username, $pwdPlain);

        flash_notify('เพิ่มผู้ใช้งานเรียบร้อย', 'success');
        return redirect()->route('backend.user.index');
    }

    /* =========================================================================
    | 3) แก้ไขผู้ใช้
     * =========================================================================*/
    public function edit(User $user)
    {
        return view('backend.user.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        [$data, $pwdPlain] = $this->validatedPayload($request, $user);

        $user->update($data);

        // กำหนดสิทธิ์
        if ($request->filled('role_id')) {
            $role = Role::find($request->role_id);
            if ($role) {
                $user->syncRoles([$role->name]);
            }
        } else {
            $user->syncRoles([]);
        }

        // ถ้าต้องการแจ้งอีเมลเมื่อเปลี่ยนรหัสผ่าน ค่อยเปิดใช้บรรทัดนี้
        // if ($pwdPlain) { $this->notifyCredentials($user->email, $user->username, $pwdPlain); }

        flash_notify('อัปเดตข้อมูลผู้ใช้งานเรียบร้อย', 'success');
        return redirect()->route('backend.user.index');
    }

    /* =========================================================================
    | 4) ลบผู้ใช้
     * =========================================================================*/
    public function destroy(User $user)
    {
        if ($user->officer_doc_path && Storage::disk('public')->exists($user->officer_doc_path)) {
            Storage::disk('public')->delete($user->officer_doc_path);
        }
        $user->delete();
        flash_notify('ลบผู้ใช้งานเรียบร้อย', 'success');
        return back();
    }

    /* =========================================================================
    | Helpers
     * =========================================================================*/

    /**
     * Validate + Normalize + Upload
     * คืนค่า: [$data สำหรับบันทึก, $passwordPlain ถ้ามีการตั้ง/สุ่มให้]
     */
    private function validatedPayload(Request $request, ?User $current): array
    {
        $rules = [
            // ผู้ลงทะเบียน
            'contact_cid'           => ['required', 'regex:/^\d{13}$/', Rule::unique('users', 'contact_cid')->ignore($current?->id)],
            'contact_name'          => ['required', 'string', 'max:255'],
            'contact_position'      => ['required', 'string', 'max:255'],
            'contact_mobile'        => ['required', 'string', 'max:60'],
            'email'                 => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($current?->id)],

            // เอกสารเจ้าหน้าที่
            'officer_doc'           => [$current ? 'sometimes' : 'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'remove_officer_doc'    => ['nullable', 'boolean'],

            // บัญชีผู้ใช้
            'username'              => ['required', 'string', 'max:60', Rule::unique('users', 'username')->ignore($current?->id)],
            'password'              => [$current ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => [$current ? 'nullable' : 'required', 'same:password', 'required_with:password'],

            // Role
            'role_id'               => ['required', 'integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],

            // 🔹 สถานะการพิจารณา (รับทั้งไทย/อังกฤษ)
            'reg_status'            => ['nullable', 'string', Rule::in(['รอตรวจสอบ', 'อนุมัติ', 'ไม่อนุมัติ'])],
        ];

        $messages = [
            'required'                            => 'กรุณากรอก :attribute',
            'string'                              => ':attribute ต้องเป็นข้อความ',
            'max'                                 => 'ความยาว :attribute ต้องไม่เกิน :max ตัวอักษร',
            'email'                               => 'รูปแบบ :attribute ไม่ถูกต้อง',
            'unique'                              => ':attribute นี้ถูกใช้งานแล้ว',
            'mimes'                               => ':attribute ต้องเป็นไฟล์ประเภท: :values',
            'max.file'                            => 'ขนาดไฟล์ :attribute ต้องไม่เกิน :max กิโลไบต์',
            'password.min'                        => 'รหัสผ่านต้องมีอย่างน้อย :min ตัวอักษร',
            'password.confirmed'                  => 'กรุณายืนยันรหัสผ่านให้ตรงกัน',
            'password_confirmation.same'          => 'ยืนยันรหัสผ่านไม่ตรงกับรหัสผ่าน',
            'password_confirmation.required_with' => 'กรุณากรอกยืนยันรหัสผ่าน',
            'contact_cid.regex'                   => 'เลขบัตรประชาชนต้องเป็นตัวเลข 13 หลัก',
        ];

        $attributes = [
            'contact_cid'           => 'เลขบัตรประจำตัวประชาชน',
            'contact_name'          => 'ชื่อ-สกุล',
            'contact_position'      => 'ตำแหน่ง',
            'contact_mobile'        => 'โทรศัพท์มือถือ',
            'email'                 => 'อีเมล',
            'officer_doc'           => 'เอกสารยืนยันตัวเจ้าหน้าที่',
            'username'              => 'Username',
            'password'              => 'รหัสผ่าน',
            'password_confirmation' => 'ยืนยันรหัสผ่าน',
            'role_id'               => 'สิทธิ์การใช้งาน',
            'reg_status'            => 'สถานะการพิจารณา',
        ];

        $validator = Validator::make($request->all(), $rules, $messages, $attributes);

        // ตรวจ checksum บัตรประชาชน
        $validator->after(function ($v) use ($request) {
            $cid = preg_replace('/\D/', '', (string) $request->input('contact_cid'));
            if ($cid && !$this->isValidThaiCitizenId($cid)) {
                $v->errors()->add('contact_cid', 'เลขบัตรประชาชนไม่ถูกต้อง (เช็กซัมไม่ผ่าน)');
            }
        });

        $data = $validator->validate();

        // Normalize
        $data['contact_cid'] = preg_replace('/\D/', '', (string) ($data['contact_cid'] ?? ''));
        $data['name']        = $data['contact_name'];

        // Password
        $passwordPlain = null;
        if (!$current) {
            $passwordPlain    = $data['password'];
            $data['password'] = Hash::make($data['password']);
        } else {
            if (!empty($data['password'])) {
                $passwordPlain    = $data['password'];
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password'], $data['password_confirmation']);
            }
        }

        // Upload / Remove officer_doc
        if ($request->boolean('remove_officer_doc') && $current) {
            if ($current->officer_doc_path && Storage::disk('public')->exists($current->officer_doc_path)) {
                Storage::disk('public')->delete($current->officer_doc_path);
            }
            $data['officer_doc_path']        = null;
            $data['officer_doc_verified_at'] = null;
            $data['officer_doc_verified_by'] = null;
        }
        if ($request->hasFile('officer_doc')) {
            $path                            = $request->file('officer_doc')->store('officer_docs', 'public');
            $data['officer_doc_path']        = $path;
            $data['officer_doc_verified_at'] = null;
            $data['officer_doc_verified_by'] = null;
        }

        /* 🔹 เปิด/ปิดการใช้งานอัตโนมัติตาม reg_status (ภาษาไทย) */
        $incomingStatus = $request->input('reg_status'); // อาจเป็น null
        if ($incomingStatus !== null) {
            $data['reg_status'] = $incomingStatus;
            $data['is_active']  = $incomingStatus === 'อนุมัติ' ? 1 : 0;
        } else {
            // ไม่มี reg_status เข้ามา → ใช้ค่าเดิมถ้ามี ไม่งั้น default = 0
            $effectiveStatus   = $current?->reg_status;
            $data['is_active'] = $effectiveStatus === 'อนุมัติ' ? 1 : 0;
        }

        // เคลียร์ฟิลด์ชั่วคราว
        unset($data['officer_doc'], $data['remove_officer_doc']);

        return [$data, $passwordPlain];
    }

    /**
     * ส่งอีเมลแจ้ง credential แบบง่าย
     */
    private function notifyCredentials(string $email, string $username, ?string $passwordPlain): void
    {
        if (!$passwordPlain) {
            return;
        }

        try {
            Mail::raw(
                "ระบบผู้เดินทาง - บัญชีของคุณถูกสร้างโดยผู้ดูแลระบบ\n\n" .
                "Username: {$username}\n" .
                "Password: {$passwordPlain}\n\n" .
                "เข้าสู่ระบบแล้วกรุณาเปลี่ยนรหัสผ่านทันทีเพื่อความปลอดภัย",
                function ($m) use ($email) {
                    $m->to($email)->subject('บัญชีผู้ใช้งานถูกสร้าง (ระบบผู้เดินทาง)');
                }
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function isValidThaiCitizenId(string $cid): bool
    {
        if (!preg_match('/^\d{13}$/', $cid)) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += intval($cid[$i]) * (13 - $i);
        }
        $check = (11 - ($sum % 11)) % 10;
        return $check === intval($cid[12]);
    }
}
