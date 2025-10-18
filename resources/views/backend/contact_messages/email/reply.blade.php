{{-- resources/views/emails/contact_messages/reply.blade.php --}}
<p>เรียน คุณ{{ $name }}</p>
<p>เกี่ยวกับ: <strong>{{ $subject }}</strong></p>

<p>ข้อความที่ท่านส่งมา:</p>
<pre style="white-space:pre-wrap">{{ $userText }}</pre>

<hr>
<p>คำตอบจากทีมงาน:</p>
<pre style="white-space:pre-wrap">{{ $replyText }}</pre>

<p>ขอบคุณครับ</p>
