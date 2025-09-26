<?php

// database/migrations/2025_09_26_000000_create_health_regions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_regions', function (Blueprint $table) {
            $table->tinyIncrements('id');              // 1..13
            $table->tinyInteger('code')->unique();     // 1..13
            $table->string('title');                   // ชื่อทางการ เช่น สำนักงานป้องกันควบคุมโรค/เขตสุขภาพที่ 1 เชียงใหม่
            $table->string('short_title')->nullable(); // สคร.1, สคร.2 ...
            $table->string('hq_province')->nullable(); // จังหวัดที่ตั้งสำนักงาน เช่น เชียงใหม่
            $table->string('phone')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_regions');
    }
};
