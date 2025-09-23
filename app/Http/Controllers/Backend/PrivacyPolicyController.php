<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\PrivacyPolicy;
use Illuminate\Http\Request;

class PrivacyPolicyController extends Controller
{
    public function edit()
    {
        // ให้มีเรคอร์ดเสมอ
        $policy = PrivacyPolicy::firstOrCreate(['id' => 1], [
            'title'       => 'นโยบายการคุ้มครองข้อมูลส่วนบุคคล',
            'description' => null,
        ]);

        return view('backend.privacy_policy.edit', compact('policy'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'], // รับ HTML จาก editor
        ]);

        PrivacyPolicy::where('id', 1)->update($data);

        flash_notify('บันทึกการแก้ไขแล้ว', 'success');
        return back();
    }
}
