<?php

namespace App\Services;

use App\Support\HttpSslConfigurator;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GoogleSheetService
{
    private const SCOPE = 'https://www.googleapis.com/auth/spreadsheets';

    public function __construct(
        private SystemParameterService $parameters,
    ) {}

    public function getConfig(string $profile = 'daily'): array
    {
        $params = $this->parameters->all();
        $sheetId = $profile === 'summary'
            ? trim((string) ($params['summaryReportGoogleSheetId'] ?? ''))
            : trim((string) ($params['googleSheetId'] ?? ''));
        if ($profile === 'summary' && $sheetId === '') {
            $sheetId = (string) config('nttu.summary_report_google_sheet_id', '');
        }

        return [
            'sheet_id' => $this->normalizeSheetId($sheetId),
            'email' => trim((string) ($params['googleServiceAccountEmail'] ?? '')),
            'private_key' => $this->cleanPrivateKey((string) ($params['googlePrivateKey'] ?? '')),
            'default_tab' => $profile === 'summary'
                ? ''
                : (trim((string) ($params['reportSheetTabName'] ?? 'Báo cáo Tổng hợp')) ?: 'Báo cáo Tổng hợp'),
            'profile' => $profile,
        ];
    }

    public function assertConfigured(string $profile = 'daily'): array
    {
        $config = $this->getConfig($profile);
        if ($config['sheet_id'] === '' || $config['email'] === '' || $config['private_key'] === '') {
            $message = $profile === 'summary'
                ? 'Cấu hình Google Sheet báo cáo tổng hợp học kỳ chưa hoàn thiện trong Tham số hệ thống (tab Tích hợp).'
                : 'Cấu hình Google Sheets chưa hoàn thiện trong Tham số hệ thống.';

            throw new \RuntimeException($message);
        }

        return $config;
    }

    public function getTabs(?string $tabKey = null, string $profile = 'daily'): array
    {
        $config = $this->assertConfigured($profile);
        $token = $this->accessToken($config, 'https://www.googleapis.com/auth/spreadsheets.readonly');

        $titles = $this->listSheetTitles($config, $token);

        $result = [
            'tabs' => $titles,
            'default_tab' => $config['default_tab'],
            'tab_suggestions' => $profile === 'summary'
                ? DailyReportService::interactiveReportGoogleSheetTabSuggestions()
                : DailyReportService::googleSheetTabSuggestions(),
        ];

        if ($tabKey !== null && $tabKey !== '') {
            $normalizedKey = $this->normalizeTabKey($tabKey);
            $result['suggested_tab'] = DailyReportService::pickGoogleSheetTab(
                $normalizedKey,
                $titles,
                $config['default_tab'] !== '' ? $config['default_tab'] : null,
            );
        }

        return $result;
    }

    public function resolvePushTabName(string $tabKey, string $requestedTab, string $profile = 'daily'): string
    {
        if ($tabKey === '') {
            return trim($requestedTab);
        }

        try {
            $normalizedKey = $this->normalizeTabKey($tabKey);
            $tabs = $this->getTabs($normalizedKey, $profile);
            $resolved = DailyReportService::pickGoogleSheetTab(
                $normalizedKey,
                $tabs['tabs'] ?? [],
                trim($requestedTab),
            );

            return $resolved ?? trim($requestedTab);
        } catch (\Throwable) {
            return trim($requestedTab);
        }
    }

    public static function matchTabTitle(array $titles, string $requested): ?string
    {
        $requested = trim($requested);
        if ($requested === '') {
            return null;
        }

        if (in_array($requested, $titles, true)) {
            return $requested;
        }

        $requestedNorm = self::normalizeTabName($requested);
        foreach ($titles as $title) {
            if (self::normalizeTabName($title) === $requestedNorm) {
                return $title;
            }
        }

        foreach ($titles as $title) {
            $titleNorm = self::normalizeTabName($title);
            if (str_contains($titleNorm, $requestedNorm) || str_contains($requestedNorm, $titleNorm)) {
                return $title;
            }
        }

        return null;
    }

    private const FAQ_MATCH_THRESHOLD = 0.72;

    /** @var array<string, list<array{question: string, answer: string}>> */
    private array $faqEntriesCache = [];

    public function findFaqAnswer(string $question, ?string $tabName = null): ?string
    {
        $entries = $this->loadFaqEntries($tabName);
        if ($entries === []) {
            return null;
        }

        $bestAnswer = null;
        $bestScore = 0.0;

        foreach ($entries as $entry) {
            $score = $this->faqMatchScore($question, $entry['question']);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestAnswer = $entry['answer'];
            }
        }

        if ($bestAnswer !== null && $bestScore >= self::FAQ_MATCH_THRESHOLD) {
            return $bestAnswer;
        }

        return null;
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    public function loadFaqEntries(?string $tabName = null): array
    {
        $tab = $this->resolveFaqTabName($tabName);
        if (isset($this->faqEntriesCache[$tab])) {
            return $this->faqEntriesCache[$tab];
        }

        $config = $this->assertConfigured();
        $token = $this->accessToken($config, 'https://www.googleapis.com/auth/spreadsheets.readonly');
        $rows = $this->fetchSheetValues($config, $token, $tab);

        $entries = [];
        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $question = trim((string) ($row[0] ?? ''));
            $answer = trim((string) ($row[1] ?? ''));

            if ($question === '' || $answer === '') {
                continue;
            }

            if ($index === 0 && $this->looksLikeFaqHeader($question)) {
                continue;
            }

            $entries[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        $this->faqEntriesCache[$tab] = $entries;

        return $entries;
    }

    private function resolveFaqTabName(?string $tabName): string
    {
        $tab = trim((string) ($tabName ?? $this->parameters->get('faqSheetTabName') ?? 'FAQ'));

        return $tab !== '' ? $tab : 'FAQ';
    }

    private function looksLikeFaqHeader(string $text): bool
    {
        $normalized = mb_strtolower(trim($text));

        return str_contains($normalized, 'câu hỏi')
            || str_contains($normalized, 'question')
            || $normalized === 'hỏi'
            || $normalized === 'faq';
    }

    private function faqMatchScore(string $query, string $candidate): float
    {
        $queryNorm = $this->normalizeFaqText($query);
        $candidateNorm = $this->normalizeFaqText($candidate);

        if ($queryNorm === '' || $candidateNorm === '') {
            return 0.0;
        }

        if ($queryNorm === $candidateNorm) {
            return 1.0;
        }

        if (str_contains($candidateNorm, $queryNorm) || str_contains($queryNorm, $candidateNorm)) {
            $shorter = min(mb_strlen($queryNorm), mb_strlen($candidateNorm));
            $longer = max(mb_strlen($queryNorm), mb_strlen($candidateNorm));

            return $longer > 0 ? 0.88 + (0.1 * ($shorter / $longer)) : 0.88;
        }

        similar_text($queryNorm, $candidateNorm, $charPercent);
        $charScore = $charPercent / 100;

        $queryTokens = $this->faqTokens($queryNorm);
        $candidateTokens = $this->faqTokens($candidateNorm);

        if ($queryTokens === [] || $candidateTokens === []) {
            return $charScore;
        }

        $intersection = count(array_intersect($queryTokens, $candidateTokens));
        $union = count(array_unique(array_merge($queryTokens, $candidateTokens)));
        $jaccard = $union > 0 ? $intersection / $union : 0.0;

        $queryCoverage = count(array_intersect($queryTokens, $candidateTokens)) / count($queryTokens);
        $tokenScore = max($jaccard, $queryCoverage * 0.95);

        return max($charScore, $tokenScore);
    }

    private function normalizeFaqText(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = preg_replace('/[?.!,;:\-–—\'"“”()]/u', '', $text) ?? $text;

        return trim($text);
    }

    /**
     * @return list<string>
     */
    private function faqTokens(string $text): array
    {
        $stopWords = [
            'la', 'là', 'va', 'và', 'cua', 'của', 'cho', 'khi', 'thi', 'thì',
            'duoc', 'được', 'khong', 'không', 'nhu', 'như', 'the', 'thế', 'nao', 'nào',
            'gi', 'gì', 'o', 'ở', 'tai', 'tại', 'mot', 'một', 'cac', 'các', 'co', 'có',
            'hay', 'hoac', 'hoặc', 'neu', 'nếu', 'toi', 'tôi', 'ban', 'bạn', 'la', 'là',
        ];

        $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(
            $tokens,
            static fn (string $token) => mb_strlen($token) >= 2 && ! in_array($token, $stopWords, true),
        ));
    }

    public function searchFaqContext(string $question, ?string $tabName = null): string
    {
        $answer = $this->findFaqAnswer($question, $tabName);

        return $answer !== null ? $answer : '';
    }

    /**
     * @param  array<int, array<string, mixed>>  $data
     * @param  array<int, string>  $fields
     */
    public function pushDynamic(array $data, array $fields, string $tabName, string $profile = 'daily'): array
    {
        $config = $this->assertConfigured($profile);
        $token = $this->accessToken($config);

        $sheetTitle = $tabName !== '' ? $tabName : 'Dữ liệu Xuất';
        $sheetMeta = $this->getSheetMeta($config, $token, $sheetTitle, true);
        $sheetTitle = $sheetMeta['title'];

        $columnCount = count($fields) + 1;
        try {
            $rowsBySheetRow = $this->loadSheetRowMapForPush(
                $config,
                $token,
                $sheetTitle,
                $sheetMeta['row_count'],
                $columnCount,
            );
            $analysis = $this->analyzeExistingRows($fields, $rowsBySheetRow, $sheetMeta['row_count'], $columnCount);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $newData = collect($data)->filter(function (array $item) use ($fields, $analysis) {
            $fingerprint = $this->rowFingerprintFromItem($item, $fields);

            return $fingerprint !== '' && ! isset($analysis['fingerprints'][$fingerprint]);
        })->values()->all();

        if ($newData === []) {
            return [
                'success' => true,
                'message' => 'Tất cả dữ liệu này đã tồn tại trên Sheet (trùng khớp nội dung). Không có gì để cập nhật.',
            ];
        }

        $newRows = [];
        $maxSttValue = $analysis['maxSttValue'];
        $startRow = $analysis['nextInsertRow'];
        $nextAutoStt = $maxSttValue;

        $this->ensureGridCapacityForWrite(
            $config,
            $token,
            $sheetTitle,
            $startRow,
            count($newData),
            $columnCount,
        );

        $this->hydrateColumnAForRows(
            $config,
            $token,
            $sheetTitle,
            $rowsBySheetRow,
            $startRow,
            count($newData),
        );

        foreach ($newData as $index => $item) {
            $sheetRow = $startRow + $index;
            $existing = trim((string) ($rowsBySheetRow[$sheetRow][0] ?? ''));
            if ($this->isNumericSttCell($existing)) {
                $stt = (int) $existing;
            } else {
                $nextAutoStt++;
                $stt = $nextAutoStt;
            }

            $row = [$stt];
            foreach ($fields as $field) {
                $row[] = $this->formatCellValue($field, $item[$field] ?? '');
            }
            $newRows[] = $row;
        }

        try {
            $writeResult = $this->appendSheetValuesAtRow(
                $config,
                $token,
                $sheetTitle,
                $startRow,
                count($fields) + 1,
                $newRows,
                $rowsBySheetRow,
            );
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        if ($writeResult['updatedCells'] <= 0) {
            return [
                'success' => false,
                'message' => sprintf(
                    'Google Sheets không ghi được dữ liệu vào "%s" (dòng %d). Kiểm tra tên tab và quyền Editor.',
                    $sheetTitle,
                    $startRow,
                ),
            ];
        }

        $insertedAtRow = $writeResult['startRow'];
        $lastDataRow = $analysis['nextInsertRow'] > 1 ? $analysis['nextInsertRow'] - 1 : 0;

        return [
            'success' => true,
            'message' => sprintf(
                '[THÀNH CÔNG] Đã chèn %d dòng mới vào trang "%s" (dòng %d; STT tiếp theo từ %d; dòng dữ liệu cuối: %d). Bỏ qua %d dòng trùng.',
                count($newData),
                $sheetTitle,
                $insertedAtRow > 0 ? $insertedAtRow : 0,
                $maxSttValue + 1,
                $lastDataRow,
                count($data) - count($newData),
            ),
        ];
    }

    /**
     * @param  array<int, string>  $fields
     * @param  array<int, array<int, string>>  $rowsBySheetRow  1-based sheet row => column values
     * @return array{maxSttValue: int, nextInsertRow: int, fingerprints: array<string, true>}
     */
    private function analyzeExistingRows(
        array $fields,
        array $rowsBySheetRow,
        int $rowCount,
        int $columnCount,
    ): array {
        $layout = $this->resolveTableLayout($rowsBySheetRow, $rowCount, $columnCount);
        $fingerprints = [];

        foreach ($rowsBySheetRow as $sheetRow => $cells) {
            if ($sheetRow < $layout['dataStartRow'] || $sheetRow > $layout['searchEndRow']) {
                continue;
            }

            $row = $this->buildRowSlice($cells, $columnCount);
            if (! $this->isDataRecordRow($row)) {
                continue;
            }

            $fingerprint = $this->rowFingerprintFromSheetRow($row, $fields);
            if ($fingerprint !== '') {
                $fingerprints[$fingerprint] = true;
            }
        }

        return [
            'maxSttValue' => $layout['maxSttValue'],
            'nextInsertRow' => $layout['nextInsertRow'],
            'fingerprints' => $fingerprints,
        ];
    }

    /**
     * @param  array<int, array<int, string>>  $rowsBySheetRow
     * @return array{dataStartRow: int, searchEndRow: int, lastDataRow: int, maxSttValue: int, nextInsertRow: int}
     */
    private function resolveTableLayout(array $rowsBySheetRow, int $rowCount, int $columnCount): array
    {
        $effectiveRowCount = max($rowCount, $rowsBySheetRow === [] ? 0 : max(array_keys($rowsBySheetRow)));
        $footerColumnSpan = max($columnCount, 26);
        $dataStartRow = $this->resolveDataStartRow($rowsBySheetRow, $effectiveRowCount);
        $footerStartRow = $this->resolveFooterStartRow($rowsBySheetRow, $effectiveRowCount, $footerColumnSpan);
        $searchEndRow = $footerStartRow > $dataStartRow
            ? $footerStartRow - 1
            : $effectiveRowCount;

        $lastDataRow = $this->resolveLastDataRow(
            $rowsBySheetRow,
            $dataStartRow,
            $searchEndRow,
            $columnCount,
        );
        $maxSttValue = $this->resolveMaxSttFromDataRows(
            $rowsBySheetRow,
            $dataStartRow,
            $searchEndRow,
            $columnCount,
        );
        $nextInsertRow = $this->resolveFirstEmptyDataRow(
            $rowsBySheetRow,
            $dataStartRow,
            $searchEndRow,
            $columnCount,
        );

        return [
            'dataStartRow' => $dataStartRow,
            'searchEndRow' => $searchEndRow,
            'lastDataRow' => $lastDataRow,
            'maxSttValue' => $maxSttValue,
            'nextInsertRow' => $nextInsertRow,
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function loadSheetRowMapForPush(
        array $config,
        string $token,
        string $sheetTitle,
        int $rowCount,
        int $columnCount,
    ): array {
        $lastCol = $this->columnLetter($columnCount);
        $boundedRows = max($rowCount, 1);
        $rowsBySheetRow = $this->fetchSheetValuesMapped(
            $config,
            $token,
            $sheetTitle,
            "A1:{$lastCol}{$boundedRows}",
        );
        $columnARows = $this->fetchSheetValuesMapped(
            $config,
            $token,
            $sheetTitle,
            "A1:A{$boundedRows}",
        );

        foreach ($columnARows as $sheetRow => $cells) {
            $value = trim((string) ($cells[0] ?? ''));
            if ($value === '') {
                continue;
            }

            if (! isset($rowsBySheetRow[$sheetRow])) {
                $rowsBySheetRow[$sheetRow] = [];
            }

            $rowsBySheetRow[$sheetRow][0] = $value;
        }

        $normalized = [];
        foreach ($rowsBySheetRow as $sheetRow => $cells) {
            $row = [];
            for ($columnIndex = 0; $columnIndex < $columnCount; $columnIndex++) {
                $row[$columnIndex] = trim((string) ($cells[$columnIndex] ?? ''));
            }
            $normalized[$sheetRow] = $row;
        }

        return $normalized;
    }

    /**
     * Đọc lại STT thực tế trên sheet ngay trước khi ghi (tránh ghi đè STT đánh sẵn).
     *
     * @param  array<int, array<int, string>>  $rowsBySheetRow
     */
    private function hydrateColumnAForRows(
        array $config,
        string $token,
        string $sheetTitle,
        array &$rowsBySheetRow,
        int $startRow,
        int $rowCount,
    ): void {
        if ($rowCount <= 0 || $startRow < 1) {
            return;
        }

        $endRow = $startRow + $rowCount - 1;
        $this->ensureGridCapacity($config, $token, $sheetTitle, $endRow, 1);

        $mapped = $this->fetchSheetValuesMapped(
            $config,
            $token,
            $sheetTitle,
            "A{$startRow}:A{$endRow}",
        );

        foreach ($mapped as $sheetRow => $cells) {
            $value = trim((string) ($cells[0] ?? ''));
            if ($value === '') {
                continue;
            }

            if (! isset($rowsBySheetRow[$sheetRow])) {
                $rowsBySheetRow[$sheetRow] = [];
            }

            $rowsBySheetRow[$sheetRow][0] = $value;
        }
    }

    /**
     * Đọc vùng sheet qua Values API, map đúng số dòng từ trường range (không dùng index+1).
     *
     * @return array<int, array<int, string>> 1-based sheet row => column values
     */
    private function fetchSheetValuesMapped(
        array $config,
        string $token,
        string $sheetTitle,
        string $cellRange,
    ): array {
        $rangePath = $this->encodeValuesRangePath($sheetTitle, $cellRange);
        $response = Http::withToken($token)
            ->get("https://sheets.googleapis.com/v4/spreadsheets/{$config['sheet_id']}/values/{$rangePath}");

        if (! $response->successful()) {
            throw new \RuntimeException($this->apiError($response));
        }

        $startRow = $this->parseRangeStartRow((string) $response->json('range', ''));
        $mapped = [];

        foreach ($response->json('values', []) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $mapped[$startRow + $index] = array_map(
                static fn ($value) => trim((string) $value),
                $row,
            );
        }

        return $mapped;
    }

    private function parseRangeStartRow(string $range): int
    {
        if (preg_match('/![A-Z]+(\d+)/i', $range, $matches)) {
            return max(1, (int) $matches[1]);
        }

        return 1;
    }

    /**
     * @param  array<int, array<int, string>>  $rowsBySheetRow
     */
    private function resolveDataStartRow(array $rowsBySheetRow, int $rowCount): int
    {
        $limit = min($rowCount, 40);

        for ($sheetRow = 1; $sheetRow <= $limit; $sheetRow++) {
            $colA = mb_strtolower(trim((string) ($rowsBySheetRow[$sheetRow][0] ?? '')));
            if (in_array($colA, ['stt', '#', 'tt'], true)) {
                $candidate = $sheetRow + 1;
                if ($this->isLikelySubHeaderRow($rowsBySheetRow[$candidate] ?? [])) {
                    return $candidate + 1;
                }

                return $candidate;
            }
        }

        for ($sheetRow = 2; $sheetRow <= $limit; $sheetRow++) {
            if ($this->isNumericSttCell($rowsBySheetRow[$sheetRow][0] ?? '')) {
                return $sheetRow;
            }
        }

        return 2;
    }

    /**
     * Dòng tiêu đề cột con (vd. VIPHAM: hàng 7 — cột A–D trống, cột E+ là tên loại vi phạm).
     *
     * @param  array<int, string>  $cells
     */
    private function isLikelySubHeaderRow(array $cells): bool
    {
        $colA = trim((string) ($cells[0] ?? ''));
        if ($colA !== '') {
            return false;
        }

        $hasEarlyContent = false;
        for ($columnIndex = 1; $columnIndex <= 3; $columnIndex++) {
            if ($this->cellHasMeaningfulContent($cells[$columnIndex] ?? '')) {
                $hasEarlyContent = true;
                break;
            }
        }

        if ($hasEarlyContent) {
            return false;
        }

        for ($columnIndex = 4; $columnIndex < count($cells); $columnIndex++) {
            if ($this->cellHasMeaningfulContent($cells[$columnIndex] ?? '')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<int, string>>  $rowsBySheetRow
     */
    private function resolveFooterStartRow(array $rowsBySheetRow, int $rowCount, int $columnCount): int
    {
        $scanFrom = max(2, $rowCount - 30);

        for ($sheetRow = $rowCount; $sheetRow >= $scanFrom; $sheetRow--) {
            $row = $this->buildRowSlice($rowsBySheetRow[$sheetRow] ?? [], $columnCount);
            if ($this->isSignatureFooterRow($row)) {
                return $sheetRow;
            }
        }

        return 0;
    }

    /**
     * Dòng cuối có dữ liệu: ít nhất một cột dữ liệu (B trở đi) có giá trị.
     *
     * @param  array<int, array<int, string>>  $rowsBySheetRow
     */
    private function resolveLastDataRow(
        array $rowsBySheetRow,
        int $dataStartRow,
        int $searchEndRow,
        int $columnCount,
    ): int {
        for ($sheetRow = $searchEndRow; $sheetRow >= $dataStartRow; $sheetRow--) {
            $cells = $rowsBySheetRow[$sheetRow] ?? [];
            if ($this->rowHasDataColumnContent($cells, $columnCount)) {
                return $sheetRow;
            }
        }

        return 0;
    }

    /**
     * Dòng ghi tiếp theo: dòng trống đầu tiên (cột B đến cột dữ liệu cuối đều trống).
     *
     * @param  array<int, array<int, string>>  $rowsBySheetRow
     */
    private function resolveFirstEmptyDataRow(
        array $rowsBySheetRow,
        int $dataStartRow,
        int $searchEndRow,
        int $columnCount,
    ): int {
        for ($sheetRow = $dataStartRow; $sheetRow <= $searchEndRow; $sheetRow++) {
            $cells = $rowsBySheetRow[$sheetRow] ?? [];
            if ($this->isRowDataEmpty($cells, $columnCount)) {
                return $sheetRow;
            }
        }

        $lastDataRow = $this->resolveLastDataRow(
            $rowsBySheetRow,
            $dataStartRow,
            $searchEndRow,
            $columnCount,
        );

        return $lastDataRow >= $dataStartRow ? $lastDataRow + 1 : $dataStartRow;
    }

    /**
     * STT lớn nhất trên các dòng đã có dữ liệu (để tạo STT liên tục: 10 → 11).
     *
     * @param  array<int, array<int, string>>  $rowsBySheetRow
     */
    private function resolveMaxSttFromDataRows(
        array $rowsBySheetRow,
        int $dataStartRow,
        int $endRow,
        int $columnCount,
    ): int {
        $maxSttValue = 0;

        for ($sheetRow = $dataStartRow; $sheetRow <= $endRow; $sheetRow++) {
            $cells = $rowsBySheetRow[$sheetRow] ?? [];
            if (! $this->rowHasDataColumnContent($cells, $columnCount)) {
                continue;
            }

            $sttRaw = trim((string) ($cells[0] ?? ''));
            if (! $this->isNumericSttCell($sttRaw)) {
                continue;
            }

            $stt = (int) $sttRaw;
            if ($stt > $maxSttValue) {
                $maxSttValue = $stt;
            }
        }

        return $maxSttValue;
    }

    /**
     * @param  array<int, string>  $cells
     */
    private function buildRowSlice(array $cells, int $columnCount): array
    {
        $row = [];
        for ($columnIndex = 0; $columnIndex < $columnCount; $columnIndex++) {
            $row[] = $cells[$columnIndex] ?? '';
        }

        return $row;
    }

    /**
     * Dòng trống = tất cả cột dữ liệu (B đến cột cuối) không có giá trị (cột A/STT không tính).
     *
     * @param  array<int, string>  $cells
     */
    private function isRowDataEmpty(array $cells, int $columnCount): bool
    {
        return ! $this->rowHasDataColumnContent($cells, $columnCount);
    }

    /**
     * Có nội dung ở ít nhất một cột dữ liệu (B trở đi, đến cột cuối của bảng).
     *
     * @param  array<int, string>  $cells
     */
    private function rowHasDataColumnContent(array $cells, int $columnCount): bool
    {
        $lastDataColumn = max(1, $columnCount - 1);

        for ($columnIndex = 1; $columnIndex <= $lastDataColumn; $columnIndex++) {
            if ($this->cellHasMeaningfulContent($cells[$columnIndex] ?? '')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ghi vào dòng cuối: chèn dòng mới chỉ khi sẽ ghi đè dữ liệu B+ đã có.
     *
     * @param  list<list<mixed>>  $rows
     * @param  array<int, array<int, string>>  $rowsBySheetRow
     * @return array{updatedCells: int, startRow: int}
     */
    private function appendSheetValuesAtRow(
        array $config,
        string $token,
        string $sheetTitle,
        int $startRow,
        int $columnCount,
        array $rows,
        array $rowsBySheetRow,
    ): array {
        if ($rows === []) {
            return ['updatedCells' => 0, 'startRow' => $startRow];
        }

        if ($this->shouldInsertRowsBeforeWrite($rowsBySheetRow, $startRow, count($rows), $columnCount)) {
            $this->insertSheetRows($config, $token, $sheetTitle, $startRow, count($rows));
        }

        return $this->writeSheetValues($config, $token, $sheetTitle, $startRow, $columnCount, $rows, $rowsBySheetRow);
    }

    private function shouldInsertRowsBeforeWrite(
        array $rowsBySheetRow,
        int $startRow,
        int $rowCount,
        int $columnCount,
    ): bool {
        for ($offset = 0; $offset < $rowCount; $offset++) {
            $cells = $rowsBySheetRow[$startRow + $offset] ?? [];
            if ($this->rowHasDataColumnContent($cells, $columnCount)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Chèn dòng mới rồi ghi dữ liệu (đẩy nội dung phía dưới xuống, không ghi đè).
     *
     * @param  list<list<mixed>>  $rows
     * @return array{updatedCells: int, startRow: int}
     */
    private function insertSheetValuesAtRow(
        array $config,
        string $token,
        string $sheetTitle,
        int $startRow,
        int $columnCount,
        array $rows,
    ): array {
        if ($rows === []) {
            return ['updatedCells' => 0, 'startRow' => $startRow];
        }

        $this->insertSheetRows($config, $token, $sheetTitle, $startRow, count($rows));

        return $this->writeSheetValues($config, $token, $sheetTitle, $startRow, $columnCount, $rows);
    }

    /**
     * Chèn thêm dòng trống tại vị trí chỉ định (Google Sheets insertDimension).
     */
    private function insertSheetRows(
        array $config,
        string $token,
        string $tabName,
        int $startRow,
        int $rowsToInsert,
    ): void {
        if ($rowsToInsert <= 0 || $startRow < 1) {
            return;
        }

        $meta = $this->getSheetMeta($config, $token, $tabName);
        $startIndex = $startRow - 1;

        if ($startIndex > $meta['row_count']) {
            $this->ensureGridCapacity(
                $config,
                $token,
                $tabName,
                $startIndex + $rowsToInsert,
                $meta['column_count'],
            );
            $meta = $this->getSheetMeta($config, $token, $tabName);
        }

        $insertAt = min($startIndex, $meta['row_count']);

        $response = Http::withToken($token)
            ->post("https://sheets.googleapis.com/v4/spreadsheets/{$config['sheet_id']}:batchUpdate", [
                'requests' => [[
                    'insertDimension' => [
                        'range' => [
                            'sheetId' => $meta['sheet_id'],
                            'dimension' => 'ROWS',
                            'startIndex' => $insertAt,
                            'endIndex' => $insertAt + $rowsToInsert,
                        ],
                        'inheritFromBefore' => false,
                    ],
                ]],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException($this->apiError($response));
        }
    }

    /**
     * Ghi dữ liệu vào dòng xác định. Giữ nguyên cột STT nếu ô A đã có số.
     *
     * @param  list<list<mixed>>  $rows
     * @param  array<int, array<int, string>>  $rowsBySheetRow
     * @return array{updatedCells: int, startRow: int}
     */
    private function writeSheetValues(
        array $config,
        string $token,
        string $sheetTitle,
        int $startRow,
        int $columnCount,
        array $rows,
        array $rowsBySheetRow = [],
    ): array {
        if ($rows === []) {
            return ['updatedCells' => 0, 'startRow' => $startRow];
        }

        $endRow = $startRow + count($rows) - 1;
        $endCol = $this->columnLetter($columnCount);
        $this->ensureGridCapacity($config, $token, $sheetTitle, $endRow, $columnCount);

        $writeSttColumn = false;
        for ($offset = 0; $offset < count($rows); $offset++) {
            $sheetRow = $startRow + $offset;
            $existing = trim((string) ($rowsBySheetRow[$sheetRow][0] ?? ''));
            if (! $this->isNumericSttCell($existing)) {
                $writeSttColumn = true;
                break;
            }
        }

        if ($writeSttColumn) {
            $rangePath = $this->encodeValuesRangePath($sheetTitle, "A{$startRow}:{$endCol}{$endRow}");
            $values = $rows;
        } else {
            $rangePath = $this->encodeValuesRangePath($sheetTitle, "B{$startRow}:{$endCol}{$endRow}");
            $values = array_map(
                static fn (array $row) => array_slice($row, 1),
                $rows,
            );
        }

        $response = Http::withToken($token)
            ->withQueryParameters(['valueInputOption' => 'USER_ENTERED'])
            ->put(
                "https://sheets.googleapis.com/v4/spreadsheets/{$config['sheet_id']}/values/{$rangePath}",
                ['values' => $values],
            );

        if (! $response->successful()) {
            throw new \RuntimeException($this->apiError($response));
        }

        return [
            'updatedCells' => (int) ($response->json('updatedCells') ?? 0),
            'startRow' => $startRow,
        ];
    }

    /**
     * @param  list<list<mixed>>  $rows
     * @return array{updatedCells: int, startRow: int}
     */
    private function appendSheetValues(
        array $config,
        string $token,
        string $sheetTitle,
        int $columnCount,
        array $rows,
    ): array {
        $endCol = $this->columnLetter($columnCount);
        $rangePath = $this->encodeValuesRangePath($sheetTitle, "A:{$endCol}");

        $response = Http::withToken($token)
            ->withQueryParameters([
                'valueInputOption' => 'USER_ENTERED',
                'insertDataOption' => 'INSERT_ROWS',
            ])
            ->post(
                "https://sheets.googleapis.com/v4/spreadsheets/{$config['sheet_id']}/values/{$rangePath}:append",
                ['values' => $rows],
            );

        if (! $response->successful()) {
            throw new \RuntimeException($this->apiError($response));
        }

        return [
            'updatedCells' => (int) ($response->json('updates.updatedCells') ?? 0),
            'startRow' => $this->parseStartRowFromUpdatedRange(
                (string) ($response->json('updates.updatedRange') ?? ''),
            ),
        ];
    }

    private function parseStartRowFromUpdatedRange(string $updatedRange): int
    {
        if (preg_match('/![A-Z]+(\d+)/', $updatedRange, $matches)) {
            return max(1, (int) $matches[1]);
        }

        return 0;
    }

    private function isNumericSttCell(mixed $value): bool
    {
        $value = trim((string) $value);

        return $value !== '' && is_numeric($value);
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function rowHasMeaningfulData(array $row): bool
    {
        if ($this->isFooterOrSignatureRow($row)) {
            return false;
        }

        foreach ($row as $value) {
            if ($this->cellHasMeaningfulContent($value)) {
                return true;
            }
        }

        return false;
    }

    private function cellHasMeaningfulContent(mixed $value): bool
    {
        $text = trim((string) $value);

        return $text !== '' && strtoupper($text) !== 'FALSE';
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isDataRecordRow(array $row): bool
    {
        if ($this->isFooterOrSignatureRow($row)) {
            return false;
        }

        if ($this->isLikelySubHeaderRow($row)) {
            return false;
        }

        for ($i = 1; $i < count($row); $i++) {
            if ($this->cellHasMeaningfulContent($row[$i] ?? '')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isFooterOrSignatureRow(array $row): bool
    {
        return $this->isSignatureFooterRow($row);
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isSignatureFooterRow(array $row): bool
    {
        $text = mb_strtolower(implode(' ', array_map(
            fn ($value) => trim((string) $value),
            $row
        )));

        if ($text === '') {
            return false;
        }

        foreach (['người báo cáo', 'chu ky', 'chữ ký', '(chữ ký)'] as $phrase) {
            if (str_contains($text, $phrase)) {
                return true;
            }
        }

        if (str_contains($text, 'hồ chí minh') && preg_match('/tháng|ngay|năm|nam/i', $text)) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<int, string>  $fields
     */
    private function rowFingerprintFromSheetRow(array $row, array $fields): string
    {
        $parts = [];
        foreach ($fields as $fieldIndex => $field) {
            $parts[] = $this->normalizedFieldValue($field, $row[$fieldIndex + 1] ?? '');
        }

        return implode('|', $parts);
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<int, string>  $fields
     */
    private function rowFingerprintFromItem(array $item, array $fields): string
    {
        $parts = [];
        foreach ($fields as $field) {
            $parts[] = $this->normalizedFieldValue($field, $item[$field] ?? '');
        }

        return implode('|', $parts);
    }

    private function normalizedFieldValue(string $field, mixed $value): string
    {
        if ($field === 'is_notification') {
            return $this->coerceNotificationValue($value) ? 'true' : 'false';
        }

        return trim((string) $this->formatCellValue($field, $value));
    }

    private function coerceNotificationValue(mixed $value): bool
    {
        return DailyReportService::normalizeNotificationValue($value);
    }

    private function cleanPrivateKey(string $key): string
    {
        $cleaned = trim($key);
        $cleaned = preg_replace('/^"|"$/', '', $cleaned) ?? $cleaned;

        return str_replace('\\n', "\n", $cleaned);
    }

    private function accessToken(array $config, string $scope = self::SCOPE): string
    {
        $credentials = new ServiceAccountCredentials($scope, [
            'client_email' => $config['email'],
            'private_key' => $config['private_key'],
        ]);

        $httpHandler = HttpSslConfigurator::googleHttpHandler();
        $token = $credentials->fetchAuthToken($httpHandler);
        if (empty($token['access_token'])) {
            throw new \RuntimeException('Không thể xác thực Google Service Account.');
        }

        return $token['access_token'];
    }

    private function assertSheetExists(array $config, string $token, string $tabName): void
    {
        $this->getSheetMeta($config, $token, $tabName);
    }

    /**
     * @return array{sheet_id: int, row_count: int, column_count: int, title: string}
     */
    private function getSheetMeta(array $config, string $token, string $tabName, bool $autoCreate = false): array
    {
        $sheets = $this->fetchSpreadsheetSheets($config, $token);
        $titles = array_map(
            fn (array $sheet) => (string) ($sheet['properties']['title'] ?? ''),
            $sheets,
        );
        $resolvedTitle = self::matchTabTitle($titles, $tabName);

        if ($resolvedTitle === null) {
            $trimmedTabName = trim($tabName);
            if ($autoCreate && $trimmedTabName !== '') {
                $this->createSheetTab($config, $token, $trimmedTabName);

                return $this->getSheetMeta($config, $token, $trimmedTabName, false);
            }

            $available = implode('", "', array_values(array_filter($titles)));
            $hint = $available !== ''
                ? "Các tab hiện có: \"{$available}\". "
                : '';

            throw new \RuntimeException(
                "KHÔNG TÌM THẤY trang tính tên là \"{$tabName}\". {$hint}Vui lòng kiểm tra lại tên Tab trong cài đặt hoặc chọn tab khác."
            );
        }

        foreach ($sheets as $sheet) {
            $props = $sheet['properties'] ?? [];
            if (($props['title'] ?? '') !== $resolvedTitle) {
                continue;
            }

            $grid = $props['gridProperties'] ?? [];

            if (! array_key_exists('sheetId', $props)) {
                throw new \RuntimeException("Không xác định được sheet ID cho tab \"{$resolvedTitle}\".");
            }

            return [
                'sheet_id' => (int) $props['sheetId'],
                'row_count' => (int) ($grid['rowCount'] ?? 1000),
                'column_count' => (int) ($grid['columnCount'] ?? 26),
                'title' => $resolvedTitle,
            ];
        }

        throw new \RuntimeException("Không xác định được metadata cho tab \"{$resolvedTitle}\".");
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchSpreadsheetSheets(array $config, string $token): array
    {
        $response = Http::withToken($token)
            ->get("https://sheets.googleapis.com/v4/spreadsheets/{$config['sheet_id']}", [
                'fields' => 'sheets.properties(sheetId,title,gridProperties(rowCount,columnCount))',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException($this->apiError($response));
        }

        return $response->json('sheets', []);
    }

    /**
     * @return list<string>
     */
    private function listSheetTitles(array $config, string $token): array
    {
        return collect($this->fetchSpreadsheetSheets($config, $token))
            ->map(fn (array $sheet) => $sheet['properties']['title'] ?? '')
            ->filter()
            ->values()
            ->all();
    }

    private function createSheetTab(array $config, string $token, string $tabName): void
    {
        $response = Http::withToken($token)
            ->post("https://sheets.googleapis.com/v4/spreadsheets/{$config['sheet_id']}:batchUpdate", [
                'requests' => [[
                    'addSheet' => [
                        'properties' => ['title' => $tabName],
                    ],
                ]],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Không thể tạo tab mới: '.$this->apiError($response));
        }
    }

    private static function normalizeTabName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return mb_strtolower($name);
    }

    private function ensureGridCapacityForWrite(
        array $config,
        string $token,
        string $tabName,
        int $startRow,
        int $rowCount,
        int $columnCount,
    ): void {
        if ($rowCount <= 0 || $startRow < 1) {
            return;
        }

        $endRow = $startRow + $rowCount - 1;
        $this->ensureGridCapacity($config, $token, $tabName, $endRow, $columnCount);
    }

    private function ensureGridCapacity(
        array $config,
        string $token,
        string $tabName,
        int $requiredLastRow,
        int $requiredLastColumn,
    ): void {
        $meta = $this->getSheetMeta($config, $token, $tabName);

        $rowsToAdd = max(0, $requiredLastRow - $meta['row_count']);
        $columnsToAdd = max(0, $requiredLastColumn - $meta['column_count']);

        if ($rowsToAdd === 0 && $columnsToAdd === 0) {
            return;
        }

        $requests = [];
        if ($rowsToAdd > 0) {
            $requests[] = [
                'appendDimension' => [
                    'sheetId' => $meta['sheet_id'],
                    'dimension' => 'ROWS',
                    'length' => $rowsToAdd,
                ],
            ];
        }
        if ($columnsToAdd > 0) {
            $requests[] = [
                'appendDimension' => [
                    'sheetId' => $meta['sheet_id'],
                    'dimension' => 'COLUMNS',
                    'length' => $columnsToAdd,
                ],
            ];
        }

        $response = Http::withToken($token)
            ->post("https://sheets.googleapis.com/v4/spreadsheets/{$config['sheet_id']}:batchUpdate", [
                'requests' => $requests,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException($this->apiError($response));
        }
    }

    private function fetchSheetValues(array $config, string $token, string $sheetTitle): array
    {
        $rangePath = $this->encodeValuesRangePath($sheetTitle, 'A:ZZ');
        $response = Http::withToken($token)
            ->get("https://sheets.googleapis.com/v4/spreadsheets/{$config['sheet_id']}/values/{$rangePath}");

        if (! $response->successful()) {
            throw new \RuntimeException($this->apiError($response));
        }

        return $response->json('values', []);
    }

    private function a1Range(string $sheetTitle, string $cells): string
    {
        $escapedTitle = str_replace("'", "''", $sheetTitle);

        return "'{$escapedTitle}'!{$cells}";
    }

    private function encodeValuesRangePath(string $sheetTitle, string $cells): string
    {
        return rawurlencode($this->a1Range($sheetTitle, $cells));
    }

    private function columnLetter(int $columnIndex): string
    {
        $letter = '';
        while ($columnIndex > 0) {
            $columnIndex--;
            $letter = chr(65 + ($columnIndex % 26)).$letter;
            $columnIndex = intdiv($columnIndex, 26);
        }

        return $letter !== '' ? $letter : 'A';
    }

    private function formatCellValue(string $field, mixed $value): mixed
    {
        if ($field === '__gap__') {
            return '';
        }

        if ($field === 'is_notification') {
            return $this->coerceNotificationValue($value);
        }

        if ($value === '' || $value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value) && (float) $value == 0.0) {
            return '';
        }

        return (string) $value;
    }

    private function apiError(Response $response): string
    {
        $message = $response->json('error.message');

        return is_string($message) && $message !== ''
            ? $message
            : 'Lỗi kết nối Google Sheets (HTTP '.$response->status().').';
    }

    public function verifyConnection(string $sheetId, string $email, string $privateKey, ?string $tabName = null): array
    {
        $finalSheetId = $this->normalizeSheetId($sheetId);
        $email = trim($email);
        $privateKey = $this->cleanPrivateKey($privateKey);

        if ($finalSheetId === '' || $email === '' || $privateKey === '') {
            return ['success' => false, 'message' => 'Vui lòng nhập đầy đủ Sheet ID, Email và Private Key.'];
        }

        try {
            $token = $this->accessToken([
                'email' => $email,
                'private_key' => $privateKey,
            ], self::SCOPE);

            $response = Http::withToken($token)
                ->get("https://sheets.googleapis.com/v4/spreadsheets/{$finalSheetId}", [
                    'fields' => 'properties.title,sheets.properties.title',
                ]);

            if (! $response->successful()) {
                return ['success' => false, 'message' => $this->formatGoogleError($this->apiError($response), $finalSheetId)];
            }

            $title = (string) $response->json('properties.title', 'Google Sheet');
            $tabMsg = '';

            if ($tabName) {
                $titles = collect($response->json('sheets', []))
                    ->map(fn ($sheet) => $sheet['properties']['title'] ?? '')
                    ->all();
                $tabMsg = in_array($tabName, $titles, true)
                    ? " Đã tìm thấy Tab \"{$tabName}\"."
                    : " Lưu ý: Không tìm thấy Tab \"{$tabName}\", hệ thống sẽ tự tạo mới khi có dữ liệu.";
            }

            return [
                'success' => true,
                'message' => "Kết nối thành công! Đã nhận diện được tệp: \"{$title}\".{$tabMsg}",
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $this->formatGoogleError($e->getMessage(), $finalSheetId)];
        }
    }

    /**
     * @param  array{timestamp?: string, employeeName?: string, proofPrinted?: string, proofOnline?: string, proofIncident?: string, proofFacility?: string}  $row
     */
    public function pushFeedbackRow(array $row): array
    {
        $params = $this->parameters->all();
        $sheetId = $this->normalizeSheetId(trim((string) ($params['feedbackSheetId'] ?? '')));
        if ($sheetId === '') {
            $sheetId = $this->normalizeSheetId((string) config('nttu.feedback_google_sheet_id', ''));
        }
        if ($sheetId === '') {
            $sheetId = $this->normalizeSheetId(trim((string) ($params['googleSheetId'] ?? '')));
        }
        $email = trim((string) ($params['evidenceServiceAccountEmail'] ?? '')) ?: trim((string) ($params['googleServiceAccountEmail'] ?? ''));
        $privateKey = $this->cleanPrivateKey(trim((string) ($params['evidencePrivateKey'] ?? '')) ?: (string) ($params['googlePrivateKey'] ?? ''));
        $tabName = trim((string) ($params['feedbackTabName'] ?? '')) ?: 'Trang tính1';

        if ($sheetId === '' || $email === '' || $privateKey === '') {
            return [
                'success' => false,
                'skipped' => true,
                'message' => 'Chưa cấu hình Google Sheet cho minh chứng ca trực.',
            ];
        }

        $config = [
            'sheet_id' => $this->normalizeSheetId($sheetId),
            'email' => $email,
            'private_key' => $privateKey,
        ];

        try {
            $token = $this->accessToken($config);
            $sheetMeta = $this->getSheetMeta($config, $token, $tabName, true);

            $fields = ['timestamp', 'employeeName', 'proofPrinted', 'proofOnline', 'proofIncident', 'proofFacility'];
            $columnCount = count($fields) + 1;
            $item = [
                'timestamp' => $row['timestamp'] ?? now()->format('d/m/Y H:i:s'),
                'employeeName' => $row['employeeName'] ?? '',
                'proofPrinted' => $row['proofPrinted'] ?? ' ',
                'proofOnline' => $row['proofOnline'] ?? ' ',
                'proofIncident' => $row['proofIncident'] ?? ' ',
                'proofFacility' => $row['proofFacility'] ?? ' ',
            ];

            $rowsBySheetRow = $this->loadSheetRowMapForPush(
                $config,
                $token,
                $tabName,
                $sheetMeta['row_count'],
                $columnCount,
            );
            $analysis = $this->analyzeExistingRows($fields, $rowsBySheetRow, $sheetMeta['row_count'], $columnCount);
            $startRow = $analysis['nextInsertRow'];
            $this->ensureGridCapacityForWrite(
                $config,
                $token,
                $tabName,
                $startRow,
                1,
                $columnCount,
            );
            $this->hydrateColumnAForRows($config, $token, $tabName, $rowsBySheetRow, $startRow, 1);
            $existing = trim((string) ($rowsBySheetRow[$startRow][0] ?? ''));
            if ($this->isNumericSttCell($existing)) {
                $stt = (int) $existing;
            } else {
                $stt = $analysis['maxSttValue'] + 1;
            }

            $newRow = [$stt];
            foreach ($fields as $field) {
                $newRow[] = $this->formatCellValue($field, $item[$field] ?? '');
            }

            $writeResult = $this->appendSheetValuesAtRow(
                $config,
                $token,
                $tabName,
                $startRow,
                count($fields) + 1,
                [$newRow],
                $rowsBySheetRow,
            );

            if ($writeResult['updatedCells'] <= 0) {
                return ['success' => false, 'message' => 'Google Sheets không ghi được dữ liệu minh chứng.'];
            }

            $startRow = $writeResult['startRow'];

            return [
                'success' => true,
                'message' => "Đã ghi vào Google Sheet (tab \"{$tabName}\", dòng ".($startRow > 0 ? $startRow : 'mới').').',
            ];
        } catch (\Throwable $e) {
            $message = $this->formatGoogleError($e->getMessage(), $sheetId);
            if (str_contains(strtolower($e->getMessage()), 'permission')) {
                $message .= " Hãy chia sẻ Google Sheet với quyền Biên tập viên (Editor) cho: {$email}";
            }

            return ['success' => false, 'message' => $message];
        }
    }

    public function uploadFeedbackFile(\Illuminate\Http\UploadedFile $file): ?string
    {
        $params = $this->parameters->all();
        $folderId = $this->normalizeDriveFolderId(trim((string) ($params['googleDriveFolderId'] ?? '')));
        if ($folderId === '') {
            return null;
        }

        $email = trim((string) ($params['evidenceServiceAccountEmail'] ?? ''))
            ?: trim((string) ($params['googleServiceAccountEmail'] ?? ''));
        $privateKey = $this->cleanPrivateKey(
            trim((string) ($params['evidencePrivateKey'] ?? ''))
            ?: (string) ($params['googlePrivateKey'] ?? '')
        );

        if ($email === '' || $privateKey === '') {
            return null;
        }

        try {
            $token = $this->accessToken([
                'email' => $email,
                'private_key' => $privateKey,
            ], 'https://www.googleapis.com/auth/drive');

            $metadata = json_encode([
                'name' => $file->getClientOriginalName(),
                'parents' => [$folderId],
            ], JSON_UNESCAPED_UNICODE);
            $boundary = 'nttu_boundary_'.bin2hex(random_bytes(8));
            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $binary = file_get_contents($file->getRealPath());
            if ($binary === false) {
                return null;
            }

            $body = "--{$boundary}\r\n"
                ."Content-Type: application/json; charset=UTF-8\r\n\r\n"
                .$metadata."\r\n"
                ."--{$boundary}\r\n"
                ."Content-Type: {$mime}\r\n\r\n"
                .$binary."\r\n"
                ."--{$boundary}--";

            $response = Http::withToken($token)
                ->withHeaders(['Content-Type' => "multipart/related; boundary={$boundary}"])
                ->withBody($body, "multipart/related; boundary={$boundary}")
                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,webViewLink,webContentLink&supportsAllDrives=true');

            if (! $response->successful()) {
                return null;
            }

            $fileId = (string) $response->json('id', '');
            if ($fileId === '') {
                return null;
            }

            Http::withToken($token)
                ->post("https://www.googleapis.com/drive/v3/files/{$fileId}/permissions?supportsAllDrives=true", [
                    'role' => 'reader',
                    'type' => 'anyone',
                ]);

            return (string) ($response->json('webViewLink')
                ?: $response->json('webContentLink')
                ?: "https://drive.google.com/file/d/{$fileId}/view");
        } catch (\Throwable) {
            return null;
        }
    }

    public function verifyDriveConnection(string $folderId, string $email, string $privateKey): array
    {
        $folderId = trim($folderId);
        $email = trim($email);
        $privateKey = $this->cleanPrivateKey($privateKey);

        if ($folderId === '' || $email === '' || $privateKey === '') {
            return ['success' => false, 'message' => 'Vui lòng nhập Folder ID, Email và Private Key.'];
        }

        try {
            $token = $this->accessToken([
                'email' => $email,
                'private_key' => $privateKey,
            ], 'https://www.googleapis.com/auth/drive');

            $response = Http::withToken($token)
                ->get("https://www.googleapis.com/drive/v3/files/{$folderId}", [
                    'fields' => 'name,kind,mimeType,capabilities',
                ]);

            if (! $response->successful()) {
                return ['success' => false, 'message' => $this->formatGoogleError($this->apiError($response), $folderId)];
            }

            if (($response->json('mimeType') ?? '') !== 'application/vnd.google-apps.folder') {
                return ['success' => false, 'message' => 'ID này không phải là một Thư mục (Folder).'];
            }

            if (! ($response->json('capabilities.canAddChildren') ?? false)) {
                $name = (string) $response->json('name', '');

                return [
                    'success' => false,
                    'message' => "Kết nối thành công nhưng bạn chỉ có quyền XEM thư mục \"{$name}\". Vui lòng cấp quyền Editor cho Service Account.",
                ];
            }

            $name = (string) $response->json('name', '');

            return [
                'success' => true,
                'message' => "Kết nối Drive hoàn hảo! Thư mục: \"{$name}\" (Đã sẵn sàng để tải lên).",
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $this->formatGoogleError($e->getMessage(), $folderId)];
        }
    }

    private function normalizeSheetId(string $sheetId): string
    {
        $finalSheetId = trim($sheetId);
        if (str_contains($finalSheetId, 'docs.google.com/spreadsheets/d/')) {
            if (preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $finalSheetId, $matches)) {
                $finalSheetId = $matches[1];
            }
        }

        return $finalSheetId;
    }

    private function normalizeDriveFolderId(string $folderId): string
    {
        $folderId = trim($folderId);
        if (preg_match('/[-\w]{25,}/', $folderId, $matches)) {
            return $matches[0];
        }

        return $folderId;
    }

    private function formatGoogleError(string $msg, string $id): string
    {
        if (str_contains($msg, 'invalid argument')) {
            return "Lỗi Google Sheet ID không hợp lệ: \"{$id}\". Xin đảm bảo đây là ID của tệp Excel, không phải ID thư mục!";
        }
        if (str_contains(strtolower($msg), 'invalid_grant')) {
            return 'Lỗi xác thực (invalid_grant): Email hoặc Private Key của Service Account không chính xác.';
        }
        if (str_contains($msg, '404')) {
            return 'Lỗi 404: Không tìm thấy tài nguyên Google. Hãy kiểm tra lại ID.';
        }
        if (str_contains($msg, '403') || str_contains(strtolower($msg), 'does not have permission')) {
            return 'Không có quyền truy cập Google Sheet. Hãy chia sẻ quyền Biên tập viên (Editor) cho email Service Account.';
        }
        if (str_contains($msg, 'SSL certificate problem') || str_contains($msg, 'cURL error 60')) {
            return 'Lỗi chứng chỉ SSL trên máy chủ. Hệ thống đã cấu hình CA bundle; nếu vẫn lỗi, hãy khởi động lại PHP/Apache hoặc cập nhật curl.cainfo trong php.ini.';
        }

        return $msg !== '' ? $msg : 'Không thể kết nối Google (Lỗi không xác định).';
    }

    private function normalizeTabKey(string $tabKey): string
    {
        return match ($tabKey) {
            'property' => 'good-deeds-property',
            'deed' => 'good-deeds-deed',
            default => $tabKey,
        };
    }
}
