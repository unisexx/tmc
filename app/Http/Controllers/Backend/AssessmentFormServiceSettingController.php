<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AssessmentForm;
use App\Models\StHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssessmentFormServiceSettingController extends Controller
{
    public function edit(AssessmentForm $form)
    {
        $level    = $form->level_code;
        $services = StHealthService::active()->forLevel($level)->orderBy('ordering')->orderBy('id')->get();
        $pivot    = $form->services()->pluck('assessment_form_service.is_enabled', 'st_health_services.id');

        foreach ($services as $svc) {
            $svc->resolved_enabled = $pivot->has($svc->id) ? (bool) $pivot[$svc->id] : (bool) $svc->default_enabled;
        }

        return view('backend.st_health_services.setting_per_form', compact('form', 'services'));
    }

    public function update(Request $request, AssessmentForm $form)
    {
        $level    = $form->level_code;
        $services = StHealthService::active()->forLevel($level)->pluck('id');

        $payload = [];
        foreach ($services as $id) {
            $payload[$id] = ['is_enabled' => (bool) $request->boolean("svc_$id")];
        }

        DB::transaction(function () use ($form, $payload) {
            $form->services()->sync($payload);
        });

        return back()->with('flash_success', 'บันทึกการตั้งค่าการแสดงผลแล้ว');
    }

    // ====== เพิ่มเมธอดใหม่สำหรับ AJAX toggle ======
    public function toggle(Request $request, AssessmentForm $form)
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer'],
            'enabled'    => ['required', 'boolean'],
        ]);

        $svcId   = (int) $data['service_id'];
        $enabled = (bool) $data['enabled'];

        // ตรวจสอบสิทธิ์และความสอดคล้องของระดับ
        $service = StHealthService::active()
            ->forLevel($form->level_code)
            ->whereKey($svcId)
            ->firstOrFail();

        DB::transaction(function () use ($form, $svcId, $enabled) {
            $exists = $form->services()->where('st_health_services.id', $svcId)->exists();
            if ($exists) {
                $form->services()->updateExistingPivot($svcId, ['is_enabled' => $enabled]);
            } else {
                $form->services()->attach($svcId, ['is_enabled' => $enabled]);
            }
        });

        return response()->json([
            'ok'         => true,
            'service_id' => $svcId,
            'enabled'    => $enabled,
        ]);
    }
}
