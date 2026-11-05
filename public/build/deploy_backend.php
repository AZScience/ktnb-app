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

echo "<h2>🚀 Đang tải mã nguồn toàn diện từ GitHub...</h2>";
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

$updated = [];
$errors = [];

$write = function($dest, $data) use (&$updated, &$errors) {
    $dir = dirname($dest);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @chmod($dest, 0666);
    if (@file_put_contents($dest, $data) !== false) {
        $updated[] = $dest;
    } else {
        $errors[] = $dest;
    }
};

for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if (strpos($name, $rootPrefix) !== 0 || substr($name, -1) === '/') {
        continue;
    }

    $relPath = substr($name, strlen($rootPrefix));

    if (strpos($relPath, 'resources/views/') === 0) {
        $write($appDir . '/' . $relPath, $zip->getFromIndex($i));
    }
    elseif (strpos($relPath, 'app/Http/Controllers/') === 0) {
        $write($appDir . '/' . $relPath, $zip->getFromIndex($i));
    }
    elseif (strpos($relPath, 'app/Models/') === 0) {
        $write($appDir . '/' . $relPath, $zip->getFromIndex($i));
    }
    elseif (strpos($relPath, 'app/Services/') === 0) {
        $write($appDir . '/' . $relPath, $zip->getFromIndex($i));
    }
    elseif (strpos($relPath, 'routes/') === 0) {
        $write($appDir . '/' . $relPath, $zip->getFromIndex($i));
    }
    elseif ($relPath === 'public/update_frontend.php') {
        $write($publicDir . '/update_frontend.php', $zip->getFromIndex($i));
    }
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
        \Illuminate\Support\Facades\Artisan::call('route:clear');
    }
} catch (\Throwable $e) {
    echo "<p>Lưu ý cache: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h1 style='color:green;'>✅ CẬP NHẬT BACKEND & BLADE VIEWS THÀNH CÔNG!</h1>";
echo "<p><b>Số file đã cập nhật:</b> " . count($updated) . "</p>";
if (!empty($errors)) {
    echo "<p style='color:red;'><b>Các file bị lỗi phân quyền ghi:</b><br>" . implode('<br>', $errors) . "</p>";
}
echo "<p>Danh sách file cập nhật quan trọng:</p><ul>";
foreach ($updated as $f) {
    if (strpos($f, 'monitoring') !== false || strpos($f, 'Controller') !== false) {
        echo "<li>" . htmlspecialchars($f) . "</li>";
    }
}
echo "</ul>";
