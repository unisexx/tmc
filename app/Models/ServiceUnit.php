<?php
// app/Models/ServiceUnit.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceUnit extends Model
{
    protected $fillable = ['unitCode', 'unitName', 'provinceCode', 'regionCode'];
    public function assessments()
    {return $this->hasMany(Assessment::class);}
}
