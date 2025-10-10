<?php

// app/Models/AssessmentSection.php
namespace App\Models;

use App\Models\Concerns\CrudActivity;
use Illuminate\Database\Eloquent\Model;

class AssessmentSection extends Model
{
    use CrudActivity;
    protected $fillable = ['assessment_level_id', 'assessment_component_id', 'code', 'title', 'subtitle', 'ordering'];
    public function level()
    {return $this->belongsTo(AssessmentLevel::class, 'assessment_level_id');}
    public function component()
    {return $this->belongsTo(AssessmentComponent::class, 'assessment_component_id');}
    public function questions()
    {return $this->hasMany(AssessmentQuestion::class, 'assessment_section_id')->orderBy('ordering');}
}
