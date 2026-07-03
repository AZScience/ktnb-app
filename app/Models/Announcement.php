<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'body',
        'is_active',
        'published_from',
        'published_until',
        'created_by_user_id',
        'created_by_name',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'published_from' => 'datetime',
            'published_until' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeVisibleOnDashboard(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $inner) use ($now) {
                $inner->whereNull('published_from')->orWhere('published_from', '<=', $now);
            })
            ->where(function (Builder $inner) use ($now) {
                $inner->whereNull('published_until')->orWhere('published_until', '>=', $now);
            })
            ->orderByDesc('created_at');
    }
}
