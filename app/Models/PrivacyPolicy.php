<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivacyPolicy extends Model
{
    use HasFactory;
    protected $fillable  = ['title', 'description'];
    public $incrementing = false;
    public $timestamps   = true;
}
