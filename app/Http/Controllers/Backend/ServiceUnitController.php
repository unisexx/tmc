<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ServiceUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceUnitController extends Controller
{
    public function switch(Request $req)
    {
            $user  = Auth::user();
            $input = $req->input('service_unit_id');

            // 🟦 1) ถ้าเป็นแอดมินและเลือก "ภาพรวม" (ไม่เลือกหน่วย)
            if ($user->isAdmin() && ($input === null || $input === '')) {
                session()->forget('current_service_unit_id');
                flash_notify('เข้าสู่มุมมองภาพรวม (ไม่ผูกกับหน่วยบริการ)', 'success');
                return redirect()->route('backend.dashboard');
            }

            // 🟦 2) ตรวจสอบค่าปกติ
            $id = (int) $input;
            if (!$id) {
                flash_notify('ไม่พบรหัสหน่วยบริการที่ต้องการสลับ', 'warning');
                return back();
            }

            // 🟦 3) ตรวจสอบสิทธิ์การเข้าถึง
            if ($user->isAdmin()) {
                $unit = ServiceUnit::find($id);
            } else {
                $unit = $user->serviceUnits()->where('service_units.id', $id)->first();
            }

            if (!$unit) {
                flash_notify('คุณไม่มีสิทธิ์สลับไปยังหน่วยบริการนี้ หรือหน่วยบริการไม่พบ', 'warning');
                return back();
            }

            // 🟦 4) ตั้งค่า session
            session(['current_service_unit_id' => $unit->id]);

            flash_notify('สลับหน่วยบริการเรียบร้อยแล้ว', 'success');
            return redirect()->route('backend.dashboard');
    }
}
