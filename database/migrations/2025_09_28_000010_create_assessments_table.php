<?php

// database/migrations/2025_09_28_000010_create_assessments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('service_unit_id')->constrained()->cascadeOnDelete();
            $t->string('fiscalYear', 4)->index();        // เช่น 2569
            $t->enum('round', ['1', '2'])->default('1'); // รอบที่ 1 หรือ 2
            $t->enum('level', ['basic', 'medium', 'advanced'])->index();
            $t->enum('status', ['draft', 'submitted', 'reviewed'])->default('draft')->index();
            $t->timestamp('submittedAt')->nullable();
            $t->timestamps();

            $t->unique(['service_unit_id', 'fiscalYear', 'round']);
        });
    }
    public function down(): void
    {Schema::dropIfExists('assessments');}
};
