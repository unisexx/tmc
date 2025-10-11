<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Hilight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// === เพิ่ม 2 บรรทัดนี้สำหรับ Intervention Image v3 ===
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

// หรือเปลี่ยนเป็น Imagick ได้ถ้ามี

class HilightController extends Controller
{
    // ค่ามาตรฐาน (สามารถย้ายไป config ก็ได้)
    private int $defaultWidth  = 1200;
    private int $defaultHeight = 630;

    public function index(Request $request)
    {
        $q       = trim((string) $request->get('q'));
        $reorder = $request->boolean('reorder'); // โหมดจัดเรียง (ดึงทุกรายการ)

        $query = Hilight::when(!$reorder && $q, function ($qq) use ($q) {
            $like = "%{$q}%";
            $qq->where(function ($w) use ($like) {
                $w->where('title', 'like', $like)
                    ->orWhere('link_url', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        })
            ->orderBy('ordering')
            ->orderByDesc('id');

        // โหมดจัดเรียง: ดึงทั้งหมด (ignore $q)
        $rs = $reorder ? $query->get()
            : $query->paginate(20)->withQueryString();

        return view('backend.hilight.index', compact('rs', 'q', 'reorder'));
    }

    public function create()
    {
        $hilight = new Hilight();
        return view('backend.hilight.create', compact('hilight'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'link_url'    => ['nullable', 'url'],
            'ordering'    => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],

            // ขนาดที่ “ผู้ใช้กำหนดเอง” (ถ้าไม่ส่ง จะใช้ค่า default)
            'w'           => ['nullable', 'integer', 'min:50', 'max:4000'],
            'h'           => ['nullable', 'integer', 'min:50', 'max:4000'],
        ]);

        // กำหนดขนาดปลายทาง
        $targetW = (int) ($request->input('w') ?: $this->defaultWidth);
        $targetH = (int) ($request->input('h') ?: $this->defaultHeight);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->resizeAndStore(
                file: $request->file('image'),
                width: $targetW,
                height: $targetH
            );
        }

        $data['is_active'] = (bool) $request->boolean('is_active');

        Hilight::create($data);

        flash_notify('เพิ่มรายการสำเร็จ', 'success');
        return redirect()->route('backend.hilight.index');
    }

    public function edit(Hilight $hilight)
    {
        return view('backend.hilight.edit', compact('hilight'));
    }

    public function update(Request $request, Hilight $hilight)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'link_url'    => ['nullable', 'url'],
            'ordering'    => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'w'           => ['nullable', 'integer', 'min:50', 'max:4000'],
            'h'           => ['nullable', 'integer', 'min:50', 'max:4000'],
        ]);

        $targetW = (int) ($request->input('w') ?: $this->defaultWidth);
        $targetH = (int) ($request->input('h') ?: $this->defaultHeight);

        if ($request->hasFile('image')) {
            // ลบไฟล์เดิม (ถ้ามี)
            if ($hilight->image_path) {
                Storage::disk('public')->delete($hilight->image_path);
            }

            $data['image_path'] = $this->resizeAndStore(
                file: $request->file('image'),
                width: $targetW,
                height: $targetH
            );
        }

        $data['is_active'] = (bool) $request->boolean('is_active');

        $hilight->update($data);

        flash_notify('บันทึกการแก้ไขแล้ว', 'success');
        return redirect()->route('backend.hilight.index');
    }

    public function destroy(Hilight $hilight)
    {
        if ($hilight->image_path) {
            Storage::disk('public')->delete($hilight->image_path);
        }

        $hilight->delete();

        flash_notify('ลบรายการแล้ว', 'success');
        return back();
    }

    /**
     * ย่อ/ครอปภาพให้พอดีกรอบ แล้วบันทึกลง storage/app/public/uploads/hilight/
     * คืนค่าเป็น relative path เพื่อเก็บลง DB
     */
    private function resizeAndStore(\Illuminate\Http\UploadedFile $file, int $width, int $height): string
    {
        $manager = new ImageManager(new Driver()); // ใช้ GD; เปลี่ยนเป็น Imagick ได้ถ้าติดตั้งไว้
        $image   = $manager->read($file->getPathname());

        // cover = ครอปให้ “เต็มกรอบ” โดยรักษาสัดส่วน
        $resized = $image->cover($width, $height);

        // ตั้งชื่อไฟล์ (เป็น .jpg เสมอ เพื่อลดขนาด)
        $dir      = 'uploads/hilight';
        $filename = uniqid('hl_', true) . '.jpg';
        $path     = $dir . '/' . $filename;

        // บันทึกลง disk 'public'
        Storage::disk('public')->put($path, (string) $resized->toJpeg(85));

        return $path;
    }

    /**
     * รับลำดับใหม่จากหน้า index (ลากวาง) แล้วอัปเดตลง DB
     * รูปแบบ payload: { ids: [5, 2, 9, 1, ...] } ตามลำดับบนลงล่าง
     */
    public function reorder(Request $request)
    {
        $ids = $request->input('ids', []);

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['ok' => false, 'message' => 'ไม่พบรายการ'], 422);
        }

        DB::transaction(function () use ($ids) {
            // เริ่มลำดับจาก 1
            foreach ($ids as $index => $id) {
                Hilight::whereKey($id)->update(['ordering' => $index + 1]);
            }
        });

        return response()->json(['ok' => true, 'message' => 'อัปเดตลำดับแล้ว']);
    }
}
