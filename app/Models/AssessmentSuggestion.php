<?php

// app/Models/AssessmentAnswer.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// app/Models/AssessmentSuggestion.php
class AssessmentSuggestion extends Model
{
    protected $fillable = ['assessment_form_id', 'text', 'attachment_path'];
}
