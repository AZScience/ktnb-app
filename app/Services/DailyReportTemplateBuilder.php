<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DailyReportTemplateBuilder
{
    private const FONT_NAME = 'Times New Roman';

    private const HEADER_FONT_SIZE = 12;

    private const DATA_FONT_SIZE = 12;

    /** @var array<string, string> */
    private const EXPORT_LABELS = [
        'employee' => 'Nhân viên',
        'date' => 'Ngày',
        'room' => 'Phòng',
        'period' => 'Tiết',
        'department' => 'Khoa',
        'class' => 'Lớp',
        'lecturer' => 'Giảng viên',
        'content' => 'Nội dung',
        'attending_students' => 'SV tham dự',
        'student_count' => 'Sĩ số',
        'proctor1' => 'CBCT 1',
        'proctor2' => 'CBCT 2',
        'proctor3' => 'CBCT 3',
        'incident' => 'Việc phát sinh',
        'is_notification' => 'Thông báo',
        'incident_detail' => 'Chi tiết sự việc',
        'type' => 'LT/TH',
        'officer' => 'Nhân sự ghi nhận',
        'violation_date' => 'Ngày ghi nhận',
        'building' => 'Cơ sở',
    ];

    public function __construct(
        private DailyReportService $daily,
        private StudentViolationCrosstabService $violationCrosstab,
    ) {}

    public function defaultPath(): string
    {
        return $this->resolveWritablePath(public_path('templates/daily_report_template.xlsx'));
    }

    public function writeTo(?string $path = null): string
    {
        $path = $this->resolveWritablePath($path ?? $this->defaultPath());
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $spreadsheet = $this->build();
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    public function ensureAt(?string $path = null): string
    {
        $path = $this->resolveWritablePath($path ?? $this->defaultPath());

        if (! $this->isUsableTemplate($path)) {
            return $this->writeTo($path);
        }

        return $path;
    }

    private function isUsableTemplate(string $path): bool
    {
        if (! is_file($path) || ! is_readable($path)) {
            return false;
        }

        $size = filesize($path);

        return $size !== false && $size > 0 && $size <= 10_000_000;
    }

    private function resolveWritablePath(string $preferredPath): string
    {
        $directory = dirname($preferredPath);
        if (is_dir($directory) && is_writable($directory) && ! $this->hasBlockingTemplate($preferredPath)) {
            return $preferredPath;
        }

        return storage_path('app/templates/'.basename($preferredPath));
    }

    private function hasBlockingTemplate(string $path): bool
    {
        if (! is_file($path)) {
            return false;
        }

        return ! is_writable($path) || ! $this->isUsableTemplate($path);
    }

    public function build(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        foreach ($this->daily->tabDefinitions() as $tabKey => $definition) {
            $sheetName = (string) ($definition['exportKey'] ?? strtoupper($tabKey));
            $sheet = new Worksheet($spreadsheet, $sheetName);
            $spreadsheet->addSheet($sheet);

            if ($tabKey === 'violations') {
                $this->buildViolationSheet($sheet, $definition);
            } else {
                $this->buildStandardSheet(
                    $sheet,
                    (string) ($definition['title'] ?? $definition['label'] ?? $sheetName),
                    $definition['exportCols'] ?? [],
                    $definition['columns'] ?? [],
                );
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @param  list<string>  $exportCols
     * @param  list<array{key?: string, label?: string}>  $tabColumns
     */
    private function buildStandardSheet(Worksheet $sheet, string $title, array $exportCols, array $tabColumns = []): void
    {
        $headerRow = 6;
        $dataRow = 7;
        $colCount = count($exportCols) + 1;
        $lastColLetter = Coordinate::stringFromColumnIndex($colCount);

        $this->applySheetBanner($sheet, $title, $lastColLetter);
        $sheet->setCellValue('A5', 'Ngày báo cáo:');

        $sheet->setCellValue("A{$headerRow}", 'STT');
        foreach ($exportCols as $offset => $colKey) {
            $label = $this->labelForExportColumn($colKey, $exportCols, $tabColumns);
            $sheet->setCellValue(
                Coordinate::stringFromColumnIndex($offset + 2).$headerRow,
                $label,
            );
        }

        $sheet->setCellValue("A{$dataRow}", 1);
        foreach ($exportCols as $offset => $colKey) {
            $coordinate = Coordinate::stringFromColumnIndex($offset + 2).$dataRow;
            if ($colKey === 'is_notification') {
                $sheet->getCell($coordinate)->setValueExplicit('☐', DataType::TYPE_STRING);
            } else {
                $sheet->setCellValue($coordinate, '');
            }
        }

        $this->styleHeaderRow($sheet, $headerRow, $colCount);
        $this->styleDataTemplateRow($sheet, $dataRow, $colCount, $exportCols);
        $this->setColumnWidths($sheet, $exportCols);
    }

    /** @param array<string, mixed> $definition */
    private function buildViolationSheet(Worksheet $sheet, array $definition): void
    {
        $exportCols = $definition['exportSpreadsheetCols'] ?? $definition['exportCols'] ?? [];
        $fixedCount = count($this->violationCrosstab->fixedColumns());
        $headerRow = 6;
        $subHeaderRow = 7;
        $dataRow = 8;
        $colCount = count($exportCols) + 1;
        $lastColLetter = Coordinate::stringFromColumnIndex($colCount);
        $groupLabel = (string) ($definition['crosstab']['violationGroupLabel'] ?? 'NỘI DUNG GHI NHẬN VI PHẠM SINH VIÊN');

        $this->applySheetBanner($sheet, (string) ($definition['title'] ?? 'BÁO CÁO GHI NHẬN SINH VIÊN VI PHẠM'), $lastColLetter);
        $sheet->setCellValue('A5', 'Ngày báo cáo:');

        $sheet->setCellValue("A{$headerRow}", 'STT');
        foreach ($exportCols as $offset => $colKey) {
            if ($offset < $fixedCount) {
                $sheet->setCellValue(
                    Coordinate::stringFromColumnIndex($offset + 2).$headerRow,
                    $this->labelForExportColumn($colKey, $exportCols),
                );
            }
        }

        if ($fixedCount < count($exportCols)) {
            $groupStart = Coordinate::stringFromColumnIndex($fixedCount + 2);
            $sheet->setCellValue("{$groupStart}{$headerRow}", $groupLabel);
            try {
                $sheet->mergeCells("{$groupStart}{$headerRow}:{$lastColLetter}{$headerRow}");
            } catch (\Throwable) {
                // ignore merge conflicts
            }
        }

        foreach ($exportCols as $offset => $colKey) {
            if ($offset >= $fixedCount) {
                $sheet->setCellValue(
                    Coordinate::stringFromColumnIndex($offset + 2).$subHeaderRow,
                    $this->labelForExportColumn($colKey, $exportCols),
                );
            }
        }

        $sheet->setCellValue("A{$dataRow}", 1);
        foreach ($exportCols as $offset => $colKey) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($offset + 2).$dataRow, '');
        }

        $this->styleHeaderRow($sheet, $headerRow, $colCount);
        $this->styleHeaderRow($sheet, $subHeaderRow, $colCount);
        $this->styleDataTemplateRow($sheet, $dataRow, $colCount, $exportCols);
        $this->setColumnWidths($sheet, $exportCols);
    }

    private function applySheetBanner(Worksheet $sheet, string $title, string $lastColLetter): void
    {
        $sheet->setCellValue('A1', 'PHÒNG KIỂM TRA NỘI BỘ - ĐH NGUYỄN TẤT THÀNH');
        $sheet->setCellValue('A3', mb_strtoupper($title));

        foreach ([1, 3] as $row) {
            try {
                $sheet->mergeCells("A{$row}:{$lastColLetter}{$row}");
            } catch (\Throwable) {
                // ignore merge conflicts
            }

            $sheet->getStyle("A{$row}:{$lastColLetter}{$row}")->applyFromArray([
                'font' => [
                    'name' => self::FONT_NAME,
                    'size' => self::HEADER_FONT_SIZE,
                    'bold' => true,
                    'italic' => false,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        $sheet->getStyle('A5')->applyFromArray([
            'font' => [
                'name' => self::FONT_NAME,
                'size' => self::HEADER_FONT_SIZE,
                'bold' => true,
                'italic' => false,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private function styleHeaderRow(Worksheet $sheet, int $headerRow, int $colCount): void
    {
        $lastColLetter = Coordinate::stringFromColumnIndex($colCount);
        $range = "A{$headerRow}:{$lastColLetter}{$headerRow}";

        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'name' => self::FONT_NAME,
                'size' => self::HEADER_FONT_SIZE,
                'bold' => true,
                'italic' => false,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F3F4F6'],
            ],
        ]);
    }

    /**
     * @param  list<string>  $exportCols
     */
    private function styleDataTemplateRow(Worksheet $sheet, int $dataRow, int $colCount, array $exportCols): void
    {
        $lastColLetter = Coordinate::stringFromColumnIndex($colCount);
        $range = "A{$dataRow}:{$lastColLetter}{$dataRow}";

        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'name' => self::FONT_NAME,
                'size' => self::DATA_FONT_SIZE,
                'bold' => false,
                'italic' => false,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $sheet->getStyle("A{$dataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (['attending_students', 'student_count', 'is_notification'] as $centerCol) {
            $letter = $this->resolveColumnLetter($exportCols, $centerCol);
            if ($letter !== null) {
                $sheet->getStyle("{$letter}{$dataRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        $notificationLetter = $this->resolveColumnLetter($exportCols, 'is_notification');
        if ($notificationLetter !== null) {
            $cell = $sheet->getCell("{$notificationLetter}{$dataRow}");
            $cell->getStyle()->getFont()->setBold(false)->setItalic(false);
            $cell->getStyle()->getFill()->setFillType(Fill::FILL_NONE);
        }

        $sheet->getRowDimension($dataRow)->setRowHeight(-1);
    }

    /**
     * @param  list<string>  $exportCols
     */
    private function setColumnWidths(Worksheet $sheet, array $exportCols): void
    {
        $widths = [
            'employee' => 16,
            'date' => 12,
            'room' => 10,
            'period' => 8,
            'department' => 14,
            'class' => 12,
            'lecturer' => 18,
            'content' => 22,
            'attending_students' => 10,
            'student_count' => 8,
            'proctor1' => 14,
            'proctor2' => 14,
            'proctor3' => 14,
            'incident' => 16,
            'is_notification' => 11,
            'incident_detail' => 24,
            'type' => 8,
            'officer' => 16,
            'violation_date' => 12,
            'building' => 12,
        ];

        $sheet->getColumnDimension('A')->setWidth(5);
        foreach ($exportCols as $offset => $colKey) {
            $letter = Coordinate::stringFromColumnIndex($offset + 2);
            $sheet->getColumnDimension($letter)->setWidth($widths[$colKey] ?? 12);
        }
    }

    /**
     * @param  list<string>  $exportCols
     * @param  list<array{key?: string, label?: string}>  $tabColumns
     */
    private function labelForExportColumn(string $colKey, array $exportCols, array $tabColumns = []): string
    {
        if ($colKey === '__gap__') {
            return '';
        }

        // Ưu tiên nhãn cột của tab hiện tại (vd. homeroom: attending_students → "SV dự").
        foreach ($tabColumns as $column) {
            if (($column['key'] ?? null) === $colKey && isset($column['label'])) {
                return (string) $column['label'];
            }
        }

        if (isset(self::EXPORT_LABELS[$colKey])) {
            return self::EXPORT_LABELS[$colKey];
        }

        foreach ($this->violationCrosstab->violationTypeColumns() as $column) {
            if ($column['key'] === $colKey) {
                return $column['label'];
            }
        }

        foreach ($this->daily->tabDefinitions() as $definition) {
            foreach ($definition['columns'] ?? [] as $column) {
                if (($column['key'] ?? null) === $colKey) {
                    return (string) ($column['label'] ?? $colKey);
                }
            }
        }

        return $colKey;
    }

    /**
     * @param  list<string>  $exportCols
     */
    private function resolveColumnLetter(array $exportCols, string $colKey): ?string
    {
        $offset = array_search($colKey, $exportCols, true);

        return $offset === false
            ? null
            : Coordinate::stringFromColumnIndex($offset + 2);
    }
}
