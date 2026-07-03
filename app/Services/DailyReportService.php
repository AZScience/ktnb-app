<?php

namespace App\Services;

use App\Models\DailySchedule;
use App\Models\Employee;
use App\Models\StudentViolation;
use App\Models\User;
use Illuminate\Support\Collection;

class DailyReportService
{
    public function __construct(
        private StudentViolationCrosstabService $violationCrosstab,
    ) {}

    /**
     * @return array{nickname: string, fullName: string, aliases: list<string>}
     */
    public function resolveOfficerContext(?User $user): array
    {
        if (! $user) {
            return ['nickname' => '', 'fullName' => '', 'aliases' => []];
        }

        $employee = Employee::query()
            ->where('email', $user->email)
            ->first(['nickname', 'name']);

        $nickname = trim((string) ($employee?->nickname ?: $employee?->name ?: $user->name));
        $fullName = trim((string) ($employee?->name ?: $user->name));

        $aliases = [];
        foreach ([$employee?->nickname, $employee?->name, $user->name] as $value) {
            $normalized = mb_strtolower(trim((string) $value));
            if ($normalized !== '' && ! in_array($normalized, $aliases, true)) {
                $aliases[] = $normalized;
            }
        }

        return [
            'nickname' => $nickname,
            'fullName' => $fullName,
            'aliases' => $aliases,
        ];
    }

    public function resolveOfficerNickname(?User $user): string
    {
        return $this->resolveOfficerContext($user)['nickname'];
    }

    public function resolveOfficerFullName(?User $user): string
    {
        return $this->resolveOfficerContext($user)['fullName'];
    }

    /**
     * @return list<string> Lowercase trimmed aliases used when matching officer on reports.
     */
    public function resolveOfficerAliases(?User $user): array
    {
        return $this->resolveOfficerContext($user)['aliases'];
    }

    public function isoToDisplay(string $isoDate): string
    {
        if (str_contains($isoDate, '/')) {
            return $isoDate;
        }

        [$y, $m, $d] = explode('-', $isoDate);

        return sprintf('%s/%s/%s', $d, $m, $y);
    }

    public function displayToIso(string $displayDate): string
    {
        if (str_contains($displayDate, '-')) {
            return $displayDate;
        }

        [$d, $m, $y] = explode('/', $displayDate);

        return sprintf('%s-%s-%s', $y, $m, $d);
    }

    /**
     * @param  list<string>  $officerAliases
     */
    public function dailySchedules(string $displayDate, string $module, array $officerAliases): Collection
    {
        if ($officerAliases === []) {
            return collect();
        }

        $query = DailySchedule::query()
            ->forListTable()
            ->where('date', $displayDate)
            ->whereNotNull('recognition_date')
            ->where('recognition_date', '!=', '')
            ->whereNotNull('employee')
            ->where('employee', '!=', '')
            ->where(function ($q) use ($officerAliases) {
                foreach ($officerAliases as $alias) {
                    $q->orWhereRaw('LOWER(TRIM(employee)) = ?', [$alias]);
                }
            });

        $query->forModule($module);

        return $query->orderBy('period')->get()->map(fn (DailySchedule $row) => $this->mapSchedule($row));
    }

    /**
     * @param  list<string>  $officerAliases
     */
    public function dailyViolations(string $displayDate, array $officerAliases): Collection
    {
        if ($officerAliases === []) {
            return collect();
        }

        $records = StudentViolation::query()
            ->select(StudentViolation::INDEX_COLUMNS)
            ->where('violation_date', $displayDate)
            ->whereNotNull('officer')
            ->where('officer', '!=', '')
            ->where(function ($q) use ($officerAliases) {
                foreach ($officerAliases as $alias) {
                    $q->orWhereRaw('LOWER(TRIM(officer)) = ?', [$alias]);
                }
            })
            ->whereNotNull('violation_type')
            ->where('violation_type', '!=', '')
            ->orderBy('officer')
            ->orderBy('building')
            ->get();

        return collect($this->violationCrosstab->build($records));
    }

