<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'note', 'permissions'];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    protected function permissions(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::decodePermissionsPayload($value),
            set: fn ($value) => json_encode(
                is_array($value) ? $value : self::decodePermissionsPayload($value),
                JSON_UNESCAPED_UNICODE,
            ),
        );
    }

    /** @return array<string, array<string, bool>> */
    public static function decodePermissionsPayload(mixed $value): array
    {
        $current = $value;

        for ($i = 0; $i < 5; $i++) {
            if (is_array($current)) {
                return $current;
            }

            if (! is_string($current) || trim($current) === '') {
                return [];
            }

            $next = json_decode($current, true);
            if ($next === null && json_last_error() !== JSON_ERROR_NONE) {
                $stripped = stripslashes($current);
                if ($stripped !== $current) {
                    $next = json_decode($stripped, true);
                }
            }

            if ($next === null && json_last_error() !== JSON_ERROR_NONE) {
                break;
            }

            $current = $next;
        }

        return is_array($current) ? $current : [];
    }
}
