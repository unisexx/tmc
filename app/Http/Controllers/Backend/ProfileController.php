<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function __construct()
    {
        //
    }

    /* =========================================================================
    | แก้ไขโปรไฟล์ (หน้าแบบเดียวกับ backend.user.edit ก็ได้)
     * =========================================================================*/
    public function edit(User $user)
    {
        // ปกติหน้าโปรไฟล์ควรผูกกับ auth()->user()
        // แต่พี่ส่ง User $user มาแล้วใช้ view เดิมอยู่ ก็โอเคครับ
        return view('backend.profile.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        [$data, $pwdPlain] = $this->validatedPayload($request, $user);

        // กันไม่ให้แก้ไขฟิลด์สำคัญ
        $data['contact_cid'] = $user->contact_cid;
        $data['username']    = $user->username;
        $data['email']       = $user->email;

        $user->update($data);

        // (ไม่ยุ่งกับ role / reg_status / is_active ในโปรไฟล์)
        // (ไม่ส่งอีเมลแจ้งรหัสผ่านใหม่โดยอัตโนมัติ)

        flash_notify('อัปเดตข้อมูลโปรไฟล์เรียบร้อย', 'success');
        return back();
    }

    /* =========================================================================
    | Helpers
     * =========================================================================*/

    /**
     * Validate + Normalize
     * คืนค่า: [$data สำหรับบันทึก, $passwordPlain ถ้าผู้ใช้ตั้งใหม่]
     */
    private function validatedPayload(Request $request, ?User $current): array
    {
        $rules = [
            // ฟิลด์โปรไฟล์ที่แก้ไขได้
            'contact_cid'           => ['required', 'regex:/^\d{13}$/', 'unique:users,contact_cid,' . ($current?->id ?? 'NULL') . ',id'],
            'contact_name'          => ['required', 'string', 'max:255'],
            'contact_position'      => ['required', 'string', 'max:255'],
            'contact_mobile'        => ['required', 'string', 'max:60'],

            // เปลี่ยนรหัสผ่าน (ไม่บังคับ)
            'password'              => ['nullable', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['nullable', 'same:password', 'required_with:password'],

            // ⚠️ ไม่รับ username / email ในโปรไฟล์ (ห้ามแก้)
            // ถ้าอยากให้พ่น error เมื่อมีการส่งมา เพิ่ม 'prohibited' ได้:
            // 'username' => ['prohibited'],
            // 'email'    => ['prohibited'],
        ];

        $messages = [
            'required'                            => 'กรุณากรอก :attribute',
            'string'                              => ':attribute ต้องเป็นข้อความ',
            'max'                                 => 'ความยาว :attribute ต้องไม่เกิน :max ตัวอักษร',
            'contact_cid.regex'                   => 'เลขบัตรประชาชนต้องเป็นตัวเลข 13 หลัก',
            'contact_cid.unique'                  => 'เลขบัตรประชาชนนี้ถูกใช้งานแล้ว',
            'password.min'                        => 'รหัสผ่านต้องมีอย่างน้อย :min ตัวอักษร',
            'password.confirmed'                  => 'กรุณายืนยันรหัสผ่านให้ตรงกัน',
            'password_confirmation.same'          => 'ยืนยันรหัสผ่านไม่ตรงกับรหัสผ่าน',
            'password_confirmation.required_with' => 'กรุณากรอกยืนยันรหัสผ่าน',
        ];

        $attributes = [
            'contact_cid'           => 'เลขบัตรประจำตัวประชาชน',
            'contact_name'          => 'ชื่อ-สกุล',
            'contact_position'      => 'ตำแหน่ง',
            'contact_mobile'        => 'โทรศัพท์มือถือ',
            'password'              => 'รหัสผ่าน',
            'password_confirmation' => 'ยืนยันรหัสผ่าน',
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
        $data['name']        = $data['contact_name']; // เผื่อมีคอลัมน์ name ใช้แสดง

        // Password (เปลี่ยนเมื่อกรอก)
        $passwordPlain = null;
        if (!empty($data['password'])) {
            $passwordPlain    = $data['password'];
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password'], $data['password_confirmation']);
        }

        return [$data, $passwordPlain];
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