    public function tabDefinitions(): array
    {
        return [
            'in-person' => [
                'label' => 'KIỂM TRA PHÒNG HỌC',
                'title' => 'KIỂM TRA PHÒNG HỌC',
                'tone' => 'green',
                'columns' => [
                    ['key' => 'employee', 'label' => 'Nhân viên'],
                    ['key' => 'date', 'label' => 'Ngày'],
                    ['key' => 'room', 'label' => 'Phòng'],
                    ['key' => 'period', 'label' => 'Tiết'],
                    ['key' => 'department', 'label' => 'Khoa'],
                    ['key' => 'class', 'label' => 'Lớp'],
                    ['key' => 'lecturer', 'label' => 'Giảng viên'],
                    ['key' => 'content', 'label' => 'Nội dung'],
                    ['key' => 'incident', 'label' => 'Việc phát sinh', 'type' => 'incident'],
                    ['key' => 'is_notification', 'label' => 'Thông báo', 'type' => 'notification'],
                    ['key' => 'incident_detail', 'label' => 'Chi tiết sự việc'],
                ],
                'exportKey' => 'PHONGHOC',
                'exportCols' => ['employee', 'date', 'room', 'period', 'department', 'class', 'lecturer', 'content', 'incident', 'is_notification', 'incident_detail'],
            ],
            'online' => [
                'label' => 'KIỂM TRA TRỰC TUYẾN',
                'title' => 'KIỂM TRA LỚP HỌC TRỰC TUYẾN',
                'tone' => 'blue',
                'columns' => [
                    ['key' => 'employee', 'label' => 'Nhân viên'],
                    ['key' => 'date', 'label' => 'Ngày'],
                    ['key' => 'room', 'label' => 'Phòng'],
                    ['key' => 'period', 'label' => 'Tiết'],
                    ['key' => 'department', 'label' => 'Khoa'],
                    ['key' => 'class', 'label' => 'Lớp'],
                    ['key' => 'lecturer', 'label' => 'Giảng viên'],
                    ['key' => 'content', 'label' => 'Môn học'],
                    ['key' => 'attending_students', 'label' => 'SV tham dự'],
                    ['key' => 'incident', 'label' => 'Việc phát sinh', 'type' => 'incident'],
                    ['key' => 'is_notification', 'label' => 'Thông báo', 'type' => 'notification'],
                    ['key' => 'incident_detail', 'label' => 'Chi tiết sự việc'],
                ],
                'exportKey' => 'TRUCTUYEN',
                'exportCols' => ['employee', 'date', 'room', 'period', 'department', 'class', 'lecturer', 'content', 'attending_students', 'incident', 'is_notification', 'incident_detail'],
                'googleSheetPushCols' => ['employee', 'date', 'room', 'period', 'department', 'class', 'lecturer', 'content', 'incident', 'is_notification', 'incident_detail'],
            ],
            'homeroom' => [
                'label' => 'SINH HOẠT CỐ VẤN',
                'title' => 'SINH HOẠT CỐ VẤN',
                'tone' => 'fuchsia',
                'columns' => [
                    ['key' => 'employee', 'label' => 'Nhân viên'],
                    ['key' => 'date', 'label' => 'Ngày'],
                    ['key' => 'room', 'label' => 'Phòng'],
                    ['key' => 'period', 'label' => 'Tiết'],
                    ['key' => 'department', 'label' => 'Khoa'],
                    ['key' => 'class', 'label' => 'Lớp'],
                    ['key' => 'lecturer', 'label' => 'Giảng viên'],
                    ['key' => 'student_count', 'label' => 'SV dự'],
                    ['key' => 'incident', 'label' => 'Việc phát sinh', 'type' => 'incident'],
                    ['key' => 'is_notification', 'label' => 'Thông báo', 'type' => 'notification'],
                    ['key' => 'incident_detail', 'label' => 'Chi tiết sự việc'],
                ],
                'exportKey' => 'SINHHOAT',
                'exportCols' => ['employee', 'date', 'room', 'period', 'department', 'class', 'lecturer', 'student_count', 'incident', 'is_notification', 'incident_detail'],
            ],
            'violations' => [
                'label' => 'SINH VIÊN VI PHẠM',
                'title' => 'BÁO CÁO GHI NHẬN SINH VIÊN VI PHẠM',
                'tone' => 'red',
                'layout' => 'crosstab',
                'crosstab' => [
                    'fixedColumns' => $this->violationCrosstab->fixedColumns(),
                    'violationGroupLabel' => 'NỘI DUNG GHI NHẬN VI PHẠM SINH VIÊN',
                    'violationColumns' => $this->violationCrosstab->violationTypeColumns(),
                ],
                'columns' => $this->violationCrosstabColumnsForTable(),
                'exportKey' => 'VIPHAM',
                'exportCols' => $this->violationCrosstab->exportColumnKeys(),
                'exportSpreadsheetCols' => $this->violationCrosstab->spreadsheetExportColumnKeys(),
                'googleSheetPushCols' => $this->violationCrosstab->googleSheetExportColumnKeys(),
            ],
            'exams' => [
                'label' => 'THI KẾT THÚC MÔN',
                'title' => 'KẾT THÚC MÔN',
                'tone' => 'amber',
                'columns' => [
                    ['key' => 'employee', 'label' => 'Nhân viên'],
                    ['key' => 'date', 'label' => 'Ngày'],
                    ['key' => 'room', 'label' => 'Phòng'],
                    ['key' => 'period', 'label' => 'Tiết'],
                    ['key' => 'department', 'label' => 'Khoa'],
                    ['key' => 'class', 'label' => 'Lớp'],
                    ['key' => 'proctor1', 'label' => 'CBCT 1'],
                    ['key' => 'proctor2', 'label' => 'CBCT 2'],
                    ['key' => 'proctor3', 'label' => 'CBCT 3'],
                    ['key' => 'content', 'label' => 'Môn thi'],
                    ['key' => 'incident', 'label' => 'Việc phát sinh', 'type' => 'incident'],
                    ['key' => 'is_notification', 'label' => 'Thông báo', 'type' => 'notification'],
                    ['key' => 'incident_detail', 'label' => 'Chi tiết sự việc'],
                ],
                'exportKey' => 'LOPTHI',
                'exportCols' => ['employee', 'date', 'room', 'period', 'department', 'class', 'proctor1', 'proctor2', 'proctor3', 'content', 'incident', 'is_notification', 'incident_detail'],
            ],
            'external' => [
                'label' => 'THỰC HÀNH NGOÀI',
                'title' => 'THỰC HÀNH NGOÀI',
                'tone' => 'lime',
                'columns' => [
                    ['key' => 'employee', 'label' => 'Nhân viên'],
                    ['key' => 'date', 'label' => 'Ngày'],
                    ['key' => 'room', 'label' => 'Địa điểm'],
                    ['key' => 'period', 'label' => 'Tiết'],
                    ['key' => 'type', 'label' => 'LT/TH', 'type' => 'type'],
                    ['key' => 'department', 'label' => 'Khoa'],
                    ['key' => 'class', 'label' => 'Lớp'],
                    ['key' => 'student_count', 'label' => 'Sĩ số'],
                    ['key' => 'lecturer', 'label' => 'Giảng viên'],
                    ['key' => 'content', 'label' => 'Nội dung'],
                    ['key' => 'incident', 'label' => 'Việc phát sinh', 'type' => 'incident'],
                    ['key' => 'is_notification', 'label' => 'Thông báo', 'type' => 'notification'],
                    ['key' => 'incident_detail', 'label' => 'Chi tiết sự việc'],
                ],
                'exportKey' => 'THUCHANH',
                'exportCols' => ['employee', 'date', 'room', 'period', 'type', 'department', 'class', 'student_count', 'lecturer', 'content', 'incident', 'is_notification', 'incident_detail'],
            ],
        ];
    }

