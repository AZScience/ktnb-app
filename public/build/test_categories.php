<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = app(App\Http\Controllers\MonitoringScheduleController::class);
$ref = new ReflectionClass($controller);
$m = $ref->getMethod('allIncidentCategoriesByModule');
$m->setAccessible(true);
$res = $m->invoke($controller);

header('Content-Type: application/json');
echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
