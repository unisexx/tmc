<?php
// app/Models/ServiceUnitMessage.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceUnitMessage extends Model
{
    protected $fillable = [
        'service_unit_id', 'to_name', 'to_email',
        'from_name', 'from_email', 'subject', 'body',
        'ip', 'user_agent', 'status', 'read_at', 'handled_by', 'handled_at',
        'is_spam', 'spam_score',
    ];

    protected $casts = [
        'read_at'    => 'datetime',
        'handled_at' => 'datetime',
        'is_spam'    => 'bool',
        'spam_score' => 'int',
    ];

    public function serviceUnit()
    {
        return $this->belongsTo(ServiceUnit::class);
    }
}
