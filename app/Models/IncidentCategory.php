<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentCategory extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'recognition_id', 'name', 'note'];

    public function recognition(): BelongsTo
    {
        return $this->belongsTo(Recognition::class);
    }
}
