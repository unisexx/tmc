<?php
// database/migrations/2025_09_21_000000_create_news_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->string('category')->nullable();
            $t->string('excerpt', 500)->nullable();
            $t->longText('body')->nullable();
            $t->string('image_path')->nullable();

            // แก้จาก status เป็น is_active
            $t->boolean('is_active')->default(false);

            // ลบ published_at ออก
            $t->unsignedInteger('views')->default(0);
            $t->timestamps();
            $t->softDeletes();

            // index ปรับเหลือ is_active
            $t->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
