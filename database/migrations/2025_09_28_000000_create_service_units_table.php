<?php

// database/migrations/2025_09_28_000000_create_service_units_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_units', function (Blueprint $t) {
            $t->id();
            $t->string('unitCode')->unique()->index(); // รหัสหน่วย (อปท./รพ.)
            $t->string('unitName');
            $t->string('provinceCode', 10)->nullable();
            $t->string('regionCode', 10)->nullable(); // สคร. 1-13 (ถ้ามี)
            $t->timestamps();
        });
    }
    public function down(): void
    {Schema::dropIfExists('service_units');}
};
