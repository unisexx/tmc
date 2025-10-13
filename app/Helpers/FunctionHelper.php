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

if (!function_exists('thFullDate')) {
    function thFullDate($date)
    {
        if (empty($date)) {
            return '-';
        }

        $ts     = \Carbon\Carbon::parse($date);
        $months = [
            1 => 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม',
        ];
        return $ts->format('j') . ' ' . $months[(int) $ts->format('n')] . ' ' . ($ts->year + 543);
    }
}

/**
 * แปลง working_hours_json → ข้อความภาษาไทยหลายบรรทัด
 * โครงสร้างอินพุตที่รองรับ:
 * [
 *   "mon" => ["08:00-12:00","13:00-17:00"],
 *   "tue" => [],
 *   ...
 * ]
 */
if (!function_exists('workingHoursToThaiLines')) {
    function workingHoursToThaiLines(array | string | null $json): string
    {
        if (empty($json)) {
            return workingHoursEmptyLines();
        }

        if (is_string($json)) {
            $json = json_decode($json, true);
        }
        if (!is_array($json)) {
            return workingHoursEmptyLines();
        }

        $days = [
            'mon' => 'จันทร์',
            'tue' => 'อังคาร',
            'wed' => 'พุธ',
            'thu' => 'พฤหัสบดี',
            'fri' => 'ศุกร์',
            'sat' => 'เสาร์',
            'sun' => 'อาทิตย์',
        ];

        $lines = [];
        foreach ($days as $key => $label) {
            $ranges = $json[$key] ?? [];
            if (!is_array($ranges)) {
                $ranges = [];
            }

            // เรียงช่วงเวลา (จากเวลาเริ่ม)
            usort($ranges, function ($a, $b) {
                $sa = (int) substr($a, 0, 2);
                $sb = (int) substr($b, 0, 2);
                return $sa <=> $sb;
            });

            $text    = count($ranges) ? implode(', ', $ranges) : '— ปิดทำการ —';
            $lines[] = "{$label} : {$text}";
        }

        return implode("\n", $lines);
    }
}

/**
 * คืนข้อความ default (ทุกวันปิดทำการ)
 */
if (!function_exists('workingHoursEmptyLines')) {
    function workingHoursEmptyLines(): string
    {
        return "จันทร์ : — ปิดทำการ —\nอังคาร : — ปิดทำการ —\nพุธ : — ปิดทำการ —\nพฤหัสบดี : — ปิดทำการ —\nศุกร์ : — ปิดทำการ —\nเสาร์ : — ปิดทำการ —\nอาทิตย์ : — ปิดทำการ —";
    }
}

/**
 * ตัวช่วยสำหรับแสดงใน Blade ให้เป็นหลายบรรทัดพร้อม escape HTML
 * ใช้แทน nl2br(e()) ได้สะดวก
 */
if (!function_exists('renderWorkingHoursHtml')) {
    function renderWorkingHoursHtml(array | string | null $json): string
    {
        $text = workingHoursToThaiLines($json);
        return nl2br(e($text));
    }
}

if (!function_exists('renderWorkingHoursTable')) {
    /**
     * แสดงวัน-เวลาทำการเป็นตาราง Bootstrap
     * รับได้ทั้ง array และ JSON string
     */
    function renderWorkingHoursTable(array | string | null $json): string
    {
        // แปลงอินพุตเป็น array
        if (is_string($json)) {
            $json = json_decode($json, true);
        }
        $data = is_array($json) ? $json : [];

        // ลำดับวันในสัปดาห์
        $days = [
            'mon' => 'จันทร์',
            'tue' => 'อังคาร',
            'wed' => 'พุธ',
            'thu' => 'พฤหัสบดี',
            'fri' => 'ศุกร์',
            'sat' => 'เสาร์',
            'sun' => 'อาทิตย์',
        ];

        // helper แปลงช่วงเวลา "HH:MM-HH:MM" → "HH:MM น. – HH:MM น."
        $fmtRanges = function (array $ranges): string {
            if (!$ranges) {
                return '— ปิดทำการ —';
            }
            // เรียงตามเวลาเริ่ม
            usort($ranges, function ($a, $b) {
                return strcmp(substr($a, 0, 5), substr($b, 0, 5));
            });
            $out = [];
            foreach ($ranges as $rng) {
                if (preg_match('/^(\d{2}:\d{2})-(\d{2}:\d{2})$/', $rng, $m)) {
                    $out[] = "{$m[1]} น. – {$m[2]} น.";
                } else {
                    $out[] = e($rng);
                }
            }
            return implode(', ', $out);
        };

        // สร้าง HTML ตาราง
        $html = '<table class="table table-sm table-bordered mb-0"><tbody>';
        foreach ($days as $key => $label) {
            $ranges = isset($data[$key]) && is_array($data[$key]) ? $data[$key] : [];
            $html .= '<tr>';
            $html .= '<th class="w-25">' . e($label) . '</th>';
            $html .= '<td>' . $fmtRanges($ranges) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }
}
