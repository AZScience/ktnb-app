<?php
header('Content-Type: text/plain; charset=utf-8');

$appDir = dirname(__DIR__, 2);
$viewPath = $appDir . '/resources/views/components/monitoring-schedule-table.blade.php';

if (!file_exists($viewPath)) {
    echo "File not found: " . $viewPath . "\n";
    exit;
}

$content = file_get_contents($viewPath);
echo "File size: " . strlen($content) . "\n";
echo "Has isCameraExtractSupported: " . (strpos($content, 'isCameraExtractSupported') !== false ? 'YES' : 'NO') . "\n";
echo "Has in_array(module, ['homeroom', 'exams']): " . (strpos($content, "in_array(\$module, ['homeroom', 'exams'])") !== false ? 'YES' : 'NO') . "\n";
echo "Has currentIncidentCategories: " . (strpos($content, 'currentIncidentCategories') !== false ? 'YES' : 'NO') . "\n";

$storageViews = glob($appDir . '/storage/framework/views/*.php');
echo "Cached view files count: " . count($storageViews) . "\n";

// Let's delete all cached views directly!
$deleted = 0;
foreach ($storageViews as $v) {
    if (@unlink($v)) $deleted++;
}
echo "Deleted cached views: " . $deleted . "\n";
