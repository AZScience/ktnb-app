<?php

namespace App\Services;

use App\Models\AssetReception;
use App\Models\DailySchedule;
use App\Models\DocumentRecord;
use App\Models\ExternalCheckin;
use App\Models\Petition;
use App\Models\ServiceRequest;
use App\Models\StudentViolation;
use App\Models\SystemParameter;
use Carbon\Carbon;
use Illuminate\Support\Str;

class EvidenceAggregationService
{
    public function __construct(private EvidenceStorageService $storage) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function collect(): array
    {
        $items = [];

        DailySchedule::query()
            ->whereNotNull('evidence')
            ->where('evidence', '!=', '')
            ->orderByDesc('date')
            ->limit(300)
            ->get()
            ->each(function (DailySchedule $row) use (&$items) {
                $files = $this->storage->parse($row->evidence);
                if ($files === []) {
                    return;
                }

                [$source, $sourceLabel] = $this->resolveScheduleSource($row);

                $items[] = $this->makeItem(
                    id: "schedule-{$row->id}",
                    source: $source,
                    sourceLabel: $sourceLabel,
                    title: trim(($row->class ?? 'N/A').' - '.($row->room ?? 'N/A'), ' -'),
                    description: $row->content ?: ($row->incident ?: 'Báo cáo giám sát'),
                    date: $this->parseDate($row->date),
                    dateStr: $row->date ?: 'N/A',
                    submittedBy: $row->employee ?? 'System',
                    submittedByName: $row->employee ?? 'Hệ thống',
                    items: $this->filesToItems($row->evidence),
                    status: $row->incident ? 'Có việc phát sinh' : 'Bình thường',
                );
            });

        ExternalCheckin::query()
            ->orderByDesc('created_at')
            ->limit(80)
            ->get()
            ->each(function (ExternalCheckin $row) use (&$items) {
                $urls = collect($row->photo_urls ?? [])->filter()->values()->all();
                if ($urls === []) {
                    return;
                }

                $location = is_array($row->location) ? $row->location : null;
                $lat = $location['latitude'] ?? null;
                $lng = $location['longitude'] ?? null;
                $desc = ($lat !== null && $lng !== null)
                    ? sprintf('Check-in tại tọa độ: %.4f, %.4f', $lat, $lng)
                    : 'Check-in giảng viên';

                $items[] = $this->makeItem(
                    id: "checkin-{$row->id}",
                    source: 'checkin',
                    sourceLabel: 'Check-in Giảng viên',
                    title: trim(($row->lecturer ?? $row->submitted_by ?? 'N/A').' - '.($row->class_id ?? $row->class ?? 'N/A'), ' -'),
                    description: $desc,
                    date: $row->created_at ?? now(),
                    dateStr: optional($row->created_at)->format('d/m/Y') ?? 'N/A',
                    submittedBy: $row->submitted_by ?? 'N/A',
                    submittedByName: $row->submitted_by ?? 'N/A',
                    items: $urls,
                    status: match ($row->status) {
                        'approved' => 'Đã duyệt',
                        'rejected' => 'Từ chối',
                        default => 'Chờ duyệt',
                    },
                    location: $location,
                );
            });

        ServiceRequest::query()
            ->whereNotNull('attachments')
            ->where('attachments', '!=', '')
            ->orderByDesc('request_date')
            ->limit(80)
            ->get()
            ->each(function (ServiceRequest $row) use (&$items) {
                if ($this->storage->parse($row->attachments) === []) {
                    return;
                }

                $items[] = $this->makeItem(
                    id: "request-{$row->id}",
                    source: 'requests',
                    sourceLabel: 'Tiếp nhận yêu cầu',
                    title: "Yêu cầu: {$row->student_name} ({$row->student_id})",
                    description: $row->content ?: 'Hỗ trợ sinh viên',
                    date: $this->parseDate($row->request_date) ?? $row->created_at,
                    dateStr: $row->request_date ?: optional($row->created_at)->format('d/m/Y'),
                    submittedBy: $row->recipient ?? 'N/A',
                    submittedByName: $row->recipient ?? 'N/A',
                    items: $this->filesToItems($row->attachments),
                    status: $row->status ?? 'Đã tiếp nhận',
                );
            });

        Petition::query()
            ->whereNotNull('note')
            ->where('note', 'like', '%:::http%')
            ->orderByDesc('reception_date')
            ->limit(80)
            ->get()
            ->each(function (Petition $row) use (&$items) {
                if ($this->storage->parse($row->note) === []) {
                    return;
                }

                $items[] = $this->makeItem(
                    id: "petition-{$row->id}",
                    source: 'petitions',
                    sourceLabel: 'Tiếp nhận đơn thư',
                    title: "Đơn thư: {$row->citizen_name}",
                    description: $row->summary ?: 'Đơn thư công dân',
                    date: $this->parseDate($row->reception_date),
                    dateStr: $row->reception_date ?: 'N/A',
                    submittedBy: $row->recipient ?? 'N/A',
                    submittedByName: $row->recipient ?? 'N/A',
                    items: $this->filesToItems($row->note),
                    status: $row->petition_type ?? 'Đơn thư',
                );
            });

        AssetReception::query()
            ->where(function ($query) {
                $query->where(function ($inner) {
                    $inner->whereNotNull('evidence')->where('evidence', '!=', '');
                })->orWhere(function ($inner) {
                    $inner->whereNotNull('return_evidence')->where('return_evidence', '!=', '');
                })->orWhere(function ($inner) {
                    $inner->whereNotNull('gratitude_evidence')->where('gratitude_evidence', '!=', '');
                });
            })
            ->orderByDesc('reception_date')
            ->limit(80)
            ->get()
            ->each(function (AssetReception $row) use (&$items) {
                if ($row->evidence) {
                    $items[] = $this->makeItem(
                        id: "asset-reception-{$row->id}",
                        source: 'asset_check',
                        sourceLabel: 'Nhận - Trả tài sản',
                        title: "Tiếp nhận: {$row->giver_name} ({$row->entry_number})",
                        description: $row->content ?: 'Giao nhận tài sản',
                        date: $this->parseDate($row->reception_date),
                        dateStr: $row->reception_date ?: 'N/A',
                        submittedBy: $row->receiving_staff ?? 'N/A',
                        submittedByName: $row->receiving_staff ?? 'N/A',
                        items: $this->filesToItems($row->evidence),
                        status: $row->return_status ?? '—',
                    );
                }

                if ($row->return_evidence) {
                    $items[] = $this->makeItem(
                        id: "asset-return-{$row->id}",
                        source: 'asset_check',
                        sourceLabel: 'Nhận - Trả tài sản',
                        title: "Trao trả: {$row->giver_name} ({$row->entry_number})",
                        description: $row->content ?: 'Trao trả tài sản',
                        date: $this->parseDate($row->resolution_date ?: $row->reception_date),
                        dateStr: $row->resolution_date ?: $row->reception_date ?: 'N/A',
                        submittedBy: $row->return_staff ?? 'N/A',
                        submittedByName: $row->return_staff ?? 'N/A',
                        items: $this->filesToItems($row->return_evidence),
                        status: $row->return_status ?? '—',
                    );
                }

                if ($row->gratitude_evidence) {
                    $items[] = $this->makeItem(
                        id: "asset-gratitude-{$row->id}",
                        source: 'asset_check',
                        sourceLabel: 'Nhận - Trả tài sản',
                        title: "Tri ân: {$row->giver_name} ({$row->entry_number})",
                        description: $row->content ?: 'Tri ân người tốt việc tốt',
                        date: $this->parseDate($row->gratitude_date ?: $row->reception_date),
                        dateStr: $row->gratitude_date ?: $row->reception_date ?: 'N/A',
                        submittedBy: $row->gratitude_staff ?? 'N/A',
                        submittedByName: $row->gratitude_staff ?? 'N/A',
                        items: $this->filesToItems($row->gratitude_evidence),
                        status: $row->return_status ?? '—',
                    );
                }
            });

        StudentViolation::query()
            ->orderByDesc('violation_date')
            ->limit(80)
            ->get()
            ->each(function (StudentViolation $row) use (&$items) {
                $evidenceItems = [];
                if ($row->portrait_photo) {
                    $evidenceItems[] = 'Ảnh chân dung:::'.$row->portrait_photo;
                }
                if ($row->document_photo) {
                    $evidenceItems[] = 'Ảnh giấy tờ:::'.$row->document_photo;
                }
                if ($row->signature_base64) {
                    $evidenceItems[] = 'Chữ ký xác nhận:::'.$row->signature_base64;
                }

                if ($evidenceItems === []) {
                    return;
                }

                $items[] = $this->makeItem(
                    id: "violation-{$row->id}",
                    source: 'violations',
                    sourceLabel: 'Sinh viên vi phạm',
                    title: "Vi phạm: {$row->full_name} ({$row->student_id})",
                    description: $row->violation_type ?: 'Vi phạm nội quy',
                    date: $this->parseIsoDate($row->violation_date),
                    dateStr: $row->violation_date ?: 'N/A',
                    submittedBy: $row->officer ?? 'N/A',
                    submittedByName: $row->officer ?? 'N/A',
                    items: $evidenceItems,
                    status: $row->signed ? 'Đã ký' : 'Chưa ký',
                );
            });

        DocumentRecord::query()
            ->whereNotNull('original_file')
            ->where('original_file', '!=', '')
            ->orderByDesc('received_date')
            ->limit(80)
            ->get()
            ->each(function (DocumentRecord $row) use (&$items) {
                $items[] = $this->makeItem(
                    id: "docrecord-{$row->id}",
                    source: 'document_records',
                    sourceLabel: 'Quản lý hồ sơ',
                    title: trim(($row->doc_number ?: 'Văn bản').' - '.$row->title, ' -'),
                    description: $row->abstract ?: 'Tài liệu hồ sơ văn bản',
                    date: $this->parseIsoDate($row->received_date),
                    dateStr: $row->received_date ?: 'N/A',
                    submittedBy: $row->assignee ?? 'N/A',
                    submittedByName: $row->assignee ?? 'N/A',
                    items: $this->filesToItems($row->original_file),
                    status: $row->status ?? '—',
                );
            });

        $shift = $this->shiftScheduleItem();
        if ($shift) {
            $items[] = $shift;
        }

        usort($items, fn ($a, $b) => strtotime($b['date_ts'] ?? '0') <=> strtotime($a['date_ts'] ?? '0'));

        return $items;
    }

