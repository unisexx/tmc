<?php
// app/Models/News.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class News extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'category', 'excerpt', 'body',
        'image_path', 'is_active', 'views',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        // เติม slug อัตโนมัติและรักษาให้ unique หากไม่ส่งมา
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $base = Str::slug($model->title) ?: Str::random(6);
                $slug = $base;
                $i    = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $model->slug = $slug;
            }
        });
    }

    /** ข่าวที่เปิดใช้งาน */
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /** ฟิลเตอร์ค้นหาแบบพื้นฐาน */
    public function scopeFilter($q, array $f)
    {
        // คีย์เวิร์ด
        $q->when($f['q'] ?? null, function ($qq, $kw) {
            $qq->where(function ($w) use ($kw) {
                $w->where('title', 'like', "%{$kw}%")
                    ->orWhere('excerpt', 'like', "%{$kw}%")
                    ->orWhere('body', 'like', "%{$kw}%");
            });
        });

        // หมวดหมู่
        $q->when($f['category'] ?? null, fn($qq, $ct) => $qq->where('category', $ct));

        // is_active: รับได้ทั้ง 0/1 หรือ true/false (string)
        $q->when(isset($f['is_active']) && $f['is_active'] !== '', function ($qq) use ($f) {
            $val = filter_var($f['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $qq->where('is_active', is_null($val) ? (int) $f['is_active'] : $val);
        });

        // ช่วงวันที่อิง created_at (เพราะไม่มี published_at)
        if (($f['from'] ?? null) && ($f['to'] ?? null)) {
            $q->whereBetween('created_at', [
                $f['from'] . ' 00:00:00',
                $f['to'] . ' 23:59:59',
            ]);
        }

        return $q;
    }
}
