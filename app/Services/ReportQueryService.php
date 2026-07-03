<?php

namespace App\Services;

use App\Models\AssetReception;
use App\Models\DailySchedule;
use App\Models\Petition;
use App\Models\ServiceRequest;
use App\Models\StudentViolation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportQueryService
{
    public function normalizeDate(?string $date): string
    {
        if (! $date) {
            return date('d/m/Y');
        }
        if (str_contains($date, '-')) {
            [$y, $m, $d] = explode('-', $date);

            return sprintf('%s/%s/%s', $d, $m, $y);
        }

        return $date;
    }

    public function isoFromDisplayDate(?string $date): ?string
    {
        if (! $date || $date === '---') {
            return null;
        }

        if (str_contains($date, '/')) {
            [$d, $m, $y] = explode('/', $date);

            return sprintf('%04d-%02d-%02d', (int) $y, (int) $m, (int) $d);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        return null;
    }

    /** @return array{0: string, 1: string} ISO from/to for initial page load */
    public function defaultInitialRange(string $variant = 'default'): array
    {
        $today = date('Y-m-d');

        return match ($variant) {
            'comprehensive' => [date('Y-m-d', strtotime('-6 days')), $today],
            default => [$today, $today],
        };
    }

    /** @param  list<array<string, mixed>>  $rows */
    public function defaultIsoDateRangeFromRows(array $rows, string $dateField = 'date'): array
    {
        $isoDates = collect($rows)
            ->map(fn (array $row) => $this->isoFromDisplayDate($row[$dateField] ?? null))
            ->filter()
            ->sort()
            ->values();

        $today = date('Y-m-d');

        return [
            $isoDates->first() ?? $today,
            $isoDates->last() ?? $today,
        ];
    }

    public function schedulesForDate(string $date): Collection
    {
        return DailySchedule::query()
            ->forListTable()
            ->where('date', $date)
            ->orderBy('period')
            ->get();
    }

    public function schedulesWithIncidents(?string $from, ?string $to): Collection
    {
        $from = $this->normalizeDate($from);
        $to = $this->normalizeDate($to ?? $from);

        return DailySchedule::query()
            ->forListTable()
            ->whereNotNull('incident')
            ->where('incident', '!=', '')
            ->where(fn (Builder $q) => $this->whereDisplayDateBetween($q, 'date', $from, $to))
            ->orderByRaw('STR_TO_DATE(date, "%d/%m/%Y") DESC')
            ->orderBy('period')
            ->get();
    }

    public function allSchedulesWithIncidents(): Collection
    {
        return $this->schedulesWithIncidents(
            date('01/m/Y'),
            date('d/m/Y')
        );
    }

    public function violations(?string $from, ?string $to): Collection
    {
        $from = $this->normalizeDate($from);
        $to = $this->normalizeDate($to ?? $from);

        return StudentViolation::query()
            ->select(StudentViolation::INDEX_COLUMNS)
            ->where(fn (Builder $q) => $this->whereDisplayDateBetween($q, 'violation_date', $from, $to))
            ->orderByRaw('STR_TO_DATE(violation_date, "%d/%m/%Y") DESC')
            ->get();
    }

    public function allViolations(): Collection
    {
        return $this->violations(date('d/m/Y'), date('d/m/Y'));
    }

    public function assetReceptions(?string $from, ?string $to): Collection
    {
        $from = $this->normalizeDate($from);
        $to = $this->normalizeDate($to ?? $from);

        return AssetReception::query()
            ->where(function (Builder $q) use ($from, $to) {
                foreach (['reception_date', 'gratitude_date', 'resolution_date'] as $field) {
                    $q->orWhere(fn (Builder $inner) => $this->whereDisplayDateBetween($inner, $field, $from, $to));
                }
            })
            ->orderByRaw('STR_TO_DATE(reception_date, "%d/%m/%Y") DESC')
            ->get();
    }

    public function allAssetReceptions(): Collection
    {
        return $this->assetReceptions(date('d/m/Y'), date('d/m/Y'));
    }

    public function serviceRequests(?string $from, ?string $to): Collection
    {
        $from = $this->normalizeDate($from);
        $to = $this->normalizeDate($to ?? $from);

        return ServiceRequest::query()
            ->whereRaw(
                "STR_TO_DATE(COALESCE(reception_date, request_date), '%d/%m/%Y') BETWEEN STR_TO_DATE(?, '%d/%m/%Y') AND STR_TO_DATE(?, '%d/%m/%Y')",
                [$from, $to]
            )
            ->orderByRaw('STR_TO_DATE(COALESCE(reception_date, request_date), "%d/%m/%Y") DESC')
            ->get();
    }

    public function allServiceRequests(): Collection
    {
        return $this->serviceRequests(date('d/m/Y'), date('d/m/Y'));
    }

    public function petitions(?string $from, ?string $to): Collection
    {
        $from = $this->normalizeDate($from);
        $to = $this->normalizeDate($to ?? $from);

        return Petition::query()
            ->where(fn (Builder $q) => $this->whereDisplayDateBetween($q, 'reception_date', $from, $to))
            ->orderByRaw('STR_TO_DATE(reception_date, "%d/%m/%Y") DESC')
            ->get();
    }

    public function allPetitions(): Collection
    {
        return $this->petitions(date('d/m/Y'), date('d/m/Y'));
    }

    private function whereDisplayDateBetween(
        Builder $query,
        string $column,
        string $from,
        string $to,
        bool $raw = false
    ): void {
        $expression = $raw ? $column : $column;

        $query->whereRaw(
            "STR_TO_DATE({$expression}, '%d/%m/%Y') BETWEEN STR_TO_DATE(?, '%d/%m/%Y') AND STR_TO_DATE(?, '%d/%m/%Y')",
            [$from, $to]
        );
    }

    private function dateInRange(?string $date, string $from, string $to): bool
    {
        if (! $date) {
            return false;
        }

        $normalized = $this->normalizeDate($date);

        return $normalized >= $from && $normalized <= $to;
    }

    public function monitoredSchedules(string $date, string $module): Collection
    {
        return DailySchedule::query()
            ->forListTable()
            ->where('date', $date)
            ->forModule($module)
            ->whereNotNull('employee')
            ->where('employee', '!=', '')
            ->orderBy('period')
            ->get();
    }
}
