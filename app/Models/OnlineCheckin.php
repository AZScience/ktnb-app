<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineCheckin extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'payload', 'server_timestamp'];

    protected $casts = [
        'payload' => 'array',
        'server_timestamp' => 'datetime',
    ];
}
