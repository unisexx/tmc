<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentServiceConfig extends Model
{
    use HasFactory;

    protected $table = 'assessment_service_configs';

    protected $fillable = [
        'assessment_service_unit_level_id',
        'st_health_service_id',
        'is_enabled',
    ];

    /**
     * ความสัมพันธ์กับระดับของหน่วยบริการ (ระดับพื้นฐาน/กลาง/สูง)
     */
    public function serviceUnitLevel()
    {
        return $this->belongsTo(AssessmentServiceUnitLevel::class, 'assessment_service_unit_level_id');
    }

    /**
     * ความสัมพันธ์กับตารางบริการสุขภาพ (st_health_services)
     */
    public function healthService()
    {
        return $this->belongsTo(StHealthService::class, 'st_health_service_id');
    }
}
