<?php

// app/Models/Subdistrict.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subdistrict extends Model
{
    protected $table      = 'subdistrict';
    protected $primaryKey = 'code';
    public $incrementing  = false;
    protected $keyType    = 'int';
    public $timestamps    = false;
    protected $fillable   = ['code', 'title', 'comment'];
}
