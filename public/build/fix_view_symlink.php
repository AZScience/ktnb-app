<?php
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(dirname(__DIR__)); // /www/wwwroot/kiemtranoibo-ccks.ntt.edu.vn
$monitoringDir = $root . '/resources/views/monitoring';
$schedPath = $monitoringDir . '/schedules';

echo "schedPath: $schedPath\n";
echo "is_link: " . (is_link($schedPath) ? 'YES' : 'NO') . "\n";
if (is_link($schedPath)) {
    echo "readlink target: " . @readlink($schedPath) . "\n";
    echo "Unlinking broken symlink...\n";
    @unlink($schedPath);
    echo "Unlink done. file_exists: " . (file_exists($schedPath) ? 'YES' : 'NO') . "\n";
}

if (!is_dir($schedPath)) {
    if (file_exists($schedPath)) @unlink($schedPath);
    echo "Creating schedules directory...\n";
    $mk = @mkdir($schedPath, 0777, true);
    echo "mkdir: " . ($mk ? 'SUCCESS' : 'FAILED') . "\n";
}

$indexBlade = $schedPath . '/index.blade.php';
$content = <<<'BLADE'
@extends('layouts.app')

@section('content')
    <x-monitoring-schedule-table
        :items="$items"
        :date="$date"
        :dateNotice="$dateNotice"
        :module="$module"
        :title="$title"
        :cardTitle="$cardTitle"
        :modalEntity="$modalEntity"
        :uiConfig="$uiConfig"
        :incidentCategories="$incidentCategories"
        :masterData="$masterData"
        :employeeDefault="$employeeDefault"
    />
@endsection
BLADE;

file_put_contents($indexBlade, $content);
echo "Written index.blade.php: " . (file_exists($indexBlade) ? 'YES (' . filesize($indexBlade) . ' bytes)' : 'NO') . "\n";

$monitorFormBlade = $schedPath . '/monitor-form.blade.php';
if (!file_exists($monitorFormBlade) && file_exists($monitoringDir . '/monitor-form.blade.php')) {
    copy($monitoringDir . '/monitor-form.blade.php', $monitorFormBlade);
}

// Test Laravel view
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Illuminate\Support\Facades\Artisan::call('view:clear');
echo "View monitoring.schedules.index exists: " . (view()->exists('monitoring.schedules.index') ? 'YES' : 'NO') . "\n";
