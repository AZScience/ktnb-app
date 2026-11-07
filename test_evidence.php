<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$service = app(App\Services\EvidenceAggregationService::class);
$items = $service->collect();
echo count($items) . " items collected.\n";
$scheduleCount = 0;
foreach($items as $item) {
    if (strpos($item['id'], 'schedule-') === 0) {
        $scheduleCount++;
    }
}
echo "$scheduleCount schedule items.\n";
