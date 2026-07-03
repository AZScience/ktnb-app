<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Classroom extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'name', 'building_block_id', 'seating_capacity', 'table_count',
        'exam_capacity', 'room_type', 'subject_nature', 'has_projector', 'is_inactive', 'note',
    ];

    protected $casts = [
        'has_projector' => 'boolean',
        'is_inactive' => 'boolean',
    ];

    public function buildingBlock(): BelongsTo
    {
        return $this->belongsTo(BuildingBlock::class);
    }
}
