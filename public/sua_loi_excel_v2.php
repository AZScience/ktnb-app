<?php
// Patch routes/web.php
$routesFile = __DIR__ . '/../routes/web.php';
if (file_exists($routesFile)) {
    $routesContent = file_get_contents($routesFile);
    $routesContent = str_replace(
        "Route::get('daily/export'",
        "Route::match(['GET', 'POST'], 'daily/export'",
        $routesContent
    );
    file_put_contents($routesFile, $routesContent);
}

// Manually delete all cache files in bootstrap/cache/
$cacheDir = __DIR__ . '/../bootstrap/cache/';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '*.php');
    foreach ($files as $file) {
        if (basename($file) !== '.gitignore') {
            @unlink($file);
        }
    }
}

echo "<h1 style='color:green; text-align:center; margin-top: 50px;'>Đã sửa lỗi cổng và xóa sạch bộ đệm bằng tay!</h1>";
echo "<div style='text-align:center;'><a href='/reports/daily' style='font-size: 20px;'>Bấm vào đây để quay lại trang Lịch học</a></div>";
