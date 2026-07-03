<?php
require __DIR__.'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

$path = __DIR__.'/storage/app/_minimal.xlsx';
$ss = new Spreadsheet();
$ss->getActiveSheet()->setTitle('PHONGHOC');
$ss->getActiveSheet()->setCellValue('A1', 'test');
IOFactory::createWriter($ss, 'Xlsx')->save($path);

$r = IOFactory::createReader('Xlsx');
$loaded = $r->load($path);
echo 'minimal sheets: '.count($loaded->getAllSheets())."\n";
