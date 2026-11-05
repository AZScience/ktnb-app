<?php
header('Content-Type: text/plain; charset=utf-8');
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$schedPath = resource_path('views/monitoring/schedules');
echo "schedules path: $schedPath\n";
echo "file_exists: " . (file_exists($schedPath) ? 'YES' : 'NO') . "\n";
echo "is_file: " . (is_file($schedPath) ? 'YES' : 'NO') . "\n";
echo "is_dir: " . (is_dir($schedPath) ? 'YES' : 'NO') . "\n";
echo "is_link: " . (is_link($schedPath) ? 'YES' : 'NO') . "\n";
if (file_exists($schedPath) || is_link($schedPath)) {
    echo "perms: " . substr(sprintf('%o', fileperms($schedPath)), -4) . "\n";
}

$monitoringPath = resource_path('views/monitoring');
foreach (scandir($monitoringPath) as $f) {
    if ($f === '.' || $f === '..') continue;
    $p = $monitoringPath . '/' . $f;
    echo "$f: is_dir=" . (is_dir($p) ? '1' : '0') . " is_file=" . (is_file($p) ? '1' : '0') . " size=" . filesize($p) . " perms=" . substr(sprintf('%o', fileperms($p)), -4) . "\n";
}
