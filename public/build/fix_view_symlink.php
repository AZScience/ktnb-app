<?php
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(dirname(__DIR__));
$m = $root . '/resources/views/monitoring';

echo "m: $m\n";
echo "file_exists: " . (file_exists($m) ? 'YES' : 'NO') . "\n";
echo "is_dir: " . (is_dir($m) ? 'YES' : 'NO') . "\n";
echo "is_link: " . (is_link($m) ? 'YES' : 'NO') . "\n";
echo "is_file: " . (is_file($m) ? 'YES' : 'NO') . "\n";
echo "is_writable: " . (is_writable($m) ? 'YES' : 'NO') . "\n";
echo "perms: " . substr(sprintf('%o', fileperms($m)), -4) . "\n";
echo "owner: " . fileowner($m) . "\n";

// Can we chmod it?
echo "chmod(0777)... " . (@chmod($m, 0777) ? 'SUCCESS' : 'FAILED') . "\n";
