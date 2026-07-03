<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'logged_at', 'user_id', 'user_email', 'action',
        'target_type', 'details', 'ip_address', 'previous_data', 'new_data',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'previous_data' => 'array',
        'new_data' => 'array',
    ];
}
