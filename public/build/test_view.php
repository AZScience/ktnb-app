<?php
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(dirname(__DIR__));
$monitoringDir = $root . '/resources/views/monitoring';
echo "root: $root\n";
echo "monitoringDir: $monitoringDir\n";
echo "is_dir: " . (is_dir($monitoringDir) ? '1' : '0') . "\n";
echo "is_writable: " . (is_writable($monitoringDir) ? '1' : '0') . "\n";
echo "owner: " . @fileowner($monitoringDir) . "\n";

$schedDir = $monitoringDir . '/schedules';
echo "attempting mkdir($schedDir)...\n";
$mk = @mkdir($schedDir, 0777, true);
echo "mkdir result: " . ($mk ? 'SUCCESS' : 'FAILED') . "\n";
if (!$mk) {
    $err = error_get_last();
    print_r($err);
} else {
    echo "schedules dir created successfully!\n";
}

// Also test writing index.blade.php
$indexFile = $schedDir . '/index.blade.php';
$written = @file_put_contents($indexFile, "@extends('layouts.app')\n@section('content')\n<x-monitoring-schedule-table :items=\"\$items\" :date=\"\$date\" :dateNotice=\"\$dateNotice\" :module=\"\$module\" :title=\"\$title\" :cardTitle=\"\$cardTitle\" :modalEntity=\"\$modalEntity\" :uiConfig=\"\$uiConfig\" :incidentCategories=\"\$incidentCategories\" :masterData=\"\$masterData\" :employeeDefault=\"\$employeeDefault\" />\n@endsection\n");
echo "write index.blade.php: " . ($written !== false ? 'SUCCESS (' . $written . ' bytes)' : 'FAILED') . "\n";
if ($written === false) {
    print_r(error_get_last());
}

$monitorForm = $schedDir . '/monitor-form.blade.php';
$writtenForm = @file_put_contents($monitorForm, file_get_contents($root . '/resources/views/monitoring/monitor-form.blade.php'));
echo "write monitor-form.blade.php: " . ($writtenForm !== false ? 'SUCCESS' : 'FAILED') . "\n";
