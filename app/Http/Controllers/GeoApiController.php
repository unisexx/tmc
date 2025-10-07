<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Postcode;
use App\Models\Province;
use App\Models\Subdistrict;
use Illuminate\Http\Request;

class GeoApiController extends Controller
{
    /**
     * คืนรายชื่อจังหวัดทั้งหมด
     */
    public function provinces()
    {
        return Province::where('title', 'not like', '%*%')
            ->orderBy('title')
            ->get(['code', 'title']);
    }

    /**
     * คืนรายชื่ออำเภอตามจังหวัด
     * ตัวอย่าง query: /geo/districts?province=10
     */
    public function districts(Request $req)
    {
        $prov = (int) $req->query('province'); // เช่น 10
        abort_unless($prov > 0, 400, 'invalid province code');

        // district code 4 หลัก: [prov*100+1 .. prov*100+99]
        $min = $prov * 100 + 1;
        $max = $prov * 100 + 99;

        return District::whereBetween('code', [$min, $max])
            ->where('title', 'not like', '%*%')
            ->orderBy('title')
            ->get(['code', 'title']);
    }

    /**
     * คืนรายชื่อตำบลตามอำเภอ
     * ตัวอย่าง query: /geo/subdistricts?district=1001
     */
    public function subdistricts(Request $req)
    {
        $dist = (int) $req->query('district'); // เช่น 1001
        abort_unless($dist > 0, 400, 'invalid district code');

        // subdistrict code 6 หลัก: [dist*100+1 .. dist*100+99]
        $min = $dist * 100 + 1;
        $max = $dist * 100 + 99;

        return Subdistrict::whereBetween('code', [$min, $max])
            ->where('title', 'not like', '%*%')
            ->orderBy('title')
            ->get(['code', 'title']);
    }

    public function postcodes(Request $req)
    {
        $sub  = (int) $req->query('subdistrict');
        $dist = (int) $req->query('district');
        $prov = (int) $req->query('province');

        abort_if(!$sub && !$dist && !$prov, 400, 'missing location param');

        $subTitle = $distTitle = $provTitle = null;

        if ($sub) {
            $subRow = Subdistrict::select(['code', 'title'])->find($sub);
            abort_if(!$subRow, 404, 'subdistrict not found');
            $dist     = intdiv($sub, 100);
            $subTitle = $subRow->title;
        }
        if ($dist) {
            $distRow = District::select(['code', 'title'])->find($dist);
            abort_if(!$distRow, 404, 'district not found');
            $prov      = intdiv($dist, 100);
            $distTitle = $distRow->title; // มี "เขต/อำเภอ" นำหน้าอยู่แล้ว
        }
        if ($prov) {
            $provRow = Province::select(['code', 'title'])->find($prov);
            abort_if(!$provRow, 404, 'province not found');
            $provTitle = $provRow->title; // เช่น "กรุงเทพมหานคร"
        }

        // ---------- กลยุทธ์ match ----------
        // ตาราง postcode.title เป็น "sub + dist + prov" (คั่นด้วยช่องว่าง)
        // ใช้ like ทีละชิ้น เพื่อลดปัญหาระยะห่าง/เว้นวรรค/ข้อมูลพิเศษ
        $q = Postcode::query()
            ->select('code')
            ->distinct()
            ->when($provTitle, fn($qq) => $qq->where('title', 'like', "%{$provTitle}%"))
            ->when($distTitle, fn($qq) => $qq->where('title', 'like', "%{$distTitle}%"))
            ->when($subTitle, fn($qq) => $qq->where('title', 'like', "%{$subTitle}%"));

        // ถ้ามี subdistrict แล้วอยาก match แบบ "sub dist prov" ตรง ๆ ก่อน (แม่นกว่า)
        if ($subTitle && $distTitle && $provTitle) {
            $exact = Postcode::query()
                ->select('code')->distinct()
                ->where('title', 'like', "%{$subTitle} {$distTitle} {$provTitle}%")
                ->orderBy('code')
                ->pluck('code')
                ->all();

            if (!empty($exact)) {
                return collect($exact)->map(fn($c) => ['code' => (string) $c, 'title' => (string) $c]);
            }
        }

        // ไม่ได้ผลหรือเลือกมาไม่ครบ 3 ชิ้น ⇒ ใช้วิธีค่อยๆ บังคับชิ้นส่วน (prov/dist/sub) ตามที่มี
        $rows = $q->orderBy('code')->get();

        return $rows->map(fn($r) => [
            'code'  => (string) $r->code,
            'title' => (string) $r->code, // ให้แสดงเป็นตัวเลขรหัสไปรษณีย์ตรงๆ
        ]);
    }

}
