<?php

namespace App\Services;

use ZipArchive;

class DailyReportTemplateSanitizer
{
    private const DEFAULT_MAX_ROW = 8;

    public function sourcePath(): string
    {
        return public_path('templates/daily_report_template.xlsx');
    }

    public function sanitizedPath(): string
    {
        return storage_path('app/templates/daily_report_template.xlsx');
    }

    public function isUsableSource(string $path): bool
    {
        if (! is_file($path) || ! is_readable($path)) {
            return false;
        }

        $size = filesize($path);

        return $size !== false && $size > 10_000;
    }

    public function isUsableSanitized(string $path): bool
    {
        if (! is_file($path) || ! is_readable($path)) {
            return false;
        }

        $size = filesize($path);

        return $size !== false && $size > 10_000 && $size <= 10_000_000;
    }

    public function ensureSanitized(?string $targetPath = null): string
    {
        $targetPath = $targetPath ?? $this->sanitizedPath();
        $sourcePath = $this->sourcePath();

        if ($this->isUsableSanitized($targetPath)) {
            return $targetPath;
        }

        if (! $this->isUsableSource($sourcePath)) {
            throw new \RuntimeException('Không tìm thấy file mẫu gốc daily_report_template.xlsx.');
        }

        $this->sanitize($sourcePath, $targetPath);

        return $targetPath;
    }

    public function sanitize(string $sourcePath, string $targetPath, int $maxRow = self::DEFAULT_MAX_ROW): void
    {
        $source = new ZipArchive;
        if ($source->open($sourcePath) !== true) {
            throw new \RuntimeException("Không mở được file mẫu: {$sourcePath}");
        }

        $directory = dirname($targetPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (is_file($targetPath)) {
            @unlink($targetPath);
        }

        $target = new ZipArchive;
        if ($target->open($targetPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $source->close();
            throw new \RuntimeException("Không tạo được file mẫu đã làm sạch: {$targetPath}");
        }

        for ($index = 0; $index < $source->numFiles; $index++) {
            $name = $source->getNameIndex($index);
            if (! is_string($name) || str_ends_with($name, '/')) {
                continue;
            }

            if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name) === 1) {
                $xml = $source->getFromIndex($index);
                if (! is_string($xml)) {
                    continue;
                }

                $target->addFromString($name, $this->filterWorksheetXml($xml, $maxRow));
                continue;
            }

            $contents = $source->getFromIndex($index);
            if (is_string($contents)) {
                $target->addFromString($name, $contents);
            }
        }

        $source->close();
        $target->close();
    }

    private function filterWorksheetXml(string $xml, int $maxRow): string
    {
        $filtered = preg_replace_callback(
            '/<row\b[^>]*\br="(\d+)"[^>]*>.*?<\/row>/s',
            static fn (array $matches): string => ((int) $matches[1]) <= $maxRow ? $matches[0] : '',
            $xml,
        );

        if (! is_string($filtered)) {
            throw new \RuntimeException('Không thể làm sạch worksheet XML.');
        }

        $lastCol = $this->detectLastColumn($filtered, $maxRow) ?? 'M';
        $dimensionRef = "A1:{$lastCol}{$maxRow}";

        if (preg_match('/<dimension\b[^>]*ref="[^"]*"/', $filtered) === 1) {
            $filtered = preg_replace(
                '/<dimension\b[^>]*ref="[^"]*"/',
                '<dimension ref="'.$dimensionRef.'"',
                $filtered,
                1,
            );
        }

        return $filtered;
    }

    private function detectLastColumn(string $xml, int $maxRow): ?string
    {
        $lastIndex = 1;

        if (preg_match_all('/\br="([A-Z]+)(\d+)"/', $xml, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if ((int) $match[2] > $maxRow) {
                    continue;
                }

                $index = $this->columnIndex($match[1]);
                if ($index > $lastIndex) {
                    $lastIndex = $index;
                }
            }
        }

        return $this->columnLetter($lastIndex);
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;
        $length = strlen($letters);

        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return $index;
    }

    private function columnLetter(int $index): string
    {
        $letters = '';

        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letters = chr(65 + $mod).$letters;
            $index = intdiv($index - 1, 26);
        }

        return $letters !== '' ? $letters : 'A';
    }
}
