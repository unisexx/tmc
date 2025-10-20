<?php
// app/Http/Controllers/Backend/AssessmentServiceConfigController.php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AssessmentServiceConfig;
use App\Models\AssessmentServiceUnitLevel;
use App\Models\StHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssessmentServiceConfigController extends Controller
{
    // หน้าแก้ไขสวิตช์บริการตาม "ระดับหน่วยบริการ"
    public function edit(AssessmentServiceUnitLevel $level)
    {
        $services = StHealthService::active()
            ->forLevel($level->level_code)
            ->orderBy('ordering')->orderBy('id')
            ->get();

        // ดึงสถานะจากตารางใหม่ assessment_service_configs
        $pivot = AssessmentServiceConfig::query()
            ->where('assessment_service_unit_level_id', $level->id)
            ->pluck('is_enabled', 'st_health_service_id');

        foreach ($services as $svc) {
            $svc->resolved_enabled = $pivot->has($svc->id)
                ? (bool) $pivot[$svc->id]
                : (bool) $svc->default_enabled;
        }

        // หมายเหตุ: ปรับ view ตามโปรเจกต์ของคุณ
        return view('backend.st_health_services.setting_per_level', [
            'level'    => $level,
            'services' => $services,
        ]);
    }

    // บันทึกแบบฟอร์มแบบเหมาเป็นชุด
    public function update(Request $request, AssessmentServiceUnitLevel $level)
    {
        $serviceIds = StHealthService::active()
            ->forLevel($level->level_code)
            ->pluck('id');

        $rows = [];
        $now  = now();
        foreach ($serviceIds as $id) {
            $rows[] = [
                'assessment_service_unit_level_id' => $level->id,
                'st_health_service_id'             => $id,
                'is_enabled'                       => $request->boolean("svc_$id"),
                'created_at'                       => $now,
                'updated_at'                       => $now,
            ];
        }

        DB::transaction(function () use ($rows) {
            AssessmentServiceConfig::upsert(
                $rows,
                ['assessment_service_unit_level_id', 'st_health_service_id'],
                ['is_enabled', 'updated_at']
            );
        });

        return back()->with('flash_success', 'บันทึกการตั้งค่าการแสดงผลแล้ว');
    }

    // สลับสถานะรายรายการแบบ AJAX
    public function toggle(Request $request, AssessmentServiceUnitLevel $level)
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer'],
            'enabled'    => ['required', 'boolean'],
        ]);

        // ไม่กรองด้วย forLevel เพื่อให้ตรงกับรายการที่แสดงบนหน้า
        $service = StHealthService::query()->whereKey($data['service_id'])->firstOrFail();

        AssessmentServiceConfig::updateOrCreate(
            [
                'assessment_service_unit_level_id' => $level->id,
                'st_health_service_id'             => $service->id,
            ],
            [
                'is_enabled' => (bool) $data['enabled'],
            ]
        );

        return response()->json([
            'ok'         => true,
            'service_id' => $service->id,
            'enabled'    => (bool) $data['enabled'],
        ]);
    }
}
