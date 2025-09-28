<?php

// app/Models/AssessmentAnswer.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentAnswer extends Model
{
    protected $fillable = ['assessment_id', 'assessment_item_id', 'value', 'remark', 'filePath'];
    public function assessment()
    {return $this->belongsTo(Assessment::class);}
    public function item()
    {return $this->belongsTo(AssessmentItem::class, 'assessment_item_id');}
}
