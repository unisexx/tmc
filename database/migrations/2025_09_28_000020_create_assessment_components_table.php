<?php

// database/migrations/2025_09_28_000020_create_assessment_components_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_components', function (Blueprint $t) {
            $t->id();
            $t->string('compKey')->unique(); // governance, workforce, facility, equipment, process, it
            $t->string('title');
            $t->timestamps();
        });
    }
    public function down(): void
    {Schema::dropIfExists('assessment_components');}
};
