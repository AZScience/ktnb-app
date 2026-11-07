<?php
$url = 'https://raw.githubusercontent.com/AZScience/ktnb-app/kiemtranoibo/app/Http/Controllers/MonitoringScheduleController.php?time=' . time();
$dest = __DIR__.'/../app/Http/Controllers/MonitoringScheduleController.php';

$options = [
    "http" => [
        "header" => "User-Agent: PHP\r\nCache-Control: no-cache\r\n"
    ]
];
$context = stream_context_create($options);
$content = @file_get_contents($url, false, $context);

if ($content && strlen($content) > 1000) {
    file_put_contents($dest, $content);
    echo "<h1>✅ Đã tải file thành công!</h1>";
    
    // Check if the file contains the specific fix we made
    if (strpos($content, 'break 2; // Tìm thấy link rồi thì dừng TOÀN BỘ quá trình tìm kiếm!') !== false) {
        echo "<p style='color:green; font-weight:bold;'>File ĐÃ CÓ vòng lặp mới nhất!</p>";
    } else {
        echo "<p style='color:red; font-weight:bold;'>CẢNH BÁO: File vẫn là bản cũ! Github vẫn đang cache.</p>";
    }
} else {
    echo "<h1>❌ Lỗi không tải được bản vá từ Github!</h1>";
}
