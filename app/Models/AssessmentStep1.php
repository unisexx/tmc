<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentStep1 extends Model
{
    use HasFactory;

    protected $table = 'assessment_step1';

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

    /* ==========================
    | Accessors Helper
    ========================== */
    public function getLevelTextAttribute(): ?string
    {
        return match ($this->level) {
            'basic'    => 'ระดับพื้นฐาน',
            'medium'   => 'ระดับกลาง',
            'advanced' => 'ระดับสูง',
            default    => null,
        };
    }

    public function getLevelBadgeClassAttribute(): string
    {
        return match ($this->level) {
            'basic'    => 'info',    // ฟ้า
            'medium'   => 'warning', // เหลือง
            'advanced' => 'danger',  // แดง
            default    => 'secondary',
        };
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
