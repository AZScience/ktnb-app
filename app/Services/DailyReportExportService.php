<?php

namespace App\Services;

use App\Models\DailySchedule;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Drawing;
use PhpOffice\PhpSpreadsheet\Shared\Font as SharedFont;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing as WorksheetDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DailyReportTemplateReadFilter implements IReadFilter
{
    public function __construct(private int $maxRow = 40) {}

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        return $row <= $this->maxRow;
    }
}

class DailyReportExportService
{
    private const TAB_ORDER = ['in-person', 'online', 'homeroom', 'violations', 'exams', 'external'];

    private const TEMPLATE_SHEETS = ['PHONGHOC', 'TRUCTUYEN', 'SINHHOAT', 'VIPHAM', 'LOPTHI', 'THUCHANH'];

    private const DATA_FONT_SIZE = 12;

    /** Khoảng đệm (pt) khi tính chiều cao dòng tự động. */
    private const AUTO_ROW_HEIGHT_PADDING = 4.0;

    /** @var list<string> */
    private array $temporaryImages = [];

    /** Cột xuất Excel cần canh giữa ngang nội dung. */
    private const CENTER_ALIGNED_EXPORT_COLS = [
        'is_notification',
        'attending_students',
        'student_count',
    ];

    public function __construct(
        private DailyReportService $daily,
    ) {}

