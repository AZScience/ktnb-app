<?php
header('Content-Type: application/json; charset=utf-8');

$appDir = dirname(__DIR__, 2);
require_once $appDir . '/vendor/autoload.php';
$app = require_once $appDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$result = [];
foreach (\App\Models\Recognition::with('incidentCategories')->get() as $r) {
    $result[$r->name] = $r->incidentCategories->pluck('name')->all();
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
