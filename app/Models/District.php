<?php

// app/Models/District.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table      = 'district';
    protected $primaryKey = 'code';
    public $incrementing  = false;
    protected $keyType    = 'int';
    public $timestamps    = false;
    protected $fillable   = ['code', 'title', 'comment'];
}
