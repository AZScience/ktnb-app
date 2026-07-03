<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

$path = __DIR__.'/public/templates/lichhoc_template.xlsx';
$ss = IOFactory::load($path);
$sh = $ss->getActiveSheet();
echo 'Sheet: '.$sh->getTitle()."\n";
for ($row = 1; $row <= 15; $row++) {
    $cells = [];
    for ($c = 1; $c <= 12; $c++) {
        $l = Coordinate::stringFromColumnIndex($c);
        $v = trim((string) $sh->getCell($l.$row)->getFormattedValue());
        if ($v !== '') $cells[] = "{$l}={$v}";
    }
    if ($cells) echo "Row {$row}: ".implode(' | ', $cells)."\n";
}
echo 'Merged: '.implode(', ', $sh->getMergeCells())."\n";
