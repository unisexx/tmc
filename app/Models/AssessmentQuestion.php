<?php

namespace App\Models;

use App\Models\Concerns\CrudActivity;
use Illuminate\Database\Eloquent\Model;

// app/Models/AssessmentQuestion.php
class AssessmentQuestion extends Model
{
    use CrudActivity;

    protected $fillable = ['assessment_level_id', 'assessment_component_id', 'code', 'text', 'answer_type', 'ordering', 'is_active'];

    public function level()
    {
        return $this->belongsTo(AssessmentLevel::class, 'assessment_level_id');
    }

    public function component()
    {
        return $this->belongsTo(AssessmentComponent::class, 'assessment_component_id');
    }

    public function section()
    {
        return $this->belongsTo(AssessmentSection::class, 'assessment_section_id');
    }
}
