<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ServiceUnit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class ApplicationReviewController extends Controller
{
    public function __construct()
    {
        // กำหนดสิทธิ์ได้ตามต้องการ
        // $this->middleware('permission:ผู้ใช้งาน-จัดการผู้ใช้งาน-ดู|ผู้ใช้งาน-จัดการผู้ใช้งาน-เพิ่ม|ผู้ใช้งาน-จัดการผู้ใช้งาน-แก้ไข|ผู้ใช้งาน-จัดการผู้ใช้งาน-ลบ', ['only' => ['index', 'show']]);
    }

    /* =========================================================================
    | 1) รายชื่อผู้ใช้
     * ====================================================================== */
    public function index(Request $request)
    {
        $q     = trim($request->get('q', ''));
        $users = User::query()
            ->with(['roles:id,name', 'serviceUnits' => fn($q) => $q->select('service_units.id', 'org_affiliation')])
            ->where('reg_status', '!=', 'อนุมัติ')
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($x) use ($q) {
                    $x->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('backend.application-review.index', compact('users', 'q'));
    }

    /* =========================================================================
    | 2) เพิ่มผู้ใช้
     * ====================================================================== */
    public function create()
    {
        return view('backend.application-review.create');
    }

    public function store(Request $request)
    {
        [$data, $pwdPlain] = $this->validatedPayload($request, null);

        // ตัด org_* ออกจาก payload ผู้ใช้
        $org = $this->extractOrgData($request);
        unset(
            $data['org_name'], $data['org_affiliation'], $data['org_affiliation_other'],
            $data['org_address'], $data['org_tel'], $data['org_lat'], $data['org_lng'],
            $data['org_working_hours'], $data['org_working_hours_json']
        );

        DB::transaction(function () use ($data, $pwdPlain, $request, $org) {
            // ผู้ใช้
            $user = User::create($data);

            // สิทธิ์
            if ($request->filled('role_id')) {
                if ($role = Role::find($request->role_id)) {
                    $user->assignRole($role->name);
                }
            }

            // หน่วยบริการ + pivot
            $unit = $this->upsertServiceUnit($org);
            $this->attachUnitToUser($user, $unit, 'manager');

            // แจ้งรหัสผ่าน
            $this->notifyCredentials($user->email, $user->username, $pwdPlain);
        });

        flash_notify('เพิ่มผู้ใช้งานเรียบร้อย', 'success');
        return redirect()->route('backend.application-review.index');
    }

    /* =========================================================================
    | 3) แก้ไขผู้ใช้
     * ====================================================================== */
    public function edit(User $user)
    {
        // หน่วยบริการหลัก ถ้าไม่มีให้หยิบอันแรก
        $unit = $user->serviceUnits()
            ->wherePivot('is_primary', true)
            ->first() ?? $user->serviceUnits()->first();

        return view('backend.application-review.edit', compact('user', 'unit'));
    }

    public function update(Request $request, User $user)
    {
        [$data, $pwdPlain] = $this->validatedPayload($request, $user);

        $org = $this->extractOrgData($request);
        unset(
            $data['org_name'], $data['org_affiliation'], $data['org_affiliation_other'],
            $data['org_address'], $data['org_tel'], $data['org_lat'], $data['org_lng'],
            $data['org_working_hours'], $data['org_working_hours_json']
        );

        DB::transaction(function () use ($request, $user, $data, $org) {
            // ผู้ใช้
            $user->update($data);

            // สิทธิ์
            if ($request->filled('role_id')) {
                if ($role = Role::find($request->role_id)) {
                    $user->syncRoles([$role->name]);
                }
            } else {
                $user->syncRoles([]);
            }

            // หน่วยบริการ + pivot
            $unit = $this->upsertServiceUnit($org);
            $this->attachUnitToUser($user, $unit, 'manager');
        });

        flash_notify('อัปเดตข้อมูลผู้ใช้งานเรียบร้อย', 'success');
        return redirect()->route('backend.application-review.index');
    }

    /* =========================================================================
    | 4) ลบผู้ใช้
     * ====================================================================== */
    public function destroy(User $user)
    {
        // ลบไฟล์แนบ (ถ้ามี)
        if ($user->officer_doc_path && Storage::disk('public')->exists($user->officer_doc_path)) {
            Storage::disk('public')->delete($user->officer_doc_path);
        }
        $user->delete();
        flash_notify('ลบผู้ใช้งานเรียบร้อย', 'success');
        return back();
    }

    /* =========================================================================
    | Helpers
     * ====================================================================== */

    /**
     * รวมขั้นตอน validate + normalize + เตรียมข้อมูล upload/file + PDPA
     * คืนค่า: [$data สำหรับบันทึก, $passwordPlain ถ้ามีการตั้ง/สุ่มให้]
     */
    private function validatedPayload(Request $request, ?User $current): array
    {
        $purposeWhitelist = [
            'หน่วยบริการสุขภาพผู้เดินทาง',
            'ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับจังหวัด (สสจ.)',
            'ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับเขต (สคร.)',
        ];

        $affWhitelist = [
            'สำนักงานปลัดกระทรวงสาธารณสุข', 'กรมควบคุมโรค', 'กรมการแพทย์', 'กรมสุขภาพจิต',
            'สภากาชาดไทย', 'สำนักการแพทย์ กรุงเทพมหานคร',
            'กระทรวงอุดมศึกษา วิทยาศาสตร์ วิจัยและนวัตกรรม', 'กระทรวงกลาโหม',
            'องค์กรปกครองส่วนท้องถิ่น', 'องค์การมหาชน', 'เอกชน', 'อื่น ๆ',
        ];

        // ==== Validate หลัก ====
        $rules = [
            // 1) วัตถุประสงค์
            'reg_purpose'                 => ['required', 'array', 'max:3'],
            'reg_purpose.*'               => ['string', Rule::in($purposeWhitelist)],

            // required เงื่อนไข
            'reg_supervise_province_code' => [
                'nullable', 'string', 'max:10',
                Rule::requiredIf(function () use ($request) {
                    return in_array('ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับจังหวัด (สสจ.)',
                        (array) $request->input('reg_purpose', []), true);
                }),
            ],
            'reg_supervise_region_id'     => [
                'nullable', 'integer',
                Rule::requiredIf(function () use ($request) {
                    return in_array('ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับเขต (สคร.)',
                        (array) $request->input('reg_purpose', []), true);
                }),
            ],

            // 2) หน่วยบริการ/หน่วยงาน
            'org_name'                    => ['required', 'string', 'max:255'],
            'org_affiliation'             => ['required', 'string', Rule::in($affWhitelist)],
            'org_affiliation_other'       => ['nullable', 'string', 'max:255',
                Rule::requiredIf(fn() => $request->input('org_affiliation') === 'อื่น ๆ')],
            'org_tel'                     => ['required', 'string', 'max:60'],
            'org_address'                 => ['required', 'string', 'max:1000'],
            'org_lat'                     => ['nullable', 'numeric', 'between:-90,90'],
            'org_lng'                     => ['nullable', 'numeric', 'between:-180,180'],

            // เวลาทำการ
            'org_working_hours'           => ['nullable', 'string', 'max:1000'],
            'working_hours'               => ['nullable', 'array'],
            'working_hours.*.day'         => ['required_with:working_hours', 'in:mon,tue,wed,thu,fri,sat,sun'],
            'working_hours.*.start'       => ['nullable', 'date_format:H:i'],
            'working_hours.*.end'         => ['nullable', 'date_format:H:i', 'after:working_hours.*.start'],
            'working_hours.*.note'        => ['nullable', 'string', 'max:255'],
            'working_hours.*.closed'      => ['nullable'],

            // 3) ผู้ลงทะเบียน
            'contact_cid'                 => ['required', 'regex:/^\d{13}$/', Rule::unique('users', 'contact_cid')->ignore($current?->id)],
            'contact_name'                => ['required', 'string', 'max:255'],
            'contact_position'            => ['required', 'string', 'max:255'],
            'contact_mobile'              => ['required', 'string', 'max:60'],
            'email'                       => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($current?->id)],
            'officer_doc'                 => [
                $current ? 'sometimes' : 'required',
                'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120',
            ],
            'remove_officer_doc'          => ['nullable', 'boolean'],

            // 4) บัญชีผู้ใช้
            'username'                    => ['required', 'string', 'max:60', Rule::unique('users', 'username')->ignore($current?->id)],
            'password'                    => [
                $current ? 'nullable' : 'required',
                'string', 'min:8', 'confirmed',
            ],
            'password_confirmation'       => [
                $current ? 'nullable' : 'required',
                'same:password', 'required_with:password',
            ],

            // 5) PDPA
            'pdpa_accept'                 => ['accepted', 'boolean'],

            // 6) ตรวจสอบ/อนุมัติ (เฉพาะ Admin ที่มีสิทธิ์)
            'reg_status'                  => ['nullable', 'in:รอตรวจสอบ,อนุมัติ,ไม่อนุมัติ'],
            'reg_review_note'             => ['nullable', 'string', 'max:1000', Rule::requiredIf(fn() => $request->input('reg_status') === 'ไม่อนุมัติ'),
            ],
            'officer_doc_verified'        => ['nullable', 'in:0,1'],
            'role_id'                     => [
                'nullable',
                'integer',
                Rule::exists('roles', 'id')->where('guard_name', 'web'),
                Rule::requiredIf(fn() => $request->input('reg_status') === 'อนุมัติ'),
            ],

        ];

        // ==== Messages ====
        $messages = [
            'required'                            => 'กรุณากรอก :attribute',
            'required_with'                       => 'กรุณากรอก :attribute ให้ครบถ้วน',
            'array'                               => 'รูปแบบของ :attribute ต้องเป็นรายการข้อมูล',
            'max'                                 => 'ความยาวของ :attribute ต้องไม่เกิน :max ตัวอักษร',
            'string'                              => ':attribute ต้องเป็นข้อความตัวอักษร',
            'email'                               => 'รูปแบบ :attribute ไม่ถูกต้อง',
            'unique'                              => ':attribute นี้ถูกใช้งานแล้ว',
            'integer'                             => ':attribute ต้องเป็นตัวเลขจำนวนเต็ม',
            'numeric'                             => ':attribute ต้องเป็นตัวเลข',
            'between'                             => ':attribute ต้องอยู่ระหว่าง :min ถึง :max',
            'date_format'                         => ':attribute ต้องอยู่ในรูปแบบเวลา HH:MM',
            'after'                               => ':attribute ต้องมากกว่าเวลาเริ่ม',
            'mimes'                               => ':attribute ต้องเป็นไฟล์ประเภท: :values',
            'max.file'                            => 'ขนาดไฟล์ :attribute ต้องไม่เกิน :max กิโลไบต์',

            'reg_purpose.required'                => 'กรุณาเลือก "ในฐานะ" อย่างน้อย 1 ตัวเลือก',
            'reg_purpose.*.in'                    => 'ตัวเลือกใน "ในฐานะ" ไม่ถูกต้อง',

            'working_hours.*.day.required_with'   => 'กรุณาเลือกวันในช่วงวัน-เวลาทำการ',
            'working_hours.*.day.in'              => 'ค่าวันในช่วงวัน-เวลาทำการไม่ถูกต้อง',
            'working_hours.*.start.date_format'   => 'เวลาเริ่มต้องอยู่ในรูปแบบ HH:MM',
            'working_hours.*.end.date_format'     => 'เวลาสิ้นสุดต้องอยู่ในรูปแบบ HH:MM',
            'working_hours.*.end.after'           => 'เวลาสิ้นสุดต้องมากกว่าเวลาเริ่ม',

            'contact_cid.required'                => 'กรุณากรอกเลขบัตรประชาชน',
            'contact_cid.regex'                   => 'เลขบัตรประชาชนต้องเป็นตัวเลข 13 หลัก',
            'contact_cid.unique'                  => 'เลขบัตรประชาชนนี้ถูกใช้งานแล้ว',

            'officer_doc.mimes'                   => 'ไฟล์เอกสารเจ้าหน้าที่ต้องเป็น PDF/JPG/PNG',
            'officer_doc.max'                     => 'ไฟล์เอกสารเจ้าหน้าที่ต้องไม่เกิน 5MB',

            'password.confirmed'                  => 'กรุณายืนยันรหัสผ่านให้ตรงกัน',
            'password.min'                        => 'รหัสผ่านต้องมีอย่างน้อย :min ตัวอักษร',
            'password.required'                   => 'กรุณากรอกรหัสผ่าน',
            'password_confirmation.required'      => 'กรุณากรอกยืนยันรหัสผ่าน',
            'password_confirmation.required_with' => 'กรุณากรอกยืนยันรหัสผ่าน',
            'password_confirmation.same'          => 'ยืนยันรหัสผ่านไม่ตรงกับรหัสผ่าน',

            'pdpa_accept.accepted'                => 'กรุณายอมรับประกาศความเป็นส่วนตัว (PDPA)',

            'role_id.required'                    => 'กรุณาเลือก :attribute',
        ];

        // ==== Attributes ====
        $attributes = [
            'reg_purpose'                          => 'ในฐานะ',
            'reg_purpose.*'                        => 'ในฐานะ',
            'reg_supervise_province_code.required' => 'กรุณาเลือกจังหวัดที่กำกับดูแล (สสจ.)',
            'reg_supervise_region_id.required'     => 'กรุณาเลือกเขตสุขภาพ (สคร.)',

            'email'                                => 'อีเมล',
            'username'                             => 'Username',

            'reg_supervise_province_code'          => 'จังหวัดที่กำกับดูแล (สสจ.)',
            'reg_supervise_region_id'              => 'เขตสุขภาพ (สคร.)',

            'org_name'                             => 'ชื่อหน่วยบริการ/หน่วยงาน',
            'org_affiliation'                      => 'สังกัด',
            'org_affiliation_other'                => 'โปรดระบุสังกัด',
            'org_address'                          => 'ที่อยู่หน่วยงาน',
            'org_tel'                              => 'หมายเลขโทรศัพท์',

            'org_lat'                              => 'Latitude',
            'org_lng'                              => 'Longitude',

            'org_working_hours'                    => 'คำอธิบายเวลาทำการ',
            'working_hours'                        => 'วัน-เวลาทำการ',
            'working_hours.*.day'                  => 'วัน',
            'working_hours.*.start'                => 'เวลาเริ่ม',
            'working_hours.*.end'                  => 'เวลาสิ้นสุด',
            'working_hours.*.note'                 => 'หมายเหตุ',

            'contact_cid'                          => 'เลขบัตรประจำตัวประชาชน',
            'contact_name'                         => 'ชื่อ-สกุลผู้ลงทะเบียน',
            'contact_position'                     => 'ตำแหน่งผู้ลงทะเบียน',
            'contact_mobile'                       => 'โทรศัพท์มือถือผู้ลงทะเบียน',

            'officer_doc'                          => 'เอกสารยืนยันตัวเจ้าหน้าที่',
            'remove_officer_doc'                   => 'ลบไฟล์เอกสารเดิม',

            'password'                             => 'รหัสผ่าน',
            'password_confirmation'                => 'ยืนยันรหัสผ่าน',

            'pdpa_accept'                          => 'การยอมรับ PDPA',

            'reg_review_note'                      => 'หมายเหตุ/เหตุผลการพิจารณา กรณีที่ "ไม่อนุมัติ"',

            'role_id'                              => 'สิทธิ์การใช้งาน',
        ];

        // ใช้ Validator เพื่อ custom เช็กซัม CID
        $validator = Validator::make($request->all(), $rules, $messages, $attributes);

        $validator->after(function ($v) use ($request) {
            // เก็บเฉพาะตัวเลข + ตรวจเช็กซัม
            $cid = preg_replace('/\D/', '', (string) $request->input('contact_cid'));
            if ($cid && !$this->isValidThaiCitizenId($cid)) {
                $v->errors()->add('contact_cid', 'เลขบัตรประชาชนไม่ถูกต้อง (เช็กซัมไม่ผ่าน)');
            }
        });

        $data = $validator->validate();

        // ปรับ contact_cid ให้เหลือตัวเลขก่อนบันทึก
        $data['contact_cid'] = preg_replace('/\D/', '', (string) ($data['contact_cid'] ?? ''));

        // ==== Username/Password ====
        $passwordPlain = null;
        if (!$current) {
            // create: ต้องมี password (required + confirmed)
            $passwordPlain    = $data['password'];
            $data['password'] = Hash::make($data['password']);
        } else {
            // update: ใส่ค่อยเปลี่ยน
            if (!empty($data['password'])) {
                $passwordPlain    = $data['password'];
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password'], $data['password_confirmation']);
            }
        }

        // ==== Normalize reg_purpose ====
        if (!isset($data['reg_purpose'])) {
            $data['reg_purpose'] = [];
        }

        $cbProvince = in_array('ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับจังหวัด (สสจ.)', $data['reg_purpose'] ?? [], true);
        $cbRegion   = in_array('ผู้กำกับดูแลหน่วยบริการสุขภาพผู้เดินทางระดับเขต (สคร.)', $data['reg_purpose'] ?? [], true);

        if ($cbProvince && !$cbRegion) {
            $data['reg_supervise_region_id'] = null;
        } elseif ($cbRegion && !$cbProvince) {
            $data['reg_supervise_province_code'] = null;
        }
        if (!$cbProvince && !$cbRegion) {
            $data['reg_supervise_province_code'] = null;
            $data['reg_supervise_region_id']     = null;
        }

        // ==== เวลาทำการโครงสร้าง ====
        $data['org_working_hours_json'] = $this->normalizeWorkingHours($request->input('working_hours', []));

        // ==== อัปโหลดไฟล์เอกสารเจ้าหน้าที่ ====
        if ($request->boolean('remove_officer_doc') && $current) {
            if ($current->officer_doc_path && Storage::disk('public')->exists($current->officer_doc_path)) {
                Storage::disk('public')->delete($current->officer_doc_path);
            }
            $data['officer_doc_path']        = null;
            $data['officer_doc_verified_at'] = null;
            $data['officer_doc_verified_by'] = null;
        }
        if ($request->hasFile('officer_doc')) {
            $path                            = $request->file('officer_doc')->store('officer_docs', 'public');
            $data['officer_doc_path']        = $path;
            $data['officer_doc_verified_at'] = null;
            $data['officer_doc_verified_by'] = null;
        }

        // ==== PDPA ====
        if ($request->boolean('pdpa_accept')) {
            $data['pdpa_accepted_at'] = Carbon::now();
            $data['pdpa_version']     = $data['pdpa_version'] ?? 'v1.0';
        }

        // ===== ส่วน Admin: ตรวจสอบเอกสารเจ้าหน้าที่ =====
        if ($request->filled('officer_doc_verified')) {
            if ($request->input('officer_doc_verified') === '1') {
                $data['officer_doc_verified_at'] = Carbon::now();
                $data['officer_doc_verified_by'] = $request->user()?->id;
            } else {
                // เลือก "ยังไม่ตรวจสอบ" -> เคลียร์ค่า
                $data['officer_doc_verified_at'] = null;
                $data['officer_doc_verified_by'] = null;
            }
        }

        // ===== ส่วน Admin: อนุมัติ/ไม่อนุมัติ =====
        if ($request->filled('reg_status')) {
            $data['reg_status'] = $request->input('reg_status');

            if ($data['reg_status'] === 'อนุมัติ') {
                // (ถ้ามี business rule ตรวจไฟล์/verify เอกสาร คงไว้ตามเดิม)
                $data['approved_at'] = Carbon::now();
                $data['approved_by'] = $request->user()?->id;

                // ✅ อนุมัติ -> เปิดใช้งาน
                $data['is_active'] = 1;
            } else {
                // pending / rejected
                $data['approved_at'] = null;
                $data['approved_by'] = null;

                // ✅ อื่น ๆ -> ปิดใช้งาน
                $data['is_active'] = 0;
            }
        }

        // ===== ค่าตั้งต้น is_active กรณีไม่ได้ส่ง reg_status มา =====
        if (!array_key_exists('is_active', $data)) {
            $effectiveStatus   = $data['reg_status'] ?? ($current->reg_status ?? 'pending');
            $data['is_active'] = $effectiveStatus === 'approved' ? 1 : 0;
        }

        // เก็บหมายเหตุพิจารณา (ถ้ามี)
        if ($request->filled('reg_review_note')) {
            $data['reg_review_note'] = trim((string) $request->input('reg_review_note'));
        }

        // --- ปรับ name = contact_name ---
        if (!empty($data['contact_name'])) {
            $data['name'] = trim((string) $data['contact_name']);
        } elseif ($current) {
            // เผื่อกรณีฟิลด์หายไปในบางฟอร์ม ให้อนุรักษ์ค่าเดิมไว้
            $data['name'] = $current->name;
        }

        unset($data['officer_doc'], $data['remove_officer_doc'], $data['pdpa_accept']);

        return [$data, $passwordPlain];
    }

    /**
     * แปลง array จากฟอร์ม working_hours → array โครงสร้างสะอาด สำหรับบันทึกใน org_working_hours_json
     */
    private function normalizeWorkingHours(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            if (!is_array($r)) {
                continue;
            }

            $day    = $r['day'] ?? null;
            $start  = $r['start'] ?? null;
            $end    = $r['end'] ?? null;
            $note   = trim($r['note'] ?? '');
            $closed = !empty($r['closed']); // checkbox

            // ข้ามแถวที่ไม่มี day
            if (!$day) {
                continue;
            }

            // ถ้า closed = true ไม่ต้องมีเวลา
            if ($closed) {
                $out[] = [
                    'day'    => $day,
                    'closed' => true,
                    'start'  => null,
                    'end'    => null,
                    'note'   => $note ?: null,
                ];
                continue;
            }

            // กรณีเปิดให้บริการ ต้องมีเวลา start/end ที่ถูกต้อง
            if ($start && $end && $end > $start) {
                $out[] = [
                    'day'    => $day,
                    'closed' => false,
                    'start'  => $start,
                    'end'    => $end,
                    'note'   => $note ?: null,
                ];
            }
            // ถ้าเวลาไม่ครบ/ไม่ถูกต้อง -> ทิ้งแถว (เพื่อความสะอาดของข้อมูล)
        }
        return $out;
    }

    /**
     * ส่งอีเมลแจ้งบัญชี (แบบง่าย)
     */
    private function notifyCredentials(string $email, string $username, ?string $passwordPlain): void
    {
        if (!$passwordPlain) {
            return;
        }

        try {
            Mail::raw(
                "ระบบผู้เดินทาง - บัญชีของคุณถูกสร้างโดยผู้ดูแลระบบ\n\n" .
                "Username: {$username}\n" .
                "Password: {$passwordPlain}\n\n" .
                "เข้าสู่ระบบแล้วกรุณาเปลี่ยนรหัสผ่านทันทีเพื่อความปลอดภัย",
                function ($m) use ($email) {
                    $m->to($email)->subject('บัญชีผู้ใช้งานถูกสร้าง (ระบบผู้เดินทาง)');
                }
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function isValidThaiCitizenId(string $cid): bool
    {
        if (!preg_match('/^\d{13}$/', $cid)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += intval($cid[$i]) * (13 - $i);
        }
        $check = (11 - ($sum % 11)) % 10;
        return $check === intval($cid[12]);
    }

    // ดึง/จัดรูป org_* จาก request
    private function extractOrgData(Request $r): array
    {
        return [
            'org_name'               => trim((string) $r->input('org_name')),
            'org_affiliation'        => $r->input('org_affiliation'),
            'org_affiliation_other'  => $r->input('org_affiliation_other'),
            'org_address'            => $r->input('org_address'),
            'org_tel'                => $r->input('org_tel'),
            'org_lat'                => $r->input('org_lat'),
            'org_lng'                => $r->input('org_lng'),
            'org_working_hours'      => $r->input('org_working_hours'),
            'org_working_hours_json' => $this->normalizeWorkingHours($r->input('working_hours', [])),
        ];
    }

    // upsert service_units โดยใช่ org_name + org_address เป็น natural key แบบหยาบ
    private function upsertServiceUnit(array $org): ServiceUnit
    {
        $keyName = trim($org['org_name'] ?? '');
        if ($keyName === '') {
            throw new \InvalidArgumentException('org_name ต้องไม่ว่าง');
        }
        $keyAddr = trim($org['org_address'] ?? '');

        // กันส่งฟิลด์เกิน โดยเลือกเฉพาะคอลัมน์ที่มีจริง
        $payload = collect($org)->only([
            'org_name', 'org_affiliation', 'org_affiliation_other',
            'org_address', 'org_tel', 'org_lat', 'org_lng',
            'org_working_hours', 'org_working_hours_json',
        ])->toArray();

        return ServiceUnit::updateOrCreate(
            ['org_name' => $keyName, 'org_address' => $keyAddr],
            $payload
        );
    }

    // ผูก pivot และตั้ง primary ของ user
    private function attachUnitToUser(User $user, ServiceUnit $unit, string $role = 'manager'): void
    {
        // แนวทาง: ไม่ให้ซ้ำ role ในหน่วยเดียวกัน
        $user->serviceUnits()->syncWithoutDetaching([
            $unit->id => [
                'role'       => $role,
                'start_date' => now()->toDateString(),
                'end_date'   => null,
                'is_primary' => true,
            ],
        ]);

        // เคลียร์ primary อื่น แล้วตั้งอันนี้เป็น primary
        $user->serviceUnits()->updateExistingPivot(
            $user->serviceUnits()->pluck('service_units.id')->toArray(),
            ['is_primary' => false]
        );
        $user->serviceUnits()->updateExistingPivot($unit->id, ['is_primary' => true]);

        // อัปเดตคอลัมน์อ้างอิงใน users
        if (Schema::hasColumn('users', 'primary_service_unit_id')) {
            $user->forceFill(['primary_service_unit_id' => $unit->id])->save();
        }
    }

}
