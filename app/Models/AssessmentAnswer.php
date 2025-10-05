<?php

// app/Models/AssessmentAnswer.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// app/Models/AssessmentAnswer.php
class AssessmentAnswer extends Model
{
    protected $fillable = ['assessment_form_id', 'assessment_question_id', 'answer_bool', 'answer_text', 'attachment_path'];

    protected $casts = [
        'answer_bool' => 'boolean', // 1->true, 0->false, null->null
    ];

    public function question()
    {
        // ระบุ FK ให้ตรงชื่อคอลัมน์จริง
        return $this->belongsTo(AssessmentQuestion::class, 'assessment_question_id');
    }
}
