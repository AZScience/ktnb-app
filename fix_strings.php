<?php
$file = 'app/Http/Controllers/MonitoringScheduleController.php';
$c = file_get_contents($file);
$c = preg_replace("/Ch.*?a c.*?u h.*?nh t.*?i kho.*?n LCMS trong h.*? th.*?ng./", "Chưa cấu hình tài khoản LCMS trong hệ thống.", $c);
$c = preg_replace("/h.*? th.*?ng E-Learning \(LCMS\)/", "hệ thống E-Learning (LCMS)", $c);
$c = preg_replace("/Kh.*?ng t.*?m th.*?y Link Google Meet n.*?o t.*? \\\ kh.*?p v.*?i ti.*?u ch.*?./", "Không tìm thấy Link Google Meet nào từ \\\ khớp với tiêu chí.", $c);
file_put_contents($file, $c);
echo "Replaced\n";
