<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ActivityLog;

$logs = ActivityLog::orderByDesc('logged_at')->limit(20)->get();
foreach ($logs as $log) {
    if (strpos($log->action, 'Ã') !== false || ($log->details && strpos($log->details, 'Ã') !== false)) {
        
        $fixedAction = $log->action;
        while (strpos($fixedAction, 'Ã') !== false) {
            $temp = @mb_convert_encoding($fixedAction, 'Windows-1252', 'UTF-8');
            if (!$temp) break;
            $fixedAction = $temp;
        }

        $fixedDetails = $log->details;
        if ($fixedDetails) {
            while (strpos($fixedDetails, 'Ã') !== false) {
                $temp = @mb_convert_encoding($fixedDetails, 'Windows-1252', 'UTF-8');
                if (!$temp) break;
                $fixedDetails = $temp;
            }
        }
        
        echo "Fixed action: " . $fixedAction . "\n";
        echo "Fixed details: " . $fixedDetails . "\n";
        echo "---\n";
    }
}
