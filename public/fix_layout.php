<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "<h1>FIXING LAYOUT BUGS</h1>";

// Fix Requests
$file1 = __DIR__.'/../resources/views/monitoring/requests/index.blade.php';
if (file_exists($file1)) {
    $content = file_get_contents($file1);
    
    // Also fix the route if it hasn't been fixed yet!
    $content = str_replace("'list' => route('service-requests.index')", "'list' => route('requests.index')", $content);
    
    // Remove server-paginated
    $content = str_replace("server-paginated", "", $content);
    
    @unlink($file1);
    @file_put_contents($file1, $content);
    echo "<p style='color:green'>SUCCESS: Patched requests/index.blade.php!</p>";
}

// Fix Petitions
$file2 = __DIR__.'/../resources/views/monitoring/petitions/index.blade.php';
if (file_exists($file2)) {
    $content = file_get_contents($file2);
    
    // Remove server-paginated
    $content = str_replace("server-paginated", "", $content);
    
    @unlink($file2);
    @file_put_contents($file2, $content);
    echo "<p style='color:green'>SUCCESS: Patched petitions/index.blade.php!</p>";
}

try {
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    echo "<p style='color:green'>SUCCESS: Cleared View Cache!</p>";
} catch (\Exception $e) {
    echo "<p style='color:red'>ERROR clearing cache: " . $e->getMessage() . "</p>";
}

echo "<br><hr><h2>Đã sửa lỗi xong! Vui lòng làm mới lại trang web (F5) để kiểm tra.</h2>";
