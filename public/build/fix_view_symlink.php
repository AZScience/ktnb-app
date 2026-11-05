<?php
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(dirname(__DIR__));
$comp = $root . '/resources/views/components/monitoring-schedule-table.blade.php';

if (!file_exists($comp)) {
    echo "monitoring-schedule-table.blade.php does NOT exist!\n";
    exit;
}

$lines = file($comp);
echo "Total lines: " . count($lines) . "\n";
echo "Lines containing incident_detail or openIncidentDetailCamera:\n";
foreach ($lines as $i => $line) {
    if (strpos($line, 'incident_detail') !== false || strpos($line, 'openIncidentDetailCamera') !== false || strpos($line, 'isCameraExtractSupported') !== false) {
        echo ($i + 1) . ": " . trim($line) . "\n";
    }
}
