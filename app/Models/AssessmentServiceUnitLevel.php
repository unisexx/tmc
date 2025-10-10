<?php

namespace App\Models;

use App\Models\Concerns\CrudActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentServiceUnitLevel extends Model
{
    use HasFactory, CrudActivity;

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

    protected $appends = [
        'level_text',
        'level_badge_class',
        'status_text',
        'status_badge_class',
        'approval_text',
        'approval_badge_class',
        'is_locked',
        'can_edit',
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
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
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
    | Accessors (ดึงจาก config กลาง)
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

    public function getStatusTextAttribute(): string
    {
        $map = config('assessment.status_text', []);
        return $map[$this->status] ?? '-';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        $map = config('assessment.status_badge_class', []);
        return $map[$this->status] ?? 'secondary';
    }

    public function getApprovalTextAttribute(): ?string
    {
        $map = config('assessment.approval_text', []);
        return $map[$this->approval_status] ?? null;
    }

    public function getApprovalBadgeClassAttribute(): string
    {
        $map = config('assessment.approval_badge_class', []);
        return $map[$this->approval_status] ?? 'secondary';
    }

    /* ==========================
    | Logic Helper
    ========================== */
    public function getIsLockedAttribute(): bool
    {
        // ล็อกเมื่ออยู่ในสถานะที่ไม่ควรแก้ไขอีก
        return in_array($this->approval_status, ['pending', 'reviewing', 'approved', 'rejected'], true);
    }

    public function getCanEditAttribute(): bool
    {
        // แก้ไขได้เมื่อไม่ล็อก (เช่น ยังไม่ส่ง หรือถูกส่งกลับ returned)
        return !$this->is_locked;
    }
}
