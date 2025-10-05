<?php

// app/Models/AssessmentComponent.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentComponent extends Model
{
    protected $fillable = ['no', 'name', 'short_name'];

    public function questions()
    {
        return $this->hasMany(AssessmentQuestion::class);
    }
}
