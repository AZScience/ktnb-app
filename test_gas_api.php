<?php
$url = 'https://script.google.com/macros/s/AKfycbwyueknfK7TtzzbdSTF_6s-e-DRsHrwTz-kH0Wc65Tc9Rz3JMmiU8oKQPFduoaQEAi5/exec';
$data = json_encode(['room' => '', 'class' => '25MOT1A', 'lecturer' => 'Trần Quang Huy', 'subject' => '110107662101 - Kỹ thuật lập trình', 'period' => '13-16']);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$resp = curl_exec($ch);
echo "HTTP Code: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n";
echo "Error: " . curl_error($ch) . "\n";
echo "Response: " . $resp . "\n";
