<?php
// app/Mail/ContactMessageReply.php
namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMessageReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $messageModel, public string $replyText)
    {}

    public function build()
    {
        $m = $this->messageModel;
        return $this->subject('[ตอบกลับ] ' . $m->subject)
            ->view('backend.contact_messages.email.reply')
            ->with([
                'name'      => $m->name,
                'subject'   => $m->subject,
                'userText'  => $m->message,
                'replyText' => $this->replyText,
            ]);
    }
}
