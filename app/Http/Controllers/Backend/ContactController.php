<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function edit()
    {
        $contact = Contact::firstOrCreate(['id' => 1], [
            'address'  => null,
            'email'    => null,
            'tel'      => null,
            'fax'      => null,
            'map'      => null,
            'facebook' => null,
            'youtube'  => null,
        ]);

        return view('backend.contact.edit', compact('contact'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'address'  => ['nullable', 'string', 'max:500'],
            'email'    => ['nullable', 'email', 'max:255'],
            'tel'      => ['nullable', 'string', 'max:50'],
            'fax'      => ['nullable', 'string', 'max:50'],
            'map'      => ['nullable', 'string'], // รับได้ทั้งลิงก์หรือโค้ด iframe
            'facebook' => ['nullable', 'url', 'max:255'],
            'youtube'  => ['nullable', 'url', 'max:255'],
        ]);

        Contact::where('id', 1)->update($data);

        flash_notify('บันทึกการแก้ไขแล้ว', 'success');
        return back(); // กลับหน้าเดิม
    }
}
