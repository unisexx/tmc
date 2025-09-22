<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * แสดงหน้าแดชบอร์ด (Backend)
     */
    public function index()
    {
        // สมมุติส่งตัวแปรไป view
        $stats = [
            'users' => 150,
            'news'  => 20,
            'faq'   => 10,
        ];

        // render view backend/dashboard.blade.php
        return view('backend.dashboard', compact('stats'));
    }
}
