<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuildingBlock extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'code', 'name', 'is_inactive', 'note'];

    protected $casts = ['is_inactive' => 'boolean'];

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }
}
