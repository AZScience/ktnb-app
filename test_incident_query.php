<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$totalIncident = App\Models\DailySchedule::whereNotNull('incident')->where('incident', '!=', '')->count();
echo "Total with incident: $totalIncident\n";

$sample = App\Models\DailySchedule::whereNotNull('incident')->where('incident', '!=', '')->limit(5)->get(['id', 'date', 'incident', 'incident_detail']);
foreach($sample as $s) {
    echo "ID: {$s->id} | Date: {$s->date} | Incident: {$s->incident} | Detail: {$s->incident_detail}\n";
}
