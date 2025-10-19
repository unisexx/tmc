<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_service_configs', function (Blueprint $table) {
            $table->id();

            // เชื่อมกับตาราง assessment_service_unit_levels (ตั้งชื่อ constraint เอง)
            $table->unsignedBigInteger('assessment_service_unit_level_id');
            $table->foreign('assessment_service_unit_level_id', 'fk_asc_level')
                ->references('id')
                ->on('assessment_service_unit_levels')
                ->cascadeOnDelete();

            // เชื่อมกับตาราง st_health_services (ตั้งชื่อ constraint เอง)
            $table->unsignedBigInteger('st_health_service_id');
            $table->foreign('st_health_service_id', 'fk_asc_service')
                ->references('id')
                ->on('st_health_services')
                ->cascadeOnDelete();

            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['assessment_service_unit_level_id', 'st_health_service_id'], 'u_level_service');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_service_configs');
    }
};
