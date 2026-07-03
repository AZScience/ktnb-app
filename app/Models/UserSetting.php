<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $fillable = ['user_id', 'data'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function forUser(int $userId): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            ['data' => []],
        );
    }

    public static function presetsKey(string $module): string
    {
        return match ($module) {
            'in-person' => 'inperson_advanced_presets',
            'external-practice' => 'external_practice_advanced_presets',
            'dashboard-schedule' => 'dashboard_schedule_advanced_presets',
            'schedule-settings' => 'schedule_advanced_presets',
            default => "{$module}_advanced_presets",
        };
    }

    public static function legacyFiltersKey(string $module): string
    {
        return match ($module) {
            'in-person' => 'inperson_advanced_filters',
            'external-practice' => 'external_practice_advanced_filters',
            'dashboard-schedule' => 'dashboard_schedule_advanced_filters',
            'schedule-settings' => 'schedule_advanced_filters',
            default => "{$module}_advanced_filters",
        };
    }

    /** @return array<int, array{name: string, filters: array<string, mixed>}> */
    public function getPresets(string $module): array
    {
        $data = $this->data ?? [];
        $key = self::presetsKey($module);
        $presets = $data[$key] ?? [];

        if ($presets === [] && isset($data[self::legacyFiltersKey($module)])) {
            return [
                [
                    'name' => 'Bộ lọc cũ',
                    'filters' => $data[self::legacyFiltersKey($module)],
                ],
            ];
        }

        return is_array($presets) ? array_values($presets) : [];
    }

    /** @param array<int, array{name: string, filters: array<string, mixed>}> $presets */
    public function setPresets(string $module, array $presets): void
    {
        $data = $this->data ?? [];
        $data[self::presetsKey($module)] = $presets;
        $data['updated_at'] = now()->toIso8601String();
        $this->data = $data;
        $this->save();
    }
}
