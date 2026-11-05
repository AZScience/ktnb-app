<?php
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(dirname(__DIR__));
$compDir = $root . '/resources/views/components';

if (is_dir($compDir)) {
    $files = scandir($compDir);
    echo "Files in components:\n";
    foreach ($files as $f) {
        if (strpos($f, 'table') !== false || strpos($f, 'sched') !== false || strpos($f, 'monitor') !== false) {
            echo "  $f\n";
        }
    }
} else {
    echo "compDir is NOT a dir!\n";
}
