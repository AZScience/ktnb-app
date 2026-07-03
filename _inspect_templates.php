<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

foreach (['lichhoc' => 'lichhoc_template.xlsx', 'vipham' => 'vipham_template.xlsx'] as $label => $file) {
    $path = __DIR__."/public/templates/{$file}";
    $ss = IOFactory::load($path);
    $sh = $ss->getActiveSheet();
    echo "\n===== {$label} =====\n";
    echo 'Merged: '.implode(' | ', $sh->getMergeCells())."\n";
    for ($row = 1; $row <= 8; $row++) {
        for ($c = 1; $c <= 11; $c++) {
            $l = Coordinate::stringFromColumnIndex($c);
            $cell = $sh->getCell($l.$row);
            $v = $cell->getValue();
            if ($v !== null && $v !== '') {
                echo "  {$l}{$row}: ".json_encode($v)."\n";
            }
        }
    }
}