    /**
     * @param  array<string, list<array<string, mixed>>>  $datasets
     */
    public function download(string $dateIso, array $datasets, string $officerFullName): StreamedResponse
    {
        $categories = $this->buildCategories($datasets);
        $hasData = collect($categories)->contains(fn (array $cat) => $cat['data'] !== []);

        if (! $hasData) {
            abort(422, 'Không có dữ liệu để xuất.');
        }

        $templatePath = $this->resolveTemplatePath();

        $previousLimit = ini_get('memory_limit');
        ini_set('memory_limit', '256M');

        try {
            $spreadsheet = $this->loadTemplateSpreadsheet($templatePath);
            $displayDate = $this->daily->isoToDisplay($dateIso);
            [$y, $m, $d] = explode('-', $dateIso);
            $dateHeader = "Ngày báo cáo: {$displayDate}";
            $sheetMap = $this->buildSheetMap($spreadsheet);
            $sheetsUpdated = 0;

            foreach ($categories as $cfg) {
                $worksheet = $sheetMap[$this->normalizeSheetName($cfg['key'])] ?? null;
                if (! $worksheet instanceof Worksheet) {
                    continue;
                }

                if ($cfg['data'] === []) {
                    $sheetIndex = $spreadsheet->getIndex($worksheet);
                    $spreadsheet->removeSheetByIndex($sheetIndex);
                    continue;
                }

                $sheetsUpdated++;
                $worksheet->setAutoFilter('');
                $dataStartRow = $cfg['key'] === 'VIPHAM' ? 8 : 7;
                $worksheet->setCellValue('A5', $dateHeader);

                $colCount = count($cfg['cols']) + 1;
                $this->applyHeaderBorders($worksheet, $dataStartRow, $colCount);

                $this->fillDataRows($worksheet, $cfg['data'], $cfg['cols'], $dataStartRow, $cfg['key']);
                $lastDataRow = $dataStartRow + count($cfg['data']) - 1;
                $this->clearSurplusTemplateRows($worksheet, $lastDataRow + 1, $colCount);

                $footerStartRow = $dataStartRow + count($cfg['data']) + 2;
                $footerMergeEnd = count($cfg['cols']) + 1;
                $footerMergeStart = max(2, $footerMergeEnd - 2);
                $this->setFooterLine($worksheet, $footerStartRow, "Hồ Chí Minh, ngày {$d} tháng {$m} năm {$y}", false, true, $footerMergeStart, $footerMergeEnd);
                $this->setFooterLine($worksheet, $footerStartRow + 1, 'Người báo cáo', true, false, $footerMergeStart, $footerMergeEnd);
                $this->setFooterLine($worksheet, $footerStartRow + 2, '(chữ ký)', false, true, $footerMergeStart, $footerMergeEnd);
                $this->setFooterLine($worksheet, $footerStartRow + 6, $officerFullName !== '' ? $officerFullName : 'Người báo cáo', true, false, $footerMergeStart, $footerMergeEnd);
            }

            if ($sheetsUpdated === 0) {
                abort(422, 'Không tìm thấy sheet mẫu phù hợp để xuất.');
            }

            // Tự động tạo sheet MINHCHUNG nếu có hình ảnh minh chứng
            $this->appendEvidenceSheet($spreadsheet, $categories, $displayDate);

            $spreadsheet->setActiveSheetIndex(0);

            $filename = $this->buildFilename($officerFullName);

            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $tempFile = storage_path('framework/cache/export_' . uniqid() . '.xlsx');
            $writer->save($tempFile);

            return response()->streamDownload(function () use ($tempFile) {
                readfile($tempFile);
                @unlink($tempFile);
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } finally {
            foreach ($this->temporaryImages as $tempImg) {
                if (file_exists($tempImg)) {
                    @unlink($tempImg);
                }
            }
            $this->temporaryImages = [];

            if ($previousLimit !== false) {
                ini_set('memory_limit', (string) $previousLimit);
            }
        }
    }

    private function resolveTemplatePath(): string
    {
        $sanitizer = app(DailyReportTemplateSanitizer::class);
        $builder = app(DailyReportTemplateBuilder::class);

        $candidates = [
            public_path('templates/daily_report_template.xlsx'),
            storage_path('app/templates/daily_report_template.xlsx'),
            dirname(base_path()).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'daily_report_template.xlsx',
        ];

        foreach ($candidates as $path) {
            if ($sanitizer->isUsableSanitized($path)) {
                return $path;
            }
        }

        if ($sanitizer->isUsableSource($sanitizer->sourcePath())) {
            try {
                return $sanitizer->ensureSanitized();
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        try {
            return $builder->ensureAt();
        } catch (\Throwable $exception) {
            report($exception);
        }

        abort(500, 'Không tìm thấy tệp mẫu Excel. Chạy php scripts/rebuild_daily_report_template.php để tạo mẫu.');
    }

    private function loadTemplateSpreadsheet(string $templatePath): Spreadsheet
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setIncludeCharts(false);
        $reader->setReadDataOnly(false);
        $reader->setReadEmptyCells(false);
        $reader->setIgnoreRowsWithNoCells(true);
        $reader->setLoadSheetsOnly(self::TEMPLATE_SHEETS);
        $reader->setReadFilter(new DailyReportTemplateReadFilter);

        return $reader->load($templatePath);
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $datasets
     * @return list<array{key: string, data: list<array<string, mixed>>, cols: list<string>}>
     */
    private function buildCategories(array $datasets): array
    {
        $definitions = $this->daily->tabDefinitions();
        $categories = [];

        foreach (self::TAB_ORDER as $tabKey) {
            $def = $definitions[$tabKey] ?? null;
            if (! $def) {
                continue;
            }

            $categories[] = [
                'key' => $def['exportKey'],
                'data' => array_values($datasets[$tabKey] ?? []),
                'cols' => $def['exportSpreadsheetCols'] ?? $def['exportCols'],
            ];
        }

        return $categories;
    }

    /**
     * @return array<string, Worksheet>
     */
    private function buildSheetMap(Spreadsheet $spreadsheet): array
    {
        $map = [];
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $map[$this->normalizeSheetName($sheet->getTitle())] = $sheet;
        }

        return $map;
    }

    private function normalizeSheetName(string $name): string
    {
        $normalized = mb_strtolower(trim($name));
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized) ?: $normalized;

        return preg_replace('/\s+/', '', $normalized) ?? '';
    }

    /**
     * @param  list<array<string, mixed>>  $data
     * @param  list<string>  $cols
     */
    private function fillDataRows(Worksheet $worksheet, array $data, array $cols, int $dataStartRow, ?string $sheetKey = null): void
    {
        if ($data === []) {
            return;
        }

        $colCount = count($cols) + 1;
        $lastColLetter = Coordinate::stringFromColumnIndex($colCount);
        $lastDataRow = $dataStartRow + count($data) - 1;



        foreach ($data as $index => $item) {
            $rowIndex = $dataStartRow + $index;
            $worksheet->getRowDimension($rowIndex)->setRowHeight(-1);

            $worksheet->getCell("A{$rowIndex}")->setValue($index + 1);

            foreach ($cols as $colOffset => $colKey) {
                $colLetter = Coordinate::stringFromColumnIndex($colOffset + 2);
                $cell = $worksheet->getCell("{$colLetter}{$rowIndex}");

                if ($colKey === '__gap__') {
                    $cell->setValue('');
                } elseif ($colKey === 'is_notification') {
                    // Unicode checkbox + TYPE_STRING: tránh TYPE_BOOL (TRUE/FALSE) và tránh
                    // chỉnh style từng ô trước duplicateStyle (gây đệ quy getQuotePrefix).
                    $cell->setValueExplicit(
                        $this->notificationCheckboxGlyph($item[$colKey] ?? false),
                        DataType::TYPE_STRING,
                    );
                } else {
                    $raw = $item[$colKey] ?? '';
                    if (is_numeric($raw) && (float) $raw == 0.0) {
                        $cell->setValue('');
                    } else {
                        $cell->setValue($raw);
                    }
                }
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

        $this->applyDataRangeFormatting($worksheet, $cols, $dataStartRow, $lastDataRow, $sheetKey);
        $this->autoFitDataRowHeights($worksheet, $dataStartRow, $lastDataRow, $colCount);
    }

    private function autoFitDataRowHeights(
        Worksheet $worksheet,
        int $dataStartRow,
        int $lastDataRow,
        int $colCount,
    ): void {
        $defaultFont = $worksheet->getParent()?->getDefaultStyle()->getFont();
        $minRowHeight = $defaultFont
            ? SharedFont::getDefaultRowHeightByFont($defaultFont)
            : 15.0;

        for ($row = $dataStartRow; $row <= $lastDataRow; $row++) {
            $rowHeight = $minRowHeight;

            for ($col = 1; $col <= $colCount; $col++) {
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
        Font $font,
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

    private function applyHeaderBorders(Worksheet $worksheet, int $dataStartRow, int $colCount): void
    {
        $headerEndRow = $dataStartRow - 1;
        if ($headerEndRow < 6 || $colCount < 1) {
            return;
        }

        $lastColLetter = Coordinate::stringFromColumnIndex($colCount);
        $range = "A6:{$lastColLetter}{$headerEndRow}";

        $worksheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
    }

    /**
     * @param  list<string>  $cols
     */
    private function applyDataRangeFormatting(
        Worksheet $worksheet,
        array $cols,
        int $dataStartRow,
        int $lastDataRow,
        ?string $sheetKey,
    ): void {
        $lastColLetter = Coordinate::stringFromColumnIndex(count($cols) + 1);
        $dataRange = "A{$dataStartRow}:{$lastColLetter}{$lastDataRow}";
        $templateFont = $worksheet->getStyle("B{$dataStartRow}")->getFont();
        $fontName = $templateFont->getName() ?: 'Times New Roman';

        $worksheet->getStyle($dataRange)->applyFromArray([
            'font' => [
                'name' => $fontName,
                'size' => self::DATA_FONT_SIZE,
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $worksheet->getStyle("A{$dataStartRow}:A{$lastDataRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (self::CENTER_ALIGNED_EXPORT_COLS as $colKey) {
            $colLetter = $this->resolveColumnLetter($cols, $colKey);
            if ($colLetter === null) {
                continue;
            }

            $colRange = "{$colLetter}{$dataStartRow}:{$colLetter}{$lastDataRow}";
            $colStyle = $worksheet->getStyle($colRange);
            $colStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($colKey === 'is_notification') {
                $colStyle->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
                $colStyle->getFont()->setBold(false)->setSize(self::DATA_FONT_SIZE + 1)->setName('Segoe UI Symbol');
            }
        }

        if ($sheetKey === 'VIPHAM') {
            foreach ($cols as $colOffset => $colKey) {
                if (in_array($colKey, ['officer', 'violation_date', 'building'], true)) {
                    continue;
                }

                $colLetter = Coordinate::stringFromColumnIndex($colOffset + 2);
                $worksheet->getStyle("{$colLetter}{$dataStartRow}:{$colLetter}{$lastDataRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }
    }



    /**
     * @param  list<string>  $cols
     */
    private function resolveColumnLetter(array $cols, string $colKey): ?string
    {
        $offset = array_search($colKey, $cols, true);

        return $offset === false
            ? null
            : Coordinate::stringFromColumnIndex($offset + 2);
    }

    private function clearSurplusTemplateRows(Worksheet $worksheet, int $fromRow, int $colCount): void
    {
        $maxTemplateRow = 8;
        if ($fromRow > $maxTemplateRow) {
            return;
        }

        for ($row = $fromRow; $row <= $maxTemplateRow; $row++) {
            for ($col = 1; $col <= $colCount; $col++) {
                $letter = Coordinate::stringFromColumnIndex($col);
                $worksheet->getCell("{$letter}{$row}")->setValue(null);
            }
        }
    }

    private function notificationCheckboxGlyph(mixed $value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '☑' : '☐';
    }

    private function setFooterLine(Worksheet $worksheet, int $row, string $text, bool $bold, bool $italic, int $mergeStartCol, int $mergeEndCol): void
    {
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
                'name' => 'Times New Roman',
                'size' => 12,
                'bold' => $bold,
                'italic' => $italic,
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
        ]);
    }

    private function buildFilename(string $officer): string
    {
        $slug = $this->slugify($officer !== '' ? $officer : 'bao-cao');
        $stamp = now()->format('d-m-Y_H-i');

        return "{$slug}_{$stamp}.xlsx";
    }

    private function slugify(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'bao-cao';
    }

    /**
     * Tự động tạo và định dạng sheet MINHCHUNG chứa ảnh của các giảng viên có minh chứng.
     *
     * @param  list<array{key: string, data: list<array<string, mixed>>, cols: list<string>}>  $categories
     */
    private function appendEvidenceSheet(Spreadsheet $spreadsheet, array $categories, string $displayDate): void
    {
        $missingEvidenceIds = [];
        foreach ($categories as $cat) {
            foreach ($cat['data'] as $row) {
                if (!empty($row['id']) && empty($row['evidence'])) {
                    $missingEvidenceIds[] = (int) $row['id'];
                }
            }
        }

        $dbEvidenceMap = [];
        if ($missingEvidenceIds !== []) {
            try {
                $dbEvidenceMap = DailySchedule::query()
                    ->whereIn('id', array_unique($missingEvidenceIds))
                    ->whereNotNull('evidence')
                    ->where('evidence', '!=', '')
                    ->pluck('evidence', 'id')
                    ->all();
            } catch (\Throwable) {
                $dbEvidenceMap = [];
            }
        }

        $evidenceStorage = app(EvidenceStorageService::class);
        $groupedByLecturer = [];

        foreach ($categories as $cat) {
            foreach ($cat['data'] as $item) {
                $rawEvidence = !empty($item['evidence'])
                    ? $item['evidence']
                    : ($dbEvidenceMap[$item['id'] ?? 0] ?? null);

                if (empty($rawEvidence)) {
                    continue;
                }

                $entries = $evidenceStorage->parse((string) $rawEvidence);
                if (empty($entries)) {
                    continue;
                }

                $validImages = [];
                foreach ($entries as $entry) {
                    $url = $entry['url'] ?? '';
                    if ($url === '') {
                        continue;
                    }
                    $localPath = $this->resolveLocalImagePath($url);
                    if ($localPath !== null) {
                        $validImages[] = [
                            'name' => $entry['name'] ?? 'Minh chứng',
                            'path' => $localPath,
                        ];
                    }
                }

                if ($validImages === []) {
                    continue;
                }

                $lecturer = trim((string) ($item['lecturer'] ?? ''));
                if ($lecturer === '' || $lecturer === '0') {
                    $lecturer = 'Chưa xác định giảng viên';
                }

                $groupedByLecturer[$lecturer][] = [
                    'session' => [
                        'room' => $item['room'] ?? '',
                        'period' => $item['period'] ?? '',
                        'class' => $item['class'] ?? '',
                        'department' => $item['department'] ?? '',
                        'incident' => $item['incident'] ?? '',
                        'incident_detail' => $item['incident_detail'] ?? '',
                        'content' => $item['content'] ?? '',
                    ],
                    'images' => $validImages,
                ];
            }
        }

        if (empty($groupedByLecturer)) {
            return;
        }

        $sheetTitle = 'MINHCHUNG';
        $existingSheet = $spreadsheet->getSheetByName($sheetTitle);
        if ($existingSheet instanceof Worksheet) {
            $evidenceSheet = $existingSheet;
        } else {
            $evidenceSheet = new Worksheet($spreadsheet, $sheetTitle);
            $spreadsheet->addSheet($evidenceSheet);
        }

        $evidenceSheet->setShowGridLines(true);
        $evidenceSheet->getSheetView()->setZoomScale(95);

        // Đặt độ rộng cột
        $evidenceSheet->getColumnDimension('A')->setWidth(8);
        $evidenceSheet->getColumnDimension('B')->setWidth(38);
        $evidenceSheet->getColumnDimension('C')->setWidth(38);
        $evidenceSheet->getColumnDimension('D')->setWidth(38);
        $evidenceSheet->getColumnDimension('E')->setWidth(38);

        // Header sheet
        $evidenceSheet->setCellValue('A1', 'BẢNG HÌNH ẢNH MINH CHỨNG');
        $evidenceSheet->mergeCells('A1:E1');
        $evidenceSheet->getStyle('A1:E1')->applyFromArray([
            'font' => [
                'name' => 'Times New Roman',
                'size' => 15,
                'bold' => true,
                'color' => ['rgb' => '1E3A8A'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $evidenceSheet->getRowDimension(1)->setRowHeight(28);

        $evidenceSheet->setCellValue('A2', "Ngày báo cáo: {$displayDate}");
        $evidenceSheet->mergeCells('A2:E2');
        $evidenceSheet->getStyle('A2:E2')->applyFromArray([
            'font' => [
                'name' => 'Times New Roman',
                'size' => 11,
                'italic' => true,
                'color' => ['rgb' => '4B5563'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $evidenceSheet->getRowDimension(2)->setRowHeight(20);

        $evidenceSheet->getRowDimension(3)->setRowHeight(10);

        $currentRow = 4;
        $lecturerIndex = 1;

        foreach ($groupedByLecturer as $lecturerName => $sessions) {
            // Dòng Giảng viên
            $evidenceSheet->setCellValue("A{$currentRow}", $lecturerIndex);
            $evidenceSheet->setCellValue("B{$currentRow}", 'GIẢNG VIÊN: ' . mb_strtoupper($lecturerName, 'UTF-8'));
            $evidenceSheet->mergeCells("B{$currentRow}:E{$currentRow}");

            $evidenceSheet->getStyle("A{$currentRow}:E{$currentRow}")->applyFromArray([
                'font' => [
                    'name' => 'Times New Roman',
                    'size' => 12,
                    'bold' => true,
                    'color' => ['rgb' => '0F5132'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D1E7DD'],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'BADBCC'],
                    ],
                ],
            ]);
            $evidenceSheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $evidenceSheet->getRowDimension($currentRow)->setRowHeight(26);
            $currentRow++;

            // Dòng thông tin các ca trực và ảnh của Giảng viên đó
            foreach ($sessions as $session) {
                $infoParts = [];
                if (!empty($session['session']['room'])) {
                    $infoParts[] = 'Phòng: ' . $session['session']['room'];
                }
                if (!empty($session['session']['period'])) {
                    $infoParts[] = 'Tiết: ' . $session['session']['period'];
                }
                if (!empty($session['session']['class'])) {
                    $infoParts[] = 'Lớp: ' . $session['session']['class'];
                }
                if (!empty($session['session']['department'])) {
                    $infoParts[] = 'Khoa: ' . $session['session']['department'];
                }
                if (!empty($session['session']['content'])) {
                    $infoParts[] = 'Môn: ' . $session['session']['content'];
                }
                $incident = trim((string) ($session['session']['incident'] ?? ''));
                $detail = trim((string) ($session['session']['incident_detail'] ?? ''));
                if ($incident !== '' || $detail !== '') {
                    $infoParts[] = 'Sự việc: ' . trim("{$incident} {$detail}");
                }

                $sessionInfoStr = implode('  |  ', $infoParts);

                $evidenceSheet->setCellValue("A{$currentRow}", '');
                $evidenceSheet->setCellValue("B{$currentRow}", '• ' . ($sessionInfoStr ?: 'Thông tin ca trực'));
                $evidenceSheet->mergeCells("B{$currentRow}:E{$currentRow}");
                $evidenceSheet->getStyle("A{$currentRow}:E{$currentRow}")->applyFromArray([
                    'font' => [
                        'name' => 'Times New Roman',
                        'size' => 11,
                        'italic' => true,
                        'color' => ['rgb' => '374151'],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $evidenceSheet->getRowDimension($currentRow)->setRowHeight(22);
                $currentRow++;

                // Chèn ảnh (tối đa 4 ảnh / hàng ở các cột B, C, D, E)
                $imageChunks = array_chunk($session['images'], 4);
                foreach ($imageChunks as $chunk) {
                    $evidenceSheet->getRowDimension($currentRow)->setRowHeight(160);

                    foreach ($chunk as $imgOffset => $img) {
                        $colIndex = 2 + $imgOffset; // B=2, C=3, D=4, E=5
                        $colLetter = Coordinate::stringFromColumnIndex($colIndex);

                        try {
                            $drawing = new WorksheetDrawing();
                            $drawing->setName($img['name'] ?: 'Minh chứng');
                            $drawing->setDescription($img['name'] ?: 'Minh chứng');
                            $drawing->setPath($img['path']);
                            $drawing->setHeight(190);
                            $drawing->setCoordinates("{$colLetter}{$currentRow}");
                            $drawing->setOffsetX(8);
                            $drawing->setOffsetY(8);
                            $drawing->setWorksheet($evidenceSheet);
                        } catch (\Throwable) {
                            $evidenceSheet->setCellValue("{$colLetter}{$currentRow}", '[Lỗi hiển thị ảnh]');
                        }
                    }
                    $currentRow++;
                }

                // Dòng đệm giữa các ca
                $evidenceSheet->getRowDimension($currentRow)->setRowHeight(6);
                $currentRow++;
            }

            $lecturerIndex++;
            // Dòng đệm giữa các Giảng viên
            $evidenceSheet->getRowDimension($currentRow)->setRowHeight(12);
            $currentRow++;
        }
    }

    /**
     * Tìm đường dẫn file ảnh vật lý trên máy chủ từ URL hoặc chuỗi path/base64.
     */
    private function resolveLocalImagePath(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        // 1. Data URL (base64)
        if (str_starts_with($url, 'data:image/')) {
            if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $url, $matches)) {
                $ext = strtolower($matches[1]);
                if ($ext === 'jpeg') {
                    $ext = 'jpg';
                }
                $data = base64_decode($matches[2]);
                if ($data !== false) {
                    $tempPath = tempnam(sys_get_temp_dir(), 'ev_img_') . '.' . $ext;
                    file_put_contents($tempPath, $data);
                    $this->temporaryImages[] = $tempPath;
                    return $tempPath;
                }
            }
            return null;
        }

        // 2. URL hoặc đường dẫn file
        $pathOnly = parse_url($url, PHP_URL_PATH) ?: $url;
        $pathOnly = ltrim(rawurldecode($pathOnly), '/\\');

        $relPath = $pathOnly;
        if (str_starts_with($relPath, 'storage/')) {
            $relPath = substr($relPath, 8);
        }

        $candidates = [
            Storage::disk('public')->path($relPath),
            public_path('storage/' . $relPath),
            public_path($pathOnly),
            storage_path('app/public/' . $relPath),
            storage_path('app/' . $relPath),
            base_path($pathOnly),
        ];

        foreach ($candidates as $filePath) {
            if (is_file($filePath) && file_exists($filePath)) {
                $imgInfo = @getimagesize($filePath);
                if ($imgInfo !== false) {
                    return $filePath;
                }
            }
        }

        return null;
    }
}






