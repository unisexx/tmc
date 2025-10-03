<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            // เพิ่ม FK service_unit_id ไว้อ้างถึงหน่วยบริการหลักของผู้ใช้
            $t->foreignId('primary_service_unit_id')
                ->nullable()
                ->after('reg_supervise_region_id')
                ->constrained('service_units')
                ->nullOnDelete();

            // ===== ย้ายฟิลด์ org_* ไป service_units แล้ว จึงสามารถลบได้ =====
            $t->dropColumn([
                'org_name',
                'org_affiliation',
                'org_affiliation_other',
                'org_address',
                'org_tel',
                'org_lat',
                'org_lng',
                'org_working_hours',
                'org_working_hours_json',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            // rollback: เอา service_unit_id ออก
            $t->dropConstrainedForeignId('primary_service_unit_id');

            // เพิ่มฟิลด์ org_* กลับคืน
            $t->string('org_name')->nullable();
            $t->string('org_affiliation')->nullable();
            $t->string('org_affiliation_other')->nullable();
            $t->text('org_address')->nullable();
            $t->string('org_tel')->nullable();
            $t->decimal('org_lat', 10, 7)->nullable();
            $t->decimal('org_lng', 10, 7)->nullable();
            $t->text('org_working_hours')->nullable();
            $t->json('org_working_hours_json')->nullable();
        });
    }
};
