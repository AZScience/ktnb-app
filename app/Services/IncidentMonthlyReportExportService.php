<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class IncidentMonthlyReportExportService
{
    /** @var list<string> */
    private array $rowFields = [
        'stt',
        'employee',
        'date',
        'room',
        'period',
        'type',
        'department',
        'class',
        'studentCount',
        'lecturer',
        'content',
        'incident',
        'incidentDetail',
    ];

    public function __construct(
        private MonthlyReportStatsService $stats,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $templateInputs
     */
    public function download(
        array $rows,
        string $fromDisplay,
        string $toDisplay,
        string $filename,
        array $templateInputs = [],
    ): StreamedResponse {
        $templatePath = $this->resolveTemplatePath();
        $workingPath = tempnam(sys_get_temp_dir(), 'kph_monthly_');
        if ($workingPath === false) {
            abort(500, 'Không tạo được tệp báo cáo tạm.');
        }

        copy($templatePath, $workingPath);
        $this->fillDocx($workingPath, $rows, $fromDisplay, $toDisplay, $templateInputs);

        return response()->streamDownload(function () use ($workingPath) {
            readfile($workingPath);
            @unlink($workingPath);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    private function resolveTemplatePath(): string
    {
        $candidates = [
            public_path('templates/baocaothang-template.docx'),
            dirname(base_path()).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'baocaothang-template.docx',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        abort(500, 'Không tìm thấy tệp mẫu Word baocaothang-template.docx.');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $templateInputs
     */
    private function fillDocx(
        string $docxPath,
        array $rows,
        string $fromDisplay,
        string $toDisplay,
        array $templateInputs,
    ): void {
        if (! class_exists(ZipArchive::class)) {
            abort(500, 'Máy chủ chưa bật extension ZipArchive.');
        }

        $zip = new ZipArchive;
        if ($zip->open($docxPath) !== true) {
            abort(500, 'Không mở được tệp mẫu báo cáo tháng.');
        }

        $replacements = $this->documentReplacements($rows, $fromDisplay, $toDisplay, $templateInputs);
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! $name || ! preg_match('#^word/(document|header\d+|footer\d+)\.xml$#', $name)) {
                continue;
            }

            $xml = $zip->getFromName($name);
            if ($xml === false) {
                continue;
            }

            $xml = $this->replaceTextPlaceholders($xml, $replacements);
            $xml = $this->replaceSampleLiterals($xml, $replacements);
            if ($name === 'word/document.xml') {
                $xml = $this->replaceRedMetrics($xml, $replacements, $templateInputs);
                $xml = $this->fillTableRows($xml, $rows);
            }
            $zip->addFromString($name, $xml);
        }

        $zip->close();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $templateInputs
     * @return array<string, string>
     */
    private function documentReplacements(
        array $rows,
        string $fromDisplay,
        string $toDisplay,
        array $templateInputs,
    ): array {
        $fromDisplay = $this->input($templateInputs, 'titleFromDate', $fromDisplay);
        $toDisplay = $this->input($templateInputs, 'titleToDate', $toDisplay);
        $toDate = $this->parseDisplayDate($toDisplay) ?? Carbon::now();
        $fromDate = $this->parseDisplayDate($fromDisplay) ?? $toDate->copy();

        $reportMonth = (int) $toDate->format('n');
        $reportYear = (int) $toDate->format('Y');
        $nextMonthDate = $toDate->copy()->addMonthNoOverflow()->startOfMonth();
        $nextMonth = (int) $nextMonthDate->format('n');
        $nextYear = (int) $nextMonthDate->format('Y');

        $campus = $this->input($templateInputs, 'campus', '');
        $user = $this->resolveReporterNames($templateInputs);

        return [
            'from_date' => $fromDisplay,
            'to_date' => $toDisplay,
            'report_from' => $fromDisplay,
            'report_to' => $toDisplay,
            'report_day' => $toDate->format('d'),
            'report_month' => (string) $reportMonth,
            'report_month_padded' => $toDate->format('m'),
            'month' => (string) $reportMonth,
            'report_year' => (string) $reportYear,
            'year' => (string) $reportYear,
            'next_month' => (string) $nextMonth,
            'next_month_padded' => $nextMonthDate->format('m'),
            'next_year' => (string) $nextYear,
            'generated_date' => date('d/m/Y'),
            'today' => date('d/m/Y'),
            'footer_date' => sprintf(
                'ngày %s tháng %s năm %s',
                $toDate->format('d'),
                $reportMonth,
                $reportYear,
            ),
            'period_label' => sprintf('từ ngày %s đến %s', $fromDisplay, $toDisplay),
            'next_period_label' => sprintf(
                'từ ngày %s đến ngày %s',
                $toDate->copy()->addDay()->format('d/m/Y'),
                $nextMonthDate->copy()->addMonthNoOverflow()->subDay()->format('d/m/Y'),
            ),
            'next_period_title' => sprintf(
                'THÁNG %s/%s',
                $nextMonthDate->format('m'),
                $nextYear,
            ),
            'report_month_title' => sprintf('THÁNG %s/%s', $toDate->format('m'), $reportYear),
            'total_count' => (string) count($rows),
            'total' => (string) count($rows),
            'campus' => $campus,
            'user' => $user,
            'officer_name' => $user,
            'user_name' => $user,
            'from_date_obj' => $fromDate->format('d/m/Y'),
        ];
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function replaceTextPlaceholders(string $xml, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            if ($key === 'from_date_obj') {
                continue;
            }
            $xml = str_replace('${'.$key.'}', $this->escapeXml($value), $xml);
            $xml = str_replace('{{'.$key.'}}', $this->escapeXml($value), $xml);
        }

        return $xml;
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function replaceSampleLiterals(string $xml, array $replacements): string
    {
        $literalMap = [
            'ngày 09 tháng 7 năm 2026' => $replacements['footer_date'],
            '08/7/2026' => $replacements['to_date'],
            '09/07/2026' => $this->nextPeriodStart($replacements),
            '08/08/2026' => $this->nextPeriodEnd($replacements),
            '07/2026' => $replacements['report_month_padded'].'/'.$replacements['report_year'],
            '08/2026' => $replacements['next_month_padded'].'/'.$replacements['next_year'],
            'Người báo cáo' => $replacements['user'],
        ];

        foreach ($literalMap as $search => $replace) {
            if ($search !== '' && $replace !== '') {
                $xml = str_replace($search, $this->escapeXml($replace), $xml);
            }
        }

        // Title months live in separate Word runs: ...THÁNG </w:t>...<w:t>7</w:t> ... <w:t>8</w:t>
        $xml = preg_replace(
            '/(CÔNG TÁC THÁNG\s*<\/w:t>\s*<\/w:r>\s*<w:r[^>]*>\s*(?:<w:rPr>.*?<\/w:rPr>\s*)?<w:t>)7(<\/w:t>)/su',
            '${1}'.$replacements['report_month'].'${2}',
            $xml,
            2,
        ) ?? $xml;

        $xml = preg_replace(
            '/(CÔNG TÁC THÁNG\s*<\/w:t>\s*<\/w:r>\s*<w:r[^>]*>\s*(?:<w:rPr>.*?<\/w:rPr>\s*)?<w:t>)8(<\/w:t>)/su',
            '${1}'.$replacements['next_month'].'${2}',
            $xml,
            1,
        ) ?? $xml;

        if ($replacements['campus'] !== '') {
            $campus = $this->escapeXml($replacements['campus']);
            $xml = preg_replace('/\btại cơ sở\b/u', 'tại '.$campus, $xml, 3) ?? $xml;
            $xml = preg_replace('/\bCơ sở\b/u', $campus, $xml, 2) ?? $xml;
        }

        // Sample range near exam section often split; replace contiguous end date already covered.
        if (str_contains($xml, '06/6/2026')) {
            $xml = str_replace('06/6/2026', $this->escapeXml($replacements['from_date']), $xml);
        } elseif (str_contains($xml, '06/06/2026')) {
            $xml = str_replace('06/06/2026', $this->escapeXml($replacements['from_date']), $xml);
        }

        return $xml;
    }

    /** @param  array<string, string>  $replacements */
    private function nextPeriodStart(array $replacements): string
    {
        $to = $this->parseDisplayDate($replacements['to_date'] ?? '');

        return $to ? $to->copy()->addDay()->format('d/m/Y') : ($replacements['to_date'] ?? '');
    }

    /** @param  array<string, string>  $replacements */
    private function nextPeriodEnd(array $replacements): string
    {
        $to = $this->parseDisplayDate($replacements['to_date'] ?? '');
        if (! $to) {
            return $replacements['to_date'] ?? '';
        }

        return $to->copy()->addMonthNoOverflow()->format('d/m/Y');
    }

    /**
     * Thay các số màu đỏ trong template bằng số liệu tổng hợp từ Công cụ kiểm tra.
     *
     * @param  array<string, string>  $replacements
     * @param  array<string, mixed>  $templateInputs
     */
    private function replaceRedMetrics(string $xml, array $replacements, array $templateInputs): string
    {
        $reportData = $this->stats->buildReportData(
            $replacements['from_date'] ?? '',
            $replacements['to_date'] ?? '',
            $this->input($templateInputs, 'campus', ''),
        );
        $metrics = $reportData['metrics'];
        $redTexts = $reportData['redTexts'];

        $fromParts = $this->splitDisplayDate($replacements['from_date'] ?? '');
        $toParts = $this->splitDisplayDate($replacements['to_date'] ?? '');
        $dateTokens = [
            $fromParts['d'], $fromParts['m'], $fromParts['y'],
            $toParts['d'], $toParts['m'], $toParts['y'],
        ];

        if (! preg_match_all('/<w:r\b[^>]*>.*?<\/w:r>/su', $xml, $matches, PREG_OFFSET_CAPTURE)) {
            return $xml;
        }

        /** @var list<array{0: string, 1: int}> $runs */
        $runs = $matches[0];
        $metricIndex = 0;
        $dateIndex = 0;
        $textIndex = 0;
        /** @var array<int, string> $replacementsByOffset */
        $replacementsByOffset = [];

        for ($i = 0; $i < count($runs); $i++) {
            [$runXml, $offset] = $runs[$i];
            if (! $this->runIsRed($runXml)) {
                continue;
            }

            $text = trim($this->runText($runXml));
            $token = mb_strtolower($text);

            if (in_array($token, ['dd', 'mm', 'yyyy'], true)) {
                if (! isset($dateTokens[$dateIndex])) {
                    continue;
                }
                $replacementsByOffset[$offset] = $this->withRunText($runXml, $dateTokens[$dateIndex]);
                $dateIndex++;
                continue;
            }

            if (preg_match('/^\d+$/', $text)) {
                $group = [[$runXml, $offset, $text]];
                $j = $i + 1;
                while ($j < count($runs)) {
                    [$nextXml, $nextOffset] = $runs[$j];
                    if (! $this->runIsRed($nextXml)) {
                        break;
                    }
                    $nextText = trim($this->runText($nextXml));
                    if (! preg_match('/^\d+$/', $nextText)) {
                        break;
                    }
                    $prev = $group[count($group) - 1];
                    $between = substr($xml, $prev[1] + strlen($prev[0]), $nextOffset - ($prev[1] + strlen($prev[0])));
                    if (trim(strip_tags($between)) !== '') {
                        break;
                    }
                    $group[] = [$nextXml, $nextOffset, $nextText];
                    $j++;
                }

                if (! isset($metrics[$metricIndex])) {
                    break;
                }

                $value = (int) $metrics[$metricIndex];
                $metricIndex++;
                $originalWidth = array_sum(array_map(fn ($item) => strlen($item[2]), $group));
                $formatted = $this->formatMetricValue($value, max(2, $originalWidth));

                $replacementsByOffset[$group[0][1]] = $this->withRunText($group[0][0], $formatted);
                for ($k = 1; $k < count($group); $k++) {
                    $replacementsByOffset[$group[$k][1]] = $this->withRunText($group[$k][0], '');
                }

                $i = $j - 1;
                continue;
            }

            // Chữ đỏ trong (): danh sách khoa / ghi chú tương ứng.
            if ($text === '' || $text === '/' || preg_match('/^[\s\/\-]+$/u', $text)) {
                continue;
            }
            if (isset($redTexts[$textIndex])) {
                $replacement = trim((string) $redTexts[$textIndex]);
                if ($replacement !== '') {
                    $replacementsByOffset[$offset] = $this->withRunText($runXml, $replacement);
                }
                $textIndex++;
            }
        }

        krsort($replacementsByOffset);
        foreach ($replacementsByOffset as $offset => $newRun) {
            $oldRun = '';
            foreach ($runs as $run) {
                if ($run[1] === $offset) {
                    $oldRun = $run[0];
                    break;
                }
            }
            if ($oldRun === '') {
                continue;
            }
            $xml = substr_replace($xml, $newRun, $offset, strlen($oldRun));
        }

        return $xml;
    }

    private function runIsRed(string $runXml): bool
    {
        if (! preg_match('/w:color[^>]*w:val="([0-9A-Fa-f]{6})"/i', $runXml, $m)) {
            return false;
        }

        $hex = strtoupper($m[1]);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return $r >= 180 && $g <= 80 && $b <= 80;
    }

    private function runText(string $runXml): string
    {
        if (! preg_match_all('/<w:t\b[^>]*>(.*?)<\/w:t>/su', $runXml, $m)) {
            return '';
        }

        return html_entity_decode(implode('', $m[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function withRunText(string $runXml, string $text): string
    {
        $escaped = $this->escapeXml($text);
        $replaced = preg_replace(
            '/(<w:t\b[^>]*>)(.*?)(<\/w:t>)/su',
            '${1}'.$escaped.'${3}',
            $runXml,
            1,
        );

        return is_string($replaced) ? $replaced : $runXml;
    }

    private function formatMetricValue(int $value, int $minWidth): string
    {
        if ($value >= 100) {
            return (string) $value;
        }

        return str_pad((string) $value, $minWidth, '0', STR_PAD_LEFT);
    }

    /** @return array{d: string, m: string, y: string} */
    private function splitDisplayDate(string $date): array
    {
        $parts = explode('/', trim($date));
        if (count($parts) !== 3) {
            return ['d' => '00', 'm' => '00', 'y' => '0000'];
        }

        return [
            'd' => str_pad($parts[0], 2, '0', STR_PAD_LEFT),
            'm' => str_pad($parts[1], 2, '0', STR_PAD_LEFT),
            'y' => $parts[2],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function fillTableRows(string $xml, array $rows): string
    {
        $pattern = '/<w:tr\b[^>]*>.*?\$\{(?:'.implode('|', array_map('preg_quote', $this->rowFields)).')\}.*?<\/w:tr>/s';
        if (! preg_match($pattern, $xml, $matches)) {
            return $xml;
        }

        $templateRow = $matches[0];
        $dataRows = $rows === []
            ? [$this->replaceRowPlaceholders($templateRow, [], 0)]
            : array_map(fn (array $row, int $index) => $this->replaceRowPlaceholders($templateRow, $row, $index + 1), $rows, array_keys($rows));

        return str_replace($templateRow, implode('', $dataRows), $xml);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function replaceRowPlaceholders(string $templateRow, array $row, int $index): string
    {
        foreach ($this->rowFields as $field) {
            $value = $field === 'stt' ? ($index > 0 ? (string) $index : '') : $this->cleanCellValue($row[$field] ?? '');
            $templateRow = str_replace('${'.$field.'}', $this->escapeXml($value), $templateRow);
        }

        return $templateRow;
    }

    private function parseDisplayDate(string $date): ?Carbon
    {
        $date = trim($date);
        if ($date === '') {
            return null;
        }

        try {
            if (str_contains($date, '/')) {
                return Carbon::createFromFormat('d/m/Y', $date)->startOfDay();
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    /** @param  array<string, mixed>  $inputs */
    private function resolveReporterNames(array $inputs): string
    {
        $users = $inputs['users'] ?? null;
        if (is_array($users)) {
            $names = array_values(array_filter(array_map(
                fn ($name) => trim((string) $name),
                $users,
            )));

            if ($names !== []) {
                return implode(', ', $names);
            }
        }

        return $this->input(
            $inputs,
            'user',
            trim((string) (Auth::user()?->name ?? '')),
        );
    }

    /** @param  array<string, mixed>  $inputs */
    private function input(array $inputs, string $key, string $default = ''): string
    {
        $value = trim((string) ($inputs[$key] ?? ''));

        return $value !== '' ? $value : $default;
    }

    private function cleanCellValue(mixed $value): string
    {
        $value = trim((string) $value);

        return $value === '---' ? '' : $value;
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
