<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class IncidentMonthlyReportExportService
{
    /** Table placeholder field keys in column order matching the template sample row. */
    private const ROW_FIELDS = [
        'stt', 'employee', 'date', 'room', 'period', 'type',
        'department', 'class', 'studentCount', 'lecturer',
        'content', 'incident', 'incidentDetail',
    ];

    public function __construct(
        private MonthlyReportStatsService $stats,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $rows             Comprehensive report rows (comprehensiveRows output)
     * @param  array{
     *   campus?: string,
     *   department?: string,
     *   departments?: list<string>,
     *   users?: list<string>,
     *   titleFromDate?: string,
     *   titleToDate?: string,
     * }  $templateInputs
     */
    public function download(
        array $rows,
        string $fromDisplay,
        string $toDisplay,
        string $filename,
        array $templateInputs = [],
    ): StreamedResponse {
        $templatePath = public_path('templates/baocaothang-template.docx');

        if (! file_exists($templatePath)) {
            abort(500, 'Không tìm thấy tệp mẫu Word báo cáo tháng.');
        }

        $tempPath = sys_get_temp_dir() . '/' . uniqid('baocaothang_', true) . '.docx';
        copy($templatePath, $tempPath);

        try {
            $campus      = (string) ($templateInputs['campus'] ?? '');
            $department  = (string) ($templateInputs['department'] ?? '');
            $departments = (array) ($templateInputs['departments'] ?? []);
            $users       = (array) ($templateInputs['users'] ?? []);
            $titleFrom   = (string) ($templateInputs['titleFromDate'] ?? $fromDisplay);
            $titleTo     = (string) ($templateInputs['titleToDate'] ?? $toDisplay);

            $reporterName = implode(', ', array_filter(array_map('trim', $users)));

            $simpleReplacements = [
                '${from_date}'  => $fromDisplay,
                '${to_date}'    => $toDisplay,
                '${campus}'     => $campus,
                '${department}' => $department,
                '${user}'       => $reporterName,
            ];

            $reportData = $this->stats->buildReportData($fromDisplay, $toDisplay, $campus, $users, $departments);
            $metrics    = $reportData['metrics'];
            $redTexts   = $reportData['redTexts'];

            $dateParts = array_merge(
                $this->splitDateDisplay($titleFrom),
                $this->splitDateDisplay($titleTo),
            );

            $zip = new \ZipArchive();
            if ($zip->open($tempPath) !== true) {
                abort(500, 'Không thể mở tệp mẫu DOCX.');
            }

            foreach ($this->collectDocumentParts($zip) as $partName) {
                $content = $zip->getFromName($partName);
                if ($content === false) {
                    continue;
                }

                $content = $this->replaceSimplePlaceholders($content, $simpleReplacements);
                $content = $this->replaceRedRuns($content, $metrics, $redTexts, $dateParts);

                if ($partName === 'word/document.xml') {
                    $content = $this->fillTableRows($content, $rows);
                }

                $zip->addFromString($partName, $content);
            }

            $zip->close();
            $docxContent = (string) file_get_contents($tempPath);
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }

        return response()->streamDownload(static function () use ($docxContent): void {
            echo $docxContent;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    // ─── Internal helpers ──────────────────────────────────────────────────────

    /**
     * Collect all document XML parts that should receive token replacement:
     * word/document.xml plus any word/header*.xml and word/footer*.xml entries.
     *
     * @return list<string>
     */
    private function collectDocumentParts(\ZipArchive $zip): array
    {
        $parts = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (
                $name === 'word/document.xml' ||
                preg_match('/^word\/(header|footer)\d*\.xml$/i', $name)
            ) {
                $parts[] = $name;
            }
        }

        return $parts;
    }

    /**
     * Replace simple ${placeholder} tokens with their values.
     * Values are XML-encoded; placeholder names use literal ${}. 
     *
     * @param  array<string, string>  $replacements
     */
    private function replaceSimplePlaceholders(string $xml, array $replacements): string
    {
        foreach ($replacements as $placeholder => $value) {
            $xml = str_replace($placeholder, htmlspecialchars($value, ENT_XML1), $xml);
        }

        return $xml;
    }

    /**
     * Replace red-colored runs (w:color w:val="FF0000") in the XML:
     *   - Runs whose text is purely digits → next metric value (in order)
     *   - Runs whose text is "dd", "mm", or "yyyy" (case-insensitive, trimmed) → next date part
     *   - All other red runs → next redTexts entry (red color stripped, whitespace preserved)
     *
     * @param  list<int>     $metrics
     * @param  list<string>  $redTexts
     * @param  list<string>  $dateParts  [fromDay, fromMonth, fromYear, toDay, toMonth, toYear]
     */
    private function replaceRedRuns(string $xml, array $metrics, array $redTexts, array $dateParts): string
    {
        $metricIdx  = 0;
        $dateIdx    = 0;
        $redTextIdx = 0;

        // Tempered greedy token: matches <w:r> that does not contain a nested <w:r start tag.
        return (string) preg_replace_callback(
            '/<w:r\b[^>]*>(?:(?!<w:r\b).)*?<\/w:r>/s',
            function (array $m) use (
                &$metricIdx, &$dateIdx, &$redTextIdx,
                $metrics, $redTexts, $dateParts,
            ): string {
                $run = $m[0];

                // Only act on red-colored runs.
                if (! preg_match('/<w:color\s+w:val=["\']FF0000["\']/i', $run)) {
                    return $run;
                }

                // Extract the text content of the run.
                if (! preg_match('/<w:t[^>]*>(.*?)<\/w:t>/s', $run, $tm)) {
                    return $run;
                }

                $text = html_entity_decode($tm[1], ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $trimmed = trim($text);

                if (preg_match('/^\d+$/', $trimmed)) {
                    // Digit run → substitute metric value.
                    $value = (string) ($metrics[$metricIdx] ?? 0);
                    $metricIdx++;

                    return (string) preg_replace(
                        '/<w:t[^>]*>.*?<\/w:t>/s',
                        '<w:t>' . $value . '</w:t>',
                        $run,
                    );
                }

                if (in_array(mb_strtolower($trimmed), ['dd', 'mm', 'yyyy'], true)) {
                    // Date-part token → substitute date component.
                    $value = htmlspecialchars($dateParts[$dateIdx] ?? '', ENT_XML1);
                    $dateIdx++;

                    return (string) preg_replace(
                        '/<w:t[^>]*>.*?<\/w:t>/s',
                        '<w:t>' . $value . '</w:t>',
                        $run,
                    );
                }

                // Other red run → redTexts entry; remove red color so output is plain text.
                $value = htmlspecialchars($redTexts[$redTextIdx] ?? '', ENT_XML1);
                $redTextIdx++;

                $run = (string) preg_replace('/<w:color\s+w:val=["\']FF0000["\'][^\/]*\/>/i', '', $run);

                return (string) preg_replace(
                    '/<w:t[^>]*>.*?<\/w:t>/s',
                    '<w:t xml:space="preserve">' . $value . '</w:t>',
                    $run,
                );
            },
            $xml,
        );
    }

    /**
     * Find the sample table row containing ${stt}, clone and fill it for each data row,
     * then replace the single sample row with all filled rows (or remove it when $rows is empty).
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function fillTableRows(string $xml, array $rows): string
    {
        // Tempered greedy: find the first <w:tr> that contains ${stt}.
        if (! preg_match(
            '/(<w:tr\b(?:(?!<w:tr\b).)*?\$\{stt\}(?:(?!<w:tr\b).)*?<\/w:tr>)/s',
            $xml,
            $m,
            PREG_OFFSET_CAPTURE,
        )) {
            return $xml;
        }

        /** @var array{0: string, 1: int} $match */
        $match     = $m[1];
        $sampleRow = $match[0];
        $samplePos = $match[1];
        $sampleLen = strlen($sampleRow);

        if ($rows === []) {
            return substr($xml, 0, $samplePos) . substr($xml, $samplePos + $sampleLen);
        }

        $filledRows = '';
        foreach ($rows as $index => $row) {
            $rowXml  = $sampleRow;
            $rowData = array_merge((array) $row, ['stt' => $index + 1]);

            foreach (self::ROW_FIELDS as $field) {
                $encoded = htmlspecialchars((string) ($rowData[$field] ?? ''), ENT_XML1);
                $rowXml  = str_replace('${' . $field . '}', $encoded, $rowXml);
            }

            $filledRows .= $rowXml;
        }

        return substr($xml, 0, $samplePos) . $filledRows . substr($xml, $samplePos + $sampleLen);
    }

    /**
     * Split a display date "dd/mm/yyyy" into [day, month, year].
     *
     * @return list<string>
     */
    private function splitDateDisplay(string $date): array
    {
        $parts = explode('/', trim($date));

        return count($parts) === 3 ? [$parts[0], $parts[1], $parts[2]] : ['', '', ''];
    }
}
