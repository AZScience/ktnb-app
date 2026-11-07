<?php
$c = file_get_contents('tests/Feature/MonthlyReportStatsServiceTest.php');
$c = str_replace('Khoa CNTT - Đổi sang A102', 'Khoa CNTT) 05/07/2026: Đổi sang A102', $c);
file_put_contents('tests/Feature/MonthlyReportStatsServiceTest.php', $c);
