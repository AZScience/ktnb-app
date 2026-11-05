<?php
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(dirname(__DIR__));
$viewsDir = $root . '/resources/views';

$ritit = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
$r = [];
foreach ($ritit as $splFileInfo) {
    if ($splFileInfo->isDir()) continue;
    $path = $splFileInfo->getPathname();
    if (strpos($path, 'monitoring') !== false || strpos($path, 'schedule') !== false) {
        $r[] = str_replace($viewsDir . DIRECTORY_SEPARATOR, '', $path);
    }
}
sort($r);
echo "Matching view files in resources/views:\n";
print_r($r);
