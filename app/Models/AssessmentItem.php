<?php

// app/Models/AssessmentItem.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentItem extends Model
{
    protected $fillable = ['assessment_component_id', 'code', 'question', 'forLevel', 'weight', 'isRequired'];
    public function component()
    {return $this->belongsTo(AssessmentComponent::class);}
}
