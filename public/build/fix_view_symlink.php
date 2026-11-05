<?php
header('Content-Type: text/plain; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $root = dirname(dirname(__DIR__));
    $viewsDir = $root . '/resources/views';

    echo "Scanning $viewsDir...\n";
    $files = glob($viewsDir . '/monitoring/*/*.blade.php');
    print_r($files);

    $files2 = glob($viewsDir . '/monitoring/*.blade.php');
    print_r($files2);

    $files3 = glob($viewsDir . '/*/*.blade.php');
    print_r($files3);
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
