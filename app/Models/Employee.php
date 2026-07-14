<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Employee extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'user_id', 'employee_id', 'name', 'nickname', 'position',
        'birth_date', 'address', 'phone', 'role_id', 'email', 'note', 'avatar_url',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function positionRecord(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return list<string>
     */
    public static function superAdminEmailList(): array
    {
        return array_values(array_filter(array_map(
            static fn (string $email) => strtolower(trim($email)),
            config('nttu.super_admin_emails', []),
        )));
    }

    /**
     * Email ẩn trên trang Nhân viên (không gồm toàn bộ super admin).
     *
     * @return list<string>
     */
    public static function catalogHiddenEmailList(): array
    {
        return array_values(array_filter(array_map(
            static fn (string $email) => strtolower(trim($email)),
            config('nttu.catalog_hidden_emails', ['ngviphuc@gmail.com']),
        )));
    }

    public function isHiddenSuperAdminAccount(): bool
    {
        if ($this->role_id === 'system') {
            return true;
        }

        $email = strtolower(trim((string) $this->email));

        return $email !== '' && in_array($email, self::catalogHiddenEmailList(), true);
    }

    public function scopeVisibleInCatalog(Builder $query): Builder
    {
        $emails = self::catalogHiddenEmailList();

        return $query->where(function (Builder $inner) use ($emails) {
            $inner->whereNull('role_id')->orWhere('role_id', '!=', 'system');

            if ($emails !== []) {
                $placeholders = implode(',', array_fill(0, count($emails), '?'));
                $inner->whereRaw(
                    "LOWER(TRIM(COALESCE(email, ''))) NOT IN ({$placeholders})",
                    $emails,
                );
            }
        });
    }

    /**
     * Map họ tên hoặc bí danh đã lưu sang bí danh (nickname) khi có trong hồ sơ nhân viên.
     */
    public static function nicknameFor(?string $label): string
    {
        $value = self::normalizeRecipientKey($label);
        if ($value === '') {
            return '';
        }

        $lookup = self::recipientNicknameLookup();

        return $lookup[$value] ?? trim((string) $label);
    }

    public static function forgetRecipientNicknameLookup(): void
    {
        Cache::forget('employee-nickname-lookup');
    }

    /**
     * @return array<string, string>
     */
    private static function recipientNicknameLookup(): array
    {
        return Cache::remember('employee-nickname-lookup', 3600, function () {
            $map = [];
            foreach (self::query()->with('user:id,name')->get(['id', 'user_id', 'name', 'nickname']) as $employee) {
                $nickname = trim((string) ($employee->nickname ?: ''));
                $name = trim((string) ($employee->name ?: ''));
                $userName = trim((string) ($employee->user?->name ?? ''));
                $resolved = $nickname !== '' ? $nickname : ($name !== '' ? $name : $userName);

                if ($resolved === '') {
                    continue;
                }

                foreach (array_filter([$nickname, $name, $userName]) as $alias) {
                    $map[self::normalizeRecipientKey($alias)] = $resolved;
                }
            }

            return $map;
        });
    }

    private static function normalizeRecipientKey(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $normalized = preg_replace('/\s+/u', ' ', mb_strtolower($value));

        return is_string($normalized) ? $normalized : mb_strtolower($value);
    }

    public function getPositionNameAttribute(): ?string
    {
        if (! $this->position) {
            return null;
        }

        if ($this->relationLoaded('positionRecord') && $this->positionRecord) {
            return $this->positionRecord->name;
        }

        $byId = Position::find($this->position);
        if ($byId) {
            return $byId->name;
        }

        $byName = Position::where('name', $this->position)->first();

        return $byName?->name;
    }
}
