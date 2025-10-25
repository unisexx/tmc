<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ServiceUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $affWhitelist = [
            'สำนักงานปลัดกระทรวงสาธารณสุข', 'กรมควบคุมโรค', 'กรมการแพทย์', 'กรมสุขภาพจิต',
            'สภากาชาดไทย', 'สำนักการแพทย์ กรุงเทพมหานคร',
            'กระทรวงอุดมศึกษา วิทยาศาสตร์ วิจัยและนวัตกรรม', 'กระทรวงกลาโหม',
            'องค์กรปกครองส่วนท้องถิ่น', 'องค์การมหาชน', 'เอกชน', 'อื่น ๆ',
        ];

        return [
            'org_name'              => ['required', 'string', 'max:255'],
            'org_affiliation'       => ['required', 'string', 'max:255', Rule::in($affWhitelist)],
            'org_affiliation_other' => [
                'nullable', 'string', 'max:255',
                Rule::requiredIf(fn() => $this->input('org_affiliation') === 'อื่น ๆ'),
            ],
            'org_tel'               => ['nullable', 'string', 'max:60'],
            'org_email'             => ['nullable', 'string', 'email', 'max:255'],
            'org_address'           => ['nullable', 'string', 'max:1000'],

            'org_province_code'     => ['required', 'string'],
            'org_district_code'     => ['required', 'string'],
            'org_subdistrict_code'  => ['required', 'string'],
            'org_postcode'          => ['nullable', 'string', 'size:5'],

            'org_lat'               => ['nullable', 'numeric', 'between:-90,90'],
            'org_lng'               => ['nullable', 'numeric', 'between:-180,180'],

            // ส่งมาเป็นสตริง JSON จาก widget ตารางเวลา
            'working_hours_json'    => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'required'                    => 'กรุณากรอก :attribute',
            'string'                      => ':attribute ต้องเป็นข้อความตัวอักษร',
            'max'                         => 'ความยาวของ :attribute ต้องไม่เกิน :max ตัวอักษร',
            'size'                        => ':attribute ต้องมีความยาว :size หลัก',
            'numeric'                     => ':attribute ต้องเป็นตัวเลข',
            'between'                     => ':attribute ต้องอยู่ระหว่าง :min ถึง :max',
            'in'                          => ':attribute ไม่ถูกต้อง',
            'working_hours_json.required' => 'กรุณากำหนดวัน-เวลาทำการ',
        ];
    }

    public function attributes(): array
    {
        return [
            'org_name'              => 'ชื่อหน่วยบริการ/หน่วยงาน',
            'org_affiliation'       => 'สังกัด',
            'org_affiliation_other' => 'โปรดระบุสังกัด',
            'org_tel'               => 'หมายเลขโทรศัพท์',
            'org_email'             => 'อีเมล์หน่วยบริการ',
            'org_address'           => 'ที่อยู่หน่วยบริการ',
            'org_province_code'     => 'จังหวัด',
            'org_district_code'     => 'อำเภอ',
            'org_subdistrict_code'  => 'ตำบล',
            'org_postcode'          => 'รหัสไปรษณีย์',
            'org_lat'               => 'ละติจูด (Latitude)',
            'org_lng'               => 'ลองจิจูด (Longitude)',
            'working_hours_json'    => 'วัน-เวลาทำการ',
        ];
    }

    /**
     * ตรวจความถูกต้องของ JSON ตารางเวลาแบบละเอียด
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $json = $this->input('working_hours_json');

            if ($json === null || $json === '') {
                return; // ไม่บังคับต้องกรอก
            }

            try {
                $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                $v->errors()->add('working_hours_json', 'รูปแบบ JSON ไม่ถูกต้อง');
                return;
            }

            $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
            foreach ($days as $d) {
                if (!isset($data[$d]) || !is_array($data[$d])) {
                    $v->errors()->add('working_hours_json', "รูปแบบข้อมูลของวัน {$d} ไม่ถูกต้อง");
                    return;
                }
                foreach ($data[$d] as $rng) {
                    if (!is_string($rng) || !preg_match('/^\d{2}:\d{2}-\d{2}:\d{2}$/', $rng)) {
                        $v->errors()->add('working_hours_json', "ช่วงเวลาในวัน {$d} ต้องอยู่ในรูปแบบ HH:MM-HH:MM");
                        return;
                    }
                    [$a, $b] = explode('-', $rng, 2);
                    if ($b <= $a) {
                        $v->errors()->add('working_hours_json', "เวลาสิ้นสุดต้องมากกว่าเวลาเริ่ม (วัน {$d})");
                        return;
                    }
                }
            }
        });
    }

    /**
     * แปลง JSON ตารางเวลา → array พร้อมบันทึกลงคอลัมน์ JSON
     */
    public function parsedWorkingHours(): array
    {
        $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $out  = array_fill_keys($days, []);
        $json = $this->input('working_hours_json');

        if (!$json) {
            return $out;
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            foreach ($days as $d) {
                $out[$d] = array_values(array_filter(
                    (array) ($data[$d] ?? []),
                    fn($x) => is_string($x) && preg_match('/^\d{2}:\d{2}-\d{2}:\d{2}$/', $x)
                ));
            }
        } catch (\Throwable $e) {
            // ถ้า parse พลาด ให้คืนค่าเปล่า (ไม่ทำให้ transaction ล้ม)
        }

        return $out;
    }

    /**
     * Log error ให้ตามรอยง่ายเมื่อ validate ไม่ผ่าน
     */
    protected function failedValidation(Validator $validator)
    {
        \Log::warning('Validation failed in ServiceUnitRequest', [
            'errors' => $validator->errors()->toArray(),
        ]);

        throw (new ValidationException($validator))
            ->errorBag($this->errorBag)
            ->redirectTo($this->getRedirectUrl());
    }
}
