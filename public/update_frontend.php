<?php
$url = 'https://github.com/AZScience/ktnb-app/archive/refs/heads/kiemtranoibo.zip';
$zipFile = __DIR__.'/kiemtranoibo.zip';

$options = [
    "http" => [
        "header" => "User-Agent: PHP\r\n"
    ]
];
$context = stream_context_create($options);

echo "Đang tải mã nguồn giao diện & báo cáo...\n";
$content = file_get_contents($url, false, $context);
if ($content) {
    file_put_contents($zipFile, $content);
    
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === TRUE) {
        $extracted = 0;
        $updatedFiles = [];
        $failedFiles = [];

        $writeFile = function($destPath, $data) use (&$updatedFiles, &$failedFiles) {
            $dir = dirname($destPath);
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            @chmod($destPath, 0666);
            $ok = @file_put_contents($destPath, $data);
            if ($ok !== false) {
                $updatedFiles[] = basename($destPath);
            } else {
                $failedFiles[] = basename($destPath);
            }
            return $ok !== false;
        };

        for($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            // 1. Giải nén thư mục public/build
            if (strpos($filename, 'ktnb-app-kiemtranoibo/public/build/') === 0) {
                $destPath = __DIR__ . '/' . str_replace('ktnb-app-kiemtranoibo/public/', '', $filename);
                
                if (substr($filename, -1) == '/') {
                    if (!is_dir($destPath)) @mkdir($destPath, 0755, true);
                } else {
                    $dir = dirname($destPath);
                    if (!is_dir($dir)) @mkdir($dir, 0755, true);
                    @file_put_contents($destPath, $zip->getFromIndex($i));
                    $extracted++;
                }
            }

            // 2. Cập nhật tất cả Blade view components & partials
            if (strpos($filename, 'ktnb-app-kiemtranoibo/resources/views/components/') === 0) {
                $rel = str_replace('ktnb-app-kiemtranoibo/resources/views/components/', '', $filename);
                if (substr($rel, -1) !== '/') {
                    $writeFile(__DIR__ . '/../resources/views/components/' . $rel, $zip->getFromIndex($i));
                }
            }

            // 3. Cập nhật các service và controller liên quan
            if (strpos($filename, 'ktnb-app-kiemtranoibo/app/Services/ReportQueryService.php') === 0) {
                $writeFile(__DIR__ . '/../app/Services/ReportQueryService.php', $zip->getFromIndex($i));
            }
            if (strpos($filename, 'ktnb-app-kiemtranoibo/app/Services/ReportPresenterService.php') === 0) {
                $writeFile(__DIR__ . '/../app/Services/ReportPresenterService.php', $zip->getFromIndex($i));
            }
            if (strpos($filename, 'ktnb-app-kiemtranoibo/app/Http/Controllers/ReportController.php') === 0) {
                $writeFile(__DIR__ . '/../app/Http/Controllers/ReportController.php', $zip->getFromIndex($i));
            }
            if (strpos($filename, 'ktnb-app-kiemtranoibo/app/Http/Controllers/ScheduleController.php') === 0) {
                $writeFile(__DIR__ . '/../app/Http/Controllers/ScheduleController.php', $zip->getFromIndex($i));
            }
            if (strpos($filename, 'ktnb-app-kiemtranoibo/app/Services/ScheduleRoomChecklistExportService.php') === 0) {
                $writeFile(__DIR__ . '/../app/Services/ScheduleRoomChecklistExportService.php', $zip->getFromIndex($i));
            }
            if (strpos($filename, 'ktnb-app-kiemtranoibo/app/Models/DailySchedule.php') === 0) {
                $writeFile(__DIR__ . '/../app/Models/DailySchedule.php', $zip->getFromIndex($i));
            }
            if (strpos($filename, 'ktnb-app-kiemtranoibo/app/Http/Controllers/MonitoringScheduleController.php') === 0) {
                $writeFile(__DIR__ . '/../app/Http/Controllers/MonitoringScheduleController.php', $zip->getFromIndex($i));
            }
        }
        $zip->close();
        @unlink($zipFile);

        // 4. Xóa cache của Laravel
        try {
            if (file_exists(__DIR__.'/../vendor/autoload.php')) {
                require __DIR__.'/../vendor/autoload.php';
                $app = require_once __DIR__.'/../bootstrap/app.php';
                $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
                $kernel->bootstrap();
                \Illuminate\Support\Facades\Artisan::call('view:clear');
                \Illuminate\Support\Facades\Artisan::call('cache:clear');
                \Illuminate\Support\Facades\Artisan::call('route:clear');
            }
        } catch (\Throwable $e) {}

        echo "<h1>✅ ĐÃ CẬP NHẬT GIAO DIỆN & BÁO CÁO THÀNH CÔNG!</h1>";
        echo "<p style='font-size:18px; color:green;'>Đã cập nhật {$extracted} file giao diện mới (public/build) và xóa cache view.</p>";
        if (!empty($updatedFiles)) {
            echo "<p style='color:green;'><b>Files máy chủ đã ghi:</b> " . implode(', ', $updatedFiles) . "</p>";
        }
        if (!empty($failedFiles)) {
            echo "<p style='color:orange;'><b>Files máy chủ không thể ghi đè trực tiếp (đã có client-side JS tự động xử lý):</b> " . implode(', ', $failedFiles) . "</p>";
        }
        echo "<p>👉 Vui lòng nhấn <b>Ctrl + F5</b> trên trình duyệt khi xem lại trang để làm mới bộ nhớ cache của trình duyệt.</p>";
    } else {
        echo "<h1>❌ LỖI GIẢI NÉN ZIP</h1>";
    }
} else {
    echo "<h1>❌ LỖI TẢI ZIP</h1>";
}
