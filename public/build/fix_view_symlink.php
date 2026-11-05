<?php
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(dirname(__DIR__));
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$examRows = \App\Models\DailySchedule::whereIn('status', ['Phòng thi', 'Thi cuối kỳ'])
    ->orWhere('status', 'LIKE', '%thi%')
    ->limit(5)
    ->get(['id', 'date', 'building', 'room', 'status', 'content', 'type', 'proctor1']);

echo "Exam rows found:\n";
foreach ($examRows as $r) {
    print_r($r->toArray());
}
