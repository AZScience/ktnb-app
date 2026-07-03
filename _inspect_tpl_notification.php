<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

$sheets = ['PHONGHOC', 'TRUCTUYEN', 'SINHHOAT', 'VIPHAM', 'LOPTHI', 'THUCHANH'];
$reader = IOFactory::createReader('Xlsx');
$reader->setLoadSheetsOnly($sheets);
$reader->setReadDataOnly(false);
$ss = $reader->load(__DIR__.'/public/templates/daily_report_template.xlsx');

foreach ($sheets as $name) {
    $sh = $ss->getSheetByName($name);
    if (! $sh) {
        echo "Missing sheet: {$name}\n";
        continue;
    }
    $dataStart = $name === 'VIPHAM' ? 8 : 7;
    echo "=== {$name} (row {$dataStart}) ===\n";
    for ($c = 1; $c <= 15; $c++) {
        $letter = Coordinate::stringFromColumnIndex($c);
        $h = trim((string) $sh->getCell("{$letter}6")->getValue());
        if ($h === '' && $c > 13) {
            continue;
        }
        if (stripos($h, 'báo thông') !== false || stripos($h, 'thông tin') !== false || $c <= 12) {
            $cell = $sh->getCell("{$letter}{$dataStart}");
            $style = $cell->getStyle();
            echo "{$letter}6 header: {$h}\n";
            echo "  {$letter}{$dataStart} value: ".var_export($cell->getValue(), true)."\n";
            echo "  font: ".$style->getFont()->getName().' size='.$style->getFont()->getSize()."\n";
            echo "  format: ".$style->getNumberFormat()->getFormatCode()."\n";
            echo "  align H: ".$style->getAlignment()->getHorizontal()."\n";
            $dv = $cell->getDataValidation();
            echo "  validation type: ".$dv->getType().' formula1='.$dv->getFormula1()."\n";
        }
    }
    echo "\n";
}
