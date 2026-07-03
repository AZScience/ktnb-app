<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recognition extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'note'];

    public function incidentCategories(): HasMany
    {
        return $this->hasMany(IncidentCategory::class);
    }
}
