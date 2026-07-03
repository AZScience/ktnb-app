<?php
require __DIR__.'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
$r = IOFactory::createReader('Xlsx');
$r->setReadDataOnly(false);
$r->setLoadSheetsOnly(['PHONGHOC']);
$ss = $r->load(__DIR__.'/public/templates/daily_report_template.xlsx');
echo 'sheets: '.count($ss->getAllSheets()).PHP_EOL;
