<?php
header('Content-Type: text/html; charset=utf-8');

$zipUrl = 'https://github.com/AZScience/ktnb-app/archive/refs/heads/kiemtranoibo.zip?t=' . time();
$zipFile = __DIR__ . '/temp_update.zip';

$options = [
    "http" => [
        "header" => "User-Agent: PHP\r\nCache-Control: no-cache\r\n"
    ]
];
$context = stream_context_create($options);

echo "<h2>🚀 Đang tải mã nguồn từ GitHub...</h2>";
$content = file_get_contents($zipUrl, false, $context);
if (!$content) {
    die("<h2 style='color:red;'>❌ Không thể tải zip từ GitHub!</h2>");
}

file_put_contents($zipFile, $content);

$zip = new ZipArchive();
if ($zip->open($zipFile) !== true) {
    die("<h2 style='color:red;'>❌ Không mở được file zip!</h2>");
}

$rootPrefix = 'ktnb-app-kiemtranoibo/';
$appDir = dirname(__DIR__, 2);
$publicDir = dirname(__DIR__);

$targetFiles = [
    'resources/views/monitoring/schedules/index.blade.php',
    'resources/views/components/monitoring-schedule-table.blade.php',
    'app/Http/Controllers/MonitoringScheduleController.php',
    'public/update_frontend.php',
];

$results = [];

foreach ($targetFiles as $targetRel) {
    $zipEntry = $rootPrefix . $targetRel;
    $content = $zip->getFromName($zipEntry);
    if ($content === false) {
        $results[$targetRel] = 'Không tìm thấy trong ZIP';
        continue;
    }

    $dest = ($targetRel === 'public/update_frontend.php')
        ? $publicDir . '/update_frontend.php'
        : $appDir . '/' . $targetRel;

    // Check if writable, try chmod or unlink
    $dir = dirname($dest);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    
    @chmod($dir, 0777);
    @chmod($dest, 0777);
    
    // Try unlink first if write fails
    $ok = @file_put_contents($dest, $content);
    if ($ok === false) {
        @unlink($dest);
        $ok = @file_put_contents($dest, $content);
    }

    $results[$targetRel] = ($ok !== false) ? '✅ Ghi thành công (' . strlen($content) . ' bytes)' : '❌ Lỗi quyền ghi (' . substr(sprintf('%o', @fileperms($dest)), -4) . ', owner: ' . @fileowner($dest) . ')';
}

$zip->close();
@unlink($zipFile);

try {
    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }
    if (file_exists($appDir . '/vendor/autoload.php')) {
        require_once $appDir . '/vendor/autoload.php';
        $app = require_once $appDir . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
    }
} catch (\Throwable $e) {
    echo "<p>Lưu ý: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>Kết quả ghi các file mục tiêu:</h2><ul>";
foreach ($results as $file => $status) {
    echo "<li><b>{$file}:</b> {$status}</li>";
}
echo "</ul>";
