<?php

// database/migrations/2025_09_26_000001_add_health_region_id_to_province_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('province', function (Blueprint $table) {
            $table->unsignedTinyInteger('health_region_id')->nullable()->after('TITLE');
            $table->foreign('health_region_id')
                ->references('id')->on('health_regions')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->index('health_region_id');
        });
    }

    public function down(): void
    {
        Schema::table('province', function (Blueprint $table) {
            $table->dropForeign(['health_region_id']);
            $table->dropColumn('health_region_id');
        });
    }
};
