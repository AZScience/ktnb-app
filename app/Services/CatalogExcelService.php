<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CatalogExcelService
{
    private const MAX_ROWS = 5000;

    private const MAX_COLUMNS = 40;

    /**
     * @param  array<string, string>  $fieldLabels  key => Vietnamese header label
     * @param  array{
     *     aliases?: array<string, string>,
     *     headerRowIndices?: list<int>,
     *     requiredKey?: string,
     * }  $options
     * @return list<array<string, string>>
     */
    public function parseRows(string $path, array $fieldLabels, array $options = []): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = min((int) $sheet->getHighestDataRow(), self::MAX_ROWS);
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnIndex = min(
            Coordinate::columnIndexFromString($highestColumn),
            self::MAX_COLUMNS
        );

        $matrix = [];
        for ($row = 1; $row <= $highestRow; $row++) {
            $line = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $address = Coordinate::stringFromColumnIndex($col).$row;
                $line[] = $sheet->getCell($address)->getFormattedValue();
            }
            $matrix[] = $line;
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if ($matrix === []) {
            return [];
        }

        $labelToKey = $this->buildLabelToKeyMap($fieldLabels, $options['aliases'] ?? []);
        $headerRowIndices = $options['headerRowIndices'] ?? [0, 7];
        $requiredKey = $options['requiredKey'] ?? null;

        foreach ($headerRowIndices as $headerRowIndex) {
            if (! isset($matrix[$headerRowIndex])) {
                continue;
            }

            $mappedKeys = $this->mapHeaderRow($matrix[$headerRowIndex], $labelToKey);
            if ($mappedKeys === []) {
                continue;
            }

            $rows = [];
            for ($r = $headerRowIndex + 1, $count = count($matrix); $r < $count; $r++) {
                $line = $matrix[$r];
                if ($this->isEmptyDataRow($line, $mappedKeys)) {
                    continue;
                }

                $row = [];
                foreach ($mappedKeys as $i => $key) {
                    $row[$key] = trim((string) ($line[$i] ?? ''));
                }

                if (! $this->isDataRow($row, $fieldLabels)) {
                    continue;
                }

                if ($requiredKey && trim((string) ($row[$requiredKey] ?? '')) === '') {
                    continue;
                }

                $rows[] = $row;
            }

            if ($rows !== []) {
                return $rows;
            }
        }

        $rows = [];
        $keys = array_keys($fieldLabels);
        foreach ($matrix as $line) {
            if ($this->isEmptyRow($line)) {
                continue;
            }

            $row = [];
            foreach ($keys as $i => $key) {
                $row[$key] = trim((string) ($line[$i] ?? ''));
            }

            if (! $this->isDataRow($row, $fieldLabels)) {
                continue;
            }

            if ($requiredKey && trim((string) ($row[$requiredKey] ?? '')) === '') {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  list<string>  $nameAliases
     * @return list<array{name: string, note: string}>
     */
    public function parseNameNoteRows(string $path, string $nameLabel, array $nameAliases = []): array
    {
        $aliases = [];
        foreach (array_merge([$nameLabel], $nameAliases) as $alias) {
            $aliases[mb_strtolower(trim($alias))] = 'name';
        }
        $aliases['ghi chú'] = 'note';
        $aliases['ghichu'] = 'note';

        return $this->parseRows($path, [
            'name' => $nameLabel,
            'note' => 'Ghi chú',
        ], [
            'aliases' => $aliases,
            'requiredKey' => 'name',
            'headerRowIndices' => [0, 7],
        ]);
    }

    /** @param  array<string, string>  $fieldLabels */
    public static function toBool(mixed $value): bool
    {
        return in_array(mb_strtolower(trim((string) $value)), ['1', 'có', 'co', 'yes', 'true', 'x'], true);
    }

    /**
     * @param  array<string, string>  $fieldLabels
     * @param  array<string, string>  $aliases  normalized label => field key
     * @return array<string, string>
     */
    private function buildLabelToKeyMap(array $fieldLabels, array $aliases): array
    {
        $labelToKey = [];
        foreach ($fieldLabels as $key => $label) {
            $labelToKey[mb_strtolower(trim($label))] = $key;
        }

        foreach ($aliases as $alias => $key) {
            $labelToKey[mb_strtolower(trim($alias))] = $key;
        }

        return $labelToKey;
    }

    /** @param array<string, string> $labelToKey */
    private function mapHeaderRow(array $headerRow, array $labelToKey): array
    {
        $mappedKeys = [];
        foreach ($headerRow as $i => $header) {
            $normalized = mb_strtolower(trim((string) $header));
            if ($normalized !== '' && isset($labelToKey[$normalized])) {
                $mappedKeys[$i] = $labelToKey[$normalized];
            }
        }

        return $mappedKeys;
    }

    /** @param array<int, string> $mappedKeys */
    private function isEmptyDataRow(array $line, array $mappedKeys): bool
    {
        foreach ($mappedKeys as $i => $_key) {
            if (trim((string) ($line[$i] ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }

    private function isEmptyRow(array $line): bool
    {
        foreach ($line as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, string> $fieldLabels */
    private function isDataRow(array $row, array $fieldLabels): bool
    {
        $headerLabels = array_map(fn ($label) => mb_strtolower(trim($label)), array_values($fieldLabels));
        $headerLabels = array_merge($headerLabels, ['mssv', 'stt', 'số cmnd', 'cccd']);

        foreach ($row as $value) {
            $normalized = mb_strtolower(trim((string) $value));
            if ($normalized !== '' && ! in_array($normalized, $headerLabels, true)) {
                return true;
            }
        }

        return false;
    }
}
