<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\StHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StHealthServiceController extends Controller
{
    public function index(Request $req)
    {
        $q       = trim((string) $req->get('q'));
        $level   = $req->get('level');
        $active  = $req->get('active');
        $reorder = (bool) $req->boolean('reorder');

        $query = StHealthService::query()
            ->when($q, fn($qq) => $qq->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            }))
            ->when($level, fn($qq) => $qq->where('level_code', $level))
            ->when(isset($active) && $active !== '', fn($qq) => $qq->where('is_active', (bool) $active))
            ->orderBy('ordering')->orderBy('id');

        $items = $reorder
            ? $query->get()
            : $query->paginate(20)->appends($req->query());

        return view('backend.st_health_services.index', compact('items'));
    }

    public function reorder(Request $request)
    {
        $ids = (array) ($request->input('ids') ?? []);
        if (empty($ids)) {
            return response()->json(['ok' => false, 'message' => 'ไม่มีข้อมูลรายการ'], 422);
        }

        DB::transaction(function () use ($ids) {
            foreach ($ids as $idx => $id) {
                StHealthService::where('id', $id)->update(['ordering' => $idx + 1]);
            }
        });

        return response()->json(['ok' => true, 'message' => 'อัปเดตลำดับแล้ว']);
    }

    public function create()
    {
        $levels = ['basic' => 'พื้นฐาน', 'medium' => 'กลาง', 'advanced' => 'สูง'];
        $item   = new StHealthService(['default_enabled' => true, 'is_active' => true, 'ordering' => 100]);
        return view('backend.st_health_services.form', compact('item', 'levels'));
    }

    public function store(Request $req)
    {
        $data = $this->validated($req);
        $item = StHealthService::create($data);
        $item->update(['ordering' => $item->id]);

        flash_notify('เพิ่มบริการสำเร็จ', 'success');
        return redirect()->route('backend.st-health-services.index');
    }

    public function edit(StHealthService $st_health_service)
    {
        $levels = ['basic' => 'พื้นฐาน', 'medium' => 'กลาง', 'advanced' => 'สูง'];
        $item   = $st_health_service;
        return view('backend.st_health_services.form', compact('item', 'levels'));
    }

    public function update(Request $req, StHealthService $st_health_service)
    {
        $data = $this->validated($req, $st_health_service->id);
        $st_health_service->update($data);

        flash_notify('บันทึกการแก้ไขแล้ว', 'success');
        return redirect()->route('backend.st-health-services.index');
    }

    public function destroy(StHealthService $st_health_service)
    {
        $st_health_service->delete();

        flash_notify('ลบรายการแล้ว', 'success');
        return back();
    }

    private function validated(Request $req, ?int $id = null): array
    {
        $codeRule = 'nullable|string|max:64|unique:st_health_services,code';
        if ($id) {
            $codeRule .= ',' . $id;
        }

        $data = $req->validate([
            'level_code'  => 'required|in:basic,medium,advanced',
            'code'        => $codeRule,
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'required|in:0,1',
        ]);

        // ✅ ตั้งค่า default_enabled = true เสมอ
        $data['default_enabled'] = true;

        $data['is_active'] = (bool) ((int) $data['is_active']);

        return $data;
    }

}
