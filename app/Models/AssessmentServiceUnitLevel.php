<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentServiceUnitLevel extends Model
{
    use HasFactory;

    protected $table = 'assessment_service_unit_levels';

    protected $fillable = [
        'service_unit_id',
        'assess_year',
        'assess_round',
        'user_id',

        'status',
        'last_question',

        'q1',
        'q2',
        'q31',
        'q32',
        'q4',

        'level',
        'decided_at',

        'approval_status',
        'approval_remark',
        'approved_by',
        'approved_at',

        'created_by',
        'updated_by',
        'submitted_by',
        'submitted_at',

        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'assess_year'  => 'integer',
        'assess_round' => 'integer',
        'decided_at'   => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at'  => 'datetime',
    ];

    /* ==========================
    | ความสัมพันธ์
    ========================== */
    public function serviceUnit()
    {
        return $this->belongsTo(ServiceUnit::class, 'service_unit_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // ผู้ทำแบบประเมิน
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by'); // ผู้อนุมัติ
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function answers()
    {
        return $this->hasMany(AssessmentAnswer::class, 'service_unit_level_id');
    }

    public function form()
    {
        return $this->hasOne(AssessmentForm::class, 'service_unit_id', 'service_unit_id')
            ->whereColumn('assess_year', 'assessment_service_unit_levels.assess_year')
            ->whereColumn('assess_round', 'assessment_service_unit_levels.assess_round');
    }

    /* ==========================
    | Accessors Helper
    ========================== */
    public function getLevelTextAttribute(): ?string
    {
        $map = config('assessment.level_text', []);
        return $map[$this->level] ?? null;
    }

    public function getLevelBadgeClassAttribute(): string
    {
        $map = config('assessment.level_badge_class', []);
        return $map[$this->level] ?? 'secondary';
    }

    public function getApprovalTextAttribute(): ?string
    {
        return match ($this->approval_status) {
            'pending'  => 'รอดำเนินการ',
            'approved' => 'อนุมัติ',
            'rejected' => 'ไม่อนุมัติ',
            default    => null,
        };
    }

    public function getApprovalBadgeClassAttribute(): string
    {
        return match ($this->approval_status) {
            'pending'  => 'secondary',
            'approved' => 'success',
            'rejected' => 'danger',
            default    => 'secondary',
        };
    }
}
