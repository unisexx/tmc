<?php

namespace App\Models;

use App\Models\Concerns\CrudActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faq extends Model
{
    use HasFactory, SoftDeletes, CrudActivity;

    protected $table = 'faqs';

    protected $fillable = [
        'question',
        'answer',
        'ordering',
        'is_active',
        'views',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ordering'  => 'integer',
        'views'     => 'integer',
    ];

    /**
     * Scope สำหรับแสดงเฉพาะที่ active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Accessor: แปลงคำตอบเป็นแบบย่อ (ใช้ใน index list)
     */
    public function getShortAnswerAttribute(): string
    {
        return str($this->answer)->limit(100);
    }
}
