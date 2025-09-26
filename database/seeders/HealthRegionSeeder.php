<?php

// database/seeders/HealthRegionSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HealthRegionSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['id' => 1, 'code' => 1, 'title' => 'สำนักงานป้องกันควบคุมโรค/เขตสุขภาพที่ 1 เชียงใหม่', 'short_title' => 'สคร.1', 'hq_province' => 'เชียงใหม่'],
            ['id' => 2, 'code' => 2, 'title' => 'สำนักงานป้องกันควบคุมโรค/เขตสุขภาพที่ 2 พิษณุโลก', 'short_title' => 'สคร.2', 'hq_province' => 'พิษณุโลก'],
            ['id' => 3, 'code' => 3, 'title' => 'สำนักงานป้องกันควบคุมโรค/เขตสุขภาพที่ 3 นครสวรรค์', 'short_title' => 'สคร.3', 'hq_province' => 'นครสวรรค์'],
            ['id' => 4, 'code' => 4, 'title' => 'สำนักงานป้องกันควบคุมโรค/เขตสุขภาพที่ 4 สระบุรี', 'short_title' => 'สคร.4', 'hq_province' => 'สระบุรี'],
            ['id' => 5, 'code' => 5, 'title' => 'สำนักงานป้องกันควบคุมโรค/เขตสุขภาพที่ 5 ราชบุรี', 'short_title' => 'สคร.5', 'hq_province' => 'ราชบุรี'],
            ['id' => 6, 'code' => 6, 'title' => 'สำนักงานป้องกันควบคุมโรค/เขตสุขภาพที่ 6 ชลบุรี', 'short_title' => 'สคร.6', 'hq_province' => 'ชลบุรี'],
            ['id' => 7, 'code' => 7, 'title' => 'สำนักงานป้องกันควบคุมโรค/เขตสุขภาพที่ 7 ขอนแก่น', 'short_title' => 'สคร.7', 'hq_province' => 'ขอนแก่น'],
            ['id' => 8, 'code' => 8, 'title' => 'สำนักงานป้องกันควบคุมโรค/เขตสุขภาพที่ 8 อุดรธานี', 'short_title' => 'สคร.8', 'hq_province' => 'อุดรธานี'],
            ['id' => 9, 'code' => 9, 'title' => 'สำนักงานป้องกันควบคุมโรค/เขตสุขภาพที่ 9 นครราชสีมา', 'short_title' => 'สคร.9', 'hq_province' => 'นครราชสีมา'],
            ['id' => 10, 'code' => 10, 'title' => 'สำนักงานป้องกันควบคุมโรค/เขตสุขภาพที่ 10 อุบลราชธานี', 'short_title' => 'สคร.10', 'hq_province' => 'อุบลราชธานี'],
            ['id' => 11, 'code' => 11, 'title' => 'สำนักงานป้องกันควบคุมโรค/เขตสุขภาพที่ 11 นครศรีธรรมราช', 'short_title' => 'สคร.11', 'hq_province' => 'นครศรีธรรมราช'],
            ['id' => 12, 'code' => 12, 'title' => 'สำนักงานป้องกันควบคุมโรค/เขตสุขภาพที่ 12 สงขลา', 'short_title' => 'สคร.12', 'hq_province' => 'สงขลา'],
            ['id' => 13, 'code' => 13, 'title' => 'สถาบันป้องกันควบคุมโรคเขตเมือง/เขตสุขภาพที่ 13 กรุงเทพมหานคร', 'short_title' => 'สคร.13', 'hq_province' => 'กรุงเทพมหานคร'],
        ];

        DB::table('health_regions')->upsert($rows, ['id'], ['title', 'short_title', 'hq_province', 'code']);
    }
}
