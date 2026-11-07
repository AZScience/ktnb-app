<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$file = __DIR__ . '/../app/Http/Controllers/MonitoringScheduleController.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        file_put_contents($file, substr($content, 3));
        echo "Da xoa ky tu BOM an tren may chu! <br>";
    }
}

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
\Illuminate\Support\Facades\Artisan::call('optimize:clear');
if (function_exists('opcache_reset')) { opcache_reset(); echo "OPcache cleared! <br>"; }
echo "Cache cleared successfully!";
