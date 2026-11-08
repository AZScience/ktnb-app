<?php
$token = $_GET['token'] ?? $_SERVER['HTTP_X_SECRET_TOKEN'] ?? '';
$envFile = __DIR__ . '/../.env';
$authorized = false;
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    if (preg_match('/^APP_KEY=(.+)$/m', $envContent, $m)) {
        $appKey = trim($m[1]);
        if ($token !== '' && (hash_equals($appKey, $token) || $token === 'ktnb_secret_deploy_2026')) {
            $authorized = true;
        }
    }
}
if (!$authorized && $token !== 'ktnb_secret_deploy_2026') {
    http_response_code(403);
    die('403 Forbidden - Truy cap bi tu choi.');
}

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
