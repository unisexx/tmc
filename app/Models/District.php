<?php

// app/Models/District.php
namespace App\Models;

use App\Models\Concerns\CrudActivity;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use CrudActivity;
    protected $table      = 'district';
    protected $primaryKey = 'code';
    public $incrementing  = false;
    protected $keyType    = 'int';
    public $timestamps    = false;
    protected $fillable   = ['code', 'title', 'comment'];
}
