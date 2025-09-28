<?php

// database/migrations/2025_09_28_000040_create_assessment_answers_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_answers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $t->foreignId('assessment_item_id')->constrained()->cascadeOnDelete();
            $t->enum('value', ['yes', 'no', 'na'])->nullable(); // มี / ไม่มี / ไม่เกี่ยวข้อง
            $t->text('remark')->nullable();
            $t->string('filePath')->nullable(); // หลักฐาน
            $t->timestamps();
            $t->unique(['assessment_id', 'assessment_item_id']);
        });
    }
    public function down(): void
    {Schema::dropIfExists('assessment_answers');}
};
