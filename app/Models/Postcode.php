<?php

// app/Models/Postcode.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Postcode extends Model
{
    protected $table   = 'postcode';
    public $timestamps = false;

    // columns: code (char(5)/int), title (text), comment (nullable)
    protected $fillable = ['code', 'title', 'comment'];
}
