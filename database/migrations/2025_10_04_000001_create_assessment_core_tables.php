<?php

// database/migrations/2025_10_04_000001_create_assessment_core_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_levels', function (Blueprint $t) {
            $t->id()->comment('รหัสระดับการประเมิน');
            $t->string('code')->unique()->comment('รหัสระดับ: basic, medium, advanced');
            $t->string('name')->comment('ชื่อระดับ: พื้นฐาน/กลาง/สูง');
            $t->timestamps();
            $t->comment('ตาราง: ระดับการประเมินตนเองของหน่วยบริการ');
        });

        Schema::create('assessment_components', function (Blueprint $t) {
            $t->id()->comment('รหัสองค์ประกอบ');
            $t->unsignedTinyInteger('no')->comment('ลำดับองค์ประกอบ 1–6');
            $t->string('name')->comment('ชื่อองค์ประกอบการจัดหน่วยบริการ');
            $t->string('short_name')->nullable()->comment('ชื่อย่อองค์ประกอบ');
            $t->timestamps();
            $t->unique(['no']);
            $t->comment('ตาราง: 6 องค์ประกอบของหน่วยบริการสุขภาพผู้เดินทาง');
        });

        Schema::create('assessment_questions', function (Blueprint $t) {
            $t->id()->comment('รหัสคำถาม');
            $t->foreignId('assessment_level_id')->constrained('assessment_levels')->cascadeOnDelete()
                ->comment('FK: ระดับการประเมิน (พื้นฐาน/กลาง/สูง)');
            $t->foreignId('assessment_component_id')->constrained('assessment_components')->cascadeOnDelete()
                ->comment('FK: องค์ประกอบที่คำถามสังกัด');
            $t->string('code')->nullable()->comment('โค้ดคำถาม เช่น 2.1-1');
            $t->text('text')->comment('ข้อความคำถาม');
            $t->string('answer_type')->default('boolean')->comment('ชนิดคำตอบ: boolean|text|file|multi');
            $t->unsignedSmallInteger('ordering')->default(0)->comment('ลำดับการแสดงผล');
            $t->boolean('is_active')->default(true)->comment('สถานะเปิดใช้งานคำถาม');
            $t->timestamps();
            $t->index(
                ['assessment_level_id', 'assessment_component_id'],
                'aq_lvl_comp_idx' // ชื่อสั้น ไม่เกิน 64 ตัวอักษร
            );
            $t->comment('ตาราง: ชุดคำถามจำแนกตามระดับและองค์ประกอบ');
        });

        // ฟอร์มการประเมินของ "หน่วยบริการ x ปี/รอบ"
        Schema::create('assessment_forms', function (Blueprint $t) {
            $t->id()->comment('รหัสฟอร์มการประเมิน');
            $t->foreignId('service_unit_id')->constrained('service_units')->cascadeOnDelete()
                ->comment('FK: หน่วยบริการสุขภาพผู้เดินทาง');
            $t->unsignedSmallInteger('assess_year')->comment('ปีงบประมาณที่ประเมิน');
            $t->unsignedTinyInteger('assess_round')->comment('รอบการประเมิน');
            $t->string('level_code')->comment('ระดับที่ใช้แบบคำถาม: basic|intermediate|advanced');
            $t->string('status')->default('draft')
                ->comment('สถานะ: draft|submitted|reviewing|approved|rejected');
            $t->timestamp('submitted_at')->nullable()->comment('เวลาที่ส่งแบบประเมิน');
            // ทบทวนโดย สคร./ส่วนกลาง
            $t->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete()
                ->comment('FK: ผู้ทบทวน/ผู้ตรวจสอบ');
            $t->timestamp('reviewed_at')->nullable()->comment('เวลาที่ทบทวนล่าสุด');
            $t->text('review_note')->nullable()->comment('ข้อเสนอแนะ/หมายเหตุจากผู้ทบทวน');
            $t->timestamps();

            $t->unique(['service_unit_id', 'assess_year', 'assess_round'], 'form_unit_year_round_unique');
            $t->comment('ตาราง: ฟอร์มการประเมินตนเองของหน่วยบริการ แยกตามปี/รอบ/ระดับ');
        });

        Schema::create('assessment_answers', function (Blueprint $t) {
            $t->id()->comment('รหัสคำตอบของฟอร์ม');
            $t->foreignId('assessment_form_id')->constrained('assessment_forms')->cascadeOnDelete()
                ->comment('FK: ฟอร์มการประเมิน');
            $t->foreignId('assessment_question_id')->constrained('assessment_questions')->cascadeOnDelete()
                ->comment('FK: ข้อคำถาม');
            $t->boolean('answer_bool')->nullable()->comment('คำตอบแบบมี/ไม่มี');
            $t->text('answer_text')->nullable()->comment('คำตอบแบบข้อความ');
            $t->string('attachment_path')->nullable()->comment('พาธไฟล์แนบคำตอบ/หลักฐาน');
            $t->timestamps();
            $t->unique(['assessment_form_id', 'assessment_question_id'], 'form_question_unique');
            $t->comment('ตาราง: คำตอบของแบบประเมินรายข้อ');
        });

        // ข้อเสนอเพื่อการพัฒนา/แผนพัฒนา
        Schema::create('assessment_suggestions', function (Blueprint $t) {
            $t->id()->comment('รหัสข้อเสนอ/แผนพัฒนา');
            $t->foreignId('assessment_form_id')->constrained('assessment_forms')->cascadeOnDelete()
                ->comment('FK: ฟอร์มการประเมิน');
            $t->text('text')->comment('ข้อความข้อเสนอเพื่อการพัฒนา/แผนพัฒนา');
            $t->string('attachment_path')->nullable()->comment('พาธไฟล์แนบของข้อเสนอ');
            $t->timestamps();
            $t->comment('ตาราง: ข้อเสนอเพื่อการพัฒนาของหน่วยบริการ');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_suggestions');
        Schema::dropIfExists('assessment_answers');
        Schema::dropIfExists('assessment_forms');
        Schema::dropIfExists('assessment_questions');
        Schema::dropIfExists('assessment_components');
        Schema::dropIfExists('assessment_levels');
    }
};
