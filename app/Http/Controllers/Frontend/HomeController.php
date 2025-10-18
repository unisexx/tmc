<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Hilight;
use App\Models\News;

class HomeController extends Controller
{
    public function home()
    {
        $highlights = Hilight::query()
            ->where('is_active', true)
            ->orderBy('ordering')
            ->get();

        $latestNews = News::query()
            ->active()
            ->latest('created_at')
            ->take(3)
            ->get();

        $faqs = Faq::query()
            ->active()
            ->orderBy('ordering')
            ->take(5)
            ->get();

        return view('frontend.home', compact('highlights', 'latestNews', 'faqs'));
    }
}
