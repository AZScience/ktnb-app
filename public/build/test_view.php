<?php
header('Content-Type: text/plain; charset=utf-8');

$dir = dirname(__DIR__) . '/resources/views/monitoring';
echo "monitoring dir: $dir\n";
echo "is_dir: " . (is_dir($dir) ? '1' : '0') . "\n";
echo "is_writable: " . (is_writable($dir) ? '1' : '0') . "\n";
echo "owner: " . fileowner($dir) . "\n";
echo "current_user: " . get_current_user() . " / posix uid: " . (function_exists('posix_getuid') ? posix_getuid() : 'none') . "\n";

$schedDir = $dir . '/schedules';
echo "attempting mkdir($schedDir)...\n";
$mk = @mkdir($schedDir, 0777, true);
echo "mkdir result: " . ($mk ? 'SUCCESS' : 'FAILED') . "\n";
if (!$mk) {
    $err = error_get_last();
    print_r($err);
} else {
    echo "schedules dir created successfully!\n";
}
