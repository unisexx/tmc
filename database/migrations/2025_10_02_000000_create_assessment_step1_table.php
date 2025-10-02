<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_step1', function (Blueprint $table) {
            $table->id()->comment('Primary Key');

            // อ้างอิงหน่วยบริการและรอบการประเมิน
            $table->unsignedBigInteger('service_unit_id')->comment('หน่วยบริการสุขภาพผู้เดินทาง');
            $table->unsignedSmallInteger('assess_year')->comment('ปีการประเมิน (ค.ศ.)');
            $table->unsignedTinyInteger('assess_round')->comment('รอบการประเมิน (1 หรือ 2)');

            // ผู้ทำแบบประเมิน
            $table->unsignedBigInteger('user_id')->comment('ผู้ทำแบบประเมิน (user login)');

            // สถานะการทำแบบประเมิน
            $table->enum('status', ['draft', 'completed'])->default('draft')->comment('สถานะการทำแบบประเมิน draft=ยังไม่เสร็จ, completed=เสร็จแล้ว');
            $table->enum('last_question', ['q1', 'q2', 'q31', 'q32', 'q4', 'done'])->default('q1')->comment('ข้อคำถามล่าสุดที่ตอบ');

            // คำตอบของแต่ละข้อ
            $table->enum('q1', ['have', 'none'])->comment('ข้อ 1: มีแพทย์ประจำ/หมุนเวียนหรือไม่');
            $table->enum('q2', ['tm', 'other'])->nullable()->comment('ข้อ 2: ประเภทของแพทย์ (tm=เฉพาะทาง, other=สาขาอื่น)');
            $table->enum('q31', ['yes', 'no'])->nullable()->comment('ข้อ 3.1: มีบริการฉีดวัคซีน/ให้ยา/หัตถการอื่นๆ หรือไม่ (กรณีแพทย์ TM)');
            $table->enum('q32', ['yes', 'no'])->nullable()->comment('ข้อ 3.2: มีบริการฉีดวัคซีน/ให้ยา/หัตถการอื่นๆ หรือไม่ (กรณีแพทย์สาขาอื่น)');
            $table->enum('q4', ['can', 'cannot'])->nullable()->comment('ข้อ 4: ความสามารถในการให้บริการกลุ่มที่มีปัญหา');

            // ผลสรุป
            $table->enum('level', ['basic', 'medium', 'advanced'])->nullable()->comment('ผลสรุประดับ: basic=พื้นฐาน, medium=กลาง, advanced=สูง');
            $table->dateTime('decided_at')->nullable()->comment('วันเวลาเมื่อระบบสรุประดับ');

            // ฟิลด์การอนุมัติ
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->comment('สถานะการอนุมัติ: pending=รอดำเนินการ, approved=อนุมัติ, rejected=ไม่อนุมัติ');
            $table->text('approval_remark')->nullable()->comment('หมายเหตุ/เหตุผลประกอบการอนุมัติหรือไม่อนุมัติ');
            $table->unsignedBigInteger('approved_by')->nullable()->comment('ผู้มีอำนาจอนุมัติ');
            $table->dateTime('approved_at')->nullable()->comment('วันเวลาอนุมัติ');

            // ข้อมูล audit
            $table->unsignedBigInteger('created_by')->nullable()->comment('สร้างโดย user id');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('แก้ไขล่าสุดโดย user id');
            $table->unsignedBigInteger('submitted_by')->nullable()->comment('กดยืนยันส่งโดย user id');
            $table->dateTime('submitted_at')->nullable()->comment('วันเวลาที่ยืนยันส่ง');

            // ข้อมูลระบบ
            $table->string('ip_address', 64)->nullable()->comment('IP address ตอนบันทึก');
            $table->string('user_agent', 255)->nullable()->comment('User Agent ตอนบันทึก');

            $table->timestamps();
            $table->softDeletes();

            // Unique & Index
            $table->unique(['service_unit_id', 'assess_year', 'assess_round'], 'ux_step1_unit_year_round');
            $table->index(['assess_year', 'assess_round']);
            $table->index(['service_unit_id']);
            $table->index(['user_id']);
            $table->index(['approved_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_step1');
    }
};
