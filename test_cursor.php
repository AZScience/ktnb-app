<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$memory_start = memory_get_usage();
$file = fopen('test_cursor.json', 'w');
fwrite($file, "[\n");
$first = true;
foreach (\DB::table('activity_logs')->cursor() as $row) {
    if (!$first) {
        fwrite($file, ",\n");
    }
    fwrite($file, json_encode($row, JSON_UNESCAPED_UNICODE));
    $first = false;
}
fwrite($file, "\n]");
fclose($file);
echo "Memory diff: " . ((memory_get_usage() - $memory_start) / 1024 / 1024) . " MB\n";
