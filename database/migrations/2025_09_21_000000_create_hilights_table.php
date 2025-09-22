<?php

// database/migrations/2025_09_21_000000_create_hilights_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hilights', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image_path')->nullable();
            $table->string('link_url')->nullable();
            $table->unsignedInteger('ordering')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

    }
    public function down(): void
    {
        Schema::dropIfExists('hilights');
    }
};
