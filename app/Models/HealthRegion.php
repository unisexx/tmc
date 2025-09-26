<?php
// app/Models/HealthRegion.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthRegion extends Model
{
    protected $fillable = ['code', 'title', 'short_title', 'hq_province', 'phone', 'lat', 'lng'];

    public function provinces()
    {
        // ตารางจังหวัดของคุณชื่อ 'province' และคอลัมน์ชื่อ 'health_region_id'
        return $this->hasMany(Province::class, 'health_region_id');
    }
}
