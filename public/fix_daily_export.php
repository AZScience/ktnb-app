<?php

$file = __DIR__.'/../app/Services/DailyReportExportService.php';
$content = file_get_contents($file);
$content = str_replace(
    '// $this->autoFitDataRowHeights($worksheet, $dataStartRow, $lastDataRow, $colCount);',
    '$this->autoFitDataRowHeights($worksheet, $dataStartRow, $lastDataRow, $colCount);',
    $content
);
@unlink($file);
@file_put_contents($file, $content);

echo "SUCCESS";
