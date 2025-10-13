<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Models\ServiceUnit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AjaxController extends Controller
{
    /**
     * ค้นหาผู้ใช้: ?q=คำค้น&limit=20
     */
    public function ajaxUserLookup(Request $request): JsonResponse
    {
        $q     = trim($request->get('q', ''));
        $limit = (int) $request->get('limit', 20);

        $users = User::query()
            ->when($q !== '', function ($qq) use ($q) {
                $like = "%{$q}%";
                $qq->where(function ($w) use ($like) {
                    $w->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'email']);

        return response()->json([
            'results' => $users->map(fn($u) => [
                'id'   => $u->id,
                'text' => "{$u->name} <{$u->email}>",
            ]),
        ]);
    }

    /**
     * provinces by region: /api/cascade/regions/{region}/provinces
     * region = id | all | ''  -> คืนทุกจังหวัด
     */
    public function ajaxProvincesByRegion(string $region = 'all'): JsonResponse
    {
        $items = Province::query()
            ->when(!in_array($region, ['all', ''], true), fn($q) => $q->where('health_region_id', $region))
            ->orderBy('title')
            ->get(['code as value', 'title as text']);

        return response()->json(['items' => $items]);
    }

    /**
     * service-units by province: /api/cascade/provinces/{province}/service-units
     * province = code | all | '' -> คืนทุกหน่วย
     */
    // app/Http/Controllers/AjaxController.php
    public function ajaxServiceUnitsByProvince(string $province = 'all', \Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $region = $request->query('region', 'all'); // health_regions.id | 'all'

        $q = \App\Models\ServiceUnit::query()->orderBy('org_name');

        if ($province !== 'all' && $province !== '') {
            // เลือกจังหวัด → ใช้จังหวัดเป็นตัวกรองหลัก
            $q->where('org_province_code', $province);
        } elseif ($region !== 'all' && $region !== '') {
            // เลือกเฉพาะ สคร → หา province codes ใน สคร แล้ว whereIn
            $provCodes = \App\Models\Province::where('health_region_id', $region)->pluck('code');
            $q->whereIn('org_province_code', $provCodes);
        }
        // ไม่เลือกอะไรเลย → ไม่กรอง แสดงทั้งหมด

        $items = $q->get(['id as value', 'org_name as text']);

        return response()->json(['items' => $items]);
    }

}
