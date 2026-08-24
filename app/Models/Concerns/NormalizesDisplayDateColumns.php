<?php

namespace App\Models\Concerns;

use App\Support\DateStringNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

trait NormalizesDisplayDateColumns
{
    /** @var array<string, bool> */
    private static array $isoColumnExistsCache = [];

    protected static function bootNormalizesDisplayDateColumns(): void
    {
        static::saving(function (Model $model): void {
            foreach (static::displayDateIsoColumns() as $displayColumn => $isoColumn) {
                if (! static::isoColumnExists($model, $isoColumn)) {
                    continue;
                }

                if ($model->isDirty($displayColumn) || ! array_key_exists($isoColumn, $model->getAttributes())) {
                    $model->setAttribute($isoColumn, DateStringNormalizer::toIso($model->getAttribute($displayColumn)));
                }
            }
        });
    }

    /** @return array<string, string> display column => ISO shadow column */
    protected static function displayDateIsoColumns(): array
    {
        return [];
    }

    private static function isoColumnExists(Model $model, string $isoColumn): bool
    {
        $table = $model->getTable();
        $cacheKey = "{$table}.{$isoColumn}";

        if (! array_key_exists($cacheKey, self::$isoColumnExistsCache)) {
            self::$isoColumnExistsCache[$cacheKey] = Schema::hasColumn($table, $isoColumn);
        }

        return self::$isoColumnExistsCache[$cacheKey];
    }
}
