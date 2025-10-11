<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $q       = $request->get('q');
        $reorder = $request->boolean('reorder'); // โหมดจัดเรียง (ดึงทุกรายการ)

        $query = Faq::when($q, function ($qq) use ($q) {
            $qLike = "%{$q}%";
            $qq->where(function ($w) use ($qLike) {
                $w->where('question', 'like', $qLike)
                    ->orWhere('answer', 'like', $qLike);
            });
        })
            ->orderBy('ordering')
            ->orderByDesc('id');

        // ถ้าโหมดจัดเรียง ให้ดึงทั้งหมด (เพื่อใช้ drag & drop ได้ทั้งชุด)
        $rs = $reorder ? $query->get() : $query->paginate(20)->withQueryString();

        return view('backend.faq.index', compact('rs', 'q', 'reorder'));
    }

    public function create()
    {
        $faq = new Faq();
        return view('backend.faq.create', compact('faq'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'question'  => ['required', 'string', 'max:500'],
            'answer'    => ['required', 'string'],
            'ordering'  => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) $request->boolean('is_active');

        Faq::create($data);

        flash_notify('เพิ่มคำถามที่พบบ่อยสำเร็จ', 'success');
        return redirect()->route('backend.faq.index');
    }

    public function edit(Faq $faq)
    {
        return view('backend.faq.edit', compact('faq'));
    }

    public function update(Request $request, Faq $faq)
    {
        $data = $request->validate([
            'question'  => ['required', 'string', 'max:500'],
            'answer'    => ['required', 'string'],
            'ordering'  => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) $request->boolean('is_active');

        $faq->update($data);

        flash_notify('บันทึกการแก้ไขแล้ว', 'success');
        return redirect()->route('backend.faq.index');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        flash_notify('ลบรายการแล้ว', 'success');
        return back();
    }

    /**
     * รับลำดับใหม่จากหน้า index (ลากวาง) แล้วอัปเดตลง DB
     * payload: { ids: [5, 2, 9, 1, ...] } ตามลำดับบนลงล่าง
     */
    public function reorder(Request $request)
    {
        $ids = $request->input('ids', []);

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['ok' => false, 'message' => 'ไม่พบรายการ'], 422);
        }

        DB::transaction(function () use ($ids) {
            foreach ($ids as $index => $id) {
                Faq::whereKey($id)->update(['ordering' => $index + 1]); // เริ่มจาก 1
            }
        });

        return response()->json(['ok' => true, 'message' => 'อัปเดตลำดับแล้ว']);
    }

    /**
     * เพิ่ม views เมื่อผู้ใช้คลิกย่อ/ขยายดูคำตอบ (AJAX)
     * Route: POST /backend/faq/{faq}/view
     */
    public function countView(Faq $faq)
    {
        $faq->increment('views');
        return response()->json(['views' => $faq->views]);
    }
}