  public function destroy(string $compositeId): bool
    {
        if (! str_contains($compositeId, '-')) {
            return false;
        }

        [$type, $id] = explode('-', $compositeId, 2);

        return match ($type) {
            'schedule' => (bool) DailySchedule::whereKey($id)->update(['evidence' => null]),
            'checkin' => (bool) ExternalCheckin::whereKey($id)->update(['photo_urls' => null]),
            'request' => (bool) ServiceRequest::whereKey($id)->update(['attachments' => null]),
            'petition' => (bool) Petition::whereKey($id)->update(['note' => null]),
            'asset-reception' => (bool) AssetReception::whereKey($id)->update(['evidence' => null]),
            'asset-return' => (bool) AssetReception::whereKey($id)->update(['return_evidence' => null]),
            'asset-gratitude' => (bool) AssetReception::whereKey($id)->update(['gratitude_evidence' => null]),
            'asset' => (bool) AssetReception::whereKey($id)->update(['evidence' => null]),
            'violation' => (bool) StudentViolation::whereKey($id)->update([
                'portrait_photo' => null,
                'document_photo' => null,
                'signature_base64' => null,
            ]),
            'docrecord' => (bool) DocumentRecord::whereKey($id)->update(['original_file' => null]),
            'shiftschedule' => $this->clearShiftSchedule(),
            default => false,
        };
    }

