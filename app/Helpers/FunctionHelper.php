<?php

use Carbon\Carbon;

/**
 * คืนปีงบประมาณ (ค.ศ.) อ้างอิง ต.ค.เป็นเดือนแรกของปีงบ
 * ถ้าเดือนไหน >= ต.ค. นับเป็นปีงบถัดไป (year + 1)
 */
if (!function_exists('fiscalYearCE')) {
    function fiscalYearCE(?Carbon $date = null): int
    {
        $d = $date ?: now();
        return $d->month >= 10 ? $d->year + 1 : $d->year;
    }
}

/**
 * คืนรอบการประเมินจากวันที่อ้างอิง
 * 1 = ต.ค.–มี.ค., 2 = เม.ย.–ก.ย.
 */
if (!function_exists('fiscalRound')) {
    function fiscalRound(?Carbon $date = null): int
    {
        $m = ($date ?: now())->month;
        return ($m >= 10 || $m <= 3) ? 1 : 2;
    }
}

/**
 * สร้างรายการปีงบประมาณจำนวน $count ปี
 * - เริ่มจากปีงบปัจจุบัน (ค.ศ.) แล้วย้อนลงมา
 * - แสดง พ.ศ. (be) แต่เก็บ ค.ศ. (ce)
 * return: [['ce'=>2026,'be'=>2569], ...]
 */
if (!function_exists('fiscalYearOptionsBE')) {
    function fiscalYearOptionsBE(int $count = 5): array
    {
        $startCE = fiscalYearCE(); // << แก้จาก $this-> เป็นเรียกฟังก์ชัน
        $out     = [];
        for ($i = 0; $i < $count; $i++) {
            $ce    = $startCE - $i;
            $out[] = ['ce' => $ce, 'be' => $ce + 543];
        }
        return $out;
    }
}

/**
 * แปลงปี พ.ศ./ค.ศ. → ค.ศ.
 * รับ int|string|null ถ้า > 2400 ถือว่าเป็น พ.ศ. แล้วลบ 543
 */
if (!function_exists('normalizeYearToCE')) {
    function normalizeYearToCE(int | string | null $year): ?int
    {
        if ($year === null || $year === '') {
            return null;
        }

        $y = (int) $year;
        return $y > 2400 ? $y - 543 : $y;
    }
}

/**
 * (ทางเลือก) ข้อความรอบการประเมินไว้ใช้แสดงผล
 */
if (!function_exists('fiscalRoundText')) {
    function fiscalRoundText(int $round): string
    {
        return $round === 1 ? 'รอบที่ 1 (ต.ค. – มี.ค.)' : 'รอบที่ 2 (เม.ย. – ก.ย.)';
    }
}
