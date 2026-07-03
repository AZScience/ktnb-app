<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

$path = __DIR__.'/public/templates/vipham_template.xlsx';
$ss = IOFactory::load($path);
$sh = $ss->getActiveSheet();

echo "All non-empty cells rows 1-15:\n";
for ($row = 1; $row <= 15; $row++) {
    for ($c = 1; $c <= 11; $c++) {
        $l = Coordinate::stringFromColumnIndex($c);
        $cell = $sh->getCell($l.$row);
        $v = $cell->getValue();
        if ($v !== null && $v !== '') {
            $s = $cell->getStyle();
            echo "{$l}{$row}: ".json_encode($v)
                .' merge?'
                .' bold='.($s->getFont()->getBold() ? '1':'0')
                .' size='.$s->getFont()->getSize()
                .' h='.$s->getAlignment()->getHorizontal()
                ."\n";
        }
    }
}

echo "\nColumn widths:\n";
foreach (range('A','K') as $col) {
    $w = $sh->getColumnDimension($col)->getWidth();
    if ($w >= 0) echo "$col: $w\n";
}
