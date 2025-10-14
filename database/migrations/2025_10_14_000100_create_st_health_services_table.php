<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('st_health_services', function (Blueprint $table) {
            $table->id();
            $table->string('level_code', 20); // basic|medium|advanced
            $table->string('code', 64)->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('default_enabled')->default(true); // ค่าเริ่มต้นเมื่อยังไม่เคยตั้งค่าต่อหน่วย
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('ordering')->default(100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('st_health_services');
    }
};
