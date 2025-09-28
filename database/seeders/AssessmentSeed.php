<?php

// database/seeders/AssessmentSeed.php
namespace Database\Seeders;

use App\Models\AssessmentComponent;
use App\Models\AssessmentItem;
use Illuminate\Database\Seeder;

class AssessmentSeed extends Seeder
{
    public function run(): void
    {
        $components = [
            ['compKey' => 'governance', 'title' => 'องค์ประกอบที่ 1 การบริหารจัดการ'],
            ['compKey' => 'workforce', 'title' => 'องค์ประกอบที่ 2 บุคลากร'],
            ['compKey' => 'facility', 'title' => 'องค์ประกอบที่ 3 อาคาร สถานที่'],
            ['compKey' => 'equipment', 'title' => 'องค์ประกอบที่ 4 เครื่องมือ เวชภัณฑ์ เอกสาร'],
            ['compKey' => 'process', 'title' => 'องค์ประกอบที่ 5 กระบวนงาน'],
            ['compKey' => 'it', 'title' => 'องค์ประกอบที่ 6 ระบบเทคโนโลยีและข้อมูล'],
        ];

        foreach ($components as $c) {
            $comp = AssessmentComponent::firstOrCreate(['compKey' => $c['compKey']], $c);

            // ตัวอย่างข้อ 3 ระดับ/องค์ประกอบ
            foreach (['basic', 'medium', 'advanced'] as $lv) {
                AssessmentItem::firstOrCreate(
                    ['assessment_component_id' => $comp->id, 'code' => strtoupper(substr($c['compKey'], 0, 3)) . '-1'],
                    [
                        'assessment_component_id' => $comp->id,
                        'code'                    => strtoupper(substr($c['compKey'], 0, 3)) . '-1',
                        'question'                => "ตัวชี้วัดตัวอย่างของ {$c['title']} ({$lv})",
                        'forLevel' => $lv, 'weight' => 1, 'isRequired' => true,
                    ]
                );
            }
        }
    }
}
