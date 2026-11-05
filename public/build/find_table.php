<?php
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(dirname(__DIR__));

function searchFile($dir, $filename) {
    $results = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if ($file->getFilename() === $filename) {
            $results[] = $file->getPathname();
        }
    }
    return $results;
}

echo "Searching for monitoring-schedule-table.blade.php in $root...\n";
$found = searchFile($root . '/resources', 'monitoring-schedule-table.blade.php');
print_r($found);
