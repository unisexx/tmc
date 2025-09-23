<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UploadController extends Controller
{
    public function tinymce(Request $request)
    {
        // TinyMCE ส่งไฟล์มาในชื่อ field = "file"
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpeg,png,gif,webp,svg', 'max:5120'], // 5MB
        ]);

        $file = $request->file('file');

        // ปลอดภัย: สร้างชื่อไฟล์ใหม่
        $ext  = strtolower($file->getClientOriginalExtension());
        $name = Str::uuid()->toString() . '.' . $ext;

        // โฟลเดอร์ตามปี/เดือน
        $dir  = 'uploads/tinymce/' . now()->format('Y/m');
        $path = $file->storeAs($dir, $name, 'public'); // => storage/app/public/...

        if (!$path) {
            throw ValidationException::withMessages([
                'file' => 'อัปโหลดไม่สำเร็จ',
            ]);
        }

        // TinyMCE ต้องการ { location: "absolute-or-relative-url" }
        return response()->json([
            'location' => asset('storage/' . $path),
        ]);
    }
}
