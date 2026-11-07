<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$lcmsUrl = 'https://lcms.ntt.edu.vn';
$lcmsUser = 'ttgd';
$lcmsPass = \App\Models\SystemParameter::where('key', 'lcmsPass')->value('value');

$client = new \GuzzleHttp\Client(['cookies' => true, 'verify' => false]);
$res1 = $client->get($lcmsUrl . '/login/index.php');
if (preg_match('/name="logintoken" value="([^"]+)"/', (string)$res1->getBody(), $mToken)) {
    $client->post($lcmsUrl . '/login/index.php', [
        'form_params' => [
            'username' => $lcmsUser,
            'password' => $lcmsPass,
            'logintoken' => $mToken[1]
        ]
    ]);
    
    $searchUrl = 'https://lcms.ntt.edu.vn/course/search.php?areaids=core_course-course&q=011007558111';
    $resSearch = $client->get($searchUrl, ['allow_redirects' => ['track_redirects' => true]]);
    $htmlSearch = (string)$resSearch->getBody();
    
    if (preg_match_all('/href="([^"]+course\/view\.php\?id=\d+)[^"]*"/i', $htmlSearch, $mCourses)) {
        $courseUrls = array_unique($mCourses[1]);
        $firstCourse = str_replace('&amp;', '&', $courseUrls[0]);
        echo "Found course: $firstCourse\n";
        
        $resCourse = $client->get($firstCourse);
        $htmlCourse = (string)$resCourse->getBody();
        file_put_contents('storage/logs/course.html', $htmlCourse);
        echo "Course HTML saved. Length: " . strlen($htmlCourse) . "\n";
    } else {
        echo "No courses found\n";
    }
}
