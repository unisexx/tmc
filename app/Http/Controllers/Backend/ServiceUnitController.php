<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\ServiceUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServiceUnitController extends Controller
{
    public function switch(Request $req)
    {
            $user  = Auth::user();
            $input = $req->input('service_unit_id');

            // 🟦 1) ถ้าเป็นแอดมินและเลือก "ภาพรวม" (ไม่เลือกหน่วย)
            if ($user->isAdmin() && ($input === null || $input === '')) {
                session()->forget('current_service_unit_id');
                flash_notify('เข้าสู่มุมมองภาพรวม (ไม่ผูกกับหน่วยบริการ)', 'success');
                return redirect()->route('backend.dashboard');
            }

            // 🟦 2) ตรวจสอบค่าปกติ
            $id = (int) $input;
            if (!$id) {
                flash_notify('ไม่พบรหัสหน่วยบริการที่ต้องการสลับ', 'warning');
                return back();
            }

            // 🟦 3) ตรวจสอบสิทธิ์การเข้าถึง
            if ($user->isAdmin()) {
                $unit = ServiceUnit::find($id);
            } else {
                $unit = $user->serviceUnits()->where('service_units.id', $id)->first();
            }

            if (!$unit) {
                flash_notify('คุณไม่มีสิทธิ์สลับไปยังหน่วยบริการนี้ หรือหน่วยบริการไม่พบ', 'warning');
                return back();
            }

            // 🟦 4) ตั้งค่า session
            session(['current_service_unit_id' => $unit->id]);

            flash_notify('สลับหน่วยบริการเรียบร้อยแล้ว', 'success');
            return redirect()->route('backend.dashboard');
    }

    public function index(Request $request)
    {
        $q            = trim($request->get('q'));
        $provinceCode = $request->get('province');
        $affiliation  = $request->get('affiliation');

        $query = ServiceUnit::query()
            ->with(['province', 'district', 'subdistrict'])
            ->when($q, fn($qbuilder) =>
                $qbuilder->where(function ($sub) use ($q) {
                    $sub->where('org_name', 'like', "%{$q}%")
                        ->orWhere('org_tel', 'like', "%{$q}%")
                        ->orWhere('org_address', 'like', "%{$q}%");
                })
            )
            ->when($provinceCode, fn($qbuilder) =>
                $qbuilder->where('org_province_code', $provinceCode)
            )
            ->when($affiliation, fn($qbuilder) =>
                $qbuilder->where('org_affiliation', $affiliation)
            )
            ->orderBy('org_name');

        $serviceUnits = $query->paginate(10)->withQueryString();
        $provinces    = Province::orderBy('title')->pluck('title', 'code');

        return view('backend.service_unit.index', compact(
            'serviceUnits', 'q', 'provinces', 'provinceCode', 'affiliation'
        ));
    }

    public function create()
    {
        // โมเดลว่างเพื่อใช้ old() + ค่าเริ่มต้นในฟอร์มได้สะดวก
        $unit = new ServiceUnit([
            'org_lat'                => null,
            'org_lng'                => null,
            'org_working_hours_json' => [], // ฟอร์มจะ json_encode เองอยู่แล้ว
        ]);

        // รายชื่อจังหวัดสำหรับ chain select
        $provinces = Province::orderBy('title')->pluck('title', 'code');

        return view('backend.service_unit.create', compact('unit', 'provinces'));
    }

    public function store(Request $request)
    {
        [$data] = $this->validatedServiceUnit($request);

        // แปลง JSON → array
        $data['org_working_hours_json'] = $this->parseWorkingGridJson($request->input('working_hours_json'));

        // กันฟิลด์เกิน
        $payload = collect($data)->only([
            'org_name',
            'org_affiliation',
            'org_affiliation_other',
            'org_address',
            'org_province_code',
            'org_district_code',
            'org_subdistrict_code',
            'org_postcode',
            'org_tel',
            'org_lat',
            'org_lng',
            'org_working_hours_json',
        ])->toArray();

        $unit = ServiceUnit::create($payload);

        flash_notify('เพิ่มหน่วยบริการเรียบร้อยแล้ว', 'success');
        return redirect()->route('backend.service-unit.edit', $unit->id);
    }

    public function edit($id)
    {
        $unit      = ServiceUnit::with(['province', 'district', 'subdistrict'])->findOrFail($id);
        $provinces = Province::orderBy('title')->pluck('title', 'code');

        return view('backend.service_unit.edit', compact('unit', 'provinces'));
    }

    public function update(Request $request, $id)
    {
        $unit = ServiceUnit::findOrFail($id);

        [$data] = $this->validatedServiceUnit($request);

        $data['org_working_hours_json'] = $this->parseWorkingGridJson($request->input('working_hours_json'));

        $payload = collect($data)->only([
            'org_name',
            'org_affiliation',
            'org_affiliation_other',
            'org_address',
            'org_province_code',
            'org_district_code',
            'org_subdistrict_code',
            'org_postcode',
            'org_tel',
            'org_lat',
            'org_lng',
            'org_working_hours_json',
        ])->toArray();

        $unit->update($payload);

        flash_notify('บันทึกการแก้ไขแล้ว', 'success');
        return redirect()->route('backend.service-unit.edit', $unit->id);
    }

    public function destroy($id)
    {
        $unit = ServiceUnit::findOrFail($id);
        $unit->delete();

        flash_notify('ลบหน่วยบริการเรียบร้อยแล้ว', 'success');
        return redirect()->route('backend.service-unit.index');
    }

    /* ==================== Helpers ==================== */

    /**
     * ยก validation หน่วยบริการจาก ApplicationReviewController
     * คืนค่า: [$dataพร้อม normalize]
     */
    private function validatedServiceUnit(Request $request): array
    {
        $affWhitelist = [
            'สำนักงานปลัดกระทรวงสาธารณสุข', 'กรมควบคุมโรค', 'กรมการแพทย์', 'กรมสุขภาพจิต',
            'สภากาชาดไทย', 'สำนักการแพทย์ กรุงเทพมหานคร',
            'กระทรวงอุดมศึกษา วิทยาศาสตร์ วิจัยและนวัตกรรม', 'กระทรวงกลาโหม',
            'องค์กรปกครองส่วนท้องถิ่น', 'องค์การมหาชน', 'เอกชน', 'อื่น ๆ',
        ];

        $rules = [
            'org_name'              => ['required', 'string', 'max:255'],
            'org_affiliation'       => ['required', 'string', Rule::in($affWhitelist), 'max:255'],
            'org_affiliation_other' => ['nullable', 'string', 'max:255',
                Rule::requiredIf(fn() => $this->isOthers($request->input('org_affiliation'))),
            ],
            'org_tel'               => ['required', 'string', 'max:60'],
            'org_address'           => ['required', 'string', 'max:1000'],
            'org_lat'               => ['nullable', 'numeric', 'between:-90,90'],
            'org_lng'               => ['nullable', 'numeric', 'between:-180,180'],

            // ขนาด/exists ให้สอดคล้องกับตารางรหัสอ้างอิงของคุณ (ตามที่ใช้ใน ApplicationReviewController)
            'org_province_code'     => ['required', 'string', 'size:2', Rule::exists('province', 'code')],
            'org_district_code'     => ['required', 'string', 'size:4', Rule::exists('district', 'code')],
            'org_subdistrict_code'  => ['required', 'string', 'size:6', Rule::exists('subdistrict', 'code')],
            'org_postcode'          => ['required', 'string', 'size:5'],

            // เวลาทำการจากกริด
            'working_hours_json'    => ['required', 'string'],
        ];

        $messages = [
            'required'                    => 'กรุณากรอก :attribute',
            'string'                      => ':attribute ต้องเป็นข้อความตัวอักษร',
            'max'                         => 'ความยาวของ :attribute ต้องไม่เกิน :max ตัวอักษร',
            'size'                        => ':attribute ต้องมีความยาว :size หลัก',
            'numeric'                     => ':attribute ต้องเป็นตัวเลข',
            'between'                     => ':attribute ต้องอยู่ระหว่าง :min ถึง :max',
            'in'                          => ':attribute ไม่ถูกต้อง',
            'exists'                      => ':attribute ไม่พบในระบบ',
            'working_hours_json.required' => 'กรุณากำหนดวัน-เวลาทำการ',
        ];

        $attributes = [
            'org_name'              => 'ชื่อหน่วยบริการ/หน่วยงาน',
            'org_affiliation'       => 'สังกัด',
            'org_affiliation_other' => 'โปรดระบุสังกัด',
            'org_tel'               => 'หมายเลขโทรศัพท์',
            'org_address'           => 'ที่อยู่หน่วยงาน',
            'org_lat'               => 'ละติจูด (Latitude)',
            'org_lng'               => 'ลองจิจูด (Longitude)',
            'org_province_code'     => 'จังหวัด',
            'org_district_code'     => 'อำเภอ',
            'org_subdistrict_code'  => 'ตำบล',
            'org_postcode'          => 'รหัสไปรษณีย์',
            'working_hours_json'    => 'วัน-เวลาทำการ',
        ];

        $validator = Validator::make($request->all(), $rules, $messages, $attributes);

        // ตรวจรูปแบบ JSON ของกริดเวลาแบบเดียวกับ ApplicationReviewController
        $validator->after(function ($v) use ($request) {
            [$ok, $err] = $this->validateWorkingGridJson($request->input('working_hours_json'));
            if (!$ok) {
                $v->errors()->add('working_hours_json', $err ?: 'ข้อมูลวัน-เวลาทำการไม่ถูกต้อง');
            }
        });

        $data = $validator->validate();

        // normalize “อื่นๆ/อื่น ๆ” ให้เป็นฟอร์แมตเดียว และล้างช่อง other เมื่อลูกค้าไม่ได้เลือก “อื่น ๆ”
        $data['org_affiliation'] = $this->normalizeAffiliation($data['org_affiliation']);
        if (!$this->isOthers($data['org_affiliation'])) {
            $data['org_affiliation_other'] = null;
        }

        return [$data];
    }

    private function isOthers(?string $value): bool
    {
        $v = trim((string) $value);
        return $v === 'อื่น ๆ' || $v === 'อื่นๆ';
    }

    private function normalizeAffiliation(?string $value): string
    {
        $v = trim((string) $value);
        return $this->isOthers($v) ? 'อื่น ๆ' : $v;
    }

    /** ตรวจรูปแบบ JSON จากกริด: คืน [bool ok, error|null] */
    private function validateWorkingGridJson(?string $json): array
    {
        if ($json === null || $json === '') {
            return [false, 'กรุณากำหนดวัน-เวลาทำการ'];
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            return [false, 'รูปแบบ JSON ไม่ถูกต้อง'];
        }

        $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        foreach ($days as $d) {
            if (!array_key_exists($d, $data) || !is_array($data[$d])) {
                return [false, "รูปแบบข้อมูลของวัน {$d} ไม่ถูกต้อง"];
            }
            foreach ($data[$d] as $rng) {
                if (!is_string($rng) || !preg_match('/^\d{2}:\d{2}-\d{2}:\d{2}$/', $rng)) {
                    return [false, "ช่วงเวลาในวัน {$d} ต้องอยู่ในรูปแบบ HH:MM-HH:MM"];
                }
                [$a, $b] = explode('-', $rng, 2);
                if ($b <= $a) {
                    return [false, "เวลาสิ้นสุดต้องมากกว่าเวลาเริ่ม (วัน {$d})"];
                }
            }
        }
        return [true, null];
    }

    /** แปลง JSON จากกริดเวลา → array ที่คีย์วันครบเสมอ */
    private function parseWorkingGridJson(?string $json): array
    {
        $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $out  = array_fill_keys($days, []);
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
            // ถ้า parse ไม่ได้ คืนค่าเปล่าทั้งสัปดาห์
        }

        return $out;
    }

}
