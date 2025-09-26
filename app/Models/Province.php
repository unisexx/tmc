<?php
// app/Models/Province.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $table      = 'province'; // ตามที่มีอยู่
    protected $primaryKey = 'CODE';     // ถ้า CODE เป็น PK จริง
    public $incrementing  = false;      // ถ้า CODE ไม่ auto-increment
    protected $keyType    = 'int';      // ปรับตามชนิด CODE

    public function healthRegion()
    {
        return $this->belongsTo(HealthRegion::class, 'health_region_id');
    }
}
