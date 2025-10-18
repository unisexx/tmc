<?php
// app/Http/Controllers/Backend/ContactMessageController.php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageReply;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $q      = (string) $request->query('q', '');
        $status = (string) $request->query('status', '');
        $read   = (string) $request->query('read', '');

        $rs = ContactMessage::query()
            ->notSpam()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('subject', 'like', "%{$q}%")
                        ->orWhere('message', 'like', "%{$q}%");
                });
            })
            ->when(in_array($status, ['new', 'done'], true), fn($q2) => $q2->where('status', $status))
            ->when(in_array($read, ['read', 'unread'], true), function ($q2) use ($read) {
                return $read === 'read' ? $q2->whereNotNull('read_at') : $q2->whereNull('read_at');
            })
            ->latest()->paginate(20)->withQueryString();

        return view('backend.contact_messages.index', compact('rs', 'q', 'status', 'read'));
    }

    public function show(ContactMessage $contactMessage)
    {
        if (is_null($contactMessage->read_at)) {
            $contactMessage->update(['read_at' => now()]);
        }
        return view('backend.contact_messages.show', compact('contactMessage'));
    }

    // คงไว้เพื่อความเข้ากันได้เดิม ถ้าเรียก path /done โดยตรง
    public function markDone(ContactMessage $contactMessage)
    {
        $contactMessage->update([
            'status'     => 'done',
            'handled_by' => auth()->id(),
            'handled_at' => now(),
        ]);
        flash_notify('ทำเครื่องหมายเสร็จแล้ว', 'success');
        return back();
    }

    public function reply(Request $request, ContactMessage $contactMessage)
    {
        $action = $request->input('_action', 'reply'); // reply | done

        if ($action === 'done') {
            // ปิดงานจากปุ่ม "ดำเนินการแล้ว" ไม่ต้อง validate/ส่งอีเมล
            $contactMessage->update([
                'status'     => 'done',
                'handled_by' => auth()->id(),
                'handled_at' => now(),
            ]);
            flash_notify('ทำเครื่องหมายเสร็จแล้ว', 'success');
            return back();
        }

        // โหมดส่งคำตอบ
        $data = $request->validate([
            'reply_message' => ['required', 'string', 'max:5000'],
        ]);

        Mail::to($contactMessage->email)->send(
            new ContactMessageReply($contactMessage, $data['reply_message'])
        );

        $contactMessage->update([
            'reply_message' => $data['reply_message'],
            'replied_at'    => now(),
            'status'        => 'done',
            'handled_by'    => auth()->id(),
            'handled_at'    => now(),
        ]);

        flash_notify('ส่งคำตอบเรียบร้อยแล้ว', 'success');
        return back();
    }
}
