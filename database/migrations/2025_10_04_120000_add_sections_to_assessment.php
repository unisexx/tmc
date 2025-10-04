<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // สร้างตารางหัวข้อย่อย ถ้ายังไม่มี
        if (!Schema::hasTable('assessment_sections')) {
            Schema::create('assessment_sections', function (Blueprint $t) {
                $t->id();
                $t->foreignId('assessment_level_id')->constrained()->cascadeOnDelete();
                $t->foreignId('assessment_component_id')->constrained()->cascadeOnDelete();
                $t->string('code')->nullable();
                $t->string('title');
                $t->string('subtitle')->nullable();
                $t->unsignedSmallInteger('ordering')->default(0);
                $t->timestamps();
                $t->index(['assessment_level_id', 'assessment_component_id'], 'asct_lvl_cmp_idx');
            });
        }

        // เพิ่มคอลัมน์ FK ให้ assessment_questions เฉพาะเมื่อมีตารางแล้ว
        if (Schema::hasTable('assessment_questions')) {
            Schema::table('assessment_questions', function (Blueprint $t) {
                if (!Schema::hasColumn('assessment_questions', 'assessment_section_id')) {
                    $t->foreignId('assessment_section_id')
                        ->nullable()->after('assessment_component_id')
                        ->constrained('assessment_sections')->nullOnDelete();
                }
                if (!Schema::hasColumn('assessment_questions', 'code')) {
                    $t->string('code')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assessment_questions') &&
            Schema::hasColumn('assessment_questions', 'assessment_section_id')) {
            Schema::table('assessment_questions', function (Blueprint $t) {
                $t->dropConstrainedForeignId('assessment_section_id');
            });
        }
        if (Schema::hasTable('assessment_sections')) {
            Schema::drop('assessment_sections');
        }
    }
};
