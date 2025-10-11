<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceUnitManagersRequest;
use App\Http\Requests\ServiceUnitRequest;
use App\Models\Province;
use App\Models\ServiceUnit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceUnitController extends Controller
{
    /**
     * แสดงรายการหน่วยบริการ (มีตัวกรอง)
     */
    public function index(Request $request)
    {
        $q            = trim($request->get('q'));
        $provinceCode = $request->get('province');
        $affiliation  = $request->get('affiliation');

        $query = ServiceUnit::query()
            ->with(['province', 'district', 'subdistrict'])
            ->when($q, fn($qb) =>
                $qb->where(function ($sub) use ($q) {
                    $sub->where('org_name', 'like', "%{$q}%")
                        ->orWhere('org_tel', 'like', "%{$q}%")
                        ->orWhere('org_address', 'like', "%{$q}%");
                })
            )
            ->when($provinceCode, fn($qb) =>
                $qb->where('org_province_code', $provinceCode)
            )
            ->when($affiliation, fn($qb) =>
                $qb->where('org_affiliation', $affiliation)
            )
            ->orderBy('org_name');

        $serviceUnits = $query->paginate(20)->withQueryString();
        $provinces    = Province::orderBy('title')->pluck('title', 'code');

        return view('backend.service_unit.index', compact(
            'serviceUnits', 'q', 'provinces', 'provinceCode', 'affiliation'
        ));
    }

    /**
     * ฟอร์มเพิ่มหน่วยบริการใหม่
     */
    public function create()
    {
        $unit = new ServiceUnit([
            'org_lat'                => null,
            'org_lng'                => null,
            'org_working_hours_json' => [],
        ]);

        return view('backend.service_unit.create', compact('unit'));
    }

    /**
     * บันทึกหน่วยบริการใหม่
     */
    public function store(ServiceUnitRequest $request)
    {
        $data                           = $request->validated();
        $data['org_working_hours_json'] = $request->parsedWorkingHours();

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

    /**
     * ฟอร์มแก้ไขหน่วยบริการ
     */
    public function edit(ServiceUnit $service_unit)
    {
        $unit = $service_unit->load(['province', 'district', 'subdistrict']);
        return view('backend.service_unit.edit', compact('unit'));
    }

    /**
     * บันทึกการแก้ไขหน่วยบริการ
     */
    public function update(ServiceUnitRequest $request, ServiceUnit $service_unit)
    {
        $data                           = $request->validated();
        $data['org_working_hours_json'] = $request->parsedWorkingHours();

        $service_unit->update(collect($data)->only([
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
        ])->toArray());

        flash_notify('บันทึกการแก้ไขแล้ว', 'success');
        return redirect()->route('backend.service-unit.edit', $service_unit->id);
    }

    /**
     * ลบหน่วยบริการ
     */
    public function destroy(ServiceUnit $service_unit)
    {
        $service_unit->delete();
        flash_notify('ลบหน่วยบริการเรียบร้อยแล้ว', 'success');
        return redirect()->route('backend.service-unit.index');
    }

    /**
     * แสดงฟอร์มตั้งค่าผู้รับผิดชอบหน่วยงาน
     */
    public function managers(ServiceUnit $service_unit)
    {
        // รายชื่อผู้ใช้ทั้งหมดสำหรับ select2 (ไม่ใช้ AJAX)
        $allUsers = User::query()
            ->with('role:id,name')
            ->select('id', 'name', 'email', 'contact_cid', 'contact_position', 'contact_mobile', 'role_id', 'reg_purpose', 'is_active')
            ->where('is_active', 1)
            ->whereJsonContains('reg_purpose', 'T')
            ->orderBy('name')
            ->get();

        // หน่วย + ผู้ใช้ปัจจุบัน (พร้อมข้อมูลแสดงผลในตาราง)
        $unit = $service_unit->load([
            'users' => function ($q) {
                $q->with('role:id,name')
                    ->select('users.id', 'name', 'email', 'contact_cid', 'contact_position', 'contact_mobile', 'role_id');
            },
        ]);

        $current = $unit->users->map(function ($u) {
            return [
                'user_id'       => $u->id,
                'name'          => $u->name,
                'email'         => $u->email,
                'cid'           => $u->contact_cid,
                'position_name' => $u->contact_position,
                'mobile'        => $u->contact_mobile,
                'role_name'     => optional($u->role)->name ?? 'ไม่มีสิทธิ์',
                'role'          => 'manager', // fix ให้เป็น manager ตามเงื่อนไข
                'is_primary'    => (bool) ($u->pivot->is_primary ?? 0),
                'start_date'    => $u->pivot->start_date ?? null,
                'end_date'      => $u->pivot->end_date ?? null,
            ];
        });

        return view('backend.service_unit.managers', [
            'unit'         => $unit,
            'currentItems' => $current,
            'allUsers'     => $allUsers,
        ]);
    }

    /**
     * บันทึกผู้รับผิดชอบหน่วยงาน
     */
    public function managersUpdate(ServiceUnitManagersRequest $request, ServiceUnit $service_unit)
    {
        $payload = collect($request->input('managers', []))
            ->filter(fn($x) => !empty($x['user_id']))
            ->mapWithKeys(function ($row) {
                $userId = (int) $row['user_id'];
                return [
                    $userId => [
                        'role'       => $row['role'] ?? null,
                        'is_primary' => !empty($row['is_primary']),
                        'start_date' => $row['start_date'] ?? null,
                        'end_date'   => $row['end_date'] ?? null,
                    ],
                ];
            })->toArray();

        $service_unit->users()->sync($payload);

        flash_notify('บันทึกผู้รับผิดชอบหน่วยงานเรียบร้อยแล้ว', 'success');
        return redirect()->route('backend.service-unit.managers.edit', $service_unit->id);
    }

    /**
     * สลับหน่วยบริการใน session (ใช้กับ dropdown บน topbar)
     */
    public function switch(Request $req)
    {
            $user  = Auth::user();
            $input = $req->input('service_unit_id');

            // 🟦 1) ถ้าเป็นแอดมินและเลือก "ภาพรวม"
            if ($user->isAdmin() && ($input === null || $input === '')) {
                session()->forget('current_service_unit_id');
                flash_notify('เข้าสู่มุมมองภาพรวม (ไม่ผูกกับหน่วยบริการ)', 'success');
                return redirect()->route('backend.dashboard');
            }

            // 🟦 2) ตรวจสอบรหัสที่ส่งมา
            $id = (int) $input;
            if (!$id) {
                flash_notify('ไม่พบรหัสหน่วยบริการที่ต้องการสลับ', 'warning');
                return back();
            }

            // 🟦 3) ตรวจสิทธิ์การเข้าถึง
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
}
