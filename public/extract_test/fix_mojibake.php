<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ActivityLog;

$replacements = [
    'Ghi nhÃƒÂ¡Ã‚ÂºÃ‚Â­n giÃƒÆ’Ã‚Â¡m sÃƒÆ’Ã‚Â¡t' => 'Ghi nhận giám sát',
    'HÃƒÂ¡Ã‚Â»Ã‚Â§y ghi nhÃƒÂ¡Ã‚ÂºÃ‚Â­n giÃƒÆ’Ã‚Â¡m sÃƒÆ’Ã‚Â¡t' => 'Hủy ghi nhận giám sát',
    'ThÃƒÆ’Ã‚Âªm lÃƒÂ¡Ã‚Â»Ã‚Â‹ch giÃƒÆ’Ã‚Â¡m sÃƒÆ’Ã‚Â¡t' => 'Thêm lịch giám sát',
    'XÃƒÆ’Ã‚Â³a lÃƒÂ¡Ã‚Â»Ã‚Â‹ch giÃƒÆ’Ã‚Â¡m sÃƒÆ’Ã‚Â¡t' => 'Xóa lịch giám sát',
    'Ãƒâ€š· LÃƒÂ¡Ã‚Â»Ã¢â‚¬Âºp' => '· Lớp',
    'Ãƒâ€š· TiÃƒÂ¡Ã‚ÂºÃ‚Â¿t' => '· Tiết',
    'Ãƒâ€š·' => '·',
];

$logs = ActivityLog::where('action', 'like', '%Ã%')->orWhere('details', 'like', '%Ã%')->get();
$count = 0;

foreach ($logs as $log) {
    $newAction = strtr($log->action, $replacements);
    $newDetails = $log->details ? strtr($log->details, $replacements) : null;
    
    if ($newAction !== $log->action || $newDetails !== $log->details) {
        $log->action = $newAction;
        $log->details = $newDetails;
        $log->save();
        $count++;
    }
}
echo "Đã sửa thành công {$count} dòng dữ liệu bị lỗi font trong Nhật ký truy cập. Bạn có thể xóa file này.";
