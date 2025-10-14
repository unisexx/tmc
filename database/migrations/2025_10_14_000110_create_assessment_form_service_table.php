<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_form_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_form_id')->constrained('assessment_forms')->cascadeOnDelete();
            $table->foreignId('st_health_service_id')->constrained('st_health_services')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->unique(['assessment_form_id', 'st_health_service_id'], 'u_form_service');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('assessment_form_service');
    }
};
