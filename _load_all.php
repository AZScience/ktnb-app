<?php
require __DIR__.'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
ini_set('memory_limit', '256M');
$r = IOFactory::createReader('Xlsx');
$r->setReadDataOnly(false);
$ss = $r->load(__DIR__.'/public/templates/daily_report_template.xlsx');
echo 'all sheets: '.count($ss->getAllSheets())."\n";
