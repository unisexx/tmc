<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // เพิ่มคอลัมน์เฉพาะเมื่อยังไม่มี
        if (!Schema::hasColumn('assessment_step1', 'service_unit_id')) {
            Schema::table('assessment_step1', function (Blueprint $t) {
                $t->foreignId('service_unit_id')->nullable()->after('id');
            });
            // แยกคำสั่ง FK ออกมา เผื่อบาง DB ต้องการ
            Schema::table('assessment_step1', function (Blueprint $t) {
                $t->foreign('service_unit_id')
                    ->references('id')->on('service_units')
                    ->cascadeOnDelete();
            });
        }

        // ดรอป unique เดิมถ้ามี (ชื่อจากรูปคือ ux_step1_unit_year_round)
        $hasOld = collect(DB::select("SHOW INDEX FROM assessment_step1"))
            ->contains(fn($i) => $i->Key_name === 'ux_step1_unit_year_round');
        if ($hasOld) {
            Schema::table('assessment_step1', function (Blueprint $t) {
                $t->dropUnique('ux_step1_unit_year_round');
            });
        }

        // สร้าง unique ใหม่ (กันซ้ำ service_unit_id+ปี+รอบ) ถ้ายังไม่มี
        $hasNew = collect(DB::select("SHOW INDEX FROM assessment_step1"))
            ->contains(fn($i) => $i->Key_name === 'assessment_step1_unit_year_round_unique');
        if (!$hasNew) {
            Schema::table('assessment_step1', function (Blueprint $t) {
                $t->unique(
                    ['service_unit_id', 'assess_year', 'assess_round'],
                    'assessment_step1_unit_year_round_unique'
                );
            });
        }
    }

    public function down(): void
    {
        // ลบ unique ใหม่ถ้ามี
        $hasNew = collect(DB::select("SHOW INDEX FROM assessment_step1"))
            ->contains(fn($i) => $i->Key_name === 'assessment_step1_unit_year_round_unique');
        if ($hasNew) {
            Schema::table('assessment_step1', function (Blueprint $t) {
                $t->dropUnique('assessment_step1_unit_year_round_unique');
            });
        }

        // ลบ FK + คอลัมน์ ถ้ามี
        if (Schema::hasColumn('assessment_step1', 'service_unit_id')) {
            Schema::table('assessment_step1', function (Blueprint $t) {
                $t->dropConstrainedForeignId('service_unit_id');
            });
        }

        // ใส่ unique เดิมกลับ ถ้ายังไม่มี
        $hasOld = collect(DB::select("SHOW INDEX FROM assessment_step1"))
            ->contains(fn($i) => $i->Key_name === 'ux_step1_unit_year_round');
        if (!$hasOld) {
            Schema::table('assessment_step1', function (Blueprint $t) {
                $t->unique(['assess_year', 'assess_round'], 'ux_step1_unit_year_round');
            });
        }
    }
};
