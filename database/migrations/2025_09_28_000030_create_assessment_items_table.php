<?php

// database/migrations/2025_09_28_000030_create_assessment_items_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('assessment_component_id')->constrained()->cascadeOnDelete();
            $t->string('code')->index(); // รหัสข้อ เช่น GOV-1.1
            $t->text('question');        // คำถาม/ตัวชี้วัด
            $t->enum('forLevel', ['basic', 'medium', 'advanced'])->index();
            $t->unsignedSmallInteger('weight')->default(1);
            $t->boolean('isRequired')->default(true);
            $t->timestamps();
            $t->unique(['assessment_component_id', 'code']);
        });
    }
    public function down(): void
    {Schema::dropIfExists('assessment_items');}
};
