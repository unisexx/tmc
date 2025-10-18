<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    /** แสดงรายการข่าวทั้งหมด */
    public function index(Request $request)
    {
        $filters  = $request->only(['q', 'category']);
        $newsList = News::active()
            ->filter($filters)
            ->latest('created_at')
            ->paginate(6);

        return view('frontend.news.index', compact('newsList', 'filters'));
    }

    /** แสดงรายละเอียดข่าว */
    public function show($id)
    {
        $news = News::active()->findOrFail($id);
        $news->increment('views');
        return view('frontend.news.show', compact('news'));
    }
}
