<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait CrudActivity
{
    use LogsActivity;

    // ระบุ event ที่ต้องการ (จริงๆ ค่า default ก็เป็น 3 ตัวนี้)
    protected static $recordEvents = ['created', 'updated', 'deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        // ชื่อสมุดบันทึก (แยกหมวดตามระบบคุณ)
            ->useLogName('backend')

        // เลือกฟิลด์ที่จะ log:
        // 1) ถ้าโมเดลใช้ $fillable แล้วอยาก log ตามนั้น ใช้ ->logFillable()
        // 2) หรือระบุรายชื่อเอง เช่น ->logOnly(['title','status','approved_by'])
            ->logFillable()

        // บันทึกเฉพาะฟิลด์ที่ "เปลี่ยนจริง"
            ->logOnlyDirty()

        // ถ้าไม่มีฟิลด์ที่เข้าเงื่อนไข ไม่ต้องสร้าง log
            ->dontSubmitEmptyLogs()

        // กันเคสอัปเดตเฉพาะ updated_at แล้วทริกเกอร์ log เปล่า
            ->dontLogIfAttributesChangedOnly(['updated_at'])

        // ปรับข้อความบรรยายให้อ่านง่าย
            ->setDescriptionForEvent(fn(string $event) =>
                match ($event) {
                    'created' => 'สร้างรายการ',
                    'updated' => 'แก้ไขรายการ',
                    'deleted' => 'ลบรายการ',
                    default   => "ดำเนินการ: {$event}",
                }
            );
    }
}
