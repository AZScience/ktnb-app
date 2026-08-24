<?php

namespace App\Services;

class ReportInteractivePayloadService
{
    public function __construct(
        private ReportQueryService $queries,
        private ReportPresenterService $presenter,
        private ReportFilterOptionsService $filterOptions,
    ) {}

    /** @return array<string, mixed> */
    public function payload(string $variant, string $from, string $to): array
    {
        return match ($variant) {
            'comprehensive' => $this->comprehensive($from, $to),
            'student-violations' => $this->studentViolations($from, $to),
            'good-deeds' => $this->goodDeeds($from, $to),
            'request-reports' => $this->requestReports($from, $to),
            'incident-records-reports' => $this->incidentRecordsReports($from, $to),
            'incident-reports' => $this->incidentReports($from, $to),
            default => abort(404),
        };
    }

    /** @return array{rows: list<array<string, mixed>>, filterOptions: array<string, mixed>} */
    public function comprehensive(string $from, string $to): array
    {
        $rows = $this->presenter->comprehensiveRows($this->queries->schedulesWithIncidents($from, $to));

        return [
            'rows' => $rows,
            'filterOptions' => $this->filterOptions->comprehensive($rows),
        ];
    }

    /** @return array{rows: list<array<string, mixed>>, filterOptions: array<string, mixed>} */
    public function studentViolations(string $from, string $to): array
    {
        $rows = $this->presenter->violationRows($this->queries->violations($from, $to));

        return [
            'rows' => $rows,
            'filterOptions' => $this->filterOptions->violations($rows),
        ];
    }

    /** @return array{filterOptions: array<string, mixed>, tabs: array<string, array{rows: list<array<string, mixed>>}>} */
    public function goodDeeds(string $from, string $to): array
    {
        $items = $this->queries->assetReceptions($from, $to);
        $propertyItems = $items->where('return_status', 'Đã trả')->values();
        $propertyRows = $this->presenter->goodDeedsPropertyRows($propertyItems);

        return [
            'filterOptions' => $this->filterOptions->withBuildingsAndRecipients($propertyRows),
            'tabs' => [
                'property' => ['rows' => $propertyRows],
                'deed' => ['rows' => $this->presenter->goodDeedsGratitudeRows($items)],
            ],
        ];
    }

    /** @return array{rows: list<array<string, mixed>>, filterOptions: array<string, mixed>} */
    public function requestReports(string $from, string $to): array
    {
        $rows = $this->presenter->requestRows($this->queries->serviceRequests($from, $to));

        return [
            'rows' => $rows,
            'filterOptions' => $this->filterOptions->withBuildingsAndRecipients($rows),
        ];
    }

    /** @return array{rows: list<array<string, mixed>>, filterOptions: array<string, mixed>} */
        /** @return array{rows: list<array<string, mixed>>, filterOptions: array<string, mixed>} */
    public function incidentRecordsReports(string $from, string $to): array
    {
        $rows = $this->presenter->incidentRecordsRows($this->queries->incidentRecords($from, $to));

        return [
            'rows' => $rows,
            'filterOptions' => $this->filterOptions->incidentRecords($rows),
        ];
    }

    public function incidentReports(string $from, string $to): array
    {
        $rows = $this->presenter->petitionRows($this->queries->petitions($from, $to));

        return [
            'rows' => $rows,
            'filterOptions' => $this->filterOptions->withBuildingsAndRecipients($rows),
        ];
    }
}
