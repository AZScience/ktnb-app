<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$file1 = __DIR__.'/../app/Services/ScheduleExcelService.php';
$content = file_get_contents($file1);
$content = str_replace(
    'public function import(UploadedFile $file, ?string $defaultDate = null): int',
    'public function import(UploadedFile $file, ?string $defaultDate = null): array',
    $content
);
$content = preg_replace(
    '/return count\(\$this->createImportItems\(\$newItems\)\);/',
    '$imported = count($this->createImportItems($newItems)); $skipped = count($merged) - count($newItems); return ["imported" => $imported, "skipped" => $skipped];',
    $content
);
@unlink($file1);
@file_put_contents($file1, $content);

$file2 = __DIR__.'/../app/Http/Controllers/ScheduleController.php';
$content = file_get_contents($file2);
$content = str_replace(
    '$count = $this->excel->import($request->file(\'file\'), $date);',
    '$result = $this->excel->import($request->file(\'file\'), $date); $count = $result["imported"]; $skipped = $result["skipped"]; $msg = "Đã import {$count} tiết học mới." . ($skipped > 0 ? " (Bỏ qua {$skipped} tiết trùng)" : "");',
    $content
);
$content = str_replace(
    '"Đã import {$count} tiết học."',
    '$msg',
    $content
);
@unlink($file2);
@file_put_contents($file2, $content);

echo "SUCCESS";
