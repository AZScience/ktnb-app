<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\DailyReportExportService;

$service = app(DailyReportExportService::class);
$datasets = [
    'in-person' => [
        ['officer' => 'abc', 'is_notification' => false]
    ]
];
try {
    $response = $service->download('2026-08-28', $datasets, 'John Doe');
    echo "SUCCESS";
} catch (\Throwable $e) {
    echo $e->getMessage();
}
