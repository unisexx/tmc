<?php
// app/Http/Controllers/Frontend/ServiceUnitController.php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\ServiceUnit;
use Illuminate\Http\Request;

class ServiceUnitController extends Controller
{
    public function index(Request $request)
    {
        $year  = fiscalYearCE();
        $round = fiscalRound();

        $q        = trim($request->get('q', ''));
        $province = $request->get('province');
        $level    = $request->get('level'); // basic | medium | advanced | null

        $serviceUnits = ServiceUnit::query()
            ->with([
                'province',
                'district',
                'subdistrict',
                // ดึงเฉพาะคอลัมน์ที่ใช้จาก relation
                'assessmentLevelApprovedCurrent:id,service_unit_id,level',
            ])
            ->when($q, fn($qrb) =>
                $qrb->where(function ($w) use ($q) {
                    $w->where('org_name', 'like', "%{$q}%")
                        ->orWhere('org_address', 'like', "%{$q}%")
                        ->orWhere('org_tel', 'like', "%{$q}%");
                })
            )
            ->when($province, fn($qrb) => $qrb->where('org_province_code', $province))
        // ต้องมี “รอบปีงบปัจจุบันที่อนุมัติแล้ว”
            ->whereHas('assessmentLevelApprovedCurrent', function ($sub) use ($level) {
                if ($level) {
                    $sub->where('level', $level);
                }
            })
            ->orderBy('org_name')
            ->paginate(24)
            ->withQueryString();

        $provinces = Province::orderBy('title')->pluck('title', 'code');
        $levels    = ['' => 'ระดับทั้งหมด', 'basic' => 'พื้นฐาน', 'medium' => 'กลาง', 'advanced' => 'สูง'];

        return view('frontend.service_units.index', compact(
            'serviceUnits', 'q', 'province', 'level', 'provinces', 'levels', 'year', 'round'
        ));
    }
}
