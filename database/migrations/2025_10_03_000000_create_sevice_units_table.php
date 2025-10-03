<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง service_units เก็บข้อมูลหน่วยบริการ/หน่วยงาน
     */
    public function up(): void
    {
        Schema::create('service_units', function (Blueprint $t) {
            $t->id(); // รหัสหน่วยบริการ (Primary key)

                                                             // ==============================
                                                             // ฟิลด์ข้อมูลหน่วยบริการ
                                                             // ==============================
            $t->string('org_name');                          // ชื่อหน่วยบริการ/หน่วยงาน
            $t->string('org_affiliation')->nullable();       // สังกัด (เช่น กรมควบคุมโรค, กรมการแพทย์ ฯลฯ)
            $t->string('org_affiliation_other')->nullable(); // ถ้าเลือก "อื่น ๆ" ให้ระบุเพิ่มเติม
            $t->text('org_address')->nullable();             // ที่อยู่หน่วยงาน
            $t->string('org_tel')->nullable();               // เบอร์โทรศัพท์หน่วยงาน
            $t->decimal('org_lat', 10, 7)->nullable();       // พิกัด Latitude
            $t->decimal('org_lng', 10, 7)->nullable();       // พิกัด Longitude
            $t->text('org_working_hours')->nullable();       // คำอธิบายเวลาทำการ (ข้อความอิสระ)
            $t->json('org_working_hours_json')->nullable();  // เวลาทำการแบบโครงสร้าง (JSON)

            $t->timestamps(); // created_at, updated_at
        });

        /**
         * สร้างตาราง service_unit_users สำหรับเชื่อมความสัมพันธ์ระหว่าง
         * หน่วยบริการ (service_units) กับ ผู้ใช้ (users)
         */
        Schema::create('service_unit_users', function (Blueprint $t) {
            $t->id(); // Primary key

            $t->foreignId('service_unit_id') // รหัสหน่วยบริการ
                ->constrained()                  // FK ไปที่ service_units.id
                ->cascadeOnDelete();

            $t->foreignId('user_id') // รหัสผู้ใช้
                ->constrained()          // FK ไปที่ users.id
                ->cascadeOnDelete();

            $t->string('role')->default('manager');    // บทบาทของผู้ใช้ในหน่วยบริการ (เช่น manager, viewer)
            $t->date('start_date')->nullable();        // วันที่เริ่มเป็นผู้ดูแล
            $t->date('end_date')->nullable();          // วันที่สิ้นสุดสิทธิ์ (null = ยังมีสิทธิ์)
            $t->boolean('is_primary')->default(false); // ระบุว่าเป็นผู้ใช้หลักของหน่วยบริการหรือไม่

            $t->timestamps(); // created_at, updated_at

            // unique constraint กันซ้ำ ไม่ให้ user ซ้ำ role เดิมในหน่วยเดียวกัน
            $t->unique(['service_unit_id', 'user_id', 'role'], 'uq_unit_user_role');
        });
    }

    /**
     * Rollback: ลบตารางเมื่อ migrate:rollback
     */
    public function down(): void
    {
        Schema::dropIfExists('service_unit_users'); // ต้องลบตาราง pivot ก่อนเพราะมี FK
        Schema::dropIfExists('service_units');
    }
};
