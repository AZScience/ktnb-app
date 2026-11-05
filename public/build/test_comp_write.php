<?php
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(dirname(__DIR__));
$viewsDir = $root . '/resources/views';
$compDir = $viewsDir . '/components';
$monitoringDir = $viewsDir . '/monitoring';

echo "Fixing permissions (chmod 0777 on directories)...\n";
echo "chmod viewsDir: " . (chmod($viewsDir, 0777) ? 'OK' : 'FAIL') . "\n";
echo "chmod compDir: " . (chmod($compDir, 0777) ? 'OK' : 'FAIL') . "\n";
echo "chmod monitoringDir: " . (chmod($monitoringDir, 0777) ? 'OK' : 'FAIL') . "\n";

$testFile = $compDir . '/test_write.txt';
$w = @file_put_contents($testFile, "hello world!");
echo "write in compDir: " . ($w !== false ? 'SUCCESS (' . $w . ' bytes)' : 'FAILED') . "\n";
if ($w !== false) {
    @unlink($testFile);
} else {
    print_r(error_get_last());
}

$schedDir = $monitoringDir . '/schedules';
if (!is_dir($schedDir)) {
    echo "mkdir schedDir: " . (mkdir($schedDir, 0777, true) ? 'OK' : 'FAIL') . "\n";
} else {
    chmod($schedDir, 0777);
}

$testSched = $schedDir . '/index.blade.php';
$w2 = @file_put_contents($testSched, "@extends('layouts.nttu')\n@section('title', \$title)\n@section('content')\nhello\n@endsection\n");
echo "write in schedDir: " . ($w2 !== false ? 'SUCCESS (' . $w2 . ' bytes)' : 'FAILED') . "\n";
