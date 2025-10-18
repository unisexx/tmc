<?php
// database/migrations/2025_10_18_000001_create_contact_messages_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255);
            $table->string('email', 255)->index();
            $table->string('phone', 50)->nullable();
            $table->string('subject', 255);
            $table->text('message');

            // ระบบ
            $table->string('ip', 45)->nullable()->index();
            $table->string('user_agent', 512)->nullable();

            // สถานะการจัดการ
            $table->enum('status', ['new', 'in_progress', 'done', 'spam'])->default('new')->index();
            $table->unsignedBigInteger('handled_by')->nullable()->index();
            $table->timestamp('handled_at')->nullable();
            $table->timestamp('read_at')->nullable();

            // การคัดแยกสแปมเบื้องต้น/แท็ก
            $table->boolean('is_spam')->default(false)->index();
            $table->unsignedTinyInteger('spam_score')->default(0); // 0-100
            $table->json('tags')->nullable();                      // เช่น ["ระบบ","ร้องเรียน"]

            $table->timestamps();

            $table->index(['created_at', 'status']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
