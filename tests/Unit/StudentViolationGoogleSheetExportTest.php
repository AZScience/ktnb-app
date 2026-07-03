<?php

namespace Tests\Unit;

use App\Services\DailyReportService;
use App\Services\StudentViolationCrosstabService;
use Tests\TestCase;

class StudentViolationGoogleSheetExportTest extends TestCase
{
    public function test_ui_export_has_sixteen_data_columns(): void
    {
        $service = new StudentViolationCrosstabService;

        $this->assertCount(16, $service->exportColumnKeys());
        $this->assertSame($service->exportColumnKeys(), $service->spreadsheetExportColumnKeys());
    }

    public function test_google_sheet_export_includes_gap_columns_for_legacy_sheet_layout(): void
    {
        $service = new StudentViolationCrosstabService;

        $keys = $service->googleSheetExportColumnKeys();

        $this->assertCount(18, $keys);
        $this->assertSame(['officer', 'violation_date', 'building'], array_slice($keys, 0, 3));
        $this->assertSame(['__gap__', '__gap__'], array_slice($keys, 3, 2));
        $this->assertSame(
            array_column($service->violationTypeColumns(), 'key'),
            array_slice($keys, 5),
        );
    }

    public function test_daily_report_google_sheet_push_fields_use_gap_layout(): void
    {
        $daily = app(DailyReportService::class);

        $fields = $daily->googleSheetPushFields('violations');

        $this->assertCount(18, $fields);
        $this->assertSame('__gap__', $fields[3]);
        $this->assertSame('__gap__', $fields[4]);
    }

    public function test_prepare_rows_for_google_sheet_push_preserves_violation_counts(): void
    {
        $daily = app(DailyReportService::class);

        $rows = $daily->prepareRowsForGoogleSheetPush('violations', [[
            'officer' => 'CB A',
            'violation_date' => '22/06/2026',
            'building' => 'Cơ sở 1',
            'cup_tiet' => 2,
            'di_hoc_tre' => 0,
            'khac' => 1,
        ]]);

        $this->assertCount(1, $rows);
        $this->assertSame('CB A', $rows[0]['officer']);
        $this->assertSame('', $rows[0]['__gap__']);
        $this->assertSame(2, $rows[0]['cup_tiet']);
        $this->assertSame('', $rows[0]['di_hoc_tre']);
        $this->assertSame(1, $rows[0]['khac']);
    }
}
