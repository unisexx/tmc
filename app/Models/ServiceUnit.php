<?php
// app/Models/ServiceUnit.php
namespace App\Models;

use App\Models\Concerns\CrudActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
    ];

    /**
     * ความสัมพันธ์: หน่วยบริการ ↔ ผู้ใช้ (ผ่าน service_unit_users)
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'service_unit_users')
            ->withPivot(['role', 'start_date', 'end_date', 'is_primary'])
            ->withTimestamps();
    }

    // เพิ่ม relations จังหวัด/อำเภอ/ตำบล
    public function province()
    {return $this->belongsTo(Province::class, 'org_province_code', 'code');}
    public function district()
    {return $this->belongsTo(District::class, 'org_district_code', 'code');}
    public function subdistrict()
    {return $this->belongsTo(Subdistrict::class, 'org_subdistrict_code', 'code');}

    // (สะดวกใช้ใน Blade) ชื่อพื้นที่รวม
    public function getGeoTitlesAttribute(): string
    {
        $p = $this->province?->title;
        $d = $this->district?->title;
        $s = $this->subdistrict?->title;
        return collect([$p, $d, $s])->filter()->implode(' / ');
    }
}
