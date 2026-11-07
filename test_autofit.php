<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', "This is a long text\nthat should wrap\nand auto fit.");
$sheet->getStyle('A1')->getAlignment()->setWrapText(true);
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getRowDimension(1)->setRowHeight(-1);

$writer = new Xlsx($spreadsheet);
$writer->save('test_autofit.xlsx');
echo "Done";
