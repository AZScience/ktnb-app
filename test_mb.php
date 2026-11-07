<?php
mb_internal_encoding('ISO-8859-1');
$str = 'Thêm mới';
echo "Original: " . $str . "\n";
echo "mb_strimwidth: " . mb_strimwidth($str, 0, 128, '') . "\n";
