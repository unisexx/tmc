<?php

namespace App\Models;

use App\Models\Concerns\CrudActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CookiePolicy extends Model
{
    use HasFactory, CrudActivity;
    protected $fillable  = ['title', 'description'];
    public $incrementing = false;
    public $timestamps   = true;
}
