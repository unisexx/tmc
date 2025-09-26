<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // ฟิลด์เพิ่มเติม
            $table->string('username', 60)->unique()->nullable()->after('email');
            $table->string('phone', 60)->nullable()->after('username');
            $table->string('position', 255)->nullable()->after('phone');

            // ฟิลด์จาก TOR: วัตถุประสงค์การลงทะเบียน
            $table->string('reg_purpose', 50)->nullable()->after('position');

            // ฟิลด์หน่วยงาน
            $table->string('org_name', 255)->nullable()->after('reg_purpose');
            $table->string('org_affiliation', 255)->nullable()->after('org_name');
            $table->text('org_address')->nullable()->after('org_affiliation');
            $table->string('org_tel', 60)->nullable()->after('org_address');
            $table->decimal('org_lat', 10, 6)->nullable()->after('org_tel');
            $table->decimal('org_lng', 10, 6)->nullable()->after('org_lat');
            $table->text('org_working_hours')->nullable()->after('org_lng');

            // ฟิลด์ผู้ลงทะเบียน
            $table->string('contact_name', 255)->nullable()->after('org_working_hours');
            $table->string('contact_position', 255)->nullable()->after('contact_name');
            $table->string('contact_mobile', 60)->nullable()->after('contact_position');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'phone', 'position',
                'reg_purpose',
                'org_name', 'org_affiliation', 'org_address', 'org_tel', 'org_lat', 'org_lng', 'org_working_hours',
                'contact_name', 'contact_position', 'contact_mobile',
            ]);
        });
    }
};
