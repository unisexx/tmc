<?php

// app/Models/AssessmentComponent.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentComponent extends Model
{
    protected $fillable = ['compKey', 'title'];
    public function items()
    {return $this->hasMany(AssessmentItem::class);}
}
