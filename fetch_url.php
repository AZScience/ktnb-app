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
    
    $urlMod = 'https://lcms.ntt.edu.vn/mod/url/view.php?id=163860';
    $resUrl = $client->get($urlMod);
    file_put_contents('storage/logs/url_163860.html', (string)$resUrl->getBody());
}
