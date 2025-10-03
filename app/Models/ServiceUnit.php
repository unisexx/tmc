<?php
// app/Models/ServiceUnit.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ServiceUnit extends Model
{
    use HasFactory;

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
}
