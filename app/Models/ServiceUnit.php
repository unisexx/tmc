<?php
// app/Models/ServiceUnit.php

namespace App\Models;

use App\Models\Concerns\CrudActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceUnit extends Model
{
    use HasFactory, CrudActivity;

    protected $table = 'service_units';

    protected $fillable = [
        'org_name',
        'org_affiliation',
        'org_affiliation_other',
        'org_address',
        'org_tel',
        'org_lat',
        'org_lng',
        'org_working_hours',
        'org_working_hours_json',
        'org_province_code',
        'org_district_code',
        'org_subdistrict_code',
        'org_postcode',
    ];

    protected $casts = [
        'org_working_hours_json' => 'array',
        'org_lat'                => 'float',
        'org_lng'                => 'float',
    ];

    protected $appends = [
        'province_title',
        'district_title',
        'subdistrict_title',
        'geo_titles',
    ];

    /* ===== ความสัมพันธ์ผู้ใช้ ===== */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'service_unit_users')
            ->withPivot(['role', 'start_date', 'end_date', 'is_primary'])
            ->withTimestamps();
    }

    /* ===== จังหวัด/อำเภอ/ตำบล ===== */
    public function province()
    {return $this->belongsTo(Province::class, 'org_province_code', 'code');}
    public function district()
    {return $this->belongsTo(District::class, 'org_district_code', 'code');}
    public function subdistrict()
    {return $this->belongsTo(Subdistrict::class, 'org_subdistrict_code', 'code');}

    public function getProvinceTitleAttribute(): ?string
    {return $this->province->title ?? null;}
    public function getDistrictTitleAttribute(): ?string
    {return $this->district->title ?? null;}
    public function getSubdistrictTitleAttribute(): ?string
    {return $this->subdistrict->title ?? null;}

    public function getGeoTitlesAttribute(): string
    {
        return collect([
            $this->province_title,
            $this->district_title,
            $this->subdistrict_title,
        ])->filter()->implode(' / ');
    }

    /* ===== ผลประเมิน ===== */
    public function assessmentForms(): HasMany
    {
        return $this->hasMany(AssessmentForm::class, 'service_unit_id');
    }

    public function assessmentLevels(): HasMany
    {
        return $this->hasMany(AssessmentServiceUnitLevel::class, 'service_unit_id');
    }

    public function serviceUnitLevels(): HasMany
    {
        return $this->hasMany(AssessmentServiceUnitLevel::class, 'service_unit_id');
    }

    public function assessmentLevelFor(int $fiscalYear, int $round): HasOne
    {
        return $this->hasOne(AssessmentServiceUnitLevel::class, 'service_unit_id')
            ->where('assess_year', $fiscalYear)
            ->where('assess_round', $round);
    }

    public function assessmentLevelCurrent(): HasOne
    {
        return $this->assessmentLevelFor(fiscalYearCE(), fiscalRound());
    }

    public function assessmentLevelLatest(): HasOne
    {
        return $this->hasOne(AssessmentServiceUnitLevel::class, 'service_unit_id')
            ->ofMany([
                'assess_year'  => 'max',
                'assess_round' => 'max',
            ]);
    }

    public function assessmentLevelApprovedLatest(): HasOne
    {
        return $this->hasOne(AssessmentServiceUnitLevel::class, 'service_unit_id')
            ->where('approval_status', 'approved')
            ->ofMany([
                'assess_year'  => 'max',
                'assess_round' => 'max',
            ]);
    }

    public function assessmentLevelApprovedCurrent(): HasOne
    {
        return $this->hasOne(AssessmentServiceUnitLevel::class, 'service_unit_id')
            ->where('assess_year', fiscalYearCE())
            ->where('assess_round', fiscalRound())
            ->where('approval_status', 'approved');
    }
}
