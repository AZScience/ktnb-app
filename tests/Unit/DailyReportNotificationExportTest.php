<?php

namespace Tests\Unit;

use App\Services\DailyReportExportService;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use ReflectionMethod;
use Tests\TestCase;

class DailyReportNotificationExportTest extends TestCase
{
    public function test_notification_column_exports_checkbox_glyphs_for_every_row(): void
    {
        $service = app(DailyReportExportService::class);
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('PHONGHOC');

        // Header + template row giống mẫu thật (ô thông báo từng là boolean).
        $sheet->setCellValue('A6', 'STT');
        $sheet->setCellValue('B6', 'Nhân viên');
        $sheet->setCellValue('C6', 'Có báo thông tin');
        $sheet->setCellValue('A7', 1);
        $sheet->setCellValue('B7', '');
        $sheet->getCell('C7')->setValueExplicit(false, DataType::TYPE_BOOL);

        $fill = new ReflectionMethod(DailyReportExportService::class, 'fillDataRows');
        $fill->setAccessible(true);
        $fill->invoke(
            $service,
            $sheet,
            [
                ['employee' => 'A', 'is_notification' => true],
                ['employee' => 'B', 'is_notification' => false],
                ['employee' => 'C', 'is_notification' => 0],
            ],
            ['employee', 'is_notification'],
            7,
            'PHONGHOC',
        );

        $this->assertSame('☑', $sheet->getCell('C7')->getValue());
        $this->assertSame('☐', $sheet->getCell('C8')->getValue());
        $this->assertSame('☐', $sheet->getCell('C9')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('C7')->getDataType());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('C8')->getDataType());
        $this->assertNotSame('TRUE', strtoupper((string) $sheet->getCell('C7')->getFormattedValue()));
        $this->assertNotSame('FALSE', strtoupper((string) $sheet->getCell('C8')->getFormattedValue()));
    }
}
