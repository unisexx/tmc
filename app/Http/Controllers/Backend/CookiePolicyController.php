<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CookiePolicy;
use Illuminate\Http\Request;

class CookiePolicyController extends Controller
{
    public function edit()
    {
        // ให้มีเรคอร์ดเสมอ
        $policy = CookiePolicy::firstOrCreate(['id' => 1], [
            'title'       => 'นโยบายคุกกี้',
            'description' => null,
        ]);

        return view('backend.cookie_policy.edit', compact('policy'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'], // รับ HTML จาก editor
        ]);

        CookiePolicy::where('id', 1)->update($data);

        flash_notify('บันทึกการแก้ไขแล้ว', 'success');
        return back();
    }
}
