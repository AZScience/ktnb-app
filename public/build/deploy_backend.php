<?php
header('Content-Type: text/plain; charset=utf-8');

$appDir = dirname(__DIR__, 2);
echo "appDir: " . $appDir . "\n";

// List contents of $appDir
$items = scandir($appDir);
echo "Items in appDir: " . implode(', ', $items) . "\n";

if (is_dir($appDir . '/resources')) {
    echo "Items in resources: " . implode(', ', scandir($appDir . '/resources')) . "\n";
    if (is_dir($appDir . '/resources/views')) {
        echo "Items in resources/views: " . implode(', ', scandir($appDir . '/resources/views')) . "\n";
        if (is_dir($appDir . '/resources/views/components')) {
            echo "Items in resources/views/components (first 20): " . implode(', ', array_slice(scandir($appDir . '/resources/views/components'), 0, 20)) . "\n";
        }
    }
}
