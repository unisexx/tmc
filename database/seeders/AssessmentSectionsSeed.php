<?php
// database/seeders/AssessmentSectionsSeed.php

namespace Database\Seeders;

use App\Models\AssessmentComponent;
use App\Models\AssessmentLevel;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentSection;
use Illuminate\Database\Seeder;

class AssessmentSectionsSeed extends Seeder
{
    public function run(): void
    {
        // ensure masters
        $basic = AssessmentLevel::firstOrCreate(['code' => 'basic'], ['name' => 'ระดับพื้นฐาน']);
        $comp1 = AssessmentComponent::firstOrCreate(['no' => 1], ['name' => 'การบริหารจัดการ', 'short_name' => 'management']);

        /* =========================
        | องค์ประกอบที่ 1
         * =======================*/
        // 1.1) ลักษณะหน่วยบริการ
        $s11 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $basic->id, 'assessment_component_id' => $comp1->id, 'code' => '1.1'],
            ['title' => 'ลักษณะหน่วยบริการ', 'ordering' => 110]
        );
        $this->q($basic->id, $comp1->id, $s11->id, null, 'ลักษณะของหน่วยบริการ', 10, 'single', json_encode([
            'สถานพยาบาลตามกฎหมายว่าด้วยสถานพยาบาล',
            'สถานพยาบาลที่ดำเนินการโดยกระทรวง ทบวง กรม อปท. รัฐวิสาหกิจ สถาบันการศึกษาของรัฐ หน่วยงานอื่นของรัฐ และสภากาชาดไทย',
            'หน่วยบริการตามกฎหมายว่าด้วยหลักประกันสุขภาพแห่งชาติ',
        ]));

        // 1.2) นโยบาย แผนงาน
        $s12 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $basic->id, 'assessment_component_id' => $comp1->id, 'code' => '1.2'],
            ['title' => 'นโยบาย แผนงาน', 'ordering' => 120]
        );
        $this->q($basic->id, $comp1->id, $s12->id, '1', 'แผนงาน/โครงการสนับสนุนการดำเนินงานหน่วยบริการสุขภาพผู้เดินทางประจำปี', 10, 'boolean');
        $this->q($basic->id, $comp1->id, $s12->id, '2', 'แผนงาน/โครงการจัดหาวัสดุอุปกรณ์ บุคลากร เพื่อสนับสนุนงานบริการ', 20, 'boolean');
        $this->q($basic->id, $comp1->id, $s12->id, '3', 'แผนงาน/โครงการเข้าร่วมการพัฒนาศักยภาพบุคลากร', 30, 'boolean');
        $this->q($basic->id, $comp1->id, $s12->id, '4', 'แผนงาน/โครงการสื่อสารความเสี่ยงโรคและภัยสุขภาพในพื้นที่ให้กับผู้เดินทาง', 40, 'boolean');

        // 1.3) งบประมาณ
        $s13 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $basic->id, 'assessment_component_id' => $comp1->id, 'code' => '1.3'],
            ['title' => 'งบประมาณ', 'ordering' => 130]
        );
        $this->q($basic->id, $comp1->id, $s13->id, null, 'ได้รับการสนับสนุนงบประมาณสำหรับดำเนินการตามแผนงาน/โครงการที่กำหนด', 10, 'boolean');

        // 1.4) ผู้รับผิดชอบงาน
        $s14 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $basic->id, 'assessment_component_id' => $comp1->id, 'code' => '1.4'],
            ['title' => 'ผู้รับผิดชอบงาน', 'ordering' => 140]
        );
        $this->q($basic->id, $comp1->id, $s14->id, null, 'มีผู้รับผิดชอบงานที่ชัดเจน อาจเป็นกลุ่มงาน/แผนก หรือคณะกรรมการ/คณะทำงานที่ร่วมขับเคลื่อนการพัฒนาหน่วยบริการสุขภาพผู้เดินทาง', 10, 'boolean');

        /* =========================
        | องค์ประกอบที่ 2 กระบวนงาน
         * =======================*/
        $comp2 = AssessmentComponent::firstOrCreate(['no' => 2], ['name' => 'กระบวนงาน', 'short_name' => 'process']);

        // 2.1) งานบริการสุขภาพผู้เดินทาง
        $s21 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $basic->id, 'assessment_component_id' => $comp2->id, 'code' => '2.1'],
            ['title' => 'งานบริการสุขภาพผู้เดินทาง', 'ordering' => 210]
        );
        $this->q($basic->id, $comp2->id, $s21->id, '1', 'การให้คำแนะนำ/ข้อมูลทั่วไปเกี่ยวกับสุขภาพผู้เดินทาง เช่น โรคและภัยสุขภาพ สภาพพื้นที่ปลายทาง และวัคซีนสำคัญ', 10, 'boolean');
        $this->q($basic->id, $comp2->id, $s21->id, '2', 'การออกเอกสารรับรองการให้วัคซีน หรือยาป้องกันโรคระหว่างประเทศ', 20, 'boolean');

        // 2.2) งานสนับสนุน
        $s22 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $basic->id, 'assessment_component_id' => $comp2->id, 'code' => '2.2'],
            ['title' => 'งานสนับสนุน', 'ordering' => 220]
        );
        $this->q($basic->id, $comp2->id, $s22->id, '1', 'มีการเฝ้าระวังป้องกันโรคและภัยสุขภาพเชิงรุก', 10, 'boolean');
        $this->q($basic->id, $comp2->id, $s22->id, '2', 'มีประเมินความเสี่ยงโรคและภัยสุขภาพในสถานที่ท่องเที่ยว', 20, 'boolean');
        $this->q($basic->id, $comp2->id, $s22->id, '3', 'มีการลงพื้นที่ให้ความรู้แก่ผู้เดินทางตามสถานที่ท่องเที่ยว ที่พัก', 30, 'boolean');

        /* =========================
        | องค์ประกอบที่ 3 บุคลากร
         * =======================*/
        $comp3 = AssessmentComponent::firstOrCreate(['no' => 3], ['name' => 'บุคลากร', 'short_name' => 'personnel']);

        // 3.1) ตำแหน่ง
        $s31 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $basic->id, 'assessment_component_id' => $comp3->id, 'code' => '3.1'],
            ['title' => 'ตำแหน่ง', 'ordering' => 310]
        );
        $this->q(
            $basic->id, $comp3->id, $s31->id, null,
            'มีบุคลากรทางการแพทย์และสาธารณสุข เช่น แพทย์ พยาบาล เภสัชกร นักวิชาการสาธารณสุข เป็นต้น ซึ่งอยู่ภายใต้การกำกับดูแลของผู้มีใบประกอบวิชาชีพเวชกรรม',
            10, 'boolean'
        );

        // 3.2) สมรรถนะบุคลากร
        $s32 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $basic->id, 'assessment_component_id' => $comp3->id, 'code' => '3.2'],
            ['title' => 'สมรรถนะบุคลากร', 'ordering' => 320]
        );
        $this->q($basic->id, $comp3->id, $s32->id, '1', 'บุคลากรที่ให้บริการผู้รับบริการโดยตรง มีความรู้และทักษะด้านเวชศาสตร์การเดินทางและท่องเที่ยว (ภาคผนวก 5) เช่น ผ่านการอบรมของกรมควบคุมโรค สมาคมเวชศาสตร์ป้องกันแห่งประเทศไทย หรือคณะเวชศาสตร์เขตร้อน มหาวิทยาลัยมหิดล เป็นต้น', 10, 'boolean');
        $this->q($basic->id, $comp3->id, $s32->id, '2', 'บุคลากรที่ให้บริการผู้รับบริการโดยตรง สามารถสื่อสารภาษาอังกฤษ หรือภาษาต่างชาติอื่น ๆ ได้ในระดับพื้นฐาน', 20, 'boolean');

        /* =========================
        | องค์ประกอบที่ 4 อาคาร สถานที่
         * =======================*/
        $comp4 = AssessmentComponent::firstOrCreate(['no' => 4], ['name' => 'อาคาร สถานที่', 'short_name' => 'facility']);

        // 4.1) อาคาร สถานที่
        $s41 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $basic->id, 'assessment_component_id' => $comp4->id, 'code' => '4.1'],
            ['title' => 'อาคาร สถานที่', 'ordering' => 410]
        );
        $this->q($basic->id, $comp4->id, $s41->id, '1', 'มีป้ายชื่อหน่วยบริการสุขภาพผู้เดินทางที่ชัดเจน ให้ผู้รับบริการมองเห็นได้ง่าย', 10, 'boolean');
        $this->q($basic->id, $comp4->id, $s41->id, '2', 'มีการจัดสถานที่หน่วยบริการสุขภาพผู้เดินทาง แยกเป็นสัดส่วนชัดเจนจากคลินิกหรืองานบริการอื่น', 20, 'boolean');

        /* =========================
        | องค์ประกอบที่ 5 เครื่องมือ เครื่องใช้ ยา เวชภัณฑ์ ฯลฯ
         * =======================*/
        $comp5 = AssessmentComponent::firstOrCreate(
            ['no' => 5],
            ['name' => 'เครื่องมือ เครื่องใช้ ยา เวชภัณฑ์ และเอกสารทางการแพทย์', 'short_name' => 'equipment']
        );

        // 5.1) เครื่องมือ เครื่องใช้
        $s51 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $basic->id, 'assessment_component_id' => $comp5->id, 'code' => '5.1'],
            ['title' => 'เครื่องมือ เครื่องใช้', 'ordering' => 510]
        );
        $this->q($basic->id, $comp5->id, $s51->id, '1', 'อุปกรณ์เทคโนโลยีสารสนเทศ พร้อมระบบเครือข่ายอินเทอร์เน็ต สำหรับสืบค้นข้อมูลทั่วไปเกี่ยวกับสุขภาพผู้เดินทาง', 10, 'boolean');
        $this->q($basic->id, $comp5->id, $s51->id, '2', 'อุปกรณ์สำหรับการออกเอกสารรับรองการให้วัคซีน หรือยาป้องกันโรคระหว่างประเทศ', 20, 'boolean');

        // 5.2) ยา — ไม่มีคำถาม แต่ต้องมีหัวข้อ
        AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $basic->id, 'assessment_component_id' => $comp5->id, 'code' => '5.2'],
            ['title' => 'ยา', 'ordering' => 520]
        );

        // 5.3) วัคซีน — ไม่มีคำถาม แต่ต้องมีหัวข้อ
        AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $basic->id, 'assessment_component_id' => $comp5->id, 'code' => '5.3'],
            ['title' => 'วัคซีน', 'ordering' => 530]
        );

        // 5.4) เอกสารทางการแพทย์
        $s54 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $basic->id, 'assessment_component_id' => $comp5->id, 'code' => '5.4'],
            ['title' => 'เอกสารทางการแพทย์', 'ordering' => 540]
        );
        $this->q($basic->id, $comp5->id, $s54->id, null, 'เอกสารรับรองการให้วัคซีนหรือยาป้องกันโรคระหว่างประเทศ', 10, 'boolean');

        /* =========================
        | องค์ประกอบที่ 6 ระบบ เทคโนโลยี และแหล่งข้อมูล
         * =======================*/
        $comp6 = AssessmentComponent::firstOrCreate(
            ['no' => 6],
            ['name' => 'ระบบ เทคโนโลยี และแหล่งข้อมูล', 'short_name' => 'system']
        );

        // 6.1) ระบบ และเทคโนโลยี
        $s61 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $basic->id, 'assessment_component_id' => $comp6->id, 'code' => '6.1'],
            ['title' => 'ระบบ และเทคโนโลยี', 'ordering' => 610]
        );
        $this->q($basic->id, $comp6->id, $s61->id, null, 'ระบบออกเอกสารรับรองการฉีดวัคซีน เพื่อการเดินทางระหว่างประเทศ (INTERVAC)', 10, 'boolean');

        // 6.2) ฐานข้อมูลงานบริการ
        $s62 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $basic->id, 'assessment_component_id' => $comp6->id, 'code' => '6.2'],
            ['title' => 'ฐานข้อมูลงานบริการ', 'ordering' => 620]
        );
        $this->q($basic->id, $comp6->id, $s62->id, '1', 'ฐานข้อมูลทั่วไปของผู้รับบริการ เช่น ชื่อ-นามสกุล สัญชาติ เพศ อายุ และวัตถุประสงค์การเข้ารับบริการ เป็นต้น', 10, 'boolean');
        $this->q($basic->id, $comp6->id, $s62->id, '2', 'ฐานข้อมูลการออกเอกสารรับรองการให้วัคซีนหรือยาป้องกันโรคระหว่างประเทศ', 20, 'boolean');

        // 6.3) ทำเนียบ
        $s63 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $basic->id, 'assessment_component_id' => $comp6->id, 'code' => '6.3'],
            ['title' => 'ทำเนียบ', 'ordering' => 630]
        );
        $this->q($basic->id, $comp6->id, $s63->id, '1', 'มีทำเนียบเครือข่ายแพทย์เฉพาะทาง เพื่อให้หน่วยบริการสามารถขอรับคำปรึกษาระหว่างหน่วยงานได้', 10, 'boolean');
        $this->q($basic->id, $comp6->id, $s63->id, '2', 'มีทำเนียบเครือข่ายหน่วยบริการสาธารณสุขที่เกี่ยวข้องกับการส่งต่อ หรือรับการรักษาต่อ', 20, 'boolean');
        $this->q($basic->id, $comp6->id, $s63->id, '3', 'มีทำเนียบเครือข่ายเฝ้าระวังแจ้งเตือน สอบสวน ควบคุมโรคในพื้นที่', 30, 'boolean');
        $this->q($basic->id, $comp6->id, $s63->id, '4', 'มีทำเนียบเครือข่ายสื่อสารประชาสัมพันธ์ด้านสุขภาพผู้เดินทางในพื้นที่ เช่น การท่องเที่ยวจังหวัด สำนักงานท่องเที่ยวและกีฬา เครือข่ายมัคคุเทศก์ และเครือข่ายโรงแรม/ที่พัก เป็นต้น', 40, 'boolean');
    }

    private function q($lvId, $cpId, $secId, $code, $text, $ord, $type = 'boolean', $optionsJson = null): void
    {
        AssessmentQuestion::updateOrCreate(
            [
                'assessment_level_id'     => $lvId,
                'assessment_component_id' => $cpId,
                'assessment_section_id'   => $secId,
                'code'                    => $code,
            ],
            [
                'text'        => $text,
                'answer_type' => $type,        // 'boolean' | 'single'
                'options'     => $optionsJson, // JSON เมื่อเป็น single-choice
                'ordering'    => $ord,
                'is_active'   => true,
            ]
        );
    }
}
