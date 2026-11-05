<?php
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(dirname(__DIR__));
$comp = $root . '/resources/views/components/monitoring-schedule-table.blade.php';

echo "comp: $comp\n";
echo "file_exists: " . (file_exists($comp) ? 'YES' : 'NO') . "\n";
if (file_exists($comp)) {
    echo "is_writable: " . (is_writable($comp) ? 'YES' : 'NO') . "\n";
    echo "perms: " . substr(sprintf('%o', fileperms($comp)), -4) . "\n";
    echo "owner: " . fileowner($comp) . "\n";
    echo "current user: " . get_current_user() . "\n";
    echo "chmod(0666)... " . (@chmod($comp, 0666) ? 'SUCCESS' : 'FAILED') . "\n";
    echo "is_writable after chmod: " . (is_writable($comp) ? 'YES' : 'NO') . "\n";
}
