<?php
// app/Models/ContactMessage.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'message',
        'ip', 'user_agent',
        'status', 'handled_by', 'handled_at', 'read_at',
        'reply_message', 'replied_at',
        'is_spam', 'spam_score', 'tags',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
        'read_at'    => 'datetime',
        'replied_at' => 'datetime',
        'is_spam'    => 'boolean',
        'tags'       => 'array',
    ];

    // -------------------------
    // Scopes
    // -------------------------
    public function scopeNotSpam($q)
    {
        return $q->where('is_spam', false);
    }

    // -------------------------
    // Relations
    // -------------------------
    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
