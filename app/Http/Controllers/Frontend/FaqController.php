<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /** แสดงรายการคำถามที่พบบ่อยทั้งหมด */
    public function index(Request $request)
    {
        $q = $request->input('q');

        $faqs = Faq::query()
            ->active()
            ->when($q, fn($query) =>
                $query->where(function ($w) use ($q) {
                    $w->where('question', 'like', "%{$q}%")
                        ->orWhere('answer', 'like', "%{$q}%");
                })
            )
            ->orderBy('ordering')
            ->paginate(10)
            ->withQueryString();

        return view('frontend.faq.index', compact('faqs', 'q'));
    }
}
