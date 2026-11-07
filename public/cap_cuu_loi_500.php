<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "<h1>FIXING SYSTEM</h1>";
try {
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    echo "<p style='color:green'>SUCCESS: Cleared View Cache!</p>";
} catch (\Exception $e) {
    echo "<p style='color:red'>ERROR clearing cache: " . $e->getMessage() . "</p>";
}

try {
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    echo "<p style='color:green'>SUCCESS: Cleared Application Cache!</p>";
} catch (\Exception $e) {
    echo "<p style='color:red'>ERROR clearing cache: " . $e->getMessage() . "</p>";
}

$file1 = __DIR__.'/../resources/views/monitoring/requests/index.blade.php';
if (file_exists($file1)) {
    $content = file_get_contents($file1);
    if (strpos($content, 'service-requests.index') !== false) {
        $content = str_replace('service-requests.index', 'requests.index', $content);
        @unlink($file1);
        @file_put_contents($file1, $content);
        echo "<p style='color:green'>SUCCESS: Patched requests.index!</p>";
    } else {
        echo "<p style='color:blue'>INFO: requests.index already patched.</p>";
    }
}

$file2 = __DIR__.'/../resources/views/monitoring/petitions/index.blade.php';
if (file_exists($file2)) {
    $content = file_get_contents($file2);
    if (strpos($content, 'service-requests.index') !== false || strpos($content, 'petitions.missing') !== false) {
        echo "<p style='color:red'>WARNING: Bad route found in petitions!</p>";
    } else {
        echo "<p style='color:blue'>INFO: petitions.index looks fine.</p>";
    }
}

$log = __DIR__.'/../storage/logs/laravel.log';
if (file_exists($log)) {
    $lines = file($log);
    $last_lines = array_slice($lines, -100);
    echo "<h2>Recent Errors in laravel.log</h2>";
    echo "<pre style='background:#f4f4f4; padding:10px; border:1px solid #ccc; max-height:400px; overflow:auto;'>";
    foreach ($last_lines as $line) {
        if (strpos($line, 'local.ERROR') !== false) {
            echo "<strong style='color:red'>".htmlspecialchars($line)."</strong>";
        } else {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
}
