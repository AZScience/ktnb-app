<?php

namespace App\Services;

use App\Models\DailySchedule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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

class ScheduleExcelService
{
    public function __construct(
        private ScheduleLocationService $locations,
    ) {}

    private const IMPORT_HEADER_ROW = 8;

    private const ROOM_CHECKLIST_HEADER_ROW = 8;

    private const ROOM_CHECKLIST_DATA_START_ROW = 9;

    private const ROOM_CHECKLIST_LAST_COL = 'K';

    private const ROOM_CHECKLIST_FONT_NAME = 'Times New Roman';

    private const ROOM_CHECKLIST_FONT_SIZE = 11;

    private const ROOM_CHECKLIST_AUTO_ROW_HEIGHT_PADDING = 4.0;

    private const COLUMN_MAP = [
        'Ngày' => 'date',
        'Dãy nhà' => 'building',
        'Phòng' => 'room',
        'Tiết' => 'period',
        'LT/TH' => 'type',
        'Sĩ số' => 'student_count',
        'Lớp' => 'class',
        'Giảng viên' => 'lecturer',
        'Nội dung' => 'content',
        'Trạng thái' => 'status',
        'Khoa sử dụng' => 'department',
        'Ghi chú' => 'note',
    ];

    /** @var array<string, list<string>> */
    private const HEADER_ALIASES = [
        'Ngày' => ['Ngày'],
        'Dãy nhà' => ['Dãy nhà'],
        'Phòng' => ['Phòng'],
        'Tiết' => ['Tiết'],
        'LT/TH' => ['LT/TH', 'Loại'],
        'Sĩ số' => ['Sĩ số'],
        'Khoa sử dụng' => ['Khoa sử dụng', 'Khoa'],
        'Lớp' => ['Lớp'],
        'Giảng viên' => ['Giảng viên'],
        'CBCT 01' => ['CBCT 01', 'CBCT1', 'CBCT 1'],
        'CBCT 02' => ['CBCT 02', 'CBCT2', 'CBCT 2'],
        'CBCT 03' => ['CBCT 03', 'CBCT3', 'CBCT 3'],
        'Nội dung' => ['Nội dung'],
        'Trạng thái' => ['Trạng thái'],
        'Ghi chú' => ['Ghi chú'],
    ];

