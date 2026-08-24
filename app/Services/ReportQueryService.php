<?php

namespace App\Services;

use App\Models\AssetReception;
use App\Models\DailySchedule;
use App\Models\Petition;
use App\Models\IncidentRecord;
use App\Models\ServiceRequest;
use App\Models\StudentViolation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportQueryService
{
    /** @var array<string, bool> */
    private array $isoColumnCache = [];

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
            ->tap(fn (Builder $q) => $this->orderByDisplayDate($q, 'date', 'desc'))
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
            ->tap(fn (Builder $q) => $this->orderByDisplayDate($q, 'violation_date', 'desc'))
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
            ->tap(fn (Builder $q) => $this->orderByDisplayDate($q, 'reception_date', 'desc'))
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
            ->where(fn (Builder $q) => $this->whereDisplayDateBetween($q, 'COALESCE(reception_date_iso, request_date_iso)', $from, $to, true))
            ->tap(fn (Builder $q) => $this->orderByDisplayDate($q, 'COALESCE(reception_date_iso, request_date_iso)', 'desc', true))
            ->get();
    }

    public function allServiceRequests(): Collection
    {
        return $this->serviceRequests(date('d/m/Y'), date('d/m/Y'));
    }

        public function incidentRecords(?string $from, ?string $to): Collection
    {
        $query = IncidentRecord::query();

        if ($from && $to) {
            $query->whereDate('incident_time', '>=', $this->isoFromDisplayDate($from))
                  ->whereDate('incident_time', '<=', $this->isoFromDisplayDate($to));
        }

        return $query->orderBy('incident_time', 'desc')->get();
    }

    public function petitions(?string $from, ?string $to): Collection
    {
        $from = $this->normalizeDate($from);
        $to = $this->normalizeDate($to ?? $from);

        return Petition::query()
            ->where(fn (Builder $q) => $this->whereDisplayDateBetween($q, 'reception_date', $from, $to))
            ->tap(fn (Builder $q) => $this->orderByDisplayDate($q, 'reception_date', 'desc'))
            ->get();
    }

    public function allPetitions(): Collection
    {
        return $this->petitions(date('d/m/Y'), date('d/m/Y'));
    }

    public function whereDisplayDateBetween(
        $query,
        string $column,
        string $from,
        string $to,
        bool $raw = false
    ): void {
        $expression = $this->displayDateKeyExpression($query, $column, $raw);

        $query->whereRaw(
            "{$expression} BETWEEN ? AND ?",
            [$this->normalizeIsoDate($from), $this->normalizeIsoDate($to)]
        );
    }

    public function orderByDisplayDate($query, string $column, string $direction = 'asc', bool $raw = false): void
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        $query->orderByRaw($this->displayDateKeyExpression($query, $column, $raw).' '.$direction);
    }

    private function displayDateKeyExpression($query, string $column, bool $raw = false): string
    {
        $value = $this->preferredDateColumnExpression($query, $column, $raw);
        $driver = DB::connection($query->getConnection()->getName())->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            return "CASE
                WHEN {$value} REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN {$value}
                WHEN {$value} REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}$' THEN DATE_FORMAT(STR_TO_DATE({$value}, '%d/%m/%Y'), '%Y-%m-%d')
                ELSE NULL
            END";
        }

        return "CASE
            WHEN {$value} LIKE '____-__-__' THEN {$value}
            WHEN {$value} LIKE '__/__/____' THEN substr({$value}, 7, 4) || '-' || substr({$value}, 4, 2) || '-' || substr({$value}, 1, 2)
            WHEN {$value} LIKE '_/__/____' THEN substr({$value}, 6, 4) || '-' || substr({$value}, 3, 2) || '-0' || substr({$value}, 1, 1)
            WHEN {$value} LIKE '__/_/____' THEN substr({$value}, 6, 4) || '-0' || substr({$value}, 4, 1) || '-' || substr({$value}, 1, 2)
            WHEN {$value} LIKE '_/_/____' THEN substr({$value}, 5, 4) || '-0' || substr({$value}, 3, 1) || '-0' || substr({$value}, 1, 1)
            ELSE NULL
        END";
    }

    private function preferredDateColumnExpression($query, string $column, bool $raw): string
    {
        if ($raw) {
            return $column;
        }

        $grammar = $query->getQuery()->getGrammar();
        $table = method_exists($query, 'getModel')
            ? $query->getModel()->getTable()
            : (string) $query->from;
        $table = trim(preg_split('/\s+as\s+|\s+/i', $table)[0] ?? $table, '`"[] ');
        $isoColumn = $column.'_iso';
        $cacheKey = "{$table}.{$isoColumn}";

        if (! array_key_exists($cacheKey, $this->isoColumnCache)) {
            $this->isoColumnCache[$cacheKey] = $table !== '' && Schema::hasColumn($table, $isoColumn);
        }

        return $grammar->wrap($this->isoColumnCache[$cacheKey] ? $isoColumn : $column);
    }

    private function normalizeIsoDate(?string $date): string
    {
        $normalized = $this->normalizeDate($date);
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $normalized, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        return $normalized;
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
