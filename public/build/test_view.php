<?php
header('Content-Type: text/plain; charset=utf-8');
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "View monitoring.schedules.index exists: " . (view()->exists('monitoring.schedules.index') ? 'YES' : 'NO') . "\n";
echo "View monitoring.index exists: " . (view()->exists('monitoring.index') ? 'YES' : 'NO') . "\n";

$monitoringPath = resource_path('views/monitoring');
echo "Monitoring path: $monitoringPath\n";
if (is_dir($monitoringPath)) {
    echo "Is writable: " . (is_writable($monitoringPath) ? 'YES' : 'NO') . "\n";
    $files = scandir($monitoringPath);
    echo "Files in monitoring:\n";
    print_r($files);
}

$schedPath = resource_path('views/monitoring/schedules');
echo "Schedules path: $schedPath\n";
if (is_dir($schedPath)) {
    echo "Files in schedules:\n";
    print_r(scandir($schedPath));
} else {
    echo "schedules directory DOES NOT EXIST!\n";
}
