<?php
$url = 'https://raw.githubusercontent.com/AZScience/ktnb-app/kiemtranoibo/app/Http/Controllers/MonitoringScheduleController.php';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Cache-Control: no-cache',
    'Pragma: no-cache'
]);
// Bổ sung thêm tham số rác để Github tưởng là file mới
$url = $url . '?t=' . time();
curl_setopt($ch, CURLOPT_URL, $url);

$content = curl_exec($ch);
curl_close($ch);

$dest = __DIR__.'/../app/Http/Controllers/MonitoringScheduleController.php';

if ($content && strlen($content) > 1000) {
    file_put_contents($dest, $content);
    echo "<h1>✅ Đã tải file thành công!</h1>";
    
    if (strpos($content, 'break 2; // Tìm thấy link rồi thì dừng TOÀN BỘ quá trình tìm kiếm!') !== false) {
        echo "<p style='color:green; font-weight:bold; font-size: 20px;'>🎉 TUYỆT VỜI! FILE CỦA BẠN ĐÃ LÀ BẢN MỚI NHẤT!</p>";
        echo "<p>Vòng lặp kiên trì đã được tích hợp thành công!</p>";
    } else {
        echo "<p style='color:red; font-weight:bold; font-size: 20px;'>❌ CẢNH BÁO: FILE VẪN LÀ BẢN CŨ!</p>";
        echo "<p>Github vẫn đang cứng đầu lưu cache. Vui lòng thử lại sau vài phút nữa.</p>";
    }
} else {
    echo "<h1>❌ Lỗi tải file!</h1>";
}
