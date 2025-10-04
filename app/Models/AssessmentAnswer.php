<?php

// app/Models/AssessmentAnswer.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// app/Models/AssessmentAnswer.php
class AssessmentAnswer extends Model
{
    protected $fillable = ['assessment_form_id', 'assessment_question_id', 'answer_bool', 'answer_text', 'attachment_path'];
    public function question()
    {return $this->belongsTo(AssessmentQuestion::class);}
}
