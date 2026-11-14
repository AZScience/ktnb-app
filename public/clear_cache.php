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

$panelFile = __DIR__ . '/../resources/views/components/evidence-input-panel.blade.php';
if (file_exists($panelFile)) {
    $pContent = file_get_contents($panelFile);
    if (strpos($pContent, 'evidence-camera-select-box') !== false) {
        $pContent = preg_replace('/<div[^>]*class="evidence-camera-select-box[^"]*"[^>]*>.*?<\/div>\s*<\/div>/is', '', $pContent);
        $pContent = preg_replace('/<div[^>]*class="evidence-camera-select-box[^"]*"[^>]*>.*?<\/div>/is', '', $pContent);
        @chmod($panelFile, 0666);
        file_put_contents($panelFile, $pContent);
        echo "Da xoa triet de hop CHON CAMERA trong evidence-input-panel.blade.php tren may chu! <br>";
    } else {
        echo "evidence-input-panel.blade.php tren may chu da sach (khong con hop chon camera). <br>";
    }
}

$viewsDir = __DIR__ . '/../storage/framework/views';
if (is_dir($viewsDir)) {
    @chmod($viewsDir, 0777);
    $vFiles = glob($viewsDir . '/*.php');
    if ($vFiles) {
        foreach ($vFiles as $f) {
            @chmod($f, 0777);
            @unlink($f);
        }
        echo "Da xoa " . count($vFiles) . " file compiled view cache tren may chu! <br>";
    }
}

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
\Illuminate\Support\Facades\Artisan::call('optimize:clear');
if (function_exists('opcache_reset')) { opcache_reset(); echo "OPcache cleared! <br>"; }
echo "Cache cleared successfully!";
