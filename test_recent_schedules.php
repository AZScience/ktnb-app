<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$schedules = App\Models\DailySchedule::whereNotNull('evidence')->where('evidence', '!=', '')->orderByDesc('created_at')->limit(10)->get();
foreach($schedules as $s) {
    echo "ID: {$s->id} | Date: {$s->date} | Created: {$s->created_at} | Has Evidence: " . (!empty($s->evidence) ? 'YES' : 'NO') . "\n";
    if (!empty($s->evidence)) {
        echo "  Evidence: {$s->evidence}\n";
    }
}
