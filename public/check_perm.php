<?php
$dest = __DIR__.'/../app/Http/Controllers/MonitoringScheduleController.php';
$content = "test";
if (is_writable($dest)) {
    echo "<h1>✅ File có thể ghi được.</h1>";
} else {
    echo "<h1>❌ LỖI: FILE KHÔNG THỂ GHI ĐƯỢC (Chặn quyền truy cập).</h1>";
}
