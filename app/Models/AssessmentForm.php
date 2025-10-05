<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentForm extends Model
{
    protected $fillable = ['service_unit_id', 'assess_year', 'assess_round', 'level_code', 'status', 'submitted_at', 'reviewer_id', 'reviewed_at', 'review_note'];
    protected $casts    = ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];

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

    public function scopeMine($q)
    {
        return $q->where('service_unit_id', session('current_service_unit_id'));
    }

    // แผนที่ชื่อระดับและ badge
    protected const LEVEL_TEXT = [
        'basic'    => 'ระดับพื้นฐาน',
        'medium'   => 'ระดับกลาง',
        'advanced' => 'ระดับสูง',
    ];
    protected const LEVEL_BADGE = [
        'basic'    => 'info',
        'medium'   => 'warning',
        'advanced' => 'success',
    ];

    // Accessors
    public function getLevelTextAttribute(): string
    {
        return static::LEVEL_TEXT[$this->level_code] ?? '-';
    }

    public function getLevelBadgeClassAttribute(): string
    {
        return static::LEVEL_BADGE[$this->level_code] ?? 'secondary';
    }
}