    private function clearShiftSchedule(): bool
    {
        return SystemParameter::where('key', 'dashboard_shift_schedule')->delete() > 0;
    }

    private function shiftScheduleItem(): ?array
    {
        $parameter = SystemParameter::where('key', 'dashboard_shift_schedule')->first();
        if (! $parameter?->value) {
            return null;
        }

        $decoded = json_decode($parameter->value, true);
        if (! is_array($decoded) || empty($decoded['path'])) {
            return null;
        }

        $updatedAt = $decoded['updated_at'] ?? now()->toIso8601String();
        $date = Carbon::parse($updatedAt);

        return $this->makeItem(
            id: 'shiftschedule-shift_schedule',
            source: 'shift_schedule',
            sourceLabel: 'Lịch trực hệ thống',
            title: 'Lịch trực: '.($decoded['name'] ?? 'Bản mới nhất'),
            description: 'Cập nhật lần cuối: '.$date->format('H:i d/m/Y'),
            date: $date,
            dateStr: $date->format('d/m/Y'),
            submittedBy: $decoded['updated_by'] ?? 'System',
            submittedByName: $decoded['updated_by_name'] ?? 'Hệ thống',
            items: [route('dashboard.shift-schedule.file')],
            status: 'Bản chính thức',
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveScheduleSource(DailySchedule $row): array
    {
        $content = Str::lower((string) ($row->content ?? ''));
        $status = Str::lower((string) ($row->status ?? ''));
        $building = Str::lower((string) ($row->building ?? ''));

        $isHomeroom = Str::contains($content.$status, ['cvht', 'shcn', 'cố vấn', 'sinh hoạt', 'chủ nhiệm']);
        $isPractice = Str::contains($building, 'ngoài');
        $isOnline = Str::contains($building, ['trực tuyến', 'online']);
        $isExam = Str::contains($status, 'thi');

        if ($isHomeroom) {
            return ['homeroom', 'Cố vấn học tập'];
        }
        if ($isOnline) {
            return ['online', 'Lớp học online'];
        }
        if ($isPractice) {
            return ['practice', 'Thực hành ngoài'];
        }
        if ($isExam) {
            return ['exams', 'Thi kết thúc môn'];
        }

        return ['direct', 'Lớp học trực tiếp'];
    }

    /**
     * @param  list<string>  $items
     * @return array<string, mixed>
     */
    private function makeItem(
        string $id,
        string $source,
        string $sourceLabel,
        string $title,
        string $description,
        mixed $date,
        ?string $dateStr,
        string $submittedBy,
        string $submittedByName,
        array $items,
        string $status,
        ?array $location = null,
    ): array {
        $carbon = $date instanceof Carbon ? $date : $this->parseDate($date) ?? now();

        return [
            'id' => $id,
            'source' => $source,
            'source_label' => $sourceLabel,
            'title' => $title,
            'description' => $description,
            'date' => $carbon->toIso8601String(),
            'date_ts' => $carbon->timestamp,
            'date_str' => $dateStr ?: $carbon->format('d/m/Y'),
            'submitted_by' => $submittedBy,
            'submitted_by_name' => $submittedByName,
            'items' => array_values($items),
            'status' => $status,
            'location' => $location,
        ];
    }

  /**
     * @return list<string>
     */
    private function filesToItems(?string $raw): array
    {
        return collect($this->storage->parse($raw))
            ->map(fn (array $file) => ($file['name'] ?? 'Tệp').':::'.$file['url'])
            ->values()
            ->all();
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', trim($value), $m)) {
            return Carbon::createFromDate((int) $m[3], (int) $m[2], (int) $m[1])->startOfDay();
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseIsoDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return Carbon::createFromDate((int) $m[1], (int) $m[2], (int) $m[3])->startOfDay();
        }

        return $this->parseDate($value);
    }
}
