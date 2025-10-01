<?php
// app/Http/Controllers/ImpersonateController.php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    public function start(Request $request, User $user)
    {
        $real = Auth::user();

        // กันเคส: จำลองตัวเอง, หรือจำลองผู้ที่สูงกว่า (ถ้ามี logic ระดับสิทธิ์)
        if ($real->id === $user->id) {
            return back()->with('error', 'ไม่สามารถจำลองเป็นตัวเองได้');
        }
        // ตัวอย่างป้องกันจำลอง Super Admin (ถ้ามี)
        // if ($user->hasRole('super-admin') && !$real->hasRole('super-admin')) {
        //     return back()->with('error', 'ไม่มีสิทธิ์จำลองผู้ใช้ระดับสูงกว่า');
        // }

        // เก็บตัวจริงไว้ใน session หากยังไม่ได้เก็บ
        if (!$request->session()->has('impersonated_by')) {
            $request->session()->put('impersonated_by', $real->id);
        }

        // login ด้วย user ปลายทาง (ไม่จำค่า remember)
        Auth::loginUsingId($user->id, false);

        // ใส่ธงกำลังจำลอง
        $request->session()->put('is_impersonating', true);

        flash_notify("กำลังจำลองเป็น {$user->contact_name}", 'success');
        return redirect()->route('backend.dashboard');
    }

    public function stop(Request $request)
    {
        $originalId = $request->session()->pull('impersonated_by'); // ดึงแล้วลบทิ้ง
        $request->session()->forget('is_impersonating');

        if (!$originalId) {
            flash_notify('ไม่มีสถานะกำลังจำลอง', 'error');
            return redirect()->route('backend.dashboard');
        }

        Auth::loginUsingId($originalId, false);

        flash_notify('ยกเลิกการจำลองและกลับสู่ผู้ใช้เดิมแล้ว', 'success');
        return redirect()->route('backend.dashboard');
    }
}
