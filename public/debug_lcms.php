<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$lcmsUrl = \App\Models\SystemParameter::where('key', 'lcmsUrl')->value('value') ?: 'https://lcms.ntt.edu.vn';
$lcmsUser = \App\Models\SystemParameter::where('key', 'lcmsUser')->value('value');
$lcmsPass = \App\Models\SystemParameter::where('key', 'lcmsPass')->value('value');

echo "<pre>";
echo "Testing LCMS search for: 010107001151\n\n";

$client = new \GuzzleHttp\Client(['cookies' => true, 'verify' => false, 'headers' => [
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
]]);

$loginUrl = rtrim($lcmsUrl, '/') . '/login/index.php';
$res1 = $client->get($loginUrl);
$html1 = (string)$res1->getBody();

if (preg_match('/name="logintoken" value="([^"]+)"/', $html1, $mToken)) {
    $loginToken = $mToken[1];
    $client->post($loginUrl, [
        'form_params' => [
            'username' => $lcmsUser,
            'password' => $lcmsPass,
            'logintoken' => $loginToken
        ]
    ]);
    echo "Login sent.\n";
    
    $searchUrl = rtrim($lcmsUrl, '/') . '/course/search.php?areaids=core_course-course&q=010107001151';
    $resSearch = $client->get($searchUrl, ['allow_redirects' => ['track_redirects' => true]]);
    $htmlSearch = (string)$resSearch->getBody();
    
    echo "Search URL: $searchUrl\n";
    if (preg_match_all('/href="([^"]+course\/view\.php\?id=\d+)[^"]*"/i', $htmlSearch, $mCourses)) {
        $courseUrls = array_unique($mCourses[1]);
        echo "Found courses:\n";
        print_r($courseUrls);
        
        foreach ($courseUrls as $courseUrl) {
            $courseUrl = str_replace('&amp;', '&', $courseUrl);
            echo "\nFetching: $courseUrl\n";
            $resCourse = $client->get($courseUrl);
            $htmlCourse = (string)$resCourse->getBody();
            
            $class = '26DDD3A';
            $classCompact = preg_replace('/[\s\-]/', '', strtolower($class));
            $courseText = preg_replace('/[\s\-]/', '', strtolower(strip_tags($htmlCourse)));
            
            if (strpos($courseText, $classCompact) === false) {
                echo "--> Class $class NOT FOUND in text!\n";
            } else {
                echo "--> Class $class FOUND in text!\n";
            }
            
            if (preg_match_all('/https:\/\/meet\.google\.com\/[a-z0-9\-]+/i', $htmlCourse, $mLinks)) {
                echo "--> Found Meet links:\n";
                print_r($mLinks[0]);
            } else {
                echo "--> NO Meet links found in HTML.\n";
            }
        }
    } else {
        echo "No course URLs found in search HTML.\n";
    }
} else {
    echo "Failed to get login token.\n";
}
