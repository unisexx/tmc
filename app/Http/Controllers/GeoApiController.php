<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Postcode;
use App\Models\Province;
use App\Models\Subdistrict;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GeoApiController extends Controller
{
    /** ===== Helpers ===== */

    /** สร้างช่วงรหัส: base * 10^digits + [1..99] */
    private function codeRange(int $base, int $digits = 2): array
    {
        $mul = 10 ** $digits; // 100 (2 หลัก), 1000 (3 หลัก) ...
        return [$base * $mul + 1, $base * $mul + ($mul - 1)];
    }

    /** ล้างชื่อพื้นที่: ตัดช่องว่างซ้ำ/เครื่องหมายพิเศษอย่าง * */
    private function clean(string $s): string
    {
        $s = trim($s);
        $s = preg_replace('/\s+/', ' ', $s);
        $s = str_replace('*', '', $s);
        return $s;
    }

    /** ห่อ response เป็น JSON เสมอ */
    private function resp($data)
    {
        return response()->json($data);
    }

    /** ===== Endpoints ===== */

    /** รายชื่อจังหวัด */
    public function provinces()
    {
        $rows = Cache::remember('geo:provinces', 86400, function () {
            return Province::query()
                ->where('title', 'not like', '%*%')
                ->orderBy('title')
                ->get(['code', 'title'])
                ->map(fn($r) => ['code' => (int) $r->code, 'title' => $this->clean($r->title)]);
        });

        return $this->resp($rows);
    }

    /** รายชื่ออำเภอตามจังหวัด ?province=10 */
    public function districts(Request $req)
    {
        $prov = (int) $req->query('province');
        abort_unless($prov > 0, 400, 'invalid province code');

        [$min, $max] = $this->codeRange($prov, 2); // 4 หลัก

        $cacheKey = "geo:districts:{$prov}";
        $rows     = Cache::remember($cacheKey, 86400, function () use ($min, $max) {
            return District::query()
                ->whereBetween('code', [$min, $max])
                ->where('title', 'not like', '%*%')
                ->orderBy('title')
                ->get(['code', 'title'])
                ->map(fn($r) => ['code' => (int) $r->code, 'title' => $this->clean($r->title)]);
        });

        return $this->resp($rows);
    }

    /** รายชื่อตำบลตามอำเภอ ?district=1001 */
    public function subdistricts(Request $req)
    {
        $dist = (int) $req->query('district');
        abort_unless($dist > 0, 400, 'invalid district code');

        [$min, $max] = $this->codeRange($dist, 2); // 6 หลัก

        $cacheKey = "geo:subdistricts:{$dist}";
        $rows     = Cache::remember($cacheKey, 86400, function () use ($min, $max) {
            return Subdistrict::query()
                ->whereBetween('code', [$min, $max])
                ->where('title', 'not like', '%*%')
                ->orderBy('title')
                ->get(['code', 'title'])
                ->map(fn($r) => ['code' => (int) $r->code, 'title' => $this->clean($r->title)]);
        });

        return $this->resp($rows);
    }

    /**
     * รหัสไปรษณีย์จาก subdistrict/district/province อย่างน้อยหนึ่ง
     * คืนรายการ [{code, title}] โดย title เป็นตัวเลขรหัสเพื่อแสดงใน <select>
     */
    public function postcodes(Request $req)
    {
        $sub  = (int) $req->query('subdistrict');
        $dist = (int) $req->query('district');
        $prov = (int) $req->query('province');
        abort_if(!$sub && !$dist && !$prov, 400, 'missing location param');

        $subTitle = $distTitle = $provTitle = null;

        if ($sub) {
            $s = Subdistrict::select(['code', 'title'])->find($sub);
            abort_if(!$s, 404, 'subdistrict not found');
            $dist     = intdiv($sub, 100);
            $subTitle = $this->clean($s->title);
        }
        if ($dist) {
            $d = District::select(['code', 'title'])->find($dist);
            abort_if(!$d, 404, 'district not found');
            $prov      = intdiv($dist, 100);
            $distTitle = $this->clean($d->title); // มี "เขต/อำเภอ" อยู่แล้ว
        }
        if ($prov) {
            $p = Province::select(['code', 'title'])->find($prov);
            abort_if(!$p, 404, 'province not found');
            $provTitle = $this->clean($p->title);
        }

        // exact-first (sub dist prov) → ถัดไป progressive match
        if ($subTitle && $distTitle && $provTitle) {
            $exact = Cache::remember(
                'geo:postcodes:exact:' . md5($subTitle . $distTitle . $provTitle),
                86400,
                fn() => Postcode::query()
                    ->select('code')->distinct()
                    ->where('title', 'like', "%{$subTitle} {$distTitle} {$provTitle}%")
                    ->orderBy('code')
                    ->pluck('code')
                    ->map(fn($c) => (string) $c)
                    ->all()
            );
            if (!empty($exact)) {
                return $this->resp(collect($exact)->map(fn($c) => ['code' => $c, 'title' => $c]));
            }
        }

        $cacheKey = 'geo:postcodes:any:' . md5(($provTitle ?? '-') . '|' . ($distTitle ?? '-') . '|' . ($subTitle ?? '-'));
        $rows     = Cache::remember($cacheKey, 86400, function () use ($provTitle, $distTitle, $subTitle) {
            $q = Postcode::query()
                ->select('code')
                ->distinct();

            if ($provTitle) {
                $q->where('title', 'like', '%' . $provTitle . '%');
            }

            if ($distTitle) {
                $q->where('title', 'like', '%' . $distTitle . '%');
            }

            if ($subTitle) {
                $q->where('title', 'like', '%' . $subTitle . '%');
            }

            return $q->orderBy('code')
                ->get()
                ->map(fn($r) => ['code' => (string) $r->code, 'title' => (string) $r->code]);
        });

        return $this->resp($rows);
    }

    /** พิกัดศูนย์กลางตำบลจาก code */
    public function subdistrictCenter(Request $req)
    {
        $code = (int) $req->query('code');
        abort_unless($code > 0, 400, 'invalid subdistrict code');

        $cacheKey = "geo:sub-center:{$code}";
        $data     = Cache::remember($cacheKey, 86400, function () use ($code) {
            $s = Subdistrict::query()
                ->select(['code', 'title', 'lat_center', 'lng_center'])
                ->where('code', $code)
                ->first();

            abort_unless($s, 404, 'subdistrict not found');
            abort_unless(!is_null($s->lat_center) && !is_null($s->lng_center), 404, 'no center coord');

            return [
                'code'  => (int) $s->code,
                'title' => $this->clean($s->title),
                'lat'   => (float) $s->lat_center,
                'lng'   => (float) $s->lng_center,
            ];
        });

        return $this->resp($data);
    }
}
