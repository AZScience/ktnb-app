<?php

namespace App\Models;

use App\Models\Concerns\NormalizesDisplayDateColumns;
use App\Services\EvidenceStorageService;
use App\Services\ScheduleLocationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DailySchedule extends Model
{
    use NormalizesDisplayDateColumns;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'date', 'building', 'room', 'period', 'type', 'department', 'class',
        'student_count', 'lecturer', 'content', 'status', 'time', 'proctor1', 'proctor2',
        'proctor3', 'note', 'recognition_date', 'employee', 'attending_students',
        'incident', 'is_notification', 'incident_detail', 'evidence',
        'meeting_link', 'actual_student_count', 'attendance_list', 'attendance_details',
        'last_seen_at', 'session_end_at',
    ];

    protected $casts = [
        'is_notification' => 'boolean',
        'attendance_list' => 'array',
        'attendance_details' => 'array',
        'last_seen_at' => 'datetime',
        'session_end_at' => 'datetime',
    ];

    protected static function displayDateIsoColumns(): array
    {
        return [
            'date' => 'date_iso',
            'recognition_date' => 'recognition_date_iso',
        ];
    }

    public function setEvidenceAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['evidence'] = $value;

            return;
        }

        if (stripos($value, 'data:') !== false) {
            $value = app(EvidenceStorageService::class)->normalizeForStorage($value) ?? '';
        }

        $this->attributes['evidence'] = $value;
    }

    /** Columns for table listings — excludes heavy blobs loaded on demand. */
    public const LIST_TABLE_COLUMNS = [
        'id', 'date', 'building', 'room', 'period', 'type', 'department', 'class',
        'student_count', 'lecturer', 'proctor1', 'proctor2', 'proctor3',
        'content', 'status', 'note', 'incident', 'incident_detail', 'employee',
        'attending_students', 'is_notification', 'recognition_date',
        'meeting_link', 'time', 'actual_student_count',
    ];

    public const DETAIL_EXTRA_COLUMNS = ['evidence', 'attendance_list', 'attendance_details'];

    public function scopeForListTable(Builder $query): Builder
    {
        return $query->select(self::LIST_TABLE_COLUMNS);
    }

    public function toListTableArray(?ScheduleLocationService $locations = null): array
    {
        $row = $this->only(self::LIST_TABLE_COLUMNS);
        $locations ??= app(ScheduleLocationService::class);
        $row['building_label'] = $locations->buildingDisplayLabel($row['building'] ?? null);
        $row['room_label'] = $locations->roomDisplayLabel($row['room'] ?? null);

        return $row;
    }

    /**
     * @param  Collection<int, self>  $items
     * @return Collection<int, array<string, mixed>>
     */
    public static function mapListTableCollection(Collection $items): Collection
    {
        if ($items->isEmpty()) {
            return collect();
        }

        $locations = app(ScheduleLocationService::class);
        $locations->warmNormalizationCaches();

        return $items->map(fn (self $item) => $item->toListTableArray($locations));
    }

    public function toDetailArray(): array
    {
        return $this->only(array_merge(self::LIST_TABLE_COLUMNS, self::DETAIL_EXTRA_COLUMNS));
    }

    public static function itemMatchesModule(self $item, string $module): bool
    {
        $building = mb_strtolower((string) ($item->building ?? ''));
        $room = mb_strtolower((string) ($item->room ?? ''));
        $content = mb_strtoupper((string) ($item->content ?? ''));
        $contentLower = mb_strtolower((string) ($item->content ?? ''));
        $status = mb_strtolower((string) ($item->status ?? ''));
        $statusRaw = (string) ($item->status ?? '');

        $homeroomKeywords = ['cvht', 'shcn', 'cố vấn', 'sinh hoạt', 'chủ nhiệm'];

        return match ($module) {
            'online' => str_contains($building, 'trực tuyến')
                && ! in_array($statusRaw, ['Phòng thi', 'Thi cuối kỳ'], true)
                && ! str_contains($content, 'SHCN')
                && ! str_contains($content, 'SINH HOẠT'),
            'exams' => in_array($statusRaw, ['Phòng thi', 'Thi cuối kỳ'], true),
            'external-practice' => str_contains($building, 'ngoài')
                || str_contains($building, 'thực hành ngoài')
                || str_contains($room, 'ngoài')
                || str_contains($room, 'ngoai')
                || in_array((string) ($item->building ?? ''), ['THN', 'THỰC HÀNH NGOÀI'], true),
            'homeroom' => collect($homeroomKeywords)->contains(
                fn (string $kw) => str_contains($contentLower, $kw) || str_contains($status, $kw)
            ),
            'in-person' => ! in_array($statusRaw, ['Phòng thi', 'Thi cuối kỳ'], true)
                && ! str_contains($building, 'trực tuyến')
                && ! str_contains($building, 'ngoài')
                && ! collect($homeroomKeywords)->contains(
                    fn (string $kw) => str_contains($contentLower, $kw) || str_contains($status, $kw)
                ),
            default => true,
        };
    }

    public static function isRecorded(?self $schedule): bool
    {
        if (! $schedule) {
            return false;
        }

        return trim((string) ($schedule->recognition_date ?? '')) !== ''
            && trim((string) ($schedule->employee ?? '')) !== '';
    }

    public static function dateSortKey(?string $date): int
    {
        if ($date && preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $m)) {
            return (int) sprintf('%04d%02d%02d', $m[3], $m[2], $m[1]);
        }

        return 0;
    }

    /**
     * @param  Collection<int, self>|\Illuminate\Database\Eloquent\Collection<int, self>  $items
     * @return Collection<int, self>
     */
    public static function sortForListTable($items)
    {
        return $items->sort(function (self $a, self $b) {
            $dateCmp = self::dateSortKey($b->date) <=> self::dateSortKey($a->date);
            if ($dateCmp !== 0) {
                return $dateCmp;
            }

            return strcmp((string) ($a->period ?? ''), (string) ($b->period ?? ''));
        })->values();
    }

    public function scopeForModule(Builder $query, string $module): Builder
    {
        return match ($module) {
            'online' => $query
                ->whereRaw('LOWER(COALESCE(building, "")) LIKE ?', ['%trực tuyến%'])
                ->whereNotIn('status', ['Phòng thi', 'Thi cuối kỳ'])
                ->whereRaw('UPPER(COALESCE(content, "")) NOT LIKE ?', ['%SHCN%'])
                ->whereRaw('UPPER(COALESCE(content, "")) NOT LIKE ?', ['%SINH HOẠT%']),
            'exams' => $query->whereIn('status', ['Phòng thi', 'Thi cuối kỳ']),
            'external-practice' => $query->where(function (Builder $q) {
                $q->whereRaw('LOWER(COALESCE(building, "")) LIKE ?', ['%ngoài%'])
                    ->orWhereRaw('LOWER(COALESCE(building, "")) LIKE ?', ['%thực hành ngoài%'])
                    ->orWhereRaw('LOWER(COALESCE(room, "")) LIKE ?', ['%ngoài%'])
                    ->orWhereRaw('LOWER(COALESCE(room, "")) LIKE ?', ['%ngoai%'])
                    ->orWhereIn('building', ['THN', 'THỰC HÀNH NGOÀI']);
            }),
            'homeroom' => $query->where(function (Builder $q) {
                foreach (['cvht', 'shcn', 'cố vấn', 'sinh hoạt', 'chủ nhiệm'] as $kw) {
                    $q->orWhereRaw('LOWER(COALESCE(content, "")) LIKE ?', ["%{$kw}%"])
                        ->orWhereRaw('LOWER(COALESCE(status, "")) LIKE ?', ["%{$kw}%"]);
                }
            }),
            'in-person' => $query
                ->whereNotIn('status', ['Phòng thi', 'Thi cuối kỳ'])
                ->whereRaw('LOWER(COALESCE(building, "")) NOT LIKE ?', ['%trực tuyến%'])
                ->whereRaw('LOWER(COALESCE(building, "")) NOT LIKE ?', ['%ngoài%'])
                ->where(function (Builder $q) {
                    foreach (['cvht', 'shcn', 'cố vấn', 'sinh hoạt', 'chủ nhiệm'] as $kw) {
                        $q->whereRaw('LOWER(COALESCE(content, "")) NOT LIKE ?', ["%{$kw}%"])
                            ->whereRaw('LOWER(COALESCE(status, "")) NOT LIKE ?', ["%{$kw}%"]);
                    }
                }),
            default => $query,
        };
    }

    public static function moduleTitle(string $module): string
    {
        return match ($module) {
            'online' => 'Lớp học online',
            'in-person' => 'Lớp học trực tiếp',
            'exams' => 'Thi kết thúc môn',
            'external-practice' => 'Thực hành ngoài',
            'homeroom' => 'Cố vấn học tập',
            default => 'Giám sát',
        };
    }

    public static function moduleCardTitle(string $module): string
    {
        return match ($module) {
            'online' => 'Lịch học trực tuyến',
            'in-person' => 'Lịch học tại phòng',
            'exams' => 'Lịch thi cuối kỳ',
            'external-practice' => 'Lịch thực hành ngoài',
            'homeroom' => 'Lịch sinh hoạt chủ nhiệm',
            default => 'Lịch giám sát',
        };
    }

    public static function moduleModalEntity(string $module): string
    {
        return match ($module) {
            'online' => 'lớp học trực tuyến',
            'in-person' => 'lớp học trực tiếp',
            'exams' => 'tiết thi',
            'external-practice' => 'lớp thực hành ngoài',
            'homeroom' => 'lớp sinh hoạt chủ nhiệm',
            default => 'lớp học',
        };
    }

    /** Recognition name used to filter incident categories (mirrors old Next.js app). */
    public static function moduleRecognitionName(string $module): ?string
    {
        return match ($module) {
            'online' => 'Lớp học trực tuyến',
            'in-person' => 'Lớp học trực tiếp',
            'external-practice' => 'Thực hành ngoài',
            'homeroom' => 'Cố vấn học tập',
            default => null,
        };
    }

    /** Tên Việc ghi nhận tương ứng với một dòng lịch (dùng cho bộ lọc báo cáo). */
    public static function resolveRecognitionName(self $item): ?string
    {
        foreach (['exams', 'online', 'external-practice', 'homeroom', 'in-person'] as $module) {
            if (! self::itemMatchesModule($item, $module)) {
                continue;
            }

            if ($module === 'exams') {
                $building = mb_strtolower((string) ($item->building ?? ''));

                return str_contains($building, 'trực tuyến')
                    ? 'Thi trực tuyến'
                    : 'Thi kết thúc học phần';
            }

            return self::moduleRecognitionName($module);
        }

        return null;
    }

    public static function showsAttendingStudents(string $module): bool
    {
        return in_array($module, ['online', 'homeroom', 'in-person', 'external-practice', 'exams'], true);
    }

    public static function requiresIncidentSelection(string $module): bool
    {
        return in_array($module, ['homeroom', 'external-practice'], true);
    }

    /** UI behavior flags to mirror old Next.js monitoring pages. */
    public static function moduleUiConfig(string $module): array
    {
        return match ($module) {
            'homeroom' => [
                'recordingOnlyOnEdit' => true,
                'requiresIncident' => true,
                'noteInClassSection' => true,
                'attendingStudentsLabel' => 'Sinh viên dự thực tế',
                'rowClickSelects' => true,
                'hideEmployeeInEdit' => true,
                'hideRecognitionDateInEdit' => true,
                'advancedDateReloads' => true,
                'iconToolbar' => true,
                'cardIcon' => 'book-user',
            ],
            'online' => [
                'recordingOnlyOnEdit' => true,
                'noteInClassSection' => true,
                'attendingStudentsLabel' => 'Sinh viên dự thực tế',
                'rowClickSelects' => true,
                'hideEmployeeInEdit' => true,
                'hideRecognitionDateInEdit' => true,
                'advancedDateReloads' => true,
                'iconToolbar' => true,
                'cardIcon' => 'laptop',
                'statusOnAddCopyOnly' => true,
            ],
            'in-person' => [
                'recordingOnlyOnEdit' => true,
                'noteInClassSection' => true,
                'attendingStudentsLabel' => 'Sinh viên dự thực tế',
                'rowClickSelects' => true,
                'hideEmployeeInEdit' => true,
                'hideRecognitionDateInEdit' => true,
                'advancedDateReloads' => true,
                'iconToolbar' => true,
                'cardIcon' => 'monitor-check',
            ],
            'exams' => [
                'recordingOnlyOnEdit' => true,
                'noteInClassSection' => true,
                'rowClickSelects' => true,
                'hideEmployeeInEdit' => true,
                'hideRecognitionDateInEdit' => true,
                'advancedDateReloads' => true,
                'iconToolbar' => true,
                'cardIcon' => 'book-check',
                'statusOnAddCopyOnly' => true,
                'classFieldsOnCopyEditable' => true,
                'usesProctors' => true,
                'classSectionTitle' => 'THÔNG TIN PHÒNG THI',
            ],
            'external-practice' => [
                'recordingOnlyOnEdit' => true,
                'requiresIncident' => true,
                'noteInClassSection' => true,
                'attendingStudentsLabel' => 'Sinh viên dự thực tế',
                'rowClickSelects' => true,
                'hideEmployeeInEdit' => true,
                'hideRecognitionDateInEdit' => true,
                'advancedDateReloads' => true,
                'iconToolbar' => true,
                'cardIcon' => 'truck',
                'statusOnAddCopyOnly' => true,
                'classFieldsOnCopyEditable' => true,
            ],
            default => [
                'recordingOnlyOnEdit' => false,
                'noteInClassSection' => false,
                'attendingStudentsLabel' => 'SV tham gia',
                'rowClickSelects' => false,
                'hideEmployeeInEdit' => false,
                'hideRecognitionDateInEdit' => false,
                'advancedDateReloads' => false,
                'iconToolbar' => false,
            ],
        };
    }
}
