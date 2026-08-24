<?php

namespace Tests\Unit;

use App\Services\CatalogExcelService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CatalogExcelServiceTest extends TestCase
{
    private CatalogExcelService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CatalogExcelService;
    }

    public function test_parse_name_note_rows_from_header_row(): void
    {
        $path = $this->writeSpreadsheet([
            ['Tên quà tặng', 'Ghi chú'],
            ['Hoa tươi', 'Sinh nhật'],
            ['Sách', ''],
        ]);

        $rows = $this->service->parseNameNoteRows($path, 'Tên quà tặng');

        $this->assertSame([
            ['name' => 'Hoa tươi', 'note' => 'Sinh nhật'],
            ['name' => 'Sách', 'note' => ''],
        ], $rows);
    }

    public function test_parse_rows_skips_header_labels_in_data(): void
    {
        $path = $this->writeSpreadsheet([
            ['Mã số', 'Họ và tên', 'Email', 'Ghi chú'],
            ['NTT-001', 'Nguyễn Văn A', 'a@ntt.edu.vn', ''],
        ]);

        $rows = $this->service->parseRows($path, [
            'employee_id' => 'Mã số',
            'name' => 'Họ và tên',
            'email' => 'Email',
            'note' => 'Ghi chú',
        ], [
            'requiredKey' => 'employee_id',
        ]);

        $this->assertCount(1, $rows);
        $this->assertSame('NTT-001', $rows[0]['employee_id']);
        $this->assertSame('Nguyễn Văn A', $rows[0]['name']);
    }

    public function test_to_bool_recognizes_vietnamese_values(): void
    {
        $this->assertTrue(CatalogExcelService::toBool('Có'));
        $this->assertTrue(CatalogExcelService::toBool('x'));
        $this->assertFalse(CatalogExcelService::toBool('Không'));
        $this->assertFalse(CatalogExcelService::toBool(''));
    }

    /** @param list<list<string>> $matrix */
    private function writeSpreadsheet(array $matrix): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($matrix as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $address = Coordinate::stringFromColumnIndex($colIndex + 1).($rowIndex + 1);
                $sheet->setCellValue($address, $value);
            }
        }

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'catalog-excel-test-'.uniqid('', true).'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        $this->assertFileExists($path);

        return $path;
    }
}
