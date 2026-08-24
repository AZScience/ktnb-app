<?php

namespace App\Services;

use App\Models\BuildingBlock;
use App\Models\Department;
use App\Models\Employee;
use App\Models\IncidentCategory;
use App\Models\Lecturer;
use App\Models\Recognition;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class ReportFilterOptionsService
{
    private const CACHE_TTL = 3600;

    /** @return list<array{label: string, value: string}> */
    private function sortedOptions(array $values): array
    {
        $values = array_values(array_unique(array_filter(array_map(
            fn ($v) => trim((string) $v),
            $values
        ), fn ($v) => $v !== '' && $v !== '---')));

        sort($values, SORT_LOCALE_STRING);

        return array_map(fn ($v) => ['label' => $v, 'value' => $v], $values);
    }

    /** @return list<string> */
    private function buildingNames(): array
    {
        return Cache::remember('report-filter:buildings', self::CACHE_TTL, fn () => BuildingBlock::query()->pluck('name')->all());
    }

    /** @return list<string> */
    private function buildingNotes(): array
    {
        return Cache::remember('report-filter:building-notes', self::CACHE_TTL, fn () => BuildingBlock::query()
            ->whereNotNull('note')
            ->pluck('note')
            ->all());
    }

    /** @return list<string> */
    private function departmentNames(): array
    {
        return Cache::remember('report-filter:departments', self::CACHE_TTL, fn () => Department::query()->pluck('name')->all());
    }

    /** @return list<string> */
    private function employeeDisplayNames(): array
    {
        return Cache::remember('report-filter:employees', self::CACHE_TTL, function () {
            return Employee::query()
                ->select(['nickname', 'name'])
                ->get()
                ->map(fn (Employee $e) => $e->nickname ?: $e->name)
                ->all();
        });
    }

    /** @return list<string> */
    private function lecturerNames(): array
    {
        return Cache::remember('report-filter:lecturers', self::CACHE_TTL, fn () => Lecturer::query()->pluck('name')->all());
    }

    /** @return list<string> */
    private function incidentCategoryNames(): array
    {
        return Cache::remember('report-filter:incident-categories', self::CACHE_TTL, fn () => IncidentCategory::query()->pluck('name')->all());
    }

    /** @return list<string> */
    private function recognitionNames(): array
    {
        return Cache::remember('report-filter:recognitions', self::CACHE_TTL, fn () => Recognition::query()
            ->orderBy('name')
            ->pluck('name')
            ->all());
    }

    /** @return list<string> */
    private function employeeNicknames(): array
    {
        return Cache::remember('report-filter:employee-nicknames', self::CACHE_TTL, fn () => Employee::query()->whereNotNull('nickname')->pluck('nickname')->all());
    }

    /** @param  list<array<string, mixed>>  $rows */
    public function comprehensive(array $rows): array
    {
        $buildings = $this->buildingNames();
        $departments = $this->departmentNames();
        $employees = $this->employeeDisplayNames();
        $lecturers = $this->lecturerNames();
        $recognitions = $this->recognitionNames();

        foreach ($rows as $row) {
            if (! empty($row['_building'])) {
                $buildings[] = $row['_building'];
            }
            if (! empty($row['department'])) {
                $departments[] = $row['department'];
            }
            if (! empty($row['employee'])) {
                $employees[] = $row['employee'];
            }
            if (! empty($row['_lecturerRaw'])) {
                $lecturers[] = $row['_lecturerRaw'];
            }
            if (! empty($row['_recognition']) && $row['_recognition'] !== '---') {
                $recognitions[] = $row['_recognition'];
            }
            foreach ($row['_proctors'] ?? [] as $proctor) {
                if ($proctor) {
                    $lecturers[] = $proctor;
                }
            }
        }

        return [
            'buildings' => $this->sortedOptions($buildings),
            'buildingNotes' => $this->sortedOptions($this->buildingNotes()),
            'departments' => $this->sortedOptions($departments),
            'employees' => $this->sortedOptions($employees),
            'lecturers' => $this->sortedOptions($lecturers),
            'recognitions' => $this->sortedOptions($recognitions),
            'users' => $this->userOptions(),
        ];
    }

    /** @return list<array{label: string, value: string}> */
    public function userOptions(): array
    {
        return Cache::remember('report-filter:users', self::CACHE_TTL, function () {
            $names = User::query()->orderBy('name')->pluck('name')->all();

            return $this->sortedOptions($names);
        });
    }

    /** @return array<string, list<array{label: string, value: string}>> */
    public function withBuildingsAndRecipients(array $rows): array
    {
        $buildings = $this->buildingNames();
        $buildingNotes = $this->buildingNotes();
        $recipients = $this->employeeNicknames();

        foreach ($rows as $row) {
            if (! empty($row['_building'])) {
                $buildings[] = $row['_building'];
            }
            if (! empty($row['_recipient'])) {
                $recipients[] = $row['_recipient'];
            }
        }

        return [
            'buildings' => $this->sortedOptions($buildings),
            'buildingNotes' => $this->sortedOptions($buildingNotes),
            'recipients' => $this->sortedOptions($recipients),
        ];
    }

    /** @return array<string, list<array{label: string, value: string}>> */
        /** @return array<string, list<array{label: string, value: string}>> */
    public function incidentRecords(array $rows): array
    {
        $locations = [];
        $creator_names = [];

        foreach ($rows as $row) {
            if (!empty($row['location'])) {
                $locations[] = $row['location'];
            }
            if (!empty($row['creator_name'])) {
                $creator_names[] = $row['creator_name'];
            }
        }

        return [
            'locations' => $this->sortedOptions($locations),
            'creator_names' => $this->sortedOptions($creator_names),
        ];
    }

    public function violations(array $rows): array
    {
        $buildings = $this->buildingNames();
        $officers = $this->employeeDisplayNames();
        $violationTypes = $this->incidentCategoryNames();

        foreach ($rows as $row) {
            if (! empty($row['_building']) && $row['_building'] !== '---') {
                $buildings[] = $row['_building'];
            }
            if (! empty($row['officer']) && $row['officer'] !== '---') {
                $officers[] = $row['officer'];
            }
            if (! empty($row['violationType']) && $row['violationType'] !== '---') {
                $violationTypes[] = $row['violationType'];
            }
        }

        return [
            'buildings' => $this->sortedOptions($buildings),
            'officers' => $this->sortedOptions($officers),
            'violationTypes' => $this->sortedOptions($violationTypes),
        ];
    }
}
