<?php

if (!function_exists('thGeoSelect')) {
    /**
     * เรนเดอร์ชุด select จังหวัด/อำเภอ/ตำบล
     *
     * @param  string $namePrefix   prefix ของ field เช่น 'org_' -> org_province_code, org_district_code, org_subdistrict_code
     * @param  array  $init         ค่าเริ่มต้น ['province_code'=>10, 'district_code'=>1001, 'subdistrict_code'=>100101]
     * @param  array  $labels       ปรับ label ได้ ถ้าอยาก
     * @return string
     */
    function thGeoSelect(string $namePrefix = 'org_', array $init = [], array $labels = []): string
    {
        $labels = array_merge([
            'province'    => 'จังหวัด',
            'district'    => 'อำเภอ/เขต',
            'subdistrict' => 'ตำบล/แขวง',
        ], $labels);

        return view('partials.geo-select-th', compact('namePrefix', 'init', 'labels'))->render();
    }
}
