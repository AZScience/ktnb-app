<?php
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(dirname(__DIR__));
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = \App\Models\DailySchedule::where('date', '05/11/2026')
    ->where(function($q) {
        $q->whereIn('status', ['Phòng thi', 'Thi cuối kỳ'])
          ->orWhere('content', 'LIKE', '%cvht%')
          ->orWhere('content', 'LIKE', '%shcn%')
          ->orWhere('status', 'LIKE', '%thi%');
    })
    ->limit(5)
    ->get(['id', 'date', 'building', 'room', 'period', 'type', 'class', 'content', 'status', 'incident', 'incident_detail']);

echo "Found " . $rows->count() . " exam/homeroom rows on 05/11/2026:\n";
foreach ($rows as $r) {
    print_r($r->toArray());
}

if ($rows->isEmpty()) {
    echo "No rows found on 05/11/2026. Looking for any exam row in DB:\n";
    $examRow = \App\Models\DailySchedule::whereIn('status', ['Phòng thi', 'Thi cuối kỳ'])->latest('id')->first(['id', 'date', 'building', 'room', 'status', 'content', 'type']);
    print_r($examRow?->toArray());

    echo "Looking for any homeroom row in DB:\n";
    $homeRow = \App\Models\DailySchedule::where('content', 'LIKE', '%cvht%')->orWhere('content', 'LIKE', '%shcn%')->latest('id')->first(['id', 'date', 'building', 'room', 'status', 'content', 'type']);
    print_r($homeRow?->toArray());
}
