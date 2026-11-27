<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CatalogExcelService
{
    private const MAX_ROWS = 100000;

    private const MAX_COLUMNS = 50;

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
        @ini_set('memory_limit', '1024M');
        @ini_set('max_execution_time', '600');

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        $spreadsheet = $reader->load($path);
        $sheets = $spreadsheet->getAllSheets();

        $labelToKey = $this->buildLabelToKeyMap($fieldLabels, $options['aliases'] ?? []);
        $allRows = [];

        foreach ($sheets as $sheet) {
            $highestRow = max((int) $sheet->getHighestRow(), (int) $sheet->getHighestDataRow());
            if ($highestRow < 1) {
                continue;
            }
            $highestRow = min($highestRow, self::MAX_ROWS);

            $highestColumn = $sheet->getHighestDataColumn();
            $highestColumnIndex = min(
                Coordinate::columnIndexFromString($highestColumn),
                self::MAX_COLUMNS
            );
            if ($highestColumnIndex < 1) {
                continue;
            }

            $endAddress = Coordinate::stringFromColumnIndex($highestColumnIndex).$highestRow;
            $matrix = $sheet->rangeToArray('A1:'.$endAddress, '', false, true, false);

            if (! empty($matrix)) {
                $sheetRows = $this->parseMatrix($matrix, $labelToKey, $fieldLabels, $options);
                if (! empty($sheetRows)) {
                    $allRows = array_merge($allRows, $sheetRows);
                }
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $sheets, $reader);
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        return $allRows;
    }

    /**
     * @param  list<list<mixed>>  $matrix
     * @param  array<string, string>  $labelToKey
     * @param  array<string, string>  $fieldLabels
     * @param  array{
     *     aliases?: array<string, string>,
     *     headerRowIndices?: list<int>,
     *     requiredKey?: string,
     * }  $options
     * @return list<array<string, string>>
     */
    private function parseMatrix(array $matrix, array $labelToKey, array $fieldLabels, array $options = []): array
    {
        $requiredKey = $options['requiredKey'] ?? null;

        // Dynamic header row detection: scan candidate rows to find the row matching the most field labels
        $scanLimit = min(50, count($matrix) - 1);
        $candidateIndices = isset($options['headerRowIndices'])
            ? array_unique(array_merge($options['headerRowIndices'], range(0, $scanLimit)))
            : range(0, $scanLimit);

        $bestHeaderIndex = null;
        $bestMappedKeys = [];
        $maxScore = 0;

        foreach ($candidateIndices as $idx) {
            if (! isset($matrix[$idx])) {
                continue;
            }

            $mappedKeys = $this->mapHeaderRow($matrix[$idx], $labelToKey);
            $matchesCount = count($mappedKeys);

            if ($matchesCount < 2) {
                continue;
            }

            // Priority score: rows with requiredKey get large bonus, plus number of mapped columns
            $hasRequiredKey = ! $requiredKey || in_array($requiredKey, $mappedKeys, true);
            $score = $matchesCount + ($hasRequiredKey ? 100 : 0);

            if ($score > $maxScore) {
                $maxScore = $score;
                $bestHeaderIndex = $idx;
                $bestMappedKeys = $mappedKeys;
            }
        }

        if ($bestHeaderIndex !== null && ! empty($bestMappedKeys)) {
            $rows = [];
            for ($r = $bestHeaderIndex + 1, $count = count($matrix); $r < $count; $r++) {
                $line = $matrix[$r];
                if ($this->isEmptyDataRow($line, $bestMappedKeys)) {
                    continue;
                }

                // Loại trừ ngay dòng header lặp lại giữa các khối/trang dữ liệu
                if ($this->isRepeatedHeaderRow($line, $labelToKey)) {
                    continue;
                }

                $row = [];
                foreach ($bestMappedKeys as $i => $key) {
                    $row[$key] = trim((string) ($line[$i] ?? ''));
                }

                // If 'name' is missing but separate 'last_name' / 'first_name' columns exist, merge them
                if ((! isset($row['name']) || trim($row['name']) === '') && (isset($row['last_name']) || isset($row['first_name']))) {
                    $row['name'] = trim(($row['last_name'] ?? '').' '.($row['first_name'] ?? ''));
                }
                unset($row['last_name'], $row['first_name']);

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

        return [];
    }

    /**
     * Chuẩn hóa tiêu đề: loại bỏ xuống dòng, khoảng trắng thừa, non-breaking space và ký tự đặc biệt
     */
    public function normalizeHeader(mixed $header): string
    {
        if ($header === null) {
            return '';
        }
        $str = (string) $header;
        // Bỏ ký tự xuống dòng (\r, \n), tab, non-breaking space (0xA0 / \xc2\xa0)
        $str = str_replace(["\r\n", "\r", "\n", "\t", "\xc2\xa0", "\u{00a0}"], ' ', $str);
        // Gom nhiều khoảng trắng liên tiếp thành 1 khoảng trắng
        $str = preg_replace('/\s+/', ' ', $str);
        // Trim các ký tự thừa ở đầu và cuối chuỗi
        $str = trim($str, " \t\n\r\0\x0B:.*-_()#");

        return mb_strtolower($str, 'UTF-8');
    }

    /**
     * @param  list<string>  $nameAliases
     * @return list<array{name: string, note: string}>
     */
    public function parseNameNoteRows(string $path, string $nameLabel, array $nameAliases = []): array
    {
        $aliases = [];
        foreach (array_merge([$nameLabel], $nameAliases) as $alias) {
            $aliases[$this->normalizeHeader($alias)] = 'name';
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
            $norm = $this->normalizeHeader($label);
            if ($norm !== '') {
                $labelToKey[$norm] = $key;
            }
        }

        foreach ($aliases as $alias => $key) {
            $norm = $this->normalizeHeader($alias);
            if ($norm !== '') {
                $labelToKey[$norm] = $key;
            }
        }

        return $labelToKey;
    }

    /** @param array<string, string> $labelToKey */
    private function mapHeaderRow(array $headerRow, array $labelToKey): array
    {
        $mappedKeys = [];
        foreach ($headerRow as $i => $header) {
            $normalized = $this->normalizeHeader($header);
            if ($normalized !== '' && isset($labelToKey[$normalized])) {
                $mappedKeys[$i] = $labelToKey[$normalized];
            }
        }

        return $mappedKeys;
    }

    /**
     * Kiểm tra xem dòng hiện tại có phải là dòng header lặp lại không
     * (nếu có từ 2 ô trở lên khớp với từ khóa tiêu đề cột)
     */
    private function isRepeatedHeaderRow(array $line, array $labelToKey): bool
    {
        $matchCount = 0;
        foreach ($line as $cell) {
            $norm = $this->normalizeHeader($cell);
            if ($norm !== '' && isset($labelToKey[$norm])) {
                $matchCount++;
                if ($matchCount >= 2) {
                    return true;
                }
            }
        }

        return false;
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
        $headerLabels = array_map(fn ($label) => $this->normalizeHeader($label), array_values($fieldLabels));
        $headerLabels = array_merge($headerLabels, [
            'mssv', 'stt', 'số cmnd', 'cccd', 'mã sinh viên', 'họ và tên', 'họ tên', 'giới tính', 'ngày sinh',
        ]);

        foreach ($row as $value) {
            $normalized = $this->normalizeHeader($value);
            if ($normalized !== '' && ! in_array($normalized, $headerLabels, true)) {
                return true;
            }
        }

        return false;
    }
}
