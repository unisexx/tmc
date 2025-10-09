<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ServiceUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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

    public function update(Request $request)
    {
        $unit = $this->resolveCurrentServiceUnit($request);
        abort_unless($unit, 403, 'ไม่พบหน่วยบริการของคุณ');

        // ===== กำหนดข้อความ validate ภาษาไทย =====
        $messages = [
            'required' => 'กรุณากรอก :attribute',
            'string'   => ':attribute ต้องเป็นข้อความตัวอักษร',
            'max'      => 'ความยาวของ :attribute ต้องไม่เกิน :max ตัวอักษร',
            'size'     => ':attribute ต้องมีความยาว :size หลัก',
            'numeric'  => ':attribute ต้องเป็นตัวเลข',
            'between'  => ':attribute ต้องอยู่ระหว่าง :min ถึง :max',
        ];

        $attributes = [
            'org_name'             => 'ชื่อหน่วยบริการ/หน่วยงาน',
            'org_address'          => 'ที่อยู่หน่วยบริการ',
            'org_province_code'    => 'จังหวัด',
            'org_district_code'    => 'อำเภอ',
            'org_subdistrict_code' => 'ตำบล',
            'org_postcode'         => 'รหัสไปรษณีย์',
            'org_lat'              => 'ละติจูด (Latitude)',
            'org_lng'              => 'ลองจิจูด (Longitude)',
            'org_tel'              => 'หมายเลขโทรศัพท์',
            'working_hours_json'   => 'วัน-เวลาทำการ',
        ];

        // ===== Validate =====
        $validator = Validator::make($request->all(), [
            'org_name'             => ['required', 'string', 'max:255'],
            'org_address'          => ['nullable', 'string', 'max:1000'],
            'org_province_code'    => ['required', 'string'],
            'org_district_code'    => ['required', 'string'],
            'org_subdistrict_code' => ['required', 'string'],
            'org_postcode'         => ['nullable', 'string', 'size:5'],
            'org_lat'              => ['nullable', 'numeric', 'between:-90,90'],
            'org_lng'              => ['nullable', 'numeric', 'between:-180,180'],
            'org_tel'              => ['nullable', 'string', 'max:60'],
            'working_hours_json'   => ['nullable', 'string'],
        ], $messages, $attributes);

        $data = $validator->validate();

        // แปลง JSON → array เพื่อเก็บลงคอลัมน์ array/json
        $data['org_working_hours_json'] = $this->parseWorkingGridJson($request->input('working_hours_json'));

        // กรองฟิลด์ให้ตรงกับ DB
        $unit->fill(collect($data)->only([
            'org_name',
            'org_address',
            'org_province_code',
            'org_district_code',
            'org_subdistrict_code',
            'org_postcode',
            'org_tel',
            'org_lat',
            'org_lng',
            'org_working_hours_json',
        ])->toArray());

        $unit->save();

        flash_notify('บันทึกการแก้ไขแล้ว', 'success');
        return redirect()->route('backend.service-unit.edit');
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

    private function parseWorkingGridJson(?string $json): array
    {
        $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $out  = array_fill_keys($days, []);
        if (!$json) {
            return $out;
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            foreach ($days as $d) {
                $out[$d] = array_values(array_filter(
                    (array) ($data[$d] ?? []),
                    fn($x) => is_string($x) && preg_match('/^\d{2}:\d{2}-\d{2}:\d{2}$/', $x)
                ));
            }
        } catch (\Throwable $e) {
            // noop
        }

        return $out;
    }
}
