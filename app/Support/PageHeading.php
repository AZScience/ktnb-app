<?php

namespace App\Support;

class PageHeading
{
    /** @return array{icon: string, tone: string} */
    public static function icon(?string $label): array
    {
        $label = trim(strip_tags((string) $label));
        $icons = config('nttu.page_heading_icons', []);
        $default = $icons['_default'] ?? ['icon' => 'layout', 'tone' => 'primary'];

        if ($label === '') {
            return $default;
        }

        if (isset($icons[$label])) {
            return $icons[$label];
        }

        foreach ($icons as $key => $meta) {
            if ($key === '_default') {
                continue;
            }

            $isPrefixKey = str_ends_with($key, ':') || str_ends_with($key, ' ');
            if (! $isPrefixKey) {
                continue;
            }

            if (str_starts_with($label, $key)) {
                return $meta;
            }
        }

        return $default;
    }

    public static function isSubtitle(?string $label): bool
    {
        $label = trim(strip_tags((string) $label));

        if ($label === '') {
            return false;
        }

        return str_contains($label, 'Chào mừng')
            || str_contains($label, 'Quản lý thông tin định danh')
            || mb_strlen($label) > 72;
    }

    /** @return array{icon: string, tone: string} */
    public static function catalogIcon(string $storageKey, string $fallbackIcon = 'briefcase'): array
    {
        $icons = config('nttu.catalog_entity_icons', []);

        return $icons[$storageKey] ?? ['icon' => $fallbackIcon, 'tone' => 'primary'];
    }
}
