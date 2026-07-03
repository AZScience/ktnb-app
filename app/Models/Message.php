<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'sender_user_id', 'sender_legacy_id', 'recipient_user_ids',
        'recipient_legacy_ids', 'subject', 'body', 'attachments',
        'is_read', 'trash_by_user_ids', 'sent_at',
    ];

    protected $casts = [
        'recipient_user_ids' => 'array',
        'recipient_legacy_ids' => 'array',
        'attachments' => 'array',
        'trash_by_user_ids' => 'array',
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
