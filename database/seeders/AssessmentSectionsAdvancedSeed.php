<?php

namespace Database\Seeders;

use App\Models\AssessmentComponent;
use App\Models\AssessmentLevel;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentSection;
use Illuminate\Database\Seeder;

class AssessmentSectionsAdvancedSeed extends Seeder
{
    public function run(): void
    {
        $advanced = AssessmentLevel::firstOrCreate(['code' => 'advanced'], ['name' => 'ระดับสูง']);

        /* ===== องค์ประกอบที่ 1 การบริหารจัดการ ===== */
        $comp1 = AssessmentComponent::firstOrCreate(['no' => 1], ['name' => 'การบริหารจัดการ', 'short_name' => 'management']);

        // 1.1) ลักษณะหน่วยบริการ (single)
        $s11 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $advanced->id, 'assessment_component_id' => $comp1->id, 'code' => '1.1'],
            ['title' => 'ลักษณะหน่วยบริการ', 'ordering' => 110]
        );
        $this->q($advanced->id, $comp1->id, $s11->id, null, 'ลักษณะของหน่วยบริการ', 10, 'single', json_encode([
            '1. สถานพยาบาลตามกฎหมายว่าด้วยสถานพยาบาล',
            '2. สถานพยาบาลที่ดำเนินการโดยกระทรวง ทบวง กรม องค์กรปกครองส่วนท้องถิ่น รัฐวิสาหกิจ สถาบันการศึกษาของรัฐ หน่วยงานอื่นของรัฐ และสภากาชาดไทย',
            '3. หน่วยบริการตามกฎหมายว่าด้วยหลักประกันสุขภาพแห่งชาติ',
        ]));

        // 1.2) นโยบาย แผนงาน
        $s12 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $advanced->id, 'assessment_component_id' => $comp1->id, 'code' => '1.2'],
            ['title' => 'นโยบาย แผนงาน', 'ordering' => 120]
        );
        $this->q($advanced->id, $comp1->id, $s12->id, '1', 'นโยบาย และแผนงาน/โครงการสนับสนุนการดำเนินงานหน่วยบริการสุขภาพผู้เดินทางประจำปี หรือมีแผนระยะยาว โดยครอบคลุมภารกิจที่เกี่ยวข้อง', 10, 'boolean');
        $this->q($advanced->id, $comp1->id, $s12->id, '2', 'แผนงาน/โครงการการพัฒนาและสนับสนุนงานบริการ', 20, 'boolean');
        $this->q($advanced->id, $comp1->id, $s12->id, '3', 'แผนงาน/โครงการการส่งเสริมพัฒนาศักยภาพบุคลากร', 30, 'boolean');
        $this->q($advanced->id, $comp1->id, $s12->id, '4', 'แผนงาน/โครงการการเฝ้าระวังป้องกันควบคุมโรคในผู้เดินทาง สื่อสารความเสี่ยงโรคและภัยสุขภาพในพื้นที่ให้กับผู้เดินทาง', 40, 'boolean');
        $this->q($advanced->id, $comp1->id, $s12->id, '5', 'แผนงาน/โครงการการสร้างความร่วมมือกับเครือข่ายที่เกี่ยวข้อง', 50, 'boolean');

        // 1.3) งบประมาณ
        $s13 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $advanced->id, 'assessment_component_id' => $comp1->id, 'code' => '1.3'],
            ['title' => 'งบประมาณ', 'ordering' => 130]
        );
        $this->q($advanced->id, $comp1->id, $s13->id, null, 'ได้รับการสนับสนุนงบประมาณสำหรับดำเนินการตามแผนงาน/โครงการที่กำหนด', 10, 'boolean');

        // 1.4) ผู้รับผิดชอบงาน
        $s14 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $advanced->id, 'assessment_component_id' => $comp1->id, 'code' => '1.4'],
            ['title' => 'ผู้รับผิดชอบงาน', 'ordering' => 140]
        );
        $this->q($advanced->id, $comp1->id, $s14->id, null, 'มีผู้รับผิดชอบงานที่ชัดเจน อาจเป็นกลุ่มงาน/แผนก หรืออยู่ในรูปคณะกรรมการ/คณะทำงานที่ร่วมขับเคลื่อนการพัฒนาหน่วยบริการสุขภาพผู้เดินทาง', 10, 'boolean');

        /* ===== องค์ประกอบที่ 2 กระบวนงาน ===== */
        $comp2 = AssessmentComponent::firstOrCreate(['no' => 2], ['name' => 'กระบวนงาน', 'short_name' => 'process']);

        // 2.1) งานบริการสุขภาพผู้เดินทาง
        $s21 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $advanced->id, 'assessment_component_id' => $comp2->id, 'code' => '2.1'],
            ['title' => 'งานบริการสุขภาพผู้เดินทาง', 'ordering' => 210]
        );
        $this->q($advanced->id, $comp2->id, $s21->id, '1', 'การให้บริการก่อนเดินทาง : ให้คำแนะนำ/ข้อมูลทั่วไปเกี่ยวกับสุขภาพผู้เดินทาง เช่น โรคและภัยสุขภาพที่อาจพบเจอจากการเดินทางท่องเที่ยว ข้อมูลทั่วไปของสถานที่/ประเทศปลายทาง (สภาพภูมิประเทศ สภาพภูมิอากาศ ความเสี่ยงด้านสุขภาพ) วัคซีนป้องกันโรคระหว่างประเทศที่สำคัญ เป็นต้น', 10, 'boolean');
        $this->q($advanced->id, $comp2->id, $s21->id, '2', 'การให้บริการก่อนเดินทาง : ให้คำปรึกษาเฉพาะบุคคล ณ หน่วยบริการ หรือผ่านโทรเวชกรรม (Telemedicine)', 20, 'boolean');
        $this->q($advanced->id, $comp2->id, $s21->id, '3', 'การให้บริการก่อนเดินทาง : ฉีดวัคซีนป้องกันโรคระหว่างประเทศ', 30, 'boolean');
        $this->q($advanced->id, $comp2->id, $s21->id, '4', 'การให้บริการก่อนเดินทาง : ให้ยาป้องกันโรคที่เกิดจากการเดินทางโดยเฉพาะยาป้องกันโรคแพ้ความสูง และยาป้องกันโรคมาลาเรีย', 40, 'boolean');
        $this->q($advanced->id, $comp2->id, $s21->id, '5', 'การให้บริการก่อนเดินทาง : การออกเอกสารรับรองการให้วัคซีน หรือยาป้องกันโรคระหว่างประเทศ', 50, 'boolean');
        $this->q($advanced->id, $comp2->id, $s21->id, '6', 'การให้บริการก่อนเดินทาง : ออกใบรับรองแพทย์ fit to fly/ศึกษาต่อ/อื่นๆ ที่เกี่ยวข้อง', 60, 'boolean');
        $this->q($advanced->id, $comp2->id, $s21->id, '7', 'การให้บริการขณะเดินทาง หรือหลังกลับจากการเดินทาง : ปรึกษาปัญหาเบื้องต้นที่เกิดขึ้นขณะเดินทาง หรือหลังกลับจากการเดินทาง ณ หน่วยบริการ หรือผ่านโทรเวชกรรม (Telemedicine)', 70, 'boolean');
        $this->q($advanced->id, $comp2->id, $s21->id, '8', 'การให้บริการขณะเดินทาง หรือหลังกลับจากการเดินทาง : รักษาโรคติดเชื้อ หรือภาวะผิดปกติที่เกิดขึ้นจากการเดินทางท่องเที่ยว', 80, 'boolean');
        $this->q($advanced->id, $comp2->id, $s21->id, '9', 'การให้บริการขณะเดินทาง หรือหลังกลับจากการเดินทาง : กักกันผู้ป่วย/แยกกักผู้สงสัยป่วยจากโรคติดเชื้อ', 90, 'boolean');
        $this->q($advanced->id, $comp2->id, $s21->id, '10', 'ตรวจสมรรถนะทางร่างกาย : คลื่นไฟฟ้าหัวใจ (EKG)', 100, 'boolean');
        $this->q($advanced->id, $comp2->id, $s21->id, '11', 'ตรวจสมรรถนะทางร่างกาย : สายตาและตาบอดสี', 110, 'boolean');
        $this->q($advanced->id, $comp2->id, $s21->id, '12', 'ตรวจสมรรถนะทางร่างกาย : การได้ยิน', 120, 'boolean');
        $this->q($advanced->id, $comp2->id, $s21->id, '13', 'ตรวจทางห้องปฏิบัติการและรังสีวิทยา : ตรวจทางห้องปฏิบัติการพื้นฐานจากตัวอย่างเลือด ปัสสาวะ อุจจาระ', 130, 'boolean');
        $this->q($advanced->id, $comp2->id, $s21->id, '14', 'ตรวจทางห้องปฏิบัติการและรังสีวิทยา : Tuberculin skin test หรือ IGRA', 140, 'boolean');
        $this->q($advanced->id, $comp2->id, $s21->id, '15', 'ตรวจทางห้องปฏิบัติการและรังสีวิทยา : ตรวจระดับภูมิคุ้มกันในร่างกาย', 150, 'boolean');
        $this->q($advanced->id, $comp2->id, $s21->id, '16', 'ตรวจทางห้องปฏิบัติการและรังสีวิทยา : ตรวจเพื่อการวินิจฉัยโรคติดเชื้อ เช่น RT-PCR test สำหรับเชื้อ SARS-CoV-2, malaria thick/thin film, Antigen/ Serology test for dengue infection', 160, 'boolean');
        $this->q($advanced->id, $comp2->id, $s21->id, '17', 'ตรวจทางรังสีวิทยา', 170, 'boolean');

        // 2.2) งานสนับสนุน
        $s22 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $advanced->id, 'assessment_component_id' => $comp2->id, 'code' => '2.2'],
            ['title' => 'งานสนับสนุน', 'ordering' => 220]
        );
        $this->q($advanced->id, $comp2->id, $s22->id, '1', 'มีการเฝ้าระวังป้องกันโรคและภัยสุขภาพเชิงรุก', 10, 'boolean');
        $this->q($advanced->id, $comp2->id, $s22->id, '2', 'มีการประเมินความเสี่ยงโรคและภัยสุขภาพในสถานที่ท่องเที่ยว', 20, 'boolean');
        $this->q($advanced->id, $comp2->id, $s22->id, '3', 'มีการลงพื้นที่ให้ความรู้แก่ผู้เดินทางตามสถานที่ท่องเที่ยว ที่พัก', 30, 'boolean');
        $this->q($advanced->id, $comp2->id, $s22->id, '4', 'มีแผนการช่วยเหลือผู้ป่วยฉุกเฉิน ทั้งกรณีการช่วยชีวิตขั้นพื้นฐาน และภาวะแพ้รุนแรง (Anaphylaxis) และควรมีการซ้อมแผนอย่างน้อยปีละ 1 ครั้ง', 40, 'boolean');
        $this->q($advanced->id, $comp2->id, $s22->id, '5', 'มีแผนการส่งต่อผู้ป่วยไปรับการรักษาต่อ และควรมีการซ้อมแผนอย่างน้อยปีละ 1 ครั้ง', 50, 'boolean');
        $this->q($advanced->id, $comp2->id, $s22->id, '6', 'มีการศึกษาวิจัย และพัฒนาองค์ความรู้ด้านเวชศาสตร์การเดินทางและท่องเที่ยว', 60, 'boolean');

        /* ===== องค์ประกอบที่ 3 บุคลากร ===== */
        $comp3 = AssessmentComponent::firstOrCreate(['no' => 3], ['name' => 'บุคลากร', 'short_name' => 'personnel']);

        // 3.1) ตำแหน่ง
        $s31 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $advanced->id, 'assessment_component_id' => $comp3->id, 'code' => '3.1'],
            ['title' => 'ตำแหน่ง', 'ordering' => 310]
        );
        $this->q($advanced->id, $comp3->id, $s31->id, '1', 'แพทย์ วุฒิบัตร/อนุมัติบัตรเพื่อแสดงความรู้ความชำนาญในการประกอบวิชาชีพเวชกรรม สาขาเวชศาสตร์ป้องกัน แขนงเวชศาสตร์การเดินทางและท่องเที่ยว', 10, 'boolean');
        $this->q($advanced->id, $comp3->id, $s31->id, '2', 'พยาบาล', 20, 'boolean');
        $this->q($advanced->id, $comp3->id, $s31->id, '3', 'เภสัชกร', 30, 'boolean');
        $this->q($advanced->id, $comp3->id, $s31->id, '4', 'นักวิชาการสาธารณสุข หรือบุคลากรทางการแพทย์และสาธารณสุขอื่นๆ ซึ่งอยู่ภายใต้การกำกับดูแลของผู้มีใบประกอบวิชาชีพเวชกรรม', 40, 'boolean');

        // 3.2) สมรรถนะบุคลากร
        $s32 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $advanced->id, 'assessment_component_id' => $comp3->id, 'code' => '3.2'],
            ['title' => 'สมรรถนะบุคลากร', 'ordering' => 320]
        );
        $this->q($advanced->id, $comp3->id, $s32->id, '1', 'บุคลากรที่ให้บริการผู้รับบริการโดยตรง มีความรู้และทักษะด้านเวชศาสตร์ การเดินทางและท่องเที่ยว (ภาคผนวก 5) เช่น ผ่านการอบรมของกรมควบคุมโรค สมาคมเวชศาสตร์ป้องกันแห่งประเทศไทย หรือคณะเวชศาสตร์เขตร้อน มหาวิทยาลัยมหิดล เป็นต้น', 10, 'boolean');
        $this->q($advanced->id, $comp3->id, $s32->id, '2', 'บุคลากรที่ให้บริการผู้รับบริการโดยตรง สามารถสื่อสารภาษาอังกฤษ หรือ ภาษาต่างชาติอื่นๆ ได้ในระดับพื้นฐาน', 20, 'boolean');
        $this->q($advanced->id, $comp3->id, $s32->id, '3', 'บุคลากรทุกคน ได้รับฝึกอบรมการช่วยชีวิตขั้นพื้นฐาน (basic life support)', 30, 'boolean');
        $this->q($advanced->id, $comp3->id, $s32->id, '4', 'แพทย์มีองค์ความรู้และทักษะเบื้องต้นในเรื่อง วัคซีน และยาป้องกันและรักษาโรคที่เกิดจากการเดินทาง', 40, 'boolean');
        $this->q($advanced->id, $comp3->id, $s32->id, '5', 'แพทย์มีองค์ความรู้และทักษะเบื้องต้นในเรื่อง การออกหนังสือรับรองการสร้างเสริมภูมิคุ้มกันโรค', 50, 'boolean');
        $this->q($advanced->id, $comp3->id, $s32->id, '6', 'แพทย์มีองค์ความรู้และทักษะเบื้องต้นในเรื่อง โรคที่เกี่ยวข้องกับการเดินทาง ตามระบาดวิทยาของโรคในพื้นที่', 60, 'boolean');
        $this->q($advanced->id, $comp3->id, $s32->id, '7', 'แพทย์มีองค์ความรู้และทักษะเบื้องต้นในเรื่อง การให้คำปรึกษาก่อนการเดินทางในผู้เดินทางที่มีปัญหาสุขภาพซับซ้อน', 70, 'boolean');
        $this->q($advanced->id, $comp3->id, $s32->id, '8', 'แพทย์มีองค์ความรู้และทักษะเบื้องต้นในเรื่อง การให้คำปรึกษาก่อนการเดินทางในผู้เดินทางที่มีกิจกรรมต่างๆ', 80, 'boolean');
        $this->q($advanced->id, $comp3->id, $s32->id, '9', 'แพทย์มีองค์ความรู้และทักษะเบื้องต้นในเรื่อง การให้การตรวจวินิจฉัย และรักษาโรคที่เกิดขึ้นระหว่าง หรือหลังการเดินทาง', 90, 'boolean');

        /* ===== องค์ประกอบที่ 4 อาคาร สถานที่ ===== */
        $comp4 = AssessmentComponent::firstOrCreate(['no' => 4], ['name' => 'อาคาร สถานที่', 'short_name' => 'facility']);

        $s41 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $advanced->id, 'assessment_component_id' => $comp4->id, 'code' => '4.1'],
            ['title' => 'อาคาร สถานที่', 'ordering' => 410]
        );
        $this->q($advanced->id, $comp4->id, $s41->id, '1', 'มีป้ายชื่อหน่วยบริการสุขภาพผู้เดินทางที่ชัดเจน ให้ผู้รับบริการมองเห็นได้ง่าย', 10, 'boolean');
        $this->q($advanced->id, $comp4->id, $s41->id, '2', 'มีการจัดสถานที่ หน่วยบริการสุขภาพผู้เดินทาง แยกเป็นสัดส่วนชัดเจนจากคลินิกหรืองานบริการอื่น', 20, 'boolean');
        $this->q($advanced->id, $comp4->id, $s41->id, '3', 'มีจุดคัดกรองโรคผู้มารับบริการ', 30, 'boolean');
        $this->q($advanced->id, $comp4->id, $s41->id, '4', 'มีห้องฉีดวัคซีน', 40, 'boolean');
        $this->q($advanced->id, $comp4->id, $s41->id, '5', 'มีสถานที่ให้ผู้รับบริการนั่งรอสังเกตอาการ หลังจากได้รับวัคซีน', 50, 'boolean');
        $this->q($advanced->id, $comp4->id, $s41->id, '6', 'มีสถานที่ที่สามารถให้การดูแลรักษากรณีฉุกเฉิน เช่น การช่วยชีวิตขั้นพื้นฐานได้อย่างสะดวกรวดเร็ว', 60, 'boolean');
        $this->q($advanced->id, $comp4->id, $s41->id, '7', 'มีสถานที่เฉพาะสำหรับแยกผู้ป่วยโรคติดเชื้อ ขณะรอเคลื่อนย้ายหรือส่งต่อ', 70, 'boolean');

        /* ===== องค์ประกอบที่ 5 เครื่องมือ เครื่องใช้ ยา เวชภัณฑ์ และเอกสารทางการแพทย์ ===== */
        $comp5 = AssessmentComponent::firstOrCreate(
            ['no' => 5],
            ['name' => 'เครื่องมือ เครื่องใช้ ยา เวชภัณฑ์ และเอกสารทางการแพทย์', 'short_name' => 'equipment']
        );

        // 5.1) เครื่องมือ เครื่องใช้
        $s51 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $advanced->id, 'assessment_component_id' => $comp5->id, 'code' => '5.1'],
            ['title' => 'เครื่องมือ เครื่องใช้', 'ordering' => 510]
        );
        $this->q($advanced->id, $comp5->id, $s51->id, '1', 'ตู้หรืออุปกรณ์เก็บเวชระเบียน', 10, 'boolean');
        $this->q($advanced->id, $comp5->id, $s51->id, '2', 'ตู้หรือชั้นเก็บยาและเวชภัณฑ์อื่น', 20, 'boolean');
        $this->q($advanced->id, $comp5->id, $s51->id, '3', 'เครื่องมืออุปกรณ์และเวชภัณฑ์สำหรับควบคุมการติดเชื้อในกรณีที่จำเป็นต้องมี', 30, 'boolean');
        $this->q($advanced->id, $comp5->id, $s51->id, '4', 'อุปกรณ์ช่วยเหลือผู้ป่วยฉุกเฉิน', 40, 'boolean');
        $this->q($advanced->id, $comp5->id, $s51->id, '5', 'ชุดตรวจโรคและชุดให้การรักษาทั่วไปตามมาตรฐานการประกอบวิชาชีพ', 50, 'boolean');
        $this->q($advanced->id, $comp5->id, $s51->id, '6', 'ตู้เย็นสำหรับเก็บยาหรือเวชภัณฑ์อื่น', 60, 'boolean');
        $this->q($advanced->id, $comp5->id, $s51->id, '7', 'อุปกรณ์เทคโนโลยีสารสนเทศ พร้อมระบบเครือข่ายอินเทอร์เน็ต สำหรับสืบค้นข้อมูลทั่วไปเกี่ยวกับสุขภาพผู้เดินทาง', 70, 'boolean');
        $this->q($advanced->id, $comp5->id, $s51->id, '8', 'อุปกรณ์สำหรับการออกเอกสารรับรองการให้วัคซีน หรือยาป้องกันโรคระหว่างประเทศ', 80, 'boolean');

        // 5.2) ยา
        $s52 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $advanced->id, 'assessment_component_id' => $comp5->id, 'code' => '5.2'],
            ['title' => 'ยา', 'ordering' => 520]
        );
        $this->q($advanced->id, $comp5->id, $s52->id, '1', "Traveler’s diarrhea : Norfloxacin", 10, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '2', "Traveler’s diarrhea : Ciprofloxacin", 20, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '3', "Traveler’s diarrhea : Azithromycin", 30, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '4', "Traveler’s diarrhea : ORS", 40, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '5', "Traveler’s diarrhea : Loperamide", 50, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '6', "Traveler’s diarrhea : BSS", 60, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '7', "Traveler’s diarrhea : Buscopan (Hyoscine butylbromide)", 70, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '8', "Traveler’s diarrhea : Domperidone", 80, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '9', 'Altitude illness : Acetazolamide', 90, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '10', 'Altitude illness : Dexamethasone', 100, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '11', 'Altitude illness : Nifedipine SR', 110, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '12', 'Antimalarial : Chloroquine', 120, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '13', 'Antimalarial : primaquine', 130, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '14', 'Antimalarial : DHA-PIP', 140, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '15', 'Antimalarial : Mefloquine', 150, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '16', 'Antimalarial : Atovaquone -proguanil', 160, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '17', 'Antimalarial : Doxycycline', 170, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '18', 'Antimalarial : Artesunate', 180, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '19', 'ยาแก้ปวด : Paracetamol', 190, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '20', 'ยาแก้ปวด : NSAIDs (e.g. ibuprofen, naproxen, celecoxib)', 200, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '21', 'ยาแก้อาการเมารถ : Scopolamine', 210, 'boolean');
        $this->q($advanced->id, $comp5->id, $s52->id, '22', 'ยาแก้อาการเมารถ : Dimenhydrinate', 220, 'boolean');

        // 5.3) วัคซีน
        $s53 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $advanced->id, 'assessment_component_id' => $comp5->id, 'code' => '5.3'],
            ['title' => 'วัคซีน', 'ordering' => 530]
        );
        $this->q($advanced->id, $comp5->id, $s53->id, '1', 'Yellow fever', 10, 'boolean');
        $this->q($advanced->id, $comp5->id, $s53->id, '2', 'Meningococcal ACYW', 20, 'boolean');
        $this->q($advanced->id, $comp5->id, $s53->id, '3', 'Cholera', 30, 'boolean');
        $this->q($advanced->id, $comp5->id, $s53->id, '4', 'Influenza', 40, 'boolean');
        $this->q($advanced->id, $comp5->id, $s53->id, '5', 'IPV', 50, 'boolean');
        $this->q($advanced->id, $comp5->id, $s53->id, '6', 'Tdap/Td/DTaP/aP', 60, 'boolean');
        $this->q($advanced->id, $comp5->id, $s53->id, '7', 'Japanese Encephalitis', 70, 'boolean');
        $this->q($advanced->id, $comp5->id, $s53->id, '8', 'MMR', 80, 'boolean');
        $this->q($advanced->id, $comp5->id, $s53->id, '9', 'Hepatitis B', 90, 'boolean');
        $this->q($advanced->id, $comp5->id, $s53->id, '10', 'HPV', 100, 'boolean');
        $this->q($advanced->id, $comp5->id, $s53->id, '11', 'Varicella', 110, 'boolean');
        $this->q($advanced->id, $comp5->id, $s53->id, '12', 'Zoster', 120, 'boolean');
        $this->q($advanced->id, $comp5->id, $s53->id, '13', 'Hepatitis A/ Hepatitis A/B combination', 130, 'boolean');
        $this->q($advanced->id, $comp5->id, $s53->id, '14', 'Rabies', 140, 'boolean');
        $this->q($advanced->id, $comp5->id, $s53->id, '15', 'Typhoid', 150, 'boolean');
        $this->q($advanced->id, $comp5->id, $s53->id, '16', 'Pneumococcal', 160, 'boolean');
        $this->q($advanced->id, $comp5->id, $s53->id, '17', 'Covid 19', 170, 'boolean');

        // 5.4) เอกสารทางการแพทย์
        $s54 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $advanced->id, 'assessment_component_id' => $comp5->id, 'code' => '5.4'],
            ['title' => 'เอกสารทางการแพทย์', 'ordering' => 540]
        );
        $this->q($advanced->id, $comp5->id, $s54->id, '1', 'แบบฟอร์มคัดกรองสุขภาพผู้รับบริการ', 10, 'boolean');
        $this->q($advanced->id, $comp5->id, $s54->id, '2', 'ใบยินยอมรับการฉีดวัคซีน/การรักษา', 20, 'boolean');
        $this->q($advanced->id, $comp5->id, $s54->id, '3', 'เอกสารรับรองการให้วัคซีนหรือยาป้องกันโรคระหว่างประเทศ', 30, 'boolean');
        $this->q($advanced->id, $comp5->id, $s54->id, '4', 'ใบรับรองแพทย์', 40, 'boolean');

        /* ===== องค์ประกอบที่ 6 ระบบ เทคโนโลยี และแหล่งข้อมูล ===== */
        $comp6 = AssessmentComponent::firstOrCreate(
            ['no' => 6],
            ['name' => 'ระบบ เทคโนโลยี และแหล่งข้อมูล', 'short_name' => 'system']
        );

        // 6.1) ระบบ และเทคโนโลยี
        $s61 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $advanced->id, 'assessment_component_id' => $comp6->id, 'code' => '6.1'],
            ['title' => 'ระบบ และเทคโนโลยี', 'ordering' => 610]
        );
        $this->q($advanced->id, $comp6->id, $s61->id, '1', 'ระบบเวชระเบียน', 10, 'boolean');
        $this->q($advanced->id, $comp6->id, $s61->id, '2', 'ระบบออกเอกสารรับรองการฉีดวัคซีน เพื่อการเดินทางระหว่างประเทศ (INTERVAC)', 20, 'boolean');
        $this->q($advanced->id, $comp6->id, $s61->id, '3', 'ระบบนัดหมายผู้รับบริการ', 30, 'boolean');
        $this->q($advanced->id, $comp6->id, $s61->id, '4', 'ระบบคลังยาและเวชภัณฑ์', 40, 'boolean');
        $this->q($advanced->id, $comp6->id, $s61->id, '5', 'ระบบอื่นๆ (ถ้ามี) เช่น ระบบโทรเวชกรรม', 50, 'boolean');

        // 6.2) ฐานข้อมูลงานบริการ
        $s62 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $advanced->id, 'assessment_component_id' => $comp6->id, 'code' => '6.2'],
            ['title' => 'ฐานข้อมูลงานบริการ', 'ordering' => 620]
        );
        $this->q($advanced->id, $comp6->id, $s62->id, '1', 'ฐานข้อมูลทั่วไปของผู้รับบริการ เช่น ชื่อ-นามสกุล สัญชาติ เพศ อายุ และวัตถุประสงค์การเข้ารับบริการ เป็นต้น', 10, 'boolean');
        $this->q($advanced->id, $comp6->id, $s62->id, '2', 'ฐานข้อมูลการให้บริการ เช่น การรับคำปรึกษา คำแนะนำ ยาและวัคซีนที่ได้รับ การออกเอกสารรับรองต่างๆ การเฝ้าระวังติดตามหลังกลับจากการเดินทาง ข้อมูลการเจ็บป่วย ตรวจรักษา และส่งต่อ', 20, 'boolean');

        // 6.3) ทำเนียบ
        $s63 = AssessmentSection::updateOrCreate(
            ['assessment_level_id' => $advanced->id, 'assessment_component_id' => $comp6->id, 'code' => '6.3'],
            ['title' => 'ทำเนียบ', 'ordering' => 630]
        );
        $this->q($advanced->id, $comp6->id, $s63->id, '1', 'มีทำเนียบเครือข่ายแพทย์เฉพาะทาง เพื่อให้หน่วยบริการสามารถขอรับคำปรึกษาระหว่างหน่วยงานได้', 10, 'boolean');
        $this->q($advanced->id, $comp6->id, $s63->id, '2', 'มีทำเนียบเครือข่ายหน่วยบริการสาธารณสุขที่เกี่ยวข้องกับการส่งต่อ หรือรับการรักษาต่อ', 20, 'boolean');
        $this->q($advanced->id, $comp6->id, $s63->id, '3', 'มีทำเนียบเครือข่ายเฝ้าระวังแจ้งเตือน สอบสวน ควบคุมโรคในพื้นที่', 30, 'boolean');
        $this->q($advanced->id, $comp6->id, $s63->id, '4', 'มีทำเนียบเครือข่ายสื่อสารประชาสัมพันธ์ด้านสุขภาพผู้เดินทางในพื้นที่ เช่น การท่องเที่ยวจังหวัด สำนักงานท่องเที่ยวและกีฬา เครือข่ายมัคคุเทศก์ และเครือข่ายโรงแรม/ที่พัก เป็นต้น', 40, 'boolean');
    }

    private function q($lv, $cp, $sec, $code, $text, $ord, $type = 'boolean', $opts = null): void
    {
        AssessmentQuestion::updateOrCreate(
            ['assessment_level_id' => $lv, 'assessment_component_id' => $cp, 'assessment_section_id' => $sec, 'code' => $code],
            ['text' => $text, 'answer_type' => $type, 'options' => $opts, 'ordering' => $ord, 'is_active' => true]
        );
    }
}
