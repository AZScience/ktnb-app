<?php

namespace App\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function download(Collection $rows, array $headers, string $filename): StreamedResponse
    {
        return $this->downloadFromArrays($rows->all(), $headers, $filename);
    }

    /** @param  list<array<string, mixed>>  $rows */
    public function downloadFromArrays(array $rows, array $headers, string $filename, ?string $sheetTitle = null): StreamedResponse
    {
        $filename = $this->sanitizeDownloadFilename($filename);
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        if ($sheetTitle) {
            $sheet->setTitle(mb_substr($sheetTitle, 0, 31));
        }

        $matrix = [['STT', ...array_values($headers)]];
        foreach ($rows as $index => $row) {
            $line = [$index + 1];
            foreach (array_keys($headers) as $key) {
                $value = $row[$key] ?? '';
                if (is_bool($value)) {
                    $value = $value ? 'Có' : 'Không';
                }
                $line[] = $value;
            }
            $matrix[] = $line;
        }

        $sheet->fromArray($matrix, null, 'A1', true);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function sanitizeDownloadFilename(string $filename): string
    {
        $filename = trim(str_replace(['\\', '/'], '-', $filename));
        $filename = preg_replace('/[<>:"|?*]/', '-', $filename) ?? $filename;
        $filename = preg_replace('/-+/', '-', $filename) ?? $filename;

        if ($filename === '' || $filename === '.xlsx') {
            return 'export.xlsx';
        }

        if (! str_ends_with(strtolower($filename), '.xlsx')) {
            $filename .= '.xlsx';
        }

        return $filename;
    }
}
