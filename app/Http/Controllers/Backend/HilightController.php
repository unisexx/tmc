<?php
// app/Http/Controllers/Backend/HilightController.php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Hilight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class HilightController extends Controller
{
    private int $defaultWidth  = 1200;
    private int $defaultHeight = 630;

    public function index(Request $request)
    {
        $q       = trim((string) $request->get('q'));
        $reorder = $request->boolean('reorder');

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
            // ลบ ordering ออกจากฟอร์มแล้ว ไม่ต้อง validate
            'is_active'   => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'w'           => ['nullable', 'integer', 'min:50', 'max:4000'],
            'h'           => ['nullable', 'integer', 'min:50', 'max:4000'],
        ]);

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
            // ลบ ordering ออกจากฟอร์มแล้ว ไม่ต้อง validate
            'is_active'   => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'w'           => ['nullable', 'integer', 'min:50', 'max:4000'],
            'h'           => ['nullable', 'integer', 'min:50', 'max:4000'],
        ]);

        $targetW = (int) ($request->input('w') ?: $this->defaultWidth);
        $targetH = (int) ($request->input('h') ?: $this->defaultHeight);

        if ($request->hasFile('image')) {
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

    private function resizeAndStore(\Illuminate\Http\UploadedFile $file, int $width, int $height): string
    {
        $manager = new ImageManager(new Driver());
        $image   = $manager->read($file->getPathname());
        $resized = $image->cover($width, $height);

        $dir      = 'uploads/hilight';
        $filename = uniqid('hl_', true) . '.jpg';
        $path     = $dir . '/' . $filename;

        Storage::disk('public')->put($path, (string) $resized->toJpeg(85));

        return $path;
    }

    public function reorder(Request $request)
    {
        $ids = $request->input('ids', []);

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['ok' => false, 'message' => 'ไม่พบรายการ'], 422);
        }

        DB::transaction(function () use ($ids) {
            foreach ($ids as $index => $id) {
                Hilight::whereKey($id)->update(['ordering' => $index + 1]);
            }
        });

        return response()->json(['ok' => true, 'message' => 'อัปเดตลำดับแล้ว']);
    }
}
