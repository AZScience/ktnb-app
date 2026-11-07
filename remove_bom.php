<?php
$file = 'app/Http/Controllers/MonitoringScheduleController.php';
$c = file_get_contents($file);
if (substr($c, 0, 3) === "\xef\xbb\xbf") {
    $c = substr($c, 3);
    file_put_contents($file, $c);
    echo "BOM removed\n";
} else {
    echo "No BOM found\n";
}
