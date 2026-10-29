<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;

header('Content-Type: text/html; charset=utf-8');
echo "<h2>🛠️ TIẾN HÀNH CẬP NHẬT DATABASE</h2>";

// 1. Chạy migrate chính thức của Laravel
try {
    Artisan::call('migrate', ['--force' => true]);
    $output = Artisan::output();
    echo "<pre style='background:#f4f4f4;padding:10px;'>Output migrate:\n" . htmlspecialchars($output) . "</pre>";
} catch (\Throwable $e) {
    echo "<p style='color:orange;'>Lỗi khi chạy migrate: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 2. Kiểm tra trực tiếp bảng incident_records và tự động bổ sung cột còn thiếu
if (Schema::hasTable('incident_records')) {
    echo "<p>✅ Bảng <code>incident_records</code> tồn tại.</p>";
    
    Schema::table('incident_records', function (Blueprint $table) {
        if (!Schema::hasColumn('incident_records', 'witness_signature')) {
            $table->longText('witness_signature')->nullable();
            echo "<p style='color:green;'>➕ Đã thêm cột <code>witness_signature</code></p>";
        } else {
            echo "<p style='color:blue;'>ℹ️ Cột <code>witness_signature</code> đã có sẵn.</p>";
        }
        
        if (!Schema::hasColumn('incident_records', 'creator_signature')) {
            $table->longText('creator_signature')->nullable();
            echo "<p style='color:green;'>➕ Đã thêm cột <code>creator_signature</code></p>";
        } else {
            echo "<p style='color:blue;'>ℹ️ Cột <code>creator_signature</code> đã có sẵn.</p>";
        }
        
        if (!Schema::hasColumn('incident_records', 'evidence')) {
            $table->longText('evidence')->nullable();
            echo "<p style='color:green;'>➕ Đã thêm cột <code>evidence</code></p>";
        } else {
            echo "<p style='color:blue;'>ℹ️ Cột <code>evidence</code> đã có sẵn.</p>";
        }
    });
    
    echo "<h3>🎉 HOÀN TẤT CẬP NHẬT DATABASE THÀNH CÔNG!</h3>";
} else {
    echo "<p style='color:red;'>❌ Không tìm thấy bảng incident_records!</p>";
}

// 3. Đồng bộ chuẩn hóa ngày tháng date_iso cho bảng lịch học daily_schedules
if (Schema::hasTable('daily_schedules') && Schema::hasColumn('daily_schedules', 'date_iso')) {
    try {
        $updated = \Illuminate\Support\Facades\DB::update("
            UPDATE daily_schedules 
            SET date_iso = STR_TO_DATE(date, '%d/%m/%Y') 
            WHERE (date_iso IS NULL OR date_iso = '') 
              AND date REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}$'
        ");
        echo "<p style='color:green;'>📅 Đã đồng bộ chuẩn hóa ngày cho {$updated} dòng lịch học.</p>";
    } catch (\Throwable $e) {
        echo "<p style='color:orange;'>Đồng bộ ngày: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
