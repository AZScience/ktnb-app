<?php
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(dirname(__DIR__));
$compDir = $root . '/resources/views/components';

echo "compDir: $compDir\n";
echo "file_exists: " . (file_exists($compDir) ? 'YES' : 'NO') . "\n";
echo "is_dir: " . (is_dir($compDir) ? 'YES' : 'NO') . "\n";
echo "is_link: " . (is_link($compDir) ? 'YES' : 'NO') . "\n";
echo "is_writable: " . (is_writable($compDir) ? 'YES' : 'NO') . "\n";
echo "perms: " . substr(sprintf('%o', fileperms($compDir)), -4) . "\n";
echo "owner: " . fileowner($compDir) . "\n";
echo "PHP posix uid: " . (function_exists('posix_getuid') ? posix_getuid() : 'none') . "\n";

$testFile = $compDir . '/test_write.txt';
$w = @file_put_contents($testFile, "hello");
echo "write in compDir: " . ($w !== false ? 'SUCCESS' : 'FAILED') . "\n";
if ($w === false) {
    print_r(error_get_last());
} else {
    @unlink($testFile);
}
