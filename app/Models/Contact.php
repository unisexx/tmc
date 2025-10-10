<?php

namespace App\Models;

use App\Models\Concerns\CrudActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory, CrudActivity;

    protected $fillable = [
        'address',
        'email',
        'tel',
        'fax',
        'map',
        'facebook',
        'youtube',

    ];

    // กำหนดให้ไม่ใช้ auto-increment
    public $incrementing = false;
}
