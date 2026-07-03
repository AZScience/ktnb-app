<?php
require __DIR__.'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
ini_set('memory_limit', '512M');

$r = IOFactory::createReader('Xlsx');
$r->setIncludeCharts(false);
$r->setReadDataOnly(false);
$r->setReadEmptyCells(false);
$r->setIgnoreRowsWithNoCells(true);
$r->setLoadSheetsOnly(['PHONGHOC', 'TRUCTUYEN', 'SINHHOAT', 'LOPTHI', 'THUCHANH']);

$path = __DIR__.'/public/templates/daily_report_template.xlsx';
echo 'size: '.filesize($path)."\n";

try {
    $ss = $r->load($path);
    echo 'sheets: '.count($ss->getAllSheets())."\n";
    foreach ($ss->getAllSheets() as $sh) {
        echo ' - '.$sh->getTitle()."\n";
    }
} catch (Throwable $e) {
    echo 'error: '.$e->getMessage()."\n";
}
