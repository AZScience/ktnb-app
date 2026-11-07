<?php
$file = 'app/Http/Controllers/MonitoringScheduleController.php';
$c = file_get_contents($file);
$c = preg_replace("/\\\ = \\\ === 'email' \? 'Email' : \(\\\ === 'lcms' \? '.*?' : '.*?'\);/", "\\\ = \\\ === 'email' ? 'Email' : (\\\ === 'lcms' ? 'hệ thống E-Learning (LCMS)' : 'Email hoặc LCMS');", $c);
file_put_contents($file, $c);