    /**
     * Việc phát sinh "bình thường" = trống hoặc các nhãn không cần báo cáo lên Sheet.
     */
    public static function isNotableIncident(?string $incident): bool
    {
        $value = trim((string) ($incident ?? ''));
        if ($value === '' || $value === '---') {
            return false;
        }

        $lower = mb_strtolower($value);
        $normalPhrases = [
            'bình thường',
            'học bình thường',
            'không có',
            'khong co',
            'binh thuong',
            'hoc binh thuong',
            '--- không có ---',
        ];

        foreach ($normalPhrases as $phrase) {
            if ($lower === $phrase) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    public function googleSheetPushFields(string $tabKey): array
    {
        if ($tabKey === '') {
            return [];
        }

        $def = $this->tabDefinitions()[$tabKey] ?? null;
        if (! $def) {
            return [];
        }

        if (! empty($def['googleSheetPushCols'])) {
            return $def['googleSheetPushCols'];
        }

        if ($tabKey === 'violations' && ! empty($def['exportSpreadsheetCols'])) {
            return $def['exportSpreadsheetCols'];
        }

        return $def['exportCols'] ?? [];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public function prepareRowsForGoogleSheetPush(string $tabKey, array $rows): array
    {
        $rows = $this->filterRowsForGoogleSheetPush($tabKey, $rows);
        $fields = $this->googleSheetPushFields($tabKey);

        if ($fields === []) {
            return array_map(fn (array $row) => $this->normalizeGoogleSheetRow($row), $rows);
        }

        return array_values(array_map(function (array $row) use ($fields, $tabKey) {
            $normalized = [];
            foreach ($fields as $field) {
                $normalized[$field] = $this->normalizeGoogleSheetFieldValue($field, $row[$field] ?? null, $tabKey);
            }

            return $normalized;
        }, $rows));
    }

    public static function normalizeNotificationValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = mb_strtolower(trim((string) $value));

        return in_array($normalized, ['có', 'co', 'yes', 'true', '1', 'x', 'on'], true);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeGoogleSheetRow(array $row): array
    {
        if (array_key_exists('is_notification', $row)) {
            $row['is_notification'] = self::normalizeNotificationValue($row['is_notification']);
        }

        return $row;
    }

    private function normalizeGoogleSheetFieldValue(string $field, mixed $value, string $tabKey = ''): mixed
    {
        if ($field === '__gap__') {
            return '';
        }

        if ($field === 'is_notification') {
            return self::normalizeNotificationValue($value);
        }

        if ($tabKey === 'violations' && $this->isViolationCrosstabCountField($field)) {
            $count = is_numeric($value) ? (int) $value : 0;

            return $count > 0 ? $count : '';
        }

        if ($value === null) {
            return '';
        }

        return $value;
    }

    private function isViolationCrosstabCountField(string $field): bool
    {
        static $keys = null;

        if ($keys === null) {
            $keys = array_flip(array_column($this->violationCrosstab->violationTypeColumns(), 'key'));
        }

        return isset($keys[$field]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public function filterRowsForGoogleSheetPush(string $tabKey, array $rows): array
    {
        if ($tabKey !== 'online') {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            fn (array $row) => self::isNotableIncident($row['incident'] ?? null),
        ));
    }

    private function mapSchedule(DailySchedule $row): array
    {
        return [
            'id' => $row->id,
            'employee' => $row->employee,
            'date' => $row->date,
            'room' => $row->room,
            'period' => $row->period,
            'department' => $row->department,
            'class' => $row->class,
            'lecturer' => $row->lecturer,
            'content' => $row->content,
            'type' => $row->type,
            'student_count' => $row->student_count,
            'attending_students' => $row->attending_students ?? $row->actual_student_count,
            'proctor1' => $row->proctor1,
            'proctor2' => $row->proctor2,
            'proctor3' => $row->proctor3,
            'incident' => $row->incident,
            'is_notification' => (bool) $row->is_notification,
            'incident_detail' => $row->incident_detail,
            'recognition_date' => $row->recognition_date,
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function googleSheetTabSuggestions(): array
    {
        $defs = app(self::class)->tabDefinitions();

        $legacyNames = [
            'in-person' => ['BC- KIỂM TRA PHÒNG HỌC'],
            'online' => ['BC- KIỂM TRA TRỰC TUYẾN', 'KIỂM TRA LỚP HỌC TRỰC TUYẾN'],
            'homeroom' => ['BC- SINH HOẠT CỐ VẤN'],
            'violations' => ['BC- GHI NHẬN SV VI PHẠM', 'BÁO CÁO GHI NHẬN SINH VIÊN VI PHẠM'],
            'exams' => ['BC- THI KẾT THÚC MÔN', 'KẾT THÚC MÔN'],
            'external' => ['BC- KIỂM TRA GHI NHẬN TH.DOANH NGHIỆP', 'THUCHANH', 'THỰC HÀNH NGOÀI'],
        ];

        $suggestions = [];
        foreach ($defs as $key => $def) {
            $candidates = array_filter([
                $def['exportKey'] ?? null,
                $def['title'] ?? null,
                $def['label'] ?? null,
                ...($legacyNames[$key] ?? []),
            ], fn ($value) => is_string($value) && trim($value) !== '');

            $suggestions[$key] = array_values(array_unique($candidates));
        }

        return $suggestions;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function interactiveReportGoogleSheetTabSuggestions(): array
    {
        return [
            'request-reports' => [
                'BC- TIẾP NHẬN YÊU CẦU',
                'BC-TIẾP NHẬN YÊU CẦU',
                'Tiếp nhận yêu cầu',
                'BÁO CÁO TIẾP NHẬN YÊU CẦU',
            ],
            'incident-reports' => [
                'BC- TIẾP NHẬN ĐƠN THƯ',
                'Tiếp nhận đơn thư',
                'BÁO CÁO TIẾP NHẬN ĐƠN THƯ',
            ],
            'comprehensive' => [
                'BC- VIỆC KPH',
                'Việc không phù hợp',
                'Thống kê việc KPH',
            ],
            'good-deeds-property' => [
                'BC- TIẾP NHẬN TS',
                'Tiếp nhận tài sản',
                'TIẾP NHẬN TÀI SẢN',
                'BC- NGƯỜI TỐT VIỆC TỐT',
            ],
            'good-deeds-deed' => [
                'BC- NGƯỜI TỐT VIỆC TỐT',
                'Tri ân người việc tốt',
                'NGƯỜI TỐT VIỆC TỐT',
            ],
            'good-deeds' => [
                'BC- NGƯỜI TỐT VIỆC TỐT',
                'Người tốt việc tốt',
                'Tiếp nhận tài sản',
            ],
        ];
    }

    public static function pickGoogleSheetTab(string $tabKey, array $availableTabs, ?string $preferred = null): ?string
    {
        $candidates = [];
        foreach (self::googleSheetTabSuggestions()[$tabKey] ?? [] as $name) {
            $candidates[] = $name;
        }
        foreach (self::interactiveReportGoogleSheetTabSuggestions()[$tabKey] ?? [] as $name) {
            $candidates[] = $name;
        }
        if (is_string($preferred) && trim($preferred) !== '') {
            $candidates[] = trim($preferred);
        }

        $candidates = array_values(array_unique($candidates));

        foreach ($candidates as $candidate) {
            $match = GoogleSheetService::matchTabTitle($availableTabs, $candidate);
            if ($match !== null) {
                return $match;
            }
        }

        if ($availableTabs !== []) {
            return $availableTabs[0];
        }

        return $candidates[0] ?? null;
    }

    /**
     * @return list<array{key: string, label: string, type?: string}>
     */
    private function violationCrosstabColumnsForTable(): array
    {
        $countKeys = array_column($this->violationCrosstab->violationTypeColumns(), 'key');

        $columns = [
            ...$this->violationCrosstab->fixedColumns(),
            ...$this->violationCrosstab->violationTypeColumns(),
        ];

        return array_map(function (array $col) use ($countKeys) {
            if (in_array($col['key'], $countKeys, true)) {
                $col['type'] = 'count';
            }

            return $col;
        }, $columns);
    }
}
