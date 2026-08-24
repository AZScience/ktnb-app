<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoodDeedsGratitudeTemplateExportService
{
    private const DATA_START_ROW = 7;

    private const SUMMARY_OFFSET_AFTER_DATA = 1;

    private const LAST_COL = 12; // A:L

    /** @var array<string, string> */
    private const COLUMN_MAP = [
        'B' => 'appreciationCampus',
        'C' => 'appreciationName',
        'D' => 'appreciationRecDate',
        'E' => 'appreciationGiveDate',
        'F' => 'appreciationId',
        'G' => 'appreciationDept',
        'H' => 'property',
        'I' => 'handoverCount',
        'J' => 'giftCount',
        'K' => 'contactLog',
        'L' => 'templateNote',
    ];

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function download(array $rows, string $fromDisplay, string $toDisplay, string $filename, string $officerFullName = '', array $templateInputs = []): StreamedResponse
    {
        $filename = $this->sanitizeDownloadFilename($filename);
        $spreadsheet = $this->loadTemplateSpreadsheet($this->resolveTemplatePath());
        $worksheet = $spreadsheet->getActiveSheet();

        $this->applyTitle($worksheet, $fromDisplay, $toDisplay, $templateInputs);
        $lastDataRow = $this->fillDataRows($worksheet, $rows, $templateInputs);
        $this->applySummaryAndFooter($worksheet, $rows, $lastDataRow, $toDisplay, $officerFullName, $templateInputs);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function resolveTemplatePath(): string
    {
        $candidates = [
            public_path('templates/nguoitot_template.xlsx'),
            dirname(base_path()).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'nguoitot_template.xlsx',
        ];

        foreach ($candidates as $path) {
            if (is_file($path) && filesize($path) > 0) {
                return $path;
            }
        }

        abort(500, 'Không tìm thấy tệp mẫu Excel nguoitot_template.xlsx.');
    }

    private function loadTemplateSpreadsheet(string $templatePath): Spreadsheet
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setIncludeCharts(false);
        $reader->setReadDataOnly(false);
        $reader->setReadEmptyCells(false);

        return $reader->load($templatePath);
    }

    private function applyTitle(Worksheet $worksheet, string $fromDisplay, string $toDisplay, array $templateInputs): void
    {
        $month = $this->input($templateInputs, 'reportMonth', date('n'));
        $year = $this->input($templateInputs, 'reportYear', date('Y'));
        $titleFrom = $this->input($templateInputs, 'titleFromDate', $fromDisplay);
        $titleTo = $this->input($templateInputs, 'titleToDate', $toDisplay);

        $worksheet->setTitle('THANG '.str_pad((string) $month, 2, '0', STR_PAD_LEFT).$year);
        $worksheet->setCellValue('A5', "THÁNG {$month}/{$year}\n(Từ ngày {$titleFrom} đến ngày {$titleTo})");
        $worksheet->getStyle('A5')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $worksheet->getStyle('A5')->getFont()
            ->setName('Times New Roman')
            ->setSize(14)
            ->setBold(true)
            ->getColor()
            ->setARGB('FF000000');
        $worksheet->getRowDimension(5)->setRowHeight(34);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function fillDataRows(Worksheet $worksheet, array $rows, array $templateInputs): int
    {
        $rowCount = max(1, count($rows));
        if ($rowCount > 1) {
            $worksheet->insertNewRowBefore(self::DATA_START_ROW + 1, $rowCount - 1);
        }

        for ($index = 0; $index < $rowCount; $index++) {
            $targetRow = self::DATA_START_ROW + $index;
            if ($index > 0) {
                $this->duplicateRowStyle($worksheet, self::DATA_START_ROW, $targetRow);
            }

            $row = $rows[$index] ?? null;
            $worksheet->setCellValue("A{$targetRow}", $row ? $index + 1 : '');

            foreach (self::COLUMN_MAP as $column => $field) {
                $worksheet->setCellValue("{$column}{$targetRow}", $row ? $this->templateValue($row, $field, $templateInputs) : '');
            }
        }

        return self::DATA_START_ROW + $rowCount - 1;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function applySummaryAndFooter(Worksheet $worksheet, array $rows, int $lastDataRow, string $toDisplay, string $officerFullName, array $templateInputs): void
    {
        $summaryRow = $lastDataRow + self::SUMMARY_OFFSET_AFTER_DATA;
        $giftCount = collect($rows)->filter(fn (array $row) => trim((string) ($row['gift'] ?? '')) !== '' && trim((string) ($row['gift'] ?? '')) !== '---')->count();
        $to = $this->parseDisplayDate($toDisplay);
        $month = $this->input($templateInputs, 'summaryMonth', date('m'));
        $giftMonth = $this->input($templateInputs, 'giftMonth', $this->previousMonth());
        $praiseCount = (string) count($rows);
        $letterCount = (string) count($rows);
        $inputGiftCount = (string) $giftCount;
        $letterRemain = $this->input($templateInputs, 'letterRemain', '');
        $giftRemain = $this->input($templateInputs, 'giftRemain', '');

        $worksheet->setCellValue("A{$summaryRow}", $this->richText([
            ['Kết luận:', false, true],
            ["\n- Số lượng tuyên dương trong tháng {$month}: ", false],
            [$praiseCount, false],
            ["\n- Số lượng trao thư tri ân: ", false],
            [$letterCount, false],
            ["\n- Số lượng trao quà trong tháng {$giftMonth}: ", false],
            [$inputGiftCount, false],
            ["\n- Số lượng thư tri ân tồn: ", false],
            [$letterRemain, false],
            ["\n- Số lượng quà tồn: ", false],
            [$giftRemain, false],
        ]));
        $worksheet->getStyle("A{$summaryRow}")->getAlignment()->setWrapText(true);

        $footerDateRow = $summaryRow + 2;
        $signerTitleRow = $summaryRow + 3;
        $signerNameRow = $summaryRow + 6;
        $noteRow = $summaryRow + 6;
        $date = new \DateTimeImmutable;

        $worksheet->setCellValue("J{$footerDateRow}", sprintf('Ngày %s tháng %s năm %s', $date->format('d'), $date->format('m'), $date->format('Y')));
        $worksheet->setCellValue("J{$signerTitleRow}", 'Người lập bảng');
        $worksheet->setCellValue("J{$signerNameRow}", $officerFullName !== '' ? $officerFullName : 'Họ và tên');
        $worksheet->setCellValue("A{$noteRow}", 'Ghi chú: Biểu mẫu được cập nhật bắt đầu từ chu kỳ báo cáo tháng: 05/2025');

        $worksheet->getStyle("A".self::DATA_START_ROW.':L'.$lastDataRow)
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function templateValue(array $row, string $field, array $templateInputs): string|int
    {
        return match ($field) {
            'appreciationCampus' => $this->input($templateInputs, 'campus', $this->cleanCellValue($row[$field] ?? '')),
            'handoverCount' => 1,
            'giftCount' => $this->hasText($row['gift'] ?? null) ? 1 : '',
            'contactLog' => '',
            'templateNote' => $this->joinParts([$row['gift'] ?? null, $row['note'] ?? null]),
            default => $this->cleanCellValue($row[$field] ?? ''),
        };
    }

    /**
     * @param  list<mixed>  $parts
     */
    private function joinParts(array $parts): string
    {
        $values = array_values(array_filter(array_map(
            fn (mixed $value) => $this->cleanCellValue($value),
            $parts,
        ), fn (string $value) => $value !== ''));

        return implode(' - ', array_unique($values));
    }

    private function cleanCellValue(mixed $value): string
    {
        $text = trim((string) $value);

        return $text === '---' ? '' : $text;
    }

    private function hasText(mixed $value): bool
    {
        return $this->cleanCellValue($value) !== '';
    }

    private function input(array $inputs, string $key, string $default): string
    {
        $value = trim((string) ($inputs[$key] ?? ''));

        return $value === '' ? $default : $value;
    }

    private function previousMonth(): string
    {
        return (new \DateTimeImmutable('first day of previous month'))->format('m');
    }

    /**
     * @param  list<array{0: string, 1?: bool, 2?: bool}>  $parts
     */
    private function richText(array $parts): RichText
    {
        $richText = new RichText;

        foreach ($parts as $part) {
            $text = $part[0] ?? '';
            $red = $part[1] ?? false;
            $bold = $part[2] ?? false;

            if ($text === '') {
                continue;
            }

            $run = $richText->createTextRun($text);
            $run->getFont()->setName('Times New Roman')->setSize(12);
            if ($red ?? false) {
                $run->getFont()->getColor()->setARGB('FFFF0000');
            }
            if ($bold ?? false) {
                $run->getFont()->setBold(true);
            }
        }

        return $richText;
    }

    private function parseDisplayDate(string $value): ?\DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('d/m/Y', trim($value));

        return $date instanceof \DateTimeImmutable ? $date : null;
    }

    private function duplicateRowStyle(Worksheet $worksheet, int $sourceRow, int $targetRow): void
    {
        $lastCol = Coordinate::stringFromColumnIndex(self::LAST_COL);
        $worksheet->duplicateStyle($worksheet->getStyle("A{$sourceRow}:{$lastCol}{$sourceRow}"), "A{$targetRow}:{$lastCol}{$targetRow}");
        $worksheet->getRowDimension($targetRow)->setRowHeight($worksheet->getRowDimension($sourceRow)->getRowHeight());
    }

    private function sanitizeDownloadFilename(string $filename): string
    {
        $filename = trim(str_replace(['\\', '/'], '-', $filename));
        $filename = preg_replace('/[<>:"|?*]/', '-', $filename) ?? $filename;
        $filename = preg_replace('/-+/', '-', $filename) ?? $filename;

        if ($filename === '' || $filename === '.xlsx') {
            return 'TriAnNguoiViecTot.xlsx';
        }

        if (! str_ends_with(strtolower($filename), '.xlsx')) {
            $filename .= '.xlsx';
        }

        return $filename;
    }
}
