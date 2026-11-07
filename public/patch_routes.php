<?php
$file = __DIR__ . '/../routes/web.php';
if (!file_exists($file)) {
    die("Không tìm thấy file web.php");
}
$content = file_get_contents($file);
$old = "Route::get('daily/export', [ReportController::class, 'dailyExport'])->name('daily.export');";
$new = "Route::match(['GET', 'POST'], 'daily/export', [ReportController::class, 'dailyExport'])->name('daily.export');";
if (strpos($content, $old) !== false) {
    $content = str_replace($old, $new, $content);
    file_put_contents($file, $content);
    echo "<h1>Đã vá lỗi file routes/web.php thành công!</h1>";
} else if (strpos($content, $new) !== false) {
    echo "<h1>File routes/web.php đã được vá từ trước!</h1>";
} else {
    echo "<h1>Không tìm thấy dòng code cần vá trong web.php!</h1>";
}
