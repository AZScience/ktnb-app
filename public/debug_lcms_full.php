<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "<pre style='font-family: monospace; font-size: 14px;'>";
echo "🔄 BẮT ĐẦU ĐIỀU TRA LCMS VỚI LOGIC Y HỆT CONTROLLER...\n\n";

$lcmsUrl = \App\Models\SystemParameter::where('key', 'lcmsUrl')->value('value') ?: 'https://lcms.ntt.edu.vn';
$lcmsUser = \App\Models\SystemParameter::where('key', 'lcmsUser')->value('value');
$lcmsPass = \App\Models\SystemParameter::where('key', 'lcmsPass')->value('value');

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
    echo "✅ Đã đăng nhập vào hệ thống LCMS!\n\n";

    // Data from frontend
    $department = trim("Khoa Luật");
    $subject = trim("010107001151 - Pháp luật đại cương");
    $class = trim("26DDD3A");
    $lecturer = trim("Đặng Thị Ngọc Tú");

    $subjectCode = '';
    if ($subject) {
        if (preg_match('/^([A-Z0-9]+)\s*-/', $subject, $m)) {
            $subjectCode = $m[1];
        } else {
            $subjectCode = explode(' ', $subject)[0];
        }
    }
    
    echo "Dữ liệu đã chuẩn hóa:\n";
    echo "- Subject Code: '$subjectCode'\n";
    echo "- Class: '$class'\n";
    echo "- Lecturer: '$lecturer'\n";
    echo "- Department: '$department'\n\n";

    $searchQueries = [];
    if ($subjectCode && $class && $lecturer && $department) {
        $searchQueries[] = trim("$subjectCode $class $lecturer $department");
    }
    if ($subjectCode && $class && $lecturer) {
        $searchQueries[] = trim("$subjectCode $class $lecturer");
    }
    if ($subjectCode && $class) {
        $searchQueries[] = trim("$subjectCode $class");
    }
    if ($class && $lecturer) {
        $searchQueries[] = trim("$class $lecturer");
    }
    if ($subjectCode) {
        $searchQueries[] = trim($subjectCode);
    }
    $searchQueries = array_values(array_filter(array_unique($searchQueries)));

    $bestLink = null;

    foreach ($searchQueries as $searchTerm) {
        echo "========================================\n";
        echo "🔍 Đang gõ lệnh tìm kiếm: '$searchTerm'\n";
        
        try {
            $searchQuery = urlencode($searchTerm);
            $searchUrl = rtrim($lcmsUrl, '/') . '/course/search.php?areaids=core_course-course&q=' . $searchQuery;
            
            $resSearch = $client->get($searchUrl, [
                'allow_redirects' => ['track_redirects' => true],
                'timeout' => 15,
                'http_errors' => false
            ]);
            
            echo "   Trạng thái HTTP: " . $resSearch->getStatusCode() . "\n";
            
            if ($resSearch->getStatusCode() >= 400) {
                echo "   ❌ Lỗi Moodle trả về mã " . $resSearch->getStatusCode() . ", BỎ QUA lệnh này!\n";
                continue;
            }
            
            $htmlSearch = (string)$resSearch->getBody();
            $redirectHistory = $resSearch->getHeader('X-Guzzle-Redirect-History');
            $currentUrl = empty($redirectHistory) ? $searchUrl : end($redirectHistory);
            
            $courseUrlsForTerm = [];
            if (strpos($currentUrl, 'course/view.php') !== false) {
                $courseUrlsForTerm[] = $currentUrl;
                echo "   ➡️ Moodle tự động Redirect thẳng vào khóa học!\n";
            } else {
                if (preg_match_all('/href="([^"]+course\/view\.php\?id=\d+)[^"]*"/i', $htmlSearch, $mCourses)) {
                    $courseUrlsForTerm = array_unique($mCourses[1]);
                    echo "   ✅ Moodle trả về trang danh sách. Tìm thấy " . count($courseUrlsForTerm) . " khóa học.\n";
                } else {
                    echo "   ❌ Không tìm thấy khóa học nào trong kết quả!\n";
                }
            }
            
            if (!empty($courseUrlsForTerm)) {
                foreach (array_slice($courseUrlsForTerm, 0, 10) as $courseUrl) {
                    $courseUrl = str_replace('&amp;', '&', $courseUrl);
                    echo "\n   ➡️ Đang chui vào khóa học: $courseUrl\n";
                    
                    if ($courseUrl !== $currentUrl) {
                        $resCourse = $client->get($courseUrl, ['timeout' => 10, 'http_errors' => false]);
                        $htmlCourse = (string)$resCourse->getBody();
                    } else {
                        $htmlCourse = $htmlSearch;
                    }
                    
                    if ($class) {
                        $classCompact = preg_replace('/[\s\-]/', '', strtolower($class));
                        $courseText = preg_replace('/[\s\-]/', '', strtolower(strip_tags($htmlCourse)));
                        if (strpos($courseText, $classCompact) === false) {
                            echo "      ❌ LỌC: Bỏ qua khóa học này vì không có chữ '$class'!\n";
                            continue;
                        }
                    }
                    echo "      ✅ CHẤP NHẬN: Khóa học này ĐÚNG LÀ CỦA LỚP '$class'!\n";
                    
                    $linkPositions = [];
                    if (preg_match_all('/https:\/\/meet\.google\.com\/[a-z0-9\-]+/i', $htmlCourse, $mLinks, PREG_OFFSET_CAPTURE)) {
                        echo "      🔗 Tìm thấy link LỘ DIỆN:\n";
                        foreach ($mLinks[0] as $match) {
                            $linkPositions[$match[1]] = $match[0];
                            echo "         - " . $match[0] . "\n";
                        }
                    }
                    
                    if (preg_match_all('/href="([^"]*mod\/url\/view\.php\?id=\d+)"/i', $htmlCourse, $mUrlMods, PREG_OFFSET_CAPTURE)) {
                        $checkedUrls = [];
                        foreach ($mUrlMods[1] as $match) {
                            $urlMod = $match[0];
                            $offset = $match[1];
                            if (in_array($urlMod, $checkedUrls)) continue;
                            if (count($checkedUrls) >= 5) break; 
                            $checkedUrls[] = $urlMod;
                            
                            $urlMod = str_replace('&amp;', '&', $urlMod);
                            try {
                                $resUrl = $client->get($urlMod, ['timeout' => 10]);
                                $htmlUrl = (string)$resUrl->getBody();
                                if (preg_match('/https:\/\/meet\.google\.com\/[a-z0-9\-]+/i', $htmlUrl, $mMeetHidden)) {
                                    echo "      🔗 Tìm thấy link ẨN:\n";
                                    echo "         - " . $mMeetHidden[0] . "\n";
                                    $linkPositions[$offset] = $mMeetHidden[0];
                                }
                            } catch (\Exception $e) {}
                        }
                    }
                    
                    if (!empty($linkPositions)) {
                        ksort($linkPositions);
                        $bestLink = end($linkPositions);
                        echo "\n   🎉 ĐÃ CHỐT ĐƯỢC LINK CUỐI CÙNG: $bestLink\n";
                        echo "   🛑 BREAK THÀNH CÔNG KHỎI TẤT CẢ CÁC VÒNG LẶP!\n";
                        break 2; // Test break 2
                    } else {
                        echo "      ❌ KHÔNG TÌM THẤY LINK GOOGLE MEET NÀO TRONG KHÓA NÀY!\n";
                    }
                }
            }
        } catch (\Exception $e) {
            echo "   🔥 NGOẠI LỆ: " . $e->getMessage() . "\n";
            continue;
        }
    }
    
    echo "\n========================================\n";
    if ($bestLink) {
        echo "🏆 KẾT QUẢ CUỐI CÙNG: Hệ thống trả về link $bestLink\n";
    } else {
        echo "😭 KẾT QUẢ CUỐI CÙNG: KHÔNG TÌM THẤY LINK NÀO (TRẢ VỀ LỖI CHO NGƯỜI DÙNG)\n";
    }

} else {
    echo "❌ THẤT BẠI: Không lấy được token đăng nhập.\n";
}
echo "</pre>";
