<?php

namespace App\Services;

use App\Models\DailySchedule;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScheduleRoomChecklistExportService
{
    public function __construct(
        private ScheduleExcelService $excel,
    ) {}

    /**
     * @param  null|(callable(Builder<DailySchedule>): void)  $configureQuery
     */
    public function download(Request $request, ?callable $configureQuery = null, ?string $filename = null): StreamedResponse
    {
        $query = DailySchedule::query();
        if ($configureQuery) {
            $configureQuery($query);
        }

        $items = $this->buildExportItems($request, $query);
        $displayDate = $this->normalizeScheduleDate($request->get('date')) ?: ($items->first()?->date ?? date('d/m/Y'));
        $buildingLabel = $this->exportBuildingLabel($request, $items);
        $session = (string) $request->get('period_session', 'all');
        $safeDate = str_replace('/', '-', $displayDate);
        $filename ??= 'LichHoc_'.$safeDate.'_'.date('H-i').'.xlsx';

        return $this->excel->exportRoomChecklist($items, [
            'date' => $displayDate,
            'building_label' => $buildingLabel,
            'period_session_label' => $this->periodSessionLabel(
                $session,
                $request->integer('period_start') ?: null,
                $request->integer('period_end') ?: null,
            ),
            'officer_name' => $this->exportOfficerName($request),
            'filename' => $filename,
        ]);
    }

    /**
     * @param  Builder<DailySchedule>  $query
     * @return Collection<int, DailySchedule>
     */
    private function buildExportItems(Request $request, Builder $query): Collection
    {
        $ids = $request->get('ids');

        if ($ids) {
            $idList = is_array($ids) ? $ids : array_filter(explode(',', (string) $ids));
            $query->whereIn('id', $idList);
        } elseif ($date = $request->get('date')) {
            $query->where('date', $this->normalizeScheduleDate($date));
        } else {
            abort(422, 'Vui lòng chọn ngày hoặc bản ghi cần xuất.');
        }

        if (! $ids) {
            $buildings = $request->get('buildings');
            if (is_array($buildings) && $buildings !== []) {
                $query->whereIn('building', array_values(array_filter($buildings)));
            } elseif (is_string($buildings) && $buildings !== '') {
                $query->whereIn('building', array_values(array_filter(explode(',', $buildings))));
            } elseif ($building = $request->get('building')) {
                $query->where('building', $building);
            }
        }

        $items = $query
            ->select([
                'id', 'date', 'building', 'room', 'period', 'type', 'department', 'class',
                'student_count', 'lecturer', 'proctor1', 'proctor2', 'proctor3',
                'content', 'status', 'note',
            ])
            ->get();
        $session = (string) $request->get('period_session', 'all');

        if ($session !== 'all') {
            $items = $items->filter(fn (DailySchedule $item) => $this->matchesPeriodSession(
                $item,
                $session,
                $request->integer('period_start') ?: null,
                $request->integer('period_end') ?: null,
            ));
        }

        return $this->sortForRoomChecklistExport($items->values());
    }

    /** @param  Collection<int, DailySchedule>  $items */
    private function exportBuildingLabel(Request $request, Collection $items): string
    {
        $buildings = $request->get('buildings');
        if (is_array($buildings) && $buildings !== []) {
            return implode(', ', array_values(array_filter($buildings)));
        }

        if (is_string($buildings) && $buildings !== '') {
            return $buildings;
        }

        if ($building = $request->get('building')) {
            return (string) $building;
        }

        return $items->pluck('building')->filter()->unique()->sort()->implode(', ');
    }

    private function periodSessionLabel(string $session, ?int $periodStart, ?int $periodEnd): string
    {
        return match ($session) {
            'morning' => 'Ca sáng (1-6)',
            'afternoon' => 'Ca chiều (7-12)',
            'evening' => 'Ca tối (13-17)',
            'custom' => trim('Tiết '.($periodStart ?? '…').' - '.($periodEnd ?? '…'), ' -'),
            default => '',
        };
    }

    private function matchesPeriodSession(DailySchedule $item, string $session, ?int $periodStart, ?int $periodEnd): bool
    {
        $start = $this->periodStartNumber((string) ($item->period ?? ''));
        if ($start === null) {
            return false;
        }

        return match ($session) {
            'morning' => $start >= 1 && $start <= 6,
            'afternoon' => $start >= 7 && $start <= 12,
            'evening' => $start >= 13 && $start <= 17,
            'custom' => ($periodStart === null || $start >= $periodStart)
                && ($periodEnd === null || $start <= $periodEnd),
            default => true,
        };
    }

    private function periodStartNumber(string $period): ?int
    {
        if (preg_match('/(\d+)/', trim($period), $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function periodSortNumber(string $period): int
    {
        return $this->periodStartNumber($period) ?? 9999;
    }

    /** @param  Collection<int, DailySchedule>  $items
     * @return Collection<int, DailySchedule>
     */
    private function sortForRoomChecklistExport(Collection $items): Collection
    {
        return $items->sort(function (DailySchedule $a, DailySchedule $b) {
            $periodStartCompare = $this->periodSortNumber((string) ($a->period ?? ''))
                <=> $this->periodSortNumber((string) ($b->period ?? ''));
            if ($periodStartCompare !== 0) {
                return $periodStartCompare;
            }

            $periodCompare = strcmp((string) ($a->period ?? ''), (string) ($b->period ?? ''));
            if ($periodCompare !== 0) {
                return $periodCompare;
            }

            return strnatcasecmp((string) ($a->room ?? ''), (string) ($b->room ?? ''));
        })->values();
    }

    private function exportOfficerName(Request $request): string
    {
        $user = $request->user();
        if (! $user) {
            return '';
        }

        $employee = Employee::query()->where('email', $user->email)->first();

        return trim((string) ($employee?->name ?: $user->name));
    }

    private function normalizeScheduleDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
            return sprintf('%s/%s/%s', $matches[3], $matches[2], $matches[1]);
        }

        return $date;
    }
}
