<?php
// app/Models/Province.php
namespace App\Models;

use App\Models\Concerns\CrudActivity;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use CrudActivity;
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
