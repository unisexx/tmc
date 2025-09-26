<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function __construct()
    {
        // ตั้งสิทธิ์ตามต้องการ (ถ้าใช้ spatie/permission ให้เปิดบรรทัดล่างนี้)
        // $this->middleware('permission:ผู้ใช้งาน-จัดการผู้ใช้งาน-ดู|ผู้ใช้งาน-จัดการผู้ใช้งาน-เพิ่ม|ผู้ใช้งาน-จัดการผู้ใช้งาน-แก้ไข|ผู้ใช้งาน-จัดการผู้ใช้งาน-ลบ', ['only' => ['index', 'show', 'detail']]);
        // $this->middleware('permission:ผู้ใช้งาน-จัดการผู้ใช้งาน-เพิ่ม', ['only' => ['create', 'store']]);
        // $this->middleware('permission:ผู้ใช้งาน-จัดการผู้ใช้งาน-แก้ไข', ['only' => ['edit', 'update', 'resetPassword']]);
        // $this->middleware('permission:ผู้ใช้งาน-จัดการผู้ใช้งาน-ลบ', ['only' => ['destroy']]);
    }

    /* =========================================================================
    | 1) หน้า list รายชื่อผู้ใช้ (แสดงตาราง / ค้นหา / กรอง)
     * ====================================================================== */
    public function index(Request $request)
    {
        // ค้นหาแบบง่าย ๆ (ถ้าจะใช้ DataTables server-side ค่อยผูก endpoint /list เพิ่ม)
        $q     = trim($request->get('q', ''));
        $users = User::query()
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($x) use ($q) {
                    $x->where('name', 'like', "%{$q}%")
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
    | 2) ฟอร์มเพิ่มผู้ใช้
     * ====================================================================== */
    public function create()
    {
        return view('backend.user.create');
    }

    public function store(Request $request)
    {
        // ปรับกติกาตามฟิลด์จริงในตาราง users
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'email', 'max:255', 'unique:users,email'],
            'username'          => ['required', 'string', 'max:60', 'unique:users,username'],
            'phone'             => ['nullable', 'string', 'max:60'],
            'position'          => ['nullable', 'string', 'max:255'],
            'password'          => ['nullable', 'string', 'min:8'],    // ถ้าเว้นว่างจะ gen ให้อัตโนมัติ
                                                                       // ฟิลด์ที่มาจาก “การลงทะเบียนเข้าใช้งาน” (TOR)
                                                                       // ตัวอย่าง: วัตถุประสงค์การลงทะเบียน/ข้อมูลหน่วยบริการ
            'reg_purpose'       => ['nullable', 'string', 'max:50'],   // 1.1 / 1.2 / 1.3
            'org_name'          => ['nullable', 'string', 'max:255'],  // 2.1
            'org_affiliation'   => ['nullable', 'string', 'max:255'],  // 2.2
            'org_address'       => ['nullable', 'string', 'max:1000'], // 2.3
            'org_lat'           => ['nullable', 'numeric'],
            'org_lng'           => ['nullable', 'numeric'],
            'org_tel'           => ['nullable', 'string', 'max:60'],   // 2.4
            'org_working_hours' => ['nullable', 'string', 'max:1000'], // 2.5 (เก็บเป็น text/JSON ก็ได้)
                                                                       // ผู้ลงทะเบียน
            'contact_name'      => ['nullable', 'string', 'max:255'],  // 3.1
            'contact_position'  => ['nullable', 'string', 'max:255'],  // 3.2
            'contact_mobile'    => ['nullable', 'string', 'max:60'],   // 3.3
        ]);

        if (empty($data['password'])) {
            $data['password']  = Str::password(12);
            $sendPasswordPlain = $data['password']; // เก็บไว้ส่งอีเมล
        } else {
            $sendPasswordPlain = $data['password'];
        }

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        // แจ้งอีเมล Username/Password ตาม TOR (ข้อ 4)
        try {
            Mail::raw(
                "ระบบผู้เดินทาง - บัญชีของคุณถูกสร้างโดยผู้ดูแลระบบ\n\n" .
                "Username: {$user->username}\n" .
                "Password: {$sendPasswordPlain}\n\n" .
                "เข้าสู่ระบบแล้วกรุณาเปลี่ยนรหัสผ่านทันทีเพื่อความปลอดภัย",
                function ($m) use ($user) {
                    $m->to($user->email)->subject('บัญชีผู้ใช้งานถูกสร้าง (ระบบผู้เดินทาง)');
                }
            );
        } catch (\Throwable $e) {
            // ไม่ค้าง flow: บันทึก log แล้วไปต่อ
            report($e);
        }

        return redirect()->route('backend.user.index')
            ->with('success', 'เพิ่มผู้ใช้งานเรียบร้อย');
    }

    /* =========================================================================
    | 3) แก้ไขผู้ใช้
     * ====================================================================== */
    public function edit(User $user)
    {
        return view('backend.user.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', "unique:users,email,{$user->id}"],
            'username' => ['required', 'string', 'max:60', "unique:users,username,{$user->id}"],
            'phone'             => ['nullable', 'string', 'max:60'],
            'position'          => ['nullable', 'string', 'max:255'],
            'password'          => ['nullable', 'string', 'min:8'],
            // ฟิลด์ลงทะเบียน
            'reg_purpose'       => ['nullable', 'string', 'max:50'],
            'org_name'          => ['nullable', 'string', 'max:255'],
            'org_affiliation'   => ['nullable', 'string', 'max:255'],
            'org_address'       => ['nullable', 'string', 'max:1000'],
            'org_lat'           => ['nullable', 'numeric'],
            'org_lng'           => ['nullable', 'numeric'],
            'org_tel'           => ['nullable', 'string', 'max:60'],
            'org_working_hours' => ['nullable', 'string', 'max:1000'],
            'contact_name'      => ['nullable', 'string', 'max:255'],
            'contact_position'  => ['nullable', 'string', 'max:255'],
            'contact_mobile'    => ['nullable', 'string', 'max:60'],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('backend.user.index')
            ->with('success', 'อัปเดตข้อมูลผู้ใช้งานเรียบร้อย');
    }

    /* =========================================================================
    | 4) ลบผู้ใช้
     * ====================================================================== */
    public function destroy(User $user)
    {
        $user->delete();
        return back()->with('success', 'ลบผู้ใช้งานเรียบร้อย');
    }

    /* =========================================================================
    | 5) หน้ารายละเอียดแบบ “Output การลงทะเบียน” (ตามรูป/ TOR ส่วน 2.2)
     * ====================================================================== */
    public function detail(User $user)
    {
        // ถ้ามีความสัมพันธ์อื่น ๆ (เช่น hasOne Profile/Organization) ให้ ->load(...) เพิ่ม
        // $user->load(['profile', 'organization']);
        // ในที่นี้ดึงจากคอลัมน์บนตาราง users ตามตัวอย่าง

        // timestamp ล่าสุด (ข้อ 4 ของ output)
        $lastSavedAt = optional($user->updated_at ?? $user->created_at)->timezone(config('app.timezone'));

        return view('backend.user.detail', [
            'u'           => $user,
            'lastSavedAt' => $lastSavedAt,
        ]);
    }

    /* =========================================================================
    | 6) ดาวน์โหลด PDF แบบฟอร์มลงทะเบียน (รูปแบบที่ 2)
    |    - ติดตั้งก่อน: composer require barryvdh/laravel-dompdf
    |    - สร้าง view: resources/views/backend/user/pdf.blade.php
     * ====================================================================== */
    public function downloadPdf(User $user)
    {
        // ป้องกันกรณี dompdf ยังไม่ถูกติดตั้ง
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return redirect()
                ->route('backend.user.detail', $user)
                ->with('error', 'ยังไม่ติดตั้งแพ็กเกจ PDF (barryvdh/laravel-dompdf)');
        }

        $user->refresh();
        $lastSavedAt = optional($user->updated_at ?? $user->created_at)->timezone(config('app.timezone'));

        /** @var \Barryvdh\DomPDF\PDF $pdf */
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('backend.user.pdf', [
            'u'           => $user,
            'lastSavedAt' => $lastSavedAt,
        ])->setPaper('a4', 'portrait');

        $filename = 'registration-' . $user->id . '.pdf';
        return $pdf->download($filename);
    }

    /* =========================================================================
    | 7) รีเซ็ตรหัสผ่านโดยแอดมิน & ส่งให้ผู้ใช้ทางอีเมล (ตาม TOR ข้อ 4)
     * ====================================================================== */
    public function resetPassword(User $user)
    {
        $newPassword = Str::password(12);
        $user->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();

        try {
            Mail::raw(
                "ระบบผู้เดินทาง - รหัสผ่านของคุณถูกรีเซ็ตโดยผู้ดูแลระบบ\n\n" .
                "Username: {$user->username}\n" .
                "Password ใหม่: {$newPassword}\n\n" .
                "เข้าสู่ระบบแล้วกรุณาเปลี่ยนรหัสผ่านทันทีเพื่อความปลอดภัย",
                function ($m) use ($user) {
                    $m->to($user->email)->subject('รีเซ็ตรหัสผ่าน (ระบบผู้เดินทาง)');
                }
            );
        } catch (\Throwable $e) {
            report($e);
            return back()->with('warning', 'รีเซ็ตรหัสผ่านแล้ว แต่ส่งอีเมลไม่สำเร็จ');
        }

        return back()->with('success', 'รีเซ็ตรหัสผ่านและส่งอีเมลให้ผู้ใช้เรียบร้อย');
    }

    /* =========================================================================
    | 8) ส่งออก CSV รายชื่อผู้ใช้งาน (หลีกเลี่ยงการติดตั้งแพ็กเกจเพิ่ม)
     * ====================================================================== */
    public function export(Request $request): StreamedResponse
    {
        $fileName = 'users-' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () {
            $output = fopen('php://output', 'w');

            // เขียน BOM สำหรับ Excel (กันภาษาไทยเพี้ยน)
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // ส่วนหัวคอลัมน์ — ปรับตามจริง
            fputcsv($output, [
                'ID', 'Name', 'Username', 'Email', 'Phone', 'Position',
                'Purpose', 'OrgName', 'OrgAffiliation', 'OrgTel',
                'OrgAddress', 'OrgLat', 'OrgLng', 'WorkingHours',
                'ContactName', 'ContactPosition', 'ContactMobile',
                'CreatedAt', 'UpdatedAt',
            ]);

            User::query()
                ->orderBy('id', 'asc')
                ->chunk(1000, function ($rows) use ($output) {
                    foreach ($rows as $u) {
                        fputcsv($output, [
                            $u->id,
                            $u->name,
                            $u->username,
                            $u->email,
                            $u->phone,
                            $u->position,
                            $u->reg_purpose,
                            $u->org_name,
                            $u->org_affiliation,
                            $u->org_tel,
                            $u->org_address,
                            $u->org_lat,
                            $u->org_lng,
                            $u->org_working_hours,
                            $u->contact_name,
                            $u->contact_position,
                            $u->contact_mobile,
                            optional($u->created_at)->format('Y-m-d H:i:s'),
                            optional($u->updated_at)->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }
}