    /**
     * @return array{rawCount: int, preview: array<int, array<string, mixed>>, merged: array<int, array<string, mixed>>}
     */
    public function importPreview(UploadedFile $file): array
    {
        $rawRows = $this->readRawImportRows($file);
        $merged = $this->mergeImportRows($rawRows);

        return [
            'rawCount' => count($rawRows),
            'preview' => $merged,
            'merged' => $merged,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $merged
     * @return array<int, array<string, mixed>>
     */
    public function filterNewImportItems(array $merged): array
    {
        if ($merged === []) {
            return [];
        }

        $dates = array_values(array_unique(array_filter(array_map(
            fn (array $item) => trim((string) ($item['date'] ?? '')),
            $merged,
        ))));

        $query = DailySchedule::query()
            ->select(['date', 'building', 'room', 'period', 'department', 'content', 'class', 'lecturer']);

        if ($dates !== []) {
            $query->whereIn('date', $dates);
        }

        $existingKeys = [];
        $query->cursor()->each(function (DailySchedule $item) use (&$existingKeys) {
            $existingKeys[$this->existingImportKey($item->toArray())] = true;
        });

        return array_values(array_filter($merged, function (array $item) use ($existingKeys) {
            if (trim((string) ($item['date'] ?? '')) === '') {
                return false;
            }

            return ! isset($existingKeys[$this->existingImportKey($item)]);
        }));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public function createImportItems(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $now = now();
        $created = [];
        $this->locations->warmNormalizationCaches();

        foreach (array_chunk($items, 200) as $chunk) {
            $rows = [];

            foreach ($chunk as $item) {
                $payload = $this->normalizeImportPayload($item);
                $id = (string) Str::uuid();
                $rows[] = array_merge($payload, [
                    'id' => $id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $created[] = array_merge(['id' => $id], $payload);
            }

            DailySchedule::query()->insert($rows);
        }

        return $created;
    }

    public function downloadImportTemplate(string $filename = 'Mau_Import_LichHoc.xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('LichGiangDay');

        $sheet->setCellValue('A1', 'PHÒNG KIỂM TRA NỘI BỘ - ĐH NGUYỄN TẤT THÀNH');
        $sheet->setCellValue('A2', 'Mẫu import lịch giảng dạy');
        $sheet->setCellValue('A3', 'Nhập dữ liệu từ dòng 9 trở đi. Tiêu đề cột ở dòng 8.');

        $headers = [
            'Ngày', 'Dãy nhà', 'Phòng', 'Tiết', 'LT/TH', 'Sĩ số', 'Lớp', 'Giảng viên',
            'CBCT 01', 'CBCT 02', 'CBCT 03', 'Nội dung', 'Trạng thái', 'Khoa sử dụng', 'Ghi chú',
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.self::IMPORT_HEADER_ROW, $header);
            $col++;
        }

        $sampleRow = self::IMPORT_HEADER_ROW + 1;
        $samples = [
            date('d/m/Y'), 'A', 'A101', '1→3', 'LT', 45, 'CNTT01', 'Nguyễn Văn A',
            '', '', '', 'Lập trình web', 'Phòng học', 'Công nghệ thông tin', '',
        ];
        $col = 'A';
        foreach ($samples as $value) {
            $sheet->setCellValue($col.$sampleRow, $value);
            $col++;
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(UploadedFile $file, ?string $defaultDate = null): int
    {
        $rawRows = $this->readRawImportRows($file);
        $merged = $this->mergeImportRows($rawRows);

        if ($defaultDate) {
            $merged = array_map(function (array $item) use ($defaultDate) {
                if (trim((string) ($item['date'] ?? '')) === '') {
                    $item['date'] = $defaultDate;
                }

                return $item;
            }, $merged);
        }

        $merged = array_values(array_filter(
            $merged,
            fn (array $item) => trim((string) ($item['date'] ?? '')) !== '',
        ));

        $newItems = $this->filterNewImportItems($merged);

        return count($this->createImportItems($newItems));
    }

    public function export(Collection $items, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('LichGiangDay');

        $sheet->setCellValue('A1', 'PHÒNG KIỂM TRA NỘI BỘ - ĐH NGUYỄN TẤT THÀNH');
        $sheet->setCellValue('A2', 'Danh sách lịch giảng dạy');
        $sheet->setCellValue('A3', 'Xuất lúc: '.date('d/m/Y H:i'));

        $headers = array_keys(self::COLUMN_MAP);
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.self::IMPORT_HEADER_ROW, $header);
            $col++;
        }

        $rowNum = self::IMPORT_HEADER_ROW + 1;
        foreach ($items as $item) {
            $col = 'A';
            foreach (self::COLUMN_MAP as $field) {
                $value = $item->{$field} ?? '';
                if ($field === 'student_count' && $value !== '') {
                    $value = (int) $value;
                }
                $sheet->setCellValue($col.$rowNum, $value);
                $col++;
            }
            $rowNum++;
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, DailySchedule>  $items
     * @param  array{
     *     date?: string,
     *     building_label?: string,
     *     period_session_label?: string,
     *     officer_name?: string,
     *     filename?: string,
     * }  $options
     */
    public function exportRoomChecklist(Collection $items, array $options = []): StreamedResponse
    {
        $templatePath = public_path('templates/lichhoc_template.xlsx');
        if (! is_file($templatePath)) {
            throw new \RuntimeException('Không tìm thấy file mẫu lichhoc_template.xlsx.');
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $metaParts = array_filter([
            isset($options['date']) && $options['date'] !== '' ? 'Ngày: '.$options['date'] : null,
            isset($options['building_label']) && $options['building_label'] !== '' ? 'Dãy nhà: '.$options['building_label'] : null,
            isset($options['period_session_label']) && $options['period_session_label'] !== '' ? 'Ca học: '.$options['period_session_label'] : null,
        ]);
        if ($metaParts !== []) {
            $sheet->setCellValue('A5', implode('    ', $metaParts));
        }

        $headerRow = self::ROOM_CHECKLIST_HEADER_ROW;
        $dataStartRow = self::ROOM_CHECKLIST_DATA_START_ROW;
        $lastCol = self::ROOM_CHECKLIST_LAST_COL;
        $rows = [];
        $stt = 1;

        foreach ($items as $item) {
            $studentCount = $item->student_count;
            $rows[] = [
                $stt++,
                $item->room ?? '',
                $item->period ?? '',
                $item->type ?? '',
                $item->department ?? '',
                $item->class ?? '',
                $studentCount === null || $studentCount === '' ? '' : (int) $studentCount,
                $this->resolveExportLecturer($item),
                $item->content ?? '',
                $item->status ?? '',
                $item->note ?? '',
            ];
        }

        if ($rows !== []) {
            $this->clearRoomChecklistTemplateDataRows($sheet, $dataStartRow, $dataStartRow + max(count($rows), 1) + 12);
            $sheet->fromArray($rows, null, 'A'.$dataStartRow);
        }

        $dataCount = count($rows);
        $lastDataRow = $dataCount > 0 ? $dataStartRow + $dataCount - 1 : $headerRow;
        $this->applyRoomChecklistTableStyle($sheet, $headerRow, $dataStartRow, $lastDataRow, $lastCol, $dataCount);
        if ($dataCount > 0) {
            $this->autoFitRoomChecklistRowHeights(
                $sheet,
                $dataStartRow,
                $lastDataRow,
                Coordinate::columnIndexFromString($lastCol),
            );
        }

        $footerStartRow = $dataStartRow + max($dataCount, 1) + 2;
        $this->appendRoomChecklistSignatureFooter(
            $sheet,
            $footerStartRow,
            (string) ($options['date'] ?? ''),
            (string) ($options['officer_name'] ?? ''),
        );

        $filename = $options['filename'] ?? 'LichHoc_'.date('d-m-Y_H-i').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function applyRoomChecklistTableStyle(
        Worksheet $sheet,
        int $headerRow,
        int $dataStartRow,
        int $lastDataRow,
        string $lastCol,
        int $dataCount,
    ): void {
        $tableRange = "A{$headerRow}:{$lastCol}{$lastDataRow}";

        $sheet->getStyle($tableRange)->applyFromArray([
            'font' => [
                'name' => self::ROOM_CHECKLIST_FONT_NAME,
                'size' => self::ROOM_CHECKLIST_FONT_SIZE,
                'bold' => false,
                'italic' => false,
            ],
            'alignment' => [
                'wrapText' => true,
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font' => [
                'name' => self::ROOM_CHECKLIST_FONT_NAME,
                'size' => self::ROOM_CHECKLIST_FONT_SIZE,
                'bold' => true,
                'italic' => false,
            ],
            'alignment' => [
                'wrapText' => true,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        if ($dataCount === 0) {
            return;
        }

        $dataRange = "A{$dataStartRow}:{$lastCol}{$lastDataRow}";
        $sheet->getStyle($dataRange)->applyFromArray([
            'font' => [
                'name' => self::ROOM_CHECKLIST_FONT_NAME,
                'size' => self::ROOM_CHECKLIST_FONT_SIZE,
                'bold' => false,
                'italic' => false,
            ],
            'alignment' => [
                'wrapText' => true,
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
        ]);

        foreach (['A', 'C', 'D', 'G'] as $column) {
            $sheet->getStyle("{$column}{$dataStartRow}:{$column}{$lastDataRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }
    }

    private function clearRoomChecklistTemplateDataRows(Worksheet $sheet, int $startRow, int $endRow): void
    {
        $lastColIndex = Coordinate::columnIndexFromString(self::ROOM_CHECKLIST_LAST_COL);

        for ($row = $startRow; $row <= $endRow; $row++) {
            for ($col = 1; $col <= $lastColIndex; $col++) {
                $coordinate = Coordinate::stringFromColumnIndex($col).$row;
                $sheet->setCellValue($coordinate, null);
            }

            $rowDimension = $sheet->getRowDimension($row);
            $rowDimension->setRowHeight(-1);
            $rowDimension->setCustomFormat(false);
        }
    }

    private function autoFitRoomChecklistRowHeights(
        Worksheet $sheet,
        int $dataStartRow,
        int $lastDataRow,
        int $colCount,
    ): void {
        $defaultFont = $sheet->getParent()?->getDefaultStyle()->getFont();
        $minRowHeight = $defaultFont
            ? SharedFont::getDefaultRowHeightByFont($defaultFont)
            : 15.0;

        for ($row = $dataStartRow; $row <= $lastDataRow; $row++) {
            $rowHeight = $minRowHeight;

            for ($col = 1; $col <= $colCount; $col++) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $cell = $sheet->getCell("{$colLetter}{$row}");
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
                $colWidth = $sheet->getColumnDimension($colLetter)->getWidth();

                if ($colWidth < 0) {
                    $colWidth = SharedFont::getDefaultColumnWidthByFont($defaultFont ?? $font);
                }

                $colWidthPx = max(1, Drawing::cellDimensionToPixels($colWidth, $font) - 6);
                $requiredHeight = $this->estimateRoomChecklistWrappedTextHeight($text, $font, $colWidthPx, $lineHeight);
                $rowHeight = max($rowHeight, $requiredHeight);
            }

            $rowDimension = $sheet->getRowDimension($row);
            $rowDimension->setRowHeight($rowHeight);
            $rowDimension->setCustomFormat(true, $rowHeight);
        }
    }

    private function estimateRoomChecklistWrappedTextHeight(
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

        return ($totalLines * $lineHeight) + self::ROOM_CHECKLIST_AUTO_ROW_HEIGHT_PADDING;
    }

    private function appendRoomChecklistSignatureFooter(
        Worksheet $sheet,
        int $footerStartRow,
        string $displayDate,
        string $officerName,
    ): void {
        $day = (int) date('j');
        $month = (int) date('n');
        $year = (int) date('Y');

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $displayDate, $parts)) {
            $day = (int) $parts[1];
            $month = (int) $parts[2];
            $year = (int) $parts[3];
        }

        $mergeStart = 'I';
        $mergeEnd = self::ROOM_CHECKLIST_LAST_COL;

        $this->setExportFooterLine(
            $sheet,
            $footerStartRow,
            "Hồ Chí Minh, ngày {$day} tháng {$month} năm {$year}",
            false,
            true,
            $mergeStart,
            $mergeEnd,
        );
        $this->setExportFooterLine($sheet, $footerStartRow + 1, 'Người báo cáo', true, false, $mergeStart, $mergeEnd);
        $this->setExportFooterLine($sheet, $footerStartRow + 2, '(chữ ký)', false, true, $mergeStart, $mergeEnd);
        $this->setExportFooterLine(
            $sheet,
            $footerStartRow + 6,
            $officerName !== '' ? $officerName : 'Người báo cáo',
            true,
            false,
            $mergeStart,
            $mergeEnd,
        );
    }

    private function setExportFooterLine(
        Worksheet $sheet,
        int $row,
        string $text,
        bool $bold,
        bool $italic,
        string $mergeStartCol,
        string $mergeEndCol,
    ): void {
        try {
            $sheet->mergeCells("{$mergeStartCol}{$row}:{$mergeEndCol}{$row}");
        } catch (\Throwable) {
            // Ignore merge conflicts on reused template rows.
        }

        $cell = $sheet->getCell("{$mergeStartCol}{$row}");
        $cell->setValue($text);
        $cell->getStyle()->applyFromArray([
            'font' => [
                'name' => 'Times New Roman',
                'size' => 12,
                'bold' => $bold,
                'italic' => $italic,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private function resolveExportLecturer(DailySchedule $item): string
    {
        if (($item->status ?? '') === 'Phòng thi') {
            return collect([$item->proctor1, $item->proctor2, $item->proctor3])
                ->map(fn ($value) => trim((string) ($value ?? '')))
                ->filter()
                ->implode(', ');
        }

        return trim((string) ($item->lecturer ?? ''));
    }

    /** @return list<array<string, string>> */
    private function readRawImportRows(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if ($path === false) {
            return [];
        }

        $previousLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
            $reader->setIgnoreRowsWithNoCells(true);
            $reader->setReadFilter(new ScheduleImportReadFilter());

            $spreadsheet = $reader->load($path);
            $rows = [];

            $sheet = $spreadsheet->getSheet(0);
            $highestRow = (int) $sheet->getHighestDataRow();
            $headerRow = $this->detectHeaderRow($sheet, $highestRow);

            if ($headerRow <= $highestRow) {
                $headerToColumn = $this->buildHeaderToColumnMap($sheet, $headerRow);
                if ($this->headerMapHasDateColumn($headerToColumn)) {
                    for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
                        $rowData = $this->extractRawImportRow($sheet, $row, $headerToColumn);
                        if ($this->isEmptyRawImportRow($rowData)) {
                            continue;
                        }
                        $rows[] = $rowData;
                    }
                }
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return $rows;
        } finally {
            if ($previousLimit !== false) {
                ini_set('memory_limit', (string) $previousLimit);
            }
        }
    }

    /** @param  array<string, string>  $headerToColumn */
    private function headerMapHasDateColumn(array $headerToColumn): bool
    {
        foreach (self::HEADER_ALIASES['Ngày'] as $alias) {
            if (isset($headerToColumn[$alias])) {
                return true;
            }
        }

        return false;
    }

    private function detectHeaderRow($sheet, int $highestRow): int
    {
        for ($row = 1; $row <= min($highestRow, 20); $row++) {
            for ($colIndex = 1; $colIndex <= 26; $colIndex++) {
                if (strcasecmp($this->readSheetCell($sheet, $colIndex, $row), 'Ngày') === 0) {
                    return $row;
                }
            }
        }

        return self::IMPORT_HEADER_ROW;
    }

    /** @return array<string, string> */
    private function buildHeaderToColumnMap($sheet, int $headerRow): array
    {
        $map = [];
        for ($colIndex = 1; $colIndex <= 30; $colIndex++) {
            $header = $this->readSheetCell($sheet, $colIndex, $headerRow);
            if ($header !== '') {
                $map[$header] = Coordinate::stringFromColumnIndex($colIndex);
            }
        }

        return $map;
    }

    private function readSheetCell($sheet, int $columnIndex, int $row): string
    {
        $coordinate = Coordinate::stringFromColumnIndex($columnIndex).$row;

        return trim((string) $sheet->getCell($coordinate)->getValue());
    }

    /** @param  array<string, string>  $headerToColumn */
    private function extractRawImportRow($sheet, int $row, array $headerToColumn): array
    {
        $get = function (array $aliases) use ($sheet, $row, $headerToColumn): string {
            foreach ($aliases as $alias) {
                if (! isset($headerToColumn[$alias])) {
                    continue;
                }
                $raw = $sheet->getCell($headerToColumn[$alias].$row)->getValue();

                return $this->formatImportCell($raw, in_array('Ngày', $aliases, true));
            }

            return '';
        };

        return [
            'Ngày' => $get(self::HEADER_ALIASES['Ngày']),
            'Dãy nhà' => $get(self::HEADER_ALIASES['Dãy nhà']),
            'Phòng' => $get(self::HEADER_ALIASES['Phòng']),
            'Tiết' => $get(self::HEADER_ALIASES['Tiết']),
            'LT/TH' => $get(self::HEADER_ALIASES['LT/TH']),
            'Sĩ số' => $get(self::HEADER_ALIASES['Sĩ số']),
            'Khoa sử dụng' => $get(self::HEADER_ALIASES['Khoa sử dụng']),
            'Lớp' => $get(self::HEADER_ALIASES['Lớp']),
            'Giảng viên' => $get(self::HEADER_ALIASES['Giảng viên']),
            'CBCT 01' => $get(self::HEADER_ALIASES['CBCT 01']),
            'CBCT 02' => $get(self::HEADER_ALIASES['CBCT 02']),
            'CBCT 03' => $get(self::HEADER_ALIASES['CBCT 03']),
            'Nội dung' => $get(self::HEADER_ALIASES['Nội dung']),
            'Trạng thái' => $get(self::HEADER_ALIASES['Trạng thái']) ?: 'Phòng học',
            'Ghi chú' => $get(self::HEADER_ALIASES['Ghi chú']),
        ];
    }

    private function formatImportCell(mixed $raw, bool $isDate): string
    {
        if ($raw instanceof \DateTimeInterface) {
            return $raw->format('d/m/Y');
        }

        if ($isDate && is_numeric($raw)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $raw)->format('d/m/Y');
            } catch (\Throwable) {
                // fall through
            }
        }

        if (is_scalar($raw)) {
            $value = trim((string) $raw);
            if ($isDate && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
                return sprintf('%s/%s/%s', $m[3], $m[2], $m[1]);
            }

            return $value;
        }

        return '';
    }

    /** @param  array<string, string>  $row */
    private function isEmptyRawImportRow(array $row): bool
    {
        if ($this->isFooterOrSignatureRow($row)) {
            return true;
        }

        return trim($row['Ngày'] ?? '') === ''
            && trim($row['Lớp'] ?? '') === ''
            && trim($row['Nội dung'] ?? '') === '';
    }

    /** @param  array<string, string>  $row */
    private function isFooterOrSignatureRow(array $row): bool
    {
        $text = trim(implode(' ', array_map(
            fn ($value) => trim((string) $value),
            array_filter($row, fn ($value) => trim((string) $value) !== '')
        )));

        if ($text === '') {
            return true;
        }

        if (preg_match('/Hồ Chí Minh,\s*ngày\s+\d{1,2}\s+tháng\s+\d{1,2}\s+năm\s+\d{4}/ui', $text)) {
            return true;
        }

        if (preg_match('/Người lập biểu/ui', $text)) {
            return true;
        }

        if (preg_match('/^Nguyễn\s+Vĩnh\s+Phúc$/ui', $text)) {
            return true;
        }

        return false;
    }

    /** @param  list<array<string, string>>  $rawRows
     * @return list<array<string, mixed>>
     */
    private function buildImportPreviewRows(array $rawRows): array
    {
        $previewMap = [];

        foreach ($rawRows as $row) {
            $item = $this->rawRowToSchedule($row);
            $status = (string) ($item['status'] ?? 'Phòng học');
            $lecturer = (string) ($row['Giảng viên'] ?? '');

            if ($status === 'Phòng thi') {
                $item['lecturer'] = '';
                $item['proctor1'] = $item['proctor1'] ?: $lecturer;
            }

            $baseKey = $this->normalizeImportKey(implode('-', [
                $item['date'], $item['building'], $item['room'], $item['period'], $item['type'],
                $item['department'], $item['class'], (string) ($item['student_count'] ?? ''), $item['content'],
            ]));
            $key = $status === 'Phòng thi' ? $baseKey : $this->normalizeImportKey($baseKey.$lecturer);

            if (isset($previewMap[$key])) {
                $existing = &$previewMap[$key];
                if ($status === 'Phòng thi' && $lecturer) {
                    if (empty($existing['proctor1'])) {
                        $existing['proctor1'] = $lecturer;
                    } elseif (empty($existing['proctor2'])) {
                        $existing['proctor2'] = $lecturer;
                    } elseif (empty($existing['proctor3'])) {
                        $existing['proctor3'] = $lecturer;
                    }
                }
            } else {
                $previewMap[$key] = $item;
            }
        }

        return array_values($previewMap);
    }

    /** @param  list<array<string, string>>  $rawRows
     * @return list<array<string, mixed>>
     */
    private function mergeImportRows(array $rawRows): array
    {
        $globalMergedMap = [];

        foreach ($rawRows as $row) {
            $item = $this->rawRowToSchedule($row);
            $status = (string) ($item['status'] ?? 'Phòng học');
            $lecturer = (string) ($item['lecturer'] ?? '');

            $baseKey = $this->normalizeImportKey(implode('', [
                $item['date'], $item['building'], $item['room'], $item['period'], $item['type'],
                $item['department'], $item['class'], (string) ($item['student_count'] ?? ''), $item['content'],
            ]));
            $key = $status === 'Phòng thi' ? $baseKey : $baseKey.$this->normalizeImportKey($lecturer);

            if (isset($globalMergedMap[$key])) {
                $existing = &$globalMergedMap[$key];
                if ($item['content'] && ! str_contains((string) $existing['content'], (string) $item['content'])) {
                    $existing['content'] = trim(($existing['content'] ?? '').' / '.$item['content'], ' /');
                }

                if ($status === 'Phòng thi') {
                    $nextLec = $lecturer;
                    if ($nextLec && ! in_array($nextLec, [$existing['proctor1'], $existing['proctor2'], $existing['proctor3']], true)) {
                        if (empty($existing['proctor1'])) {
                            $existing['proctor1'] = $nextLec;
                        } elseif (empty($existing['proctor2'])) {
                            $existing['proctor2'] = $nextLec;
                        } elseif (empty($existing['proctor3'])) {
                            $existing['proctor3'] = $nextLec;
                        }
                    }
                    $existing['lecturer'] = '';
                } elseif ($lecturer && ! str_contains((string) $existing['lecturer'], $lecturer)) {
                    $existing['lecturer'] = trim(($existing['lecturer'] ?? '').', '.$lecturer, ', ');
                }
            } elseif ($status === 'Phòng thi') {
                $firstLec = $lecturer;
                $globalMergedMap[$key] = [
                    ...$item,
                    'proctor1' => $firstLec ?: ($item['proctor1'] ?? ''),
                    'proctor2' => $item['proctor2'] ?? '',
                    'proctor3' => $item['proctor3'] ?? '',
                    'lecturer' => '',
                ];
            } else {
                $globalMergedMap[$key] = $item;
            }
        }

        return array_values($globalMergedMap);
    }

    /** @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private function rawRowToSchedule(array $row): array
    {
        $status = (string) ($row['Trạng thái'] ?? 'Phòng học');
        $className = (string) ($row['Lớp'] ?? '');
        $lecturer = (string) ($row['Giảng viên'] ?? '');

        if ($status === 'Phòng tự do' && $className === '' && $lecturer === '') {
            $className = 'Tự do';
            $lecturer = 'Tự do';
        }

        return [
            'date' => (string) ($row['Ngày'] ?? ''),
            'building' => (string) ($row['Dãy nhà'] ?? ''),
            'room' => (string) ($row['Phòng'] ?? ''),
            'period' => (string) ($row['Tiết'] ?? ''),
            'type' => (string) ($row['LT/TH'] ?? ''),
            'student_count' => ($row['Sĩ số'] ?? '') === '' ? null : (int) $row['Sĩ số'],
            'department' => (string) ($row['Khoa sử dụng'] ?? ''),
            'class' => $className,
            'lecturer' => $lecturer,
            'proctor1' => (string) ($row['CBCT 01'] ?? ''),
            'proctor2' => (string) ($row['CBCT 02'] ?? ''),
            'proctor3' => (string) ($row['CBCT 03'] ?? ''),
            'content' => (string) ($row['Nội dung'] ?? ''),
            'status' => $status,
            'note' => (string) ($row['Ghi chú'] ?? ''),
        ];
    }

    /** @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function normalizeImportPayload(array $item): array
    {
        $pair = $this->locations->normalizePair(
            isset($item['building']) ? (string) $item['building'] : null,
            isset($item['room']) ? (string) $item['room'] : null,
        );

        return [
            'date' => trim((string) ($item['date'] ?? '')),
            'building' => $pair['building'],
            'room' => $pair['room'],
            'period' => $this->nullableString($item['period'] ?? null),
            'type' => $this->nullableString($item['type'] ?? null),
            'department' => $this->nullableString($item['department'] ?? null),
            'class' => $this->nullableString($item['class'] ?? null),
            'student_count' => isset($item['student_count']) && $item['student_count'] !== '' ? (int) $item['student_count'] : null,
            'lecturer' => $this->nullableString($item['lecturer'] ?? null),
            'proctor1' => $this->nullableString($item['proctor1'] ?? null),
            'proctor2' => $this->nullableString($item['proctor2'] ?? null),
            'proctor3' => $this->nullableString($item['proctor3'] ?? null),
            'content' => $this->nullableString($item['content'] ?? null),
            'status' => $this->nullableString($item['status'] ?? null) ?: 'Phòng học',
            'note' => $this->nullableString($item['note'] ?? null),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    /** @param array<string, mixed> $payload */
    private function scheduleExists(array $payload): bool
    {
        return DailySchedule::query()
            ->where('date', $payload['date'] ?? '')
            ->where('building', $payload['building'] ?? '')
            ->where('room', $payload['room'] ?? '')
            ->where('period', $payload['period'] ?? '')
            ->where('department', $payload['department'] ?? '')
            ->where('content', $payload['content'] ?? '')
            ->where('class', $payload['class'] ?? '')
            ->where('lecturer', $payload['lecturer'] ?? '')
            ->exists();
    }

    /** @param array<string, mixed> $item */
    private function existingImportKey(array $item): string
    {
        return $this->normalizeImportKey(implode('', [
            $item['date'] ?? '',
            $item['building'] ?? '',
            $item['room'] ?? '',
            $item['period'] ?? '',
            $item['department'] ?? '',
            $item['content'] ?? '',
            $item['class'] ?? '',
            $item['lecturer'] ?? '',
        ]));
    }

    private function normalizeImportKey(?string $value): string
    {
        return $value ? strtolower(preg_replace('/[^a-z0-9]/i', '', $value) ?? '') : '';
    }
}
