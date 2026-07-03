<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Drawing;
use PhpOffice\PhpSpreadsheet\Shared\Font as SharedFont;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentViolationReportExportService
{
    private const DATA_START_ROW = 9;

    private const TABLE_HEADER_ROW = 8;

    private const DATE_ROW = 6;

    private const COL_COUNT = 8;

    private const LAST_COL = 11;

    private const FONT_NAME = 'Times New Roman';

    private const FONT_SIZE = 12;

    /** @var list<string> */
    private const ORG_LINES = [
        'PHÒNG KIỂM TRA NỘI BỘ',
        'TRƯỜNG ĐẠI HỌC NGUYỄN TẤT THÀNH',
    ];

    /** @var list<string> */
    private const QUOC_HIEU_LINES = [
        'CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM',
        'Độc lập - Tự do - Hạnh phúc',
    ];

    /** @var list<string> */
    private const EXPORT_COLS = [
        'fullName',
        'class',
        'studentId',
        'violationDate',
        'violationType',
        'officer',
        'note',
    ];

    /** @var list<string> */
    private const CENTER_ALIGNED_COLS = [
        'studentId',
        'violationDate',
    ];

    private const DATA_FONT_SIZE = 12;

    private const AUTO_ROW_HEIGHT_PADDING = 4.0;

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function download(
        string $fromDisplay,
        string $toDisplay,
        array $rows,
        string $filename,
        string $officerFullName = '',
    ): StreamedResponse {
        $filename = $this->sanitizeDownloadFilename($filename);
        $templatePath = $this->resolveTemplatePath();

        $previousLimit = ini_get('memory_limit');
        ini_set('memory_limit', '256M');

        try {
            $spreadsheet = $this->loadTemplateSpreadsheet($templatePath);
            $worksheet = $spreadsheet->getSheetByName('Sinh Viên Vi Phạm');

            if (! $worksheet instanceof Worksheet) {
                abort(500, 'Không tìm thấy sheet mẫu "Sinh Viên Vi Phạm".');
            }

            $worksheet->setAutoFilter('');
            $this->applyOfficialHeader($worksheet);
            $this->setDateRangeHeader($worksheet, $fromDisplay, $toDisplay);
            $this->clearDateRowBorders($worksheet);
            $lastDataRow = $this->fillDataRows($worksheet, $rows);
            $footerStartRow = $lastDataRow + 2;
            $this->appendSignatureFooter($worksheet, $footerStartRow, $toDisplay, $officerFullName);

            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->setPreCalculateFormulas(false);
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } finally {
            if ($previousLimit !== false) {
                ini_set('memory_limit', (string) $previousLimit);
            }
        }
    }

    private function resolveTemplatePath(): string
    {
        $candidates = [
            public_path('templates/vipham_template.xlsx'),
            dirname(base_path()).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'vipham_template.xlsx',
        ];

        foreach ($candidates as $path) {
            if (is_file($path) && filesize($path) > 0) {
                return $path;
            }
        }

        abort(500, 'Không tìm thấy tệp mẫu Excel vipham_template.xlsx.');
    }

    private function loadTemplateSpreadsheet(string $templatePath): Spreadsheet
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setIncludeCharts(false);
        $reader->setReadDataOnly(false);
        $reader->setReadEmptyCells(false);
        $reader->setLoadSheetsOnly(['Sinh Viên Vi Phạm']);

        return $reader->load($templatePath);
    }

    private function applyOfficialHeader(Worksheet $worksheet): void
    {
        foreach (self::ORG_LINES as $index => $line) {
            $row = $index + 1;
            $this->setMergedHeaderLine(
                $worksheet,
                $row,
                $line,
                1,
                4,
                Alignment::HORIZONTAL_CENTER,
                true,
                false,
            );
        }

        foreach (self::QUOC_HIEU_LINES as $index => $line) {
            $row = $index + 1;
            $this->setMergedHeaderLine(
                $worksheet,
                $row,
                $line,
                6,
                self::LAST_COL,
                Alignment::HORIZONTAL_CENTER,
                true,
                false,
            );
        }

        $worksheet->getStyle('F2:K2')->applyFromArray([
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
    }

    private function setDateRangeHeader(Worksheet $worksheet, string $fromDisplay, string $toDisplay): void
    {
        $text = $fromDisplay === $toDisplay
            ? "Ngày {$fromDisplay}"
            : "Từ ngày {$fromDisplay} đến ngày {$toDisplay}";

        try {
            $worksheet->mergeCells('A'.self::DATE_ROW.':E'.self::DATE_ROW);
        } catch (\Throwable) {
            // Template already merged A6:E6.
        }

        $cell = $worksheet->getCell('A'.self::DATE_ROW);
        $cell->setValue($text);
        $cell->getStyle()->applyFromArray([
            'font' => [
                'name' => self::FONT_NAME,
                'size' => self::FONT_SIZE,
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_NONE,
                ],
            ],
        ]);
    }

    private function clearDateRowBorders(Worksheet $worksheet): void
    {
        $lastColLetter = Coordinate::stringFromColumnIndex(self::LAST_COL);
        $row = self::DATE_ROW;

        $worksheet->getStyle("A{$row}:{$lastColLetter}{$row}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_NONE,
                ],
            ],
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function fillDataRows(Worksheet $worksheet, array $rows): int
    {
        if ($rows === []) {
            return self::DATA_START_ROW;
        }

        $lastColLetter = Coordinate::stringFromColumnIndex(self::COL_COUNT);
        $dataStartRow = self::DATA_START_ROW;
        $lastDataRow = $dataStartRow + count($rows) - 1;

        foreach ($rows as $index => $item) {
            $rowIndex = $dataStartRow + $index;

            if ($rowIndex !== $dataStartRow) {
                $this->duplicateRowStyle($worksheet, $dataStartRow, $rowIndex, self::COL_COUNT);
            }

            $worksheet->getCell("A{$rowIndex}")->setValue($index + 1);

            foreach (self::EXPORT_COLS as $colOffset => $colKey) {
                $colLetter = Coordinate::stringFromColumnIndex($colOffset + 2);
                $raw = $item[$colKey] ?? '';
                if (is_numeric($raw) && (float) $raw == 0.0) {
                    $raw = '';
                }
                $worksheet->getCell("{$colLetter}{$rowIndex}")->setValue($raw);
            }
        }

        $dataRange = "A{$dataStartRow}:{$lastColLetter}{$lastDataRow}";

        $worksheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $this->applyDataRangeFormatting($worksheet, $dataStartRow, $lastDataRow);
        $this->autoFitDataRowHeights($worksheet, $dataStartRow, $lastDataRow);

        return $lastDataRow;
    }

    private function appendSignatureFooter(
        Worksheet $worksheet,
        int $footerStartRow,
        string $displayDate,
        string $officerFullName,
    ): void {
        $day = (int) date('j');
        $month = (int) date('n');
        $year = (int) date('Y');

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $displayDate, $parts)) {
            $day = (int) $parts[1];
            $month = (int) $parts[2];
            $year = (int) $parts[3];
        }

        $mergeStartCol = 6;
        $mergeEndCol = self::LAST_COL;

        $this->setFooterLine(
            $worksheet,
            $footerStartRow,
            "Hồ Chí Minh, ngày {$day} tháng {$month} năm {$year}",
            false,
            true,
            $mergeStartCol,
            $mergeEndCol,
        );
        $this->setFooterLine($worksheet, $footerStartRow + 1, 'Phần ký ban hành', true, false, $mergeStartCol, $mergeEndCol);
        $this->setFooterLine($worksheet, $footerStartRow + 2, '(chữ ký)', false, true, $mergeStartCol, $mergeEndCol);
        $this->setFooterLine(
            $worksheet,
            $footerStartRow + 6,
            $officerFullName !== '' ? $officerFullName : 'Người ban hành',
            true,
            false,
            $mergeStartCol,
            $mergeEndCol,
        );
    }

    private function setMergedHeaderLine(
        Worksheet $worksheet,
        int $row,
        string $text,
        int $mergeStartCol,
        int $mergeEndCol,
        string $horizontal,
        bool $bold,
        bool $italic,
    ): void {
        $start = Coordinate::stringFromColumnIndex($mergeStartCol);
        $end = Coordinate::stringFromColumnIndex($mergeEndCol);

        try {
            $worksheet->mergeCells("{$start}{$row}:{$end}{$row}");
        } catch (\Throwable) {
            // Ignore merge conflicts on reused template rows.
        }

        $cell = $worksheet->getCell("{$start}{$row}");
        $cell->setValue($text);
        $cell->getStyle()->applyFromArray([
            'font' => [
                'name' => self::FONT_NAME,
                'size' => self::FONT_SIZE,
                'bold' => $bold,
                'italic' => $italic,
            ],
            'alignment' => [
                'horizontal' => $horizontal,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private function setFooterLine(
        Worksheet $worksheet,
        int $row,
        string $text,
        bool $bold,
        bool $italic,
        int $mergeStartCol,
        int $mergeEndCol,
    ): void {
        $start = Coordinate::stringFromColumnIndex($mergeStartCol);
        $end = Coordinate::stringFromColumnIndex($mergeEndCol);

        try {
            $worksheet->mergeCells("{$start}{$row}:{$end}{$row}");
        } catch (\Throwable) {
            // Ignore merge conflicts on reused template rows.
        }

        $cell = $worksheet->getCell("{$start}{$row}");
        $cell->setValue($text);
        $cell->getStyle()->applyFromArray([
            'font' => [
                'name' => self::FONT_NAME,
                'size' => self::FONT_SIZE,
                'bold' => $bold,
                'italic' => $italic,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private function applyDataRangeFormatting(Worksheet $worksheet, int $dataStartRow, int $lastDataRow): void
    {
        $lastColLetter = Coordinate::stringFromColumnIndex(self::COL_COUNT);
        $dataRange = "A{$dataStartRow}:{$lastColLetter}{$lastDataRow}";
        $templateFont = $worksheet->getStyle("B{$dataStartRow}")->getFont();
        $fontName = $templateFont->getName() ?: self::FONT_NAME;

        $worksheet->getStyle($dataRange)->applyFromArray([
            'font' => [
                'name' => $fontName,
                'size' => self::DATA_FONT_SIZE,
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $worksheet->getStyle("A{$dataStartRow}:A{$lastDataRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (self::CENTER_ALIGNED_COLS as $colKey) {
            $offset = array_search($colKey, self::EXPORT_COLS, true);
            if ($offset === false) {
                continue;
            }

            $colLetter = Coordinate::stringFromColumnIndex($offset + 2);
            $worksheet->getStyle("{$colLetter}{$dataStartRow}:{$colLetter}{$lastDataRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }

    private function autoFitDataRowHeights(Worksheet $worksheet, int $dataStartRow, int $lastDataRow): void
    {
        $defaultFont = $worksheet->getParent()?->getDefaultStyle()->getFont();
        $minRowHeight = $defaultFont
            ? SharedFont::getDefaultRowHeightByFont($defaultFont)
            : 15.0;

        for ($row = $dataStartRow; $row <= $lastDataRow; $row++) {
            $rowHeight = $minRowHeight;

            for ($col = 1; $col <= self::COL_COUNT; $col++) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $cell = $worksheet->getCell("{$colLetter}{$row}");
                $text = trim((string) $cell->getFormattedValue());

                if ($text === '') {
                    continue;
                }

                $style = $cell->getStyle();
                if (! $style->getAlignment()->getWrapText()) {
                    continue;
                }

                $font = $style->getFont();
                $lineHeight = SharedFont::getDefaultRowHeightByFont($font);
                $colWidth = $worksheet->getColumnDimension($colLetter)->getWidth();

                if ($colWidth < 0) {
                    $colWidth = SharedFont::getDefaultColumnWidthByFont($defaultFont ?? $font);
                }

                $colWidthPx = max(1, Drawing::cellDimensionToPixels($colWidth, $font) - 6);
                $requiredHeight = $this->estimateWrappedTextHeight($text, $font, $colWidthPx, $lineHeight);
                $rowHeight = max($rowHeight, $requiredHeight);
            }

            $rowDimension = $worksheet->getRowDimension($row);
            $rowDimension->setRowHeight($rowHeight);
            $rowDimension->setCustomFormat(true, $rowHeight);
        }
    }

    private function estimateWrappedTextHeight(
        string $text,
        \PhpOffice\PhpSpreadsheet\Style\Font $font,
        int $colWidthPx,
        float $lineHeight,
    ): float {
        $paragraphs = preg_split("/\R/u", $text) ?: [$text];
        $totalLines = 0;

        foreach ($paragraphs as $paragraph) {
            if ($paragraph === '') {
                $totalLines++;

                continue;
            }

            $textWidthPx = SharedFont::getTextWidthPixelsApprox($paragraph, $font, 0);
            $totalLines += max(1, (int) ceil($textWidthPx / $colWidthPx));
        }

        return ($totalLines * $lineHeight) + self::AUTO_ROW_HEIGHT_PADDING;
    }

    private function duplicateRowStyle(Worksheet $worksheet, int $templateRow, int $targetRow, int $colCount): void
    {
        for ($col = 1; $col <= $colCount; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $worksheet->duplicateStyle(
                $worksheet->getStyle("{$colLetter}{$templateRow}"),
                "{$colLetter}{$targetRow}",
            );
        }
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
