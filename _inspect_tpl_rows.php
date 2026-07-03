<?php
require __DIR__.'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

$path = __DIR__.'/public/templates/daily_report_template.xlsx';
$r = IOFactory::createReader('Xlsx');
$r->setLoadSheetsOnly(['TRUCTUYEN', 'PHONGHOC']);
$r->setReadEmptyCells(false);
$r->setIgnoreRowsWithNoCells(true);
$ss = $r->load($path);
foreach (['TRUCTUYEN', 'PHONGHOC'] as $name) {
    $sh = $ss->getSheetByName($name);
    echo "=== $name row 6-7 ===\n";
    for ($c = 1; $c <= 13; $c++) {
        $letter = Coordinate::stringFromColumnIndex($c);
        $h = trim((string)$sh->getCell("{$letter}6")->getValue());
        if ($h === '') continue;
        $cell7 = $sh->getCell("{$letter}7");
        $st = $cell7->getStyle();
        $dv = $cell7->getDataValidation();
        echo "{$letter} header={$h}\n";
        echo "  r7: ".var_export($cell7->getValue(),true)." dt=".$cell7->getDataType()
            ." font=".$st->getFont()->getName()."/".$st->getFont()->getSize()
            ." border=".($st->getBorders()->getAllBorders()->getBorderStyle())
            ." h=".$st->getAlignment()->getHorizontal()
            ." dv=".$dv->getType()."\n";
    }
}
