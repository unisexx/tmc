<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceUnitRequest;
use App\Models\ServiceUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceUnitProfileController extends Controller
{
    public function edit(Request $request)
    {
        $unit = $this->resolveCurrentServiceUnit($request);

        if (!$unit) {
            flash_notify('กรุณาเลือกหน่วยบริการจากเมนูด้านบน', 'warning');
            return redirect()->back();
        }

        return view('backend.service_unit_profile.edit', [
            'unit' => $unit,
            'user' => Auth::user(),
        ]);
    }

    public function update(ServiceUnitRequest $request)
    {
        $unit = $this->resolveCurrentServiceUnit($request);
        abort_unless($unit, 403, 'ไม่พบหน่วยบริการของคุณ');

        // ดึงค่า validated ทั้งหมด
        $data = $request->validated();

        // แปลงเวลาทำการจาก helper ใน FormRequest
        $data['org_working_hours_json'] = $request->parsedWorkingHours();

        // อัปเดต field ที่อนุญาตให้แก้ไข
        $unit->fill(
            collect($data)->only([
                'org_name',
                'org_affiliation',
                'org_affiliation_other',
                'org_address',
                'org_province_code',
                'org_district_code',
                'org_subdistrict_code',
                'org_postcode',
                'org_tel',
                'org_email',
                'org_lat',
                'org_lng',
                'org_working_hours_json',
            ])->toArray()
        )->save();

        flash_notify('บันทึกการแก้ไขแล้ว', 'success');
        return redirect()->route('backend.service-unit-profile.edit');
    }

    private function resolveCurrentServiceUnit(Request $request): ?ServiceUnit
    {
        $id = (int) session('current_service_unit_id');
        if ($id > 0) {
            return ServiceUnit::find($id);
        }

        $user = $request->user();
        if (method_exists($user, 'serviceUnits')) {
            return $user->serviceUnits()
                ->wherePivot('is_primary', true)
                ->first() ?? $user->serviceUnits()->first();
        }

        return null;
    }
}
