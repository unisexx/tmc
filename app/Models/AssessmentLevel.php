<?php

// app/Models/AssessmentLevel.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentLevel extends Model
{
    protected $fillable = ['code', 'name'];
    public function questions()
    {return $this->hasMany(AssessmentQuestion::class);}
}
