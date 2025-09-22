<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StatController extends Controller
{
    /**
     * แสดงหน้าแดชบอร์ด (Backend)
     */
    public function index()
    {
        return view('backend.stat');
    }
}
