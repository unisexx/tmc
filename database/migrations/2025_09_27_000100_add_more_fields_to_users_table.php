<?php

// database/migrations/2025_09_27_000100_add_more_fields_to_users_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // บทบาทกำกับดูแล
            $table->string('reg_supervise_province_code')->nullable()->after('reg_purpose');
            $table->unsignedBigInteger('reg_supervise_region_id')->nullable()->after('reg_supervise_province_code');

            // หน่วยบริการ/หน่วยงาน
            $table->string('org_affiliation_other')->nullable()->after('org_affiliation');
            $table->json('org_working_hours_json')->nullable()->after('org_working_hours');

            // ปรับ lat/lng เป็น decimal ถ้ายังไม่ใช่
            $table->decimal('org_lat', 10, 6)->nullable()->change();
            $table->decimal('org_lng', 10, 6)->nullable()->change();

            // เอกสารเจ้าหน้าที่
            $table->string('officer_doc_path')->nullable()->after('contact_mobile');
            $table->dateTime('officer_doc_verified_at')->nullable()->after('officer_doc_path');
            $table->unsignedBigInteger('officer_doc_verified_by')->nullable()->after('officer_doc_verified_at');

            // PDPA / consent
            $table->dateTime('pdpa_accepted_at')->nullable()->after('password');
            $table->string('pdpa_version')->nullable()->after('pdpa_accepted_at');
            $table->json('consent_log')->nullable()->after('pdpa_version');

            // สถานะการอนุมัติ
            $table->enum('reg_status', ['pending', 'approved', 'rejected'])->default('pending')->after('pdpa_version');
            $table->text('reg_review_note')->nullable()->after('reg_status');
            $table->dateTime('approved_at')->nullable()->after('reg_review_note');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');

            // ดัชนี
            $table->index('reg_supervise_province_code');
            $table->index('reg_supervise_region_id');
            $table->index('reg_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'reg_supervise_province_code',
                'reg_supervise_region_id',
                'org_affiliation_other',
                'org_working_hours_json',
                'officer_doc_path',
                'officer_doc_verified_at',
                'officer_doc_verified_by',
                'pdpa_accepted_at',
                'pdpa_version',
                'consent_log',
                'reg_status',
                'reg_review_note',
                'approved_at',
                'approved_by',
            ]);
        });
    }
};
