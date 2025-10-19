<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StHealthService extends Model
{
    protected $table    = 'st_health_services';
    protected $fillable = ['level_code', 'code', 'name', 'description', 'default_enabled', 'is_active', 'ordering'];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    // app/Models/StHealthService.php
    public function scopeForLevel($q, ?string $level)
    {
        $key = match ($level) {
            'basic'    => 'basic',
            'medium'   => 'medium',
            'advanced' => 'advanced',
            default    => null,
        };

        return $key ? $q->where('level_code', $key) : $q->whereRaw('1=0');
    }

}
