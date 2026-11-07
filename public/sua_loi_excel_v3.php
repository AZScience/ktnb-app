<?php
$appServiceFile = __DIR__ . '/../app/Providers/AppServiceProvider.php';
if (!file_exists($appServiceFile)) {
    die("<h1>Không tìm thấy AppServiceProvider.php</h1>");
}

$content = file_get_contents($appServiceFile);

// Remove existing hack if any
$content = preg_replace("/[ \t]*if \(request\(\)->is\('reports\/daily\/export'\).*?}/s", "", $content);

// Inject the hack at the beginning of boot()
$hack = "\n        if (request()->is('reports/daily/export') && request()->isMethod('POST')) {\n            request()->setMethod('GET');\n        }\n";
$content = preg_replace('/public function boot\(\)\s*(:\s*void)?\s*\{/i', "$hack", $content);

$success = @file_put_contents($appServiceFile, $content);

if ($success === false) {
    echo "<h1 style='color:red;'>Lỗi: Không có quyền ghi vào file AppServiceProvider.php!</h1>";
} else {
    echo "<h1 style='color:green; text-align:center; margin-top: 50px;'>Đã kích hoạt Chặn cổng (Bypass 405) thành công!</h1>";
    echo "<div style='text-align:center;'><a href='/reports/daily' style='font-size: 20px;'>Bấm vào đây để quay lại trang Lịch học</a></div>";
}
