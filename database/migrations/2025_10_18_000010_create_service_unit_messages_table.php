<?php
// database/migrations/2025_10_18_000010_create_service_unit_messages_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_unit_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('service_unit_id')->index(); // อ้างอิงหน่วยบริการ
            $table->string('to_name', 255)->nullable();             // ชื่อหน่วย/ผู้รับที่แสดง
            $table->string('to_email', 255)->nullable();            // อีเมลปลายทางที่ใช้ส่ง
            $table->string('from_name', 255);
            $table->string('from_email', 255)->index();
            $table->string('subject', 255)->nullable();
            $table->text('body');

            // ระบบ
            $table->string('ip', 45)->nullable()->index();
            $table->string('user_agent', 512)->nullable();

            // เวิร์กโฟลว์ต่อหน่วย
            $table->enum('status', ['new', 'in_progress', 'done', 'spam'])->default('new')->index();
            $table->timestamp('read_at')->nullable();
            $table->unsignedBigInteger('handled_by')->nullable()->index();
            $table->timestamp('handled_at')->nullable();

            // ปักธงสแปมเบื้องต้น
            $table->boolean('is_spam')->default(false)->index();
            $table->unsignedTinyInteger('spam_score')->default(0);

            $table->timestamps();

            // FK สมมติว่ามีตาราง service_units
            $table->foreign('service_unit_id')->references('id')->on('service_units')->onDelete('cascade');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('service_unit_messages');
    }
};
