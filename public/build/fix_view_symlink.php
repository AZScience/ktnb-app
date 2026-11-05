<?php
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(dirname(__DIR__));
$viewsDir = $root . '/resources/views';

echo "viewsDir: $viewsDir\n";
echo "is_writable: " . (is_writable($viewsDir) ? 'YES' : 'NO') . "\n";

$testFile = $viewsDir . '/test_write.txt';
$w = @file_put_contents($testFile, "hello");
echo "write in resources/views: " . ($w !== false ? 'SUCCESS' : 'FAILED') . "\n";
if ($w !== false) @unlink($testFile);

$storageViews = storage_path('framework/views');
echo "storage views dir: $storageViews\n";
echo "storage views is_writable: " . (is_writable($storageViews) ? 'YES' : 'NO') . "\n";
