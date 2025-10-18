<?php
// app/Http/Controllers/Frontend/ServiceUnitMessageController.php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ServiceUnit;
use App\Models\ServiceUnitMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceUnitMessageController extends Controller
{
    // POST /units/{serviceUnit}/messages
    public function store(Request $request, ServiceUnit $serviceUnit)
    {
        // ตัวอย่าง: serviceUnit มีฟิลด์ contact_email, org_name
        $toEmail = $serviceUnit->contact_email ?? null;

        $rules = [
            'from_name'  => 'required|string|max:255',
            'from_email' => 'required|email|max:255',
            'body'       => 'required|string|max:4000',
            'subject'    => 'nullable|string|max:255',
            'captcha'    => 'required|numeric',    // ถ้าใช้ Math CAPTCHA
            'hp'         => ['present', 'size:0'], // honeypot
        ];

        $v = Validator::make($request->all(), $rules);
        // ตรวจ Math CAPTCHA ที่เซสชันเหมือนตัวอย่างก่อนหน้า
        $v->after(function ($v) use ($request) {
            $expected = session('captcha_answer');
            $expires  = session('captcha_expires');
            if (!$expected || !$expires || now()->timestamp > $expires) {
                $v->errors()->add('captcha', 'CAPTCHA หมดอายุ');
                return;
            }
            if ((int) $request->input('captcha') !== (int) $expected) {
                $v->errors()->add('captcha', 'คำตอบ CAPTCHA ไม่ถูกต้อง');
            }
        });

        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        $msg = ServiceUnitMessage::create([
            'service_unit_id' => $serviceUnit->id,
            'to_name'         => $serviceUnit->org_name ?? null,
            'to_email'        => $toEmail,
            'from_name'       => $request->from_name,
            'from_email'      => $request->from_email,
            'subject'         => $request->subject,
            'body'            => $request->body,
            'ip'              => $request->ip(),
            'user_agent'      => substr((string) $request->userAgent(), 0, 512),
            'status'          => 'new',
        ]);

        // ถ้าต้องการส่งอีเมลจริง ให้คิวงานที่นี่ (Mail::to($toEmail)->queue(...))
        return back()->with('success', 'ส่งข้อความถึงหน่วยบริการแล้ว');
    }
}
