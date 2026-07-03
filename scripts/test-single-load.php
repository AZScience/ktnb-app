<?php
ini_set('memory_limit', '512M');
require __DIR__.'/../vendor/autoload.php';

use App\Services\ScheduleImportReadFilter;
use PhpOffice\PhpSpreadsheet\IOFactory;

$path = realpath(__DIR__.'/../../public/templates/daily_schedule_template.xlsx');
$t = microtime(true);
$reader = IOFactory::createReaderForFile($path);
$reader->setReadDataOnly(true);
$reader->setReadEmptyCells(false);
$reader->setReadFilter(new ScheduleImportReadFilter());
$wb = $reader->load($path);
echo 'Sheets loaded: '.count($wb->getAllSheets()).' in '.round(microtime(true) - $t, 2)."s\n";
echo 'Peak memory: '.round(memory_get_peak_usage(true) / 1024 / 1024)."MB\n";
