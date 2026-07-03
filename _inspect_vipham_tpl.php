<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;

$path = $argv[1] ?? __DIR__.'/public/templates/daily_report_template.xlsx';
echo "Template: {$path}\n";
$r = IOFactory::createReader('Xlsx');
$r->setLoadSheetsOnly(['VIPHAM']);
$ss = $r->load($path);
$sh = $ss->getSheetByName('VIPHAM');

for ($row = 5; $row <= 8; $row++) {
    echo "=== Row {$row} ===\n";
    for ($c = 1; $c <= 15; $c++) {
        $l = Coordinate::stringFromColumnIndex($c);
        $cell = $sh->getCell($l.$row);
        $v = trim((string) $cell->getValue());
        if ($v === '') {
            continue;
        }
        echo "  {$l}: {$v}\n";
    }
}

echo "\nPHONGHOC row 6:\n";
$r2 = IOFactory::createReader('Xlsx');
$r2->setLoadSheetsOnly(['PHONGHOC']);
$ss2 = $r2->load($path);
$ph = $ss2->getSheetByName('PHONGHOC');
for ($c = 1; $c <= 13; $c++) {
    $l = Coordinate::stringFromColumnIndex($c);
    $v = trim((string) $ph->getCell($l.'6')->getValue());
    if ($v !== '') {
        echo "  {$l}: {$v}\n";
    }
}

echo "\nMerged cells row 6-7:\n";
foreach ($sh->getMergeCells() as $range) {
    if (preg_match('/^[A-T]([67]):/', $range)) {
        echo "  {$range}\n";
    }
}
