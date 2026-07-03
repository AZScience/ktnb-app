<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ScheduleImportReadFilter implements IReadFilter
{
    private const MAX_ROW = 5000;

    private const MAX_COLUMN_INDEX = 20;

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        if ($row < 1 || $row > self::MAX_ROW) {
            return false;
        }

        $colIndex = Coordinate::columnIndexFromString($columnAddress);

        return $colIndex >= 1 && $colIndex <= self::MAX_COLUMN_INDEX;
    }
}
