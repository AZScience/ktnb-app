<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ScheduleImportReadFilter;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$samplePath = __DIR__.'/../storage/app/test_schedule_import.xlsx';
$spreadsheet = new Spreadsheet;
$sheet = $spreadsheet->getActiveSheet();
$headers = ['STT', 'Ngày', 'Dãy nhà', 'Phòng', 'Tiết', 'LT/TH', 'Sĩ số', 'Khoa sử dụng', 'Lớp', 'Giảng viên', 'CBCT 01', 'CBCT 02', 'CBCT 03', 'Nội dung', 'Trạng thái', 'Ghi chú'];
foreach ($headers as $i => $header) {
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).'7', $header);
}
$sheet->setCellValue('B8', '17/06/2026');
$sheet->setCellValue('C8', 'A');
$sheet->setCellValue('D8', '101');
$sheet->setCellValue('E8', '1-3');
$sheet->setCellValue('F8', 'LT');
$sheet->setCellValue('G8', 40);
$sheet->setCellValue('H8', 'CNTT');
$sheet->setCellValue('I8', 'DH22CNTT');
$sheet->setCellValue('J8', 'Nguyễn Văn A');
$sheet->setCellValue('N8', 'Lập trình');
$sheet->setCellValue('O8', 'Phòng học');
(new Xlsx($spreadsheet))->save($samplePath);

$paths = [$samplePath, realpath(__DIR__.'/../../public/templates/daily_schedule_template.xlsx')];

foreach (array_filter($paths) as $path) {
    echo "=== {$path}\n";
    ini_set('memory_limit', '512M');
    $reader = IOFactory::createReaderForFile($path);
    $reader->setReadDataOnly(true);
    $reader->setReadEmptyCells(false);
    $reader->setReadFilter(new ScheduleImportReadFilter());
    $names = $reader->listWorksheetNames($path);
    echo 'Sheets: '.implode(', ', $names)."\n";
    foreach ($names as $name) {
        $reader->setLoadSheetsOnly([$name]);
        $wb = $reader->load($path);
        $ws = $wb->getActiveSheet();
        echo "  Sheet {$name}, highest row: ".$ws->getHighestDataRow()."\n";
        for ($row = 1; $row <= 12; $row++) {
            $cells = [];
            for ($c = 1; $c <= 5; $c++) {
                $col = Coordinate::stringFromColumnIndex($c);
                $v = trim((string) $ws->getCell($col.$row)->getValue());
                if ($v !== '') {
                    $cells[] = $col.':'.$v;
                }
            }
            if ($cells) {
                echo "    row {$row}: ".implode(' | ', $cells)."\n";
            }
        }
        $wb->disconnectWorksheets();
    }
    echo "\n";
}

use App\Services\ScheduleExcelService;
use Illuminate\Http\UploadedFile;

$svc = app(ScheduleExcelService::class);
$file = new UploadedFile($samplePath, 'test.xlsx', null, null, true);
$result = $svc->importPreview($file);
echo "Import sample rawCount: {$result['rawCount']}\n";
