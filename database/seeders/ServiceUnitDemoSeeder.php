<?php
// database/seeders/ServiceUnitDemoSeeder.php

namespace Database\Seeders;

use App\Models\AssessmentServiceUnitLevel;
use App\Models\Province;
use App\Models\ServiceUnit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ServiceUnitDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ปีงบและรอบปัจจุบันจาก helper
        $year  = fiscalYearCE();
        $round = fiscalRound();

        // จุดอ้างอิงทั่วไทย (กระจายทุกภาค) แล้วสุ่ม jitter เล็กน้อยให้กระจายรอบเมือง
        $anchors = [
            // ภาคกลาง + กทม.
            ['name' => 'กรุงเทพมหานคร', 'lat' => 13.7563, 'lng' => 100.5018],
            ['name' => 'นนทบุรี', 'lat' => 13.8621, 'lng' => 100.5144],
            ['name' => 'นครปฐม', 'lat' => 13.8199, 'lng' => 100.0621],
            ['name' => 'พระนครศรีอยุธยา', 'lat' => 14.3532, 'lng' => 100.5684],
            ['name' => 'สระบุรี', 'lat' => 14.5289, 'lng' => 100.9101],
            ['name' => 'ราชบุรี', 'lat' => 13.5367, 'lng' => 99.8171],
            ['name' => 'เพชรบุรี', 'lat' => 13.1111, 'lng' => 99.9411],
            // ภาคเหนือ
            ['name' => 'เชียงใหม่', 'lat' => 18.7877, 'lng' => 98.9931],
            ['name' => 'เชียงราย', 'lat' => 19.9072, 'lng' => 99.8309],
            ['name' => 'ลำปาง', 'lat' => 18.2888, 'lng' => 99.4928],
            ['name' => 'น่าน', 'lat' => 18.7756, 'lng' => 100.7730],
            ['name' => 'พิษณุโลก', 'lat' => 16.8281, 'lng' => 100.2729],
            ['name' => 'ตาก', 'lat' => 16.8830, 'lng' => 99.1260],
            // ภาคอีสาน
            ['name' => 'ขอนแก่น', 'lat' => 16.4419, 'lng' => 102.8350],
            ['name' => 'อุดรธานี', 'lat' => 17.4138, 'lng' => 102.7870],
            ['name' => 'อุบลราชธานี', 'lat' => 15.2287, 'lng' => 104.8564],
            ['name' => 'นครราชสีมา', 'lat' => 14.9799, 'lng' => 102.0977],
            ['name' => 'สกลนคร', 'lat' => 17.1552, 'lng' => 104.1477],
            ['name' => 'มหาสารคาม', 'lat' => 16.1846, 'lng' => 103.3020],
            // ภาคตะวันออก
            ['name' => 'ชลบุรี', 'lat' => 13.3611, 'lng' => 100.9847],
            ['name' => 'ระยอง', 'lat' => 12.6814, 'lng' => 101.2810],
            ['name' => 'ตราด', 'lat' => 12.2436, 'lng' => 102.5179],
            // ภาคตะวันตก
            ['name' => 'กาญจนบุรี', 'lat' => 14.0228, 'lng' => 99.5328],
            ['name' => 'ประจวบคีรีขันธ์', 'lat' => 11.8120, 'lng' => 99.7979],
            // ภาคใต้
            ['name' => 'ภูเก็ต', 'lat' => 7.8804, 'lng' => 98.3923],
            ['name' => 'สงขลา', 'lat' => 7.1895, 'lng' => 100.5951],
            ['name' => 'สุราษฎร์ธานี', 'lat' => 9.1382, 'lng' => 99.3210],
            ['name' => 'นครศรีธรรมราช', 'lat' => 8.4304, 'lng' => 99.9631],
            ['name' => 'ตรัง', 'lat' => 7.5594, 'lng' => 99.6114],
            ['name' => 'ปัตตานี', 'lat' => 6.8682, 'lng' => 101.2501],
        ];

        $affiliations = [
            'กรมควบคุมโรค', 'กรมการแพทย์', 'กรมสุขภาพจิต', 'สำนักงานปลัดกระทรวงสาธารณสุข',
            'สำนักการแพทย์ กรุงเทพมหานคร', 'กระทรวงกลาโหม', 'เอกชน', 'องค์การมหาชน',
        ];

                                                                    // โหลดจังหวัดเพื่อใช้ code จริงจากฐานข้อมูล
        $provinceMap   = Province::query()->pluck('code', 'title'); // [title => code]
        $provinceCodes = Province::query()->pluck('code')->all();

        $levels = ['basic', 'medium', 'advanced'];
        $now    = now();

        // สร้าง 50 หน่วยบริการ
        for ($i = 1; $i <= 50; $i++) {
            $anchor = Arr::random($anchors);

            // สุ่มเขยื้อนพิกัดเล็กน้อยรอบจุดอ้างอิง
            $lat = round($anchor['lat'] + $this->jitter(0.18), 6);
            $lng = round($anchor['lng'] + $this->jitter(0.18), 6);

            // หารหัสจังหวัดจากชื่อ ถ้าไม่มีชื่อที่ตรง ให้สุ่มจากฐานข้อมูล
            $provinceCode = $provinceMap[$anchor['name']] ?? Arr::random($provinceCodes);

            $su = ServiceUnit::create([
                'org_name' => "หน่วยบริการทดสอบ {$i} - {$anchor['name']}",
                'org_affiliation'       => Arr::random($affiliations),
                'org_affiliation_other' => null,
                'org_address'           => "ตำบล/แขวง ตัวอย่าง อำเภอ/เขต ตัวอย่าง จังหวัด{$anchor['name']}",
                'org_tel'                => $this->fakePhone(),
                'org_lat'                => $lat,
                'org_lng'                => $lng,
                'org_working_hours'      => null,
                'org_working_hours_json' => null,
                'org_province_code'      => $provinceCode,
                'org_district_code'      => null,
                'org_subdistrict_code'   => null,
                'org_postcode'           => str_pad((string) random_int(10000, 96110), 5, '0', STR_PAD_LEFT),
            ]);

            AssessmentServiceUnitLevel::create([
                'service_unit_id' => $su->id,
                'assess_year'     => $year,
                'assess_round'    => $round,
                'user_id'         => null,

                'status'          => 'completed',
                'last_question'   => 'done',

                'q1'              => Arr::random(['have', 'none']),
                'q2'              => Arr::random(['tm', 'other']),
                'q31'             => Arr::random(['yes', 'no']),
                'q32'             => Arr::random(['yes', 'no']),
                'q4'              => Arr::random(['can', 'cannot']),

                'level'           => Arr::random($levels),
                'decided_at'      => Carbon::instance($now)->subDays(random_int(5, 30)),

                'approval_status' => 'approved',
                'approval_remark' => null,
                'approved_by'     => 1, // ปรับเป็น user id ที่มีอยู่จริงได้ตามระบบ
                'approved_at'     => Carbon::instance($now)->subDays(random_int(1, 4)),

                'created_by'      => null,
                'updated_by'      => null,
                'submitted_by'    => null,
                'submitted_at'    => Carbon::instance($now)->subDays(random_int(6, 35)),

                'ip_address'      => request()?->ip() ?? '127.0.0.1',
                'user_agent'      => 'seeder/' . app()->version(),
            ]);
        }
    }

    /**
     * สุ่มค่าเขยื้อนละติจูด/ลองจิจูดขนาดเล็ก
     */
    private function jitter(float $maxDelta = 0.1): float
    {
        return (mt_rand() / mt_getrandmax() * 2 - 1) * $maxDelta;
    }

    private function fakePhone(): string
    {
        // รูปแบบ 0xx-xxx-xxxx
        $p = '0' . random_int(20, 99) . random_int(1000000, 9999999);
        return preg_replace('/(\d{3})(\d{3})(\d{4})/', '$1-$2-$3', $p);
    }
}
