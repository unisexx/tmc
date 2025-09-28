<?php

// app/Models/Assessment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    protected $fillable = ['service_unit_id', 'fiscalYear', 'round', 'level', 'status', 'submittedAt'];
    protected $casts    = ['submittedAt' => 'datetime'];

    public function serviceUnit()
    {return $this->belongsTo(ServiceUnit::class);}
    public function answers()
    {return $this->hasMany(AssessmentAnswer::class);}

    // helper: ดึงข้อคำถามทั้งหมดตาม level
    public function itemsByLevel()
    {
        return AssessmentItem::query()
            ->whereIn('assessment_component_id', AssessmentComponent::pluck('id'))
            ->where('forLevel', $this->level)
            ->with('component')
            ->orderBy('assessment_component_id')
            ->orderBy('code')
            ->get();
    }
}
