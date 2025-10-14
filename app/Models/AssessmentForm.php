<?php

namespace App\Models;

use App\Models\Concerns\CrudActivity;
use Illuminate\Database\Eloquent\Model;

class AssessmentForm extends Model
{
    use CrudActivity;

    protected $fillable = [
        'service_unit_id',
        'assess_year',
        'assess_round',
        'level_code',
    ];

    public function serviceUnit()
    {
        return $this->belongsTo(ServiceUnit::class);
    }

    public function answers()
    {
        return $this->hasMany(AssessmentAnswer::class);
    }

    public function suggestions()
    {
        return $this->hasMany(AssessmentSuggestion::class);
    }

    // ดึง parent ผ่าน year+round+unit (ตาม relation ที่คุณใช้ใน controller)
    public function suLevel()
    {
        return $this->hasOne(AssessmentServiceUnitLevel::class, 'service_unit_id', 'service_unit_id')
            ->whereColumn('assessment_service_unit_levels.assess_year', 'assessment_forms.assess_year')
            ->whereColumn('assessment_service_unit_levels.assess_round', 'assessment_forms.assess_round');
    }

    protected $appends = [
        'computed_status',
        'computed_approval_status',
    ];

    public function getComputedStatusAttribute()
    {
        return $this->suLevel?->status;
    } // draft|completed

    public function getComputedApprovalStatusAttribute()
    {
        return $this->suLevel?->approval_status;
    } // pending|...

    public function services()
    {
        return $this->belongsToMany(StHealthService::class, 'assessment_form_service')
            ->withPivot('is_enabled')
            ->withTimestamps();
    }

    /** คืนรายการบริการสำหรับระดับของฟอร์ม พร้อมสถานะเปิด/ปิดที่ resolve แล้ว */
    public function resolvedServices()
    {
        $level = $this->level_code;
        $base  = StHealthService::active()->forLevel($level)
            ->orderBy('ordering')->orderBy('id')->get();

        // แผนที่สถานะจาก pivot
        $pivotMap = $this->services->pluck('pivot.is_enabled', 'id');

        return $base->map(function ($svc) use ($pivotMap) {
            $enabled = $pivotMap->has($svc->id)
                ? (bool) $pivotMap->get($svc->id)
                : (bool) $svc->default_enabled;
            $svc->resolved_enabled = $enabled;
            return $svc;
        });
    }
}
