<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * หลังจาก login สำเร็จ
     */
    protected function authenticated(Request $request, $user)
    {
        // ตรวจสอบว่ามีความสัมพันธ์ serviceUnits หรือไม่
        if (method_exists($user, 'serviceUnits')) {
            try {
                $primaryUnit = $user->serviceUnits()
                    ->wherePivot('is_primary', true)
                    ->first();

                if ($primaryUnit) {
                    session(['current_service_unit_id' => $primaryUnit->id]);
                } else {
                    // ถ้าไม่มี is_primary ให้ใช้หน่วยแรกแทน
                    $firstUnit = $user->serviceUnits()->first();
                    if ($firstUnit) {
                        session(['current_service_unit_id' => $firstUnit->id]);
                    } else {
                        // ถ้าไม่มีหน่วยเลย เคลียร์ session เดิมทิ้ง
                        session()->forget('current_service_unit_id');
                    }
                }
            } catch (\Throwable $e) {
                // ถ้ามี error (เช่นตารางความสัมพันธ์ยังไม่พร้อม)
                session()->forget('current_service_unit_id');
            }
        } else {
            // ไม่มี relation ก็เคลียร์ session เผื่อไว้
            session()->forget('current_service_unit_id');
        }

        // ไปหน้า dashboard หลัง login
        return redirect()->intended(route('backend.dashboard'));
    }
}
