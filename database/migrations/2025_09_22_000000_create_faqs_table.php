<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();

            // คำถาม
            $table->string('question', 500);

            // คำตอบ
            $table->longText('answer');

            // ใช้สำหรับเรียงลำดับ
            $table->unsignedInteger('ordering')->default(0)->index();

            // เปิด/ปิดการแสดงผล
            $table->boolean('is_active')->default(true)->index();

            // นับจำนวนครั้งที่ถูกดู
            $table->unsignedBigInteger('views')->default(0);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
