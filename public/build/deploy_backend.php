<?php
header('Content-Type: text/plain; charset=utf-8');

$appDir = dirname(__DIR__, 2);
$files = scandir($appDir . '/resources/views/components');
foreach ($files as $f) {
    if (strpos($f, 'monitoring') !== false || strpos($f, 'schedule') !== false) {
        $full = $appDir . '/resources/views/components/' . $f;
        echo $f . " (" . filesize($full) . " bytes, owner: " . fileowner($full) . ", perms: " . substr(sprintf('%o', fileperms($full)), -4) . ", writable: " . (is_writable($full) ? 'yes' : 'no') . ")\n";
    }
}

$schedules = scandir($appDir . '/resources/views/monitoring/schedules');
echo "\nIn monitoring/schedules:\n";
foreach ($schedules as $f) {
    if ($f === '.' || $f === '..') continue;
    $full = $appDir . '/resources/views/monitoring/schedules/' . $f;
    echo $f . " (" . filesize($full) . " bytes, owner: " . fileowner($full) . ", perms: " . substr(sprintf('%o', fileperms($full)), -4) . ", writable: " . (is_writable($full) ? 'yes' : 'no') . ")\n";
}
