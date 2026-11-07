<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$lcmsUrl = 'https://lcms.ntt.edu.vn';
$lcmsUser = 'ttgd';
$lcmsPass = \App\Models\SystemParameter::where('key', 'lcmsPass')->value('value');

$client = new \GuzzleHttp\Client(['cookies' => true, 'verify' => false, 'headers' => ['User-Agent' => 'Mozilla/5.0']]);
$res1 = $client->get($lcmsUrl . '/login/index.php');
if (preg_match('/name="logintoken" value="([^"]+)"/', (string)$res1->getBody(), $mToken)) {
    $client->post($lcmsUrl . '/login/index.php', [
        'form_params' => [
            'username' => $lcmsUser,
            'password' => $lcmsPass,
            'logintoken' => $mToken[1]
        ]
    ]);
    
    $courseUrl = 'https://lcms.ntt.edu.vn/course/view.php?id=13694';
    $resCourse = $client->get($courseUrl);
    $htmlCourse = (string)$resCourse->getBody();
    
    file_put_contents('storage/logs/course_13694.html', $htmlCourse);
    echo "Saved to storage/logs/course_13694.html - Length: " . strlen($htmlCourse) . "\n";
}
