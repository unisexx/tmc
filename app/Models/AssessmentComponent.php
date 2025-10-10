<?php

// app/Models/AssessmentComponent.php
namespace App\Models;

use App\Models\Concerns\CrudActivity;
use Illuminate\Database\Eloquent\Model;

class AssessmentComponent extends Model
{
    use CrudActivity;

    protected $fillable = ['no', 'name', 'short_name'];

    public function questions()
    {
        return $this->hasMany(AssessmentQuestion::class);
    }
}
