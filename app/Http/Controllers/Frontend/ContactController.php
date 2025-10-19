<?php
// app/Http/Controllers/Frontend/ContactController.php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function index()
    {
        $contact = Contact::firstOrCreate(['id' => 1], [
            'address'  => null,
            'email'    => null,
            'tel'      => null,
            'fax'      => null,
            'map'      => null,
            'facebook' => null,
            'youtube'  => null,
        ]);

        // Math CAPTCHA: เก็บคำตอบไว้ 10 นาที
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        session([
            'captcha_answer'  => $a + $b,
            'captcha_expires' => now()->addMinutes(10)->timestamp,
        ]);
        $captchaQuestion = "{$a} + {$b} = ?";

        return view('frontend.contact.index', compact('contact', 'captchaQuestion'));
    }

    public function send(Request $request)
    {
        // ตรวจ honeypot ก่อน
        if ($request->filled('hp')) {
            return redirect()->route('frontend.contact.index')
                ->withErrors(['hp' => 'คำขอไม่ถูกต้อง'])
                ->withInput();
        }

        // กฎตรวจข้อมูล
        $rules = [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'phone'   => 'nullable|string|max:50',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'captcha' => 'required|numeric',
            'hp'      => ['present', 'size:0'],
        ];

        // ข้อความ validate แบบกำหนดเอง
        $messages = [
            'required'         => 'กรุณากรอก:attribute',
            'string'           => ':attribute ต้องเป็นตัวอักษร',
            'email'            => 'รูปแบบอีเมลไม่ถูกต้อง',
            'max'              => ':attribute ต้องไม่เกิน :max ตัวอักษร',
            'numeric'          => ':attribute ต้องเป็นตัวเลข',
            'present'          => 'คำขอไม่ถูกต้อง',
            'size'             => 'คำขอไม่ถูกต้อง',
            'captcha.required' => 'กรุณาตอบคำถามยืนยันตัวตน',
            'captcha.numeric'  => 'คำตอบ CAPTCHA ต้องเป็นตัวเลข',
        ];

        // แทนชื่อฟิลด์ให้อ่านง่าย
        $attributes = [
            'name'    => 'ชื่อ-นามสกุลผู้ติดต่อ',
            'email'   => 'อีเมล',
            'phone'   => 'เบอร์โทรศัพท์',
            'subject' => 'หัวข้อ',
            'message' => 'ข้อความ',
            'captcha' => 'CAPTCHA',
        ];

        $v = Validator::make($request->all(), $rules, $messages, $attributes);

        // ตรวจ CAPTCHA และอายุ
        $v->after(function ($v) use ($request) {
            $expected = session('captcha_answer');
            $expires  = session('captcha_expires');

            if (!$expected || !$expires || now()->timestamp > $expires) {
                $v->errors()->add('captcha', 'CAPTCHA หมดอายุ กรุณาลองใหม่');
                return;
            }
            if ((int) $request->input('captcha') !== (int) $expected) {
                $v->errors()->add('captcha', 'คำตอบ CAPTCHA ไม่ถูกต้อง');
            }
        });

        if ($v->fails()) {
            return redirect()->route('frontend.contact.index')
                ->withErrors($v)
                ->withInput();
        }

        // ผ่านการตรวจทั้งหมด
        $data = $v->validated();

        ContactMessage::create([
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'subject'    => $data['subject'],
            'message'    => $data['message'],
            'ip'         => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
            'status'     => 'new',
            'tags'       => null,
        ]);

        // รีเซ็ต CAPTCHA หลังส่งสำเร็จ
        session()->forget(['captcha_answer', 'captcha_expires']);

        flash_notify('ส่งข้อความเรียบร้อยแล้ว', 'success');
        return redirect()->route('frontend.contact.index');
    }
}
