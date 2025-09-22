<?php

// app/Models/Hilight.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hilight extends Model
{
    protected $fillable = [
        'title', 'image_path', 'link_url', 'ordering', 'is_active', 'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_at'  => 'datetime',
        'end_at'    => 'datetime',
    ];
}
