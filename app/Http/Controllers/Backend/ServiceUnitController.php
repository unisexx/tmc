<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceUnitController extends Controller
{
    public function switch(Request $req)
    {
            $user = Auth::user();
            $id   = (int) $req->input('service_unit_id');

            // ตรวจสอบว่า user มีสิทธิ์ใน unit นี้
            if ($user->serviceUnits()->where('service_units.id', $id)->exists()) {
                session(['current_service_unit_id' => $id]);
            }

            return back();
    }
}
