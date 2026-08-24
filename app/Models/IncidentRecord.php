<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_time',
        'location',
        'participants',
        'content',
        'conclusion_time',
        'witness_name', 'witness_signature', 'creator_signature',
        'creator_name',
        'recorded_by',
        'evidence',
    ];

    protected $casts = [
        'incident_time' => 'datetime',
        'conclusion_time' => 'datetime',
        'participants' => 'array',
    ];

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
