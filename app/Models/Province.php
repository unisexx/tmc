<?php
// app/Models/Province.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $table      = 'province';
    protected $primaryKey = 'code';
    public $incrementing  = false;
    protected $keyType    = 'int';
    public $timestamps    = false;

    protected $fillable = ['code', 'title', 'health_region_id', 'comment'];

    public function healthRegion()
    {
        return $this->belongsTo(HealthRegion::class, 'health_region_id');
    }
}
