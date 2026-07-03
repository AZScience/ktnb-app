<?php
require __DIR__.'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

foreach ([
    __DIR__.'/public/templates/daily_report_template.xlsx',
    dirname(__DIR__).'/public/templates/daily_report_template.xlsx',
] as $path) {
    echo "=== {$path} ===\n";
    if (! is_file($path)) {
        echo "missing\n\n";
        continue;
    }
    echo 'size: '.filesize($path)."\n";
    try {
        $r = IOFactory::createReader('Xlsx');
        $r->setLoadSheetsOnly(['PHONGHOC']);
        $ss = $r->load($path);
        echo 'sheets: '.count($ss->getAllSheets())."\n";
        $k7 = $ss->getSheetByName('PHONGHOC')->getCell('K7');
        echo 'K7: '.var_export($k7->getValue(), true).' dt='.$k7->getDataType()."\n";
    } catch (Throwable $e) {
        echo 'error: '.$e->getMessage()."\n";
    }
    echo "\n";
}

$zipDir = __DIR__.'/public/templates/_tpl_unzip';
if (is_dir($zipDir)) {
    $repacked = __DIR__.'/public/templates/_repacked_template.xlsx';
    if (is_file($repacked)) {
        unlink($repacked);
    }
    $zip = new ZipArchive();
    if ($zip->open($repacked, ZipArchive::CREATE) === true) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($zipDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relative = substr($filePath, strlen(realpath($zipDir)) + 1);
            $relative = str_replace('\\', '/', $relative);
            if ($file->isDir()) {
                continue;
            }
            $zip->addFile($filePath, $relative);
        }
        $zip->close();
        echo "Repacked: {$repacked} size=".filesize($repacked)."\n";
        try {
            $r = IOFactory::createReader('Xlsx');
            $r->setLoadSheetsOnly(['PHONGHOC']);
            $ss = $r->load($repacked);
            echo 'repacked sheets: '.count($ss->getAllSheets())."\n";
        } catch (Throwable $e) {
            echo 'repacked error: '.$e->getMessage()."\n";
        }
    }
}
