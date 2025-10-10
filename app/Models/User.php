<?php

namespace App\Models;

use App\Models\Concerns\CrudActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, CrudActivity;

    protected $fillable = [
        'name',     // ชื่อ-สกุล (ใช้กับระบบ Auth มาตรฐาน)
        'email',    // อีเมล (ล็อกอิน + ติดต่อ)
        'username', // Username สำหรับเข้าใช้งาน
        'phone',    // เบอร์โทรศัพท์ผู้ใช้
        'position', // ตำแหน่งงาน
        'password', // รหัสผ่าน (hash แล้ว)

        /* -------------------- ข้อมูล TOR --------------------*/
        'reg_purpose',                 // วัตถุประสงค์การลงทะเบียน (array: หน่วยบริการ/สสจ./สคร.)
        'reg_supervise_province_code', // รหัสจังหวัดที่กำกับดูแล (กรณีเลือก สสจ.)
        'reg_supervise_region_id',     // รหัสเขตสุขภาพที่กำกับดูแล (กรณีเลือก สคร.)

        /* -------------------- ข้อมูลหน่วยงาน --------------------*/
        // 'org_name',               // ชื่อหน่วยบริการ/หน่วยงาน
        // 'org_affiliation',        // สังกัด (เช่น กรมควบคุมโรค, กรมการแพทย์ ฯลฯ)
        // 'org_affiliation_other',  // กรณีเลือก "อื่น ๆ" ให้ระบุเพิ่มเติม
        // 'org_address',            // ที่อยู่หน่วยงาน
        // 'org_tel',                // เบอร์โทรศัพท์หน่วยงาน
        // 'org_lat',                // พิกัด Latitude
        // 'org_lng',                // พิกัด Longitude
        // 'org_working_hours',      // คำอธิบายเวลาทำการ (ข้อความอิสระ)
        // 'org_working_hours_json', // เวลาทำการแบบโครงสร้าง (array JSON: วัน-เวลา-หมายเหตุ)

        /* -------------------- ผู้ลงทะเบียน --------------------*/
        'contact_cid',      // บัตรประชาชน 13 หลัก
        'contact_name',     // ชื่อ-สกุลผู้ลงทะเบียน
        'contact_position', // ตำแหน่งของผู้ลงทะเบียน
        'contact_mobile',   // เบอร์มือถือผู้ลงทะเบียน

        /* -------------------- เอกสารยืนยันเจ้าหน้าที่ --------------------*/
        'officer_doc_path',        // path ไฟล์เอกสารยืนยันเจ้าหน้าที่
        'officer_doc_verified_at', // วันที่ตรวจสอบ/อนุมัติเอกสารแล้ว
        'officer_doc_verified_by', // user_id ของผู้ตรวจสอบเอกสาร

        /* -------------------- PDPA / Consent --------------------*/
        'pdpa_accepted_at', // วันที่-เวลา ที่ผู้ใช้กดยอมรับ PDPA
        'pdpa_version',     // เวอร์ชันประกาศความเป็นส่วนตัวที่ยอมรับ
        'consent_log',      // Log ความยินยอม (IP / UA / เวลา ฯลฯ) แบบ JSON

        /* -------------------- สถานะการอนุมัติ --------------------*/
        'reg_status',      // สถานะการลงทะเบียน (pending/approved/rejected)
        'reg_review_note', // หมายเหตุ/เหตุผลการพิจารณา
        'approved_at',     // วันที่อนุมัติ
        'approved_by',     // user_id ของผู้อนุมัติ

        /* -------------------- อนุญาติให้ login --------------------*/
        'is_active',

        /* -------------------- สิทธิ์การใช้งาน --------------------*/
        'role_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at'       => 'datetime',
        'password'                => 'hashed',
        'reg_purpose'             => 'array',
        'org_working_hours_json'  => 'array',
        'pdpa_accepted_at'        => 'datetime',
        'officer_doc_verified_at' => 'datetime',
        'approved_at'             => 'datetime',
        'consent_log'             => 'array',
        'is_active'               => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * ความสัมพันธ์: ผู้ใช้ ↔ หน่วยบริการ (ผ่าน service_unit_users)
     */
    public function serviceUnits(): BelongsToMany
    {
        return $this->belongsToMany(ServiceUnit::class, 'service_unit_users')
            ->withPivot(['role', 'start_date', 'end_date', 'is_primary'])
            ->withTimestamps();
    }

    public function getRegPurposeLabelsAttribute(): array
    {
        $map = [
            'T' => 'หน่วยบริการสุขภาพผู้เดินทาง',
            'P' => 'ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับจังหวัด (สสจ.)',
            'R' => 'ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับเขต (สคร.)',
        ];

        return collect(explode(',', (string) $this->reg_purpose))
            ->map(fn($code) => $map[$code] ?? $code)
            ->all();
    }

    // app/Models/User.php

    public function getRegPurposeLabelsWithColorAttribute(): array
    {
        // แปลง reg_purpose เป็น array ของ code
        $codes = $this->reg_purpose_codes ?? [];

        $map = [
            'T' => ['label' => 'หน่วยบริการสุขภาพผู้เดินทาง', 'class' => 'badge bg-light-secondary'],
            'P' => ['label' => 'ผู้กำกับดูแลระดับจังหวัด (สสจ.)', 'class' => 'badge bg-light-secondary'],
            'R' => ['label' => 'ผู้กำกับดูแลระดับเขต (สคร.)', 'class' => 'badge bg-light-secondary'],
        ];

        $result = [];
        foreach ($codes as $code) {
            if (isset($map[$code])) {
                $result[] = $map[$code];
            }
        }

        return $result;
    }

    /**
     * จังหวัดที่กำกับดูแล (ใช้เมื่อ reg_purpose มี 'P')
     * FK: users.reg_supervise_province_code -> province.code
     */
    public function superviseProvince()
    {
        return $this->belongsTo(Province::class, 'reg_supervise_province_code', 'code');
    }

    /**
     * สคร. ที่กำกับดูแล (ใช้เมื่อ reg_purpose มี 'R')
     * FK: users.reg_supervise_region_id -> health_regions.id
     */
    public function superviseRegion()
    {
        return $this->belongsTo(HealthRegion::class, 'reg_supervise_region_id', 'id');
    }

    /**
     * แปลงค่า reg_purpose ให้เป็นอาร์เรย์โค้ดแบบยืดหยุ่น (รองรับ JSON, CSV, ตัวอักษรเดี่ยว)
     * ตัวอย่างผลลัพธ์: ['T'], ['P'], ['R'], หรือหลายค่าเช่น ['P','R']
     */
    public function getRegPurposeCodesAttribute(): array
    {
        $v = $this->attributes['reg_purpose'] ?? null;

        if (is_array($v)) {
            return array_values(array_filter(array_map('trim', $v)));
        }

        if (is_string($v)) {
            // ลอง JSON ก่อน
            $j = json_decode($v, true);
            if (is_array($j)) {
                return array_values(array_filter(array_map('trim', $j)));
            }
            // เผื่อบันทึกเป็นตัวอักษรเดี่ยวหรือ CSV
            $v = trim($v, " \t\n\r\0\x0B[]\"'");
            if ($v === '') {
                return [];
            }

            return array_values(array_filter(array_map('trim', explode(',', $v))));
        }

        return [];
    }

    /**
     * เช็คว่ามี purpose code นั้น ๆ ไหม (เช่น 'P' หรือ 'R')
     */
    public function hasPurpose(string $code): bool
    {
        return in_array(strtoupper($code), $this->reg_purpose_codes ?? [], true);
    }

    /**
     * คืนข้อความสถานะลงทะเบียน (ภาษาไทย)
     */
    public function getRegStatusTextAttribute(): string
    {
        $raw = $this->attributes['reg_status'] ?? null;

        return match ($raw) {
            'อนุมัติ', 'approved'    => 'อนุมัติ',
            'ไม่อนุมัติ', 'rejected' => 'ไม่อนุมัติ',
            'รอตรวจสอบ', 'pending', null => 'รอตรวจสอบ',
            default => 'รอตรวจสอบ',
        };
    }

    /**
     * คืนคลาส badge สำหรับสถานะลงทะเบียน
     */
    public function getRegStatusBadgeClassAttribute(): string
    {
        return match ($this->reg_status_text) {
            'อนุมัติ'    => 'text-bg-primary',
            'ไม่อนุมัติ' => 'text-bg-danger',
            default      => 'text-bg-warning text-dark',
        };
    }

    /**
     * คลาส badge สำหรับ Role ของผู้ใช้
     * ใช้คู่กับ Bootstrap/Light Able เช่น: text-bg-primary / text-bg-warning
     */
    public function getRoleBadgeClassAttribute(): string
    {
        $id = $this->role?->id;

        return match ($id) {
            2       => 'text-bg-danger',
            3       => 'text-bg-primary',
            4, 5 => 'text-bg-warning',
            default => 'text-bg-secondary',
        };
    }

    public function isAdmin(): bool
    {
        // ถ้าใช้ Spatie\Permission
        return $this->hasRole('Admin');

        // หรือถ้าใช้การตรวจด้วยสิทธิ์เฉพาะเจาะจง
        // return $this->roles()->where('name', 'Admin')->exists();
    }

}
