import sys

file_path = 'app/Http/Controllers/MonitoringScheduleController.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

start_marker = '// Trích xuất Mã môn từ subject'
end_marker = "foreach (array_slice($courseUrls, 0, 3) as $courseUrl) {"

start_idx = content.find(start_marker)
end_idx = content.find(end_marker)

if start_idx != -1 and end_idx != -1:
    new_logic = (
        "// 4 Cấp độ ưu tiên tìm kiếm trên LCMS\n"
        "                        $department = $request->input('department');\n"
        "                        $subjectCode = '';\n"
        "                        if ($subject) {\n"
        "                            if (preg_match('/^([A-Z0-9]+)\\\\s*-/', $subject, $m)) {\n"
        "                                $subjectCode = $m[1];\n"
        "                            } else {\n"
        "                                $subjectCode = explode(' ', $subject)[0];\n"
        "                            }\n"
        "                        }\n"
        "\n"
        "                        $searchQueries = [];\n"
        "                        if ($subjectCode && $class && $lecturer && $department) {\n"
        "                            $searchQueries[] = trim(\"$subjectCode $class $lecturer $department\");\n"
        "                        }\n"
        "                        if ($subjectCode && $class && $lecturer) {\n"
        "                            $searchQueries[] = trim(\"$subjectCode $class $lecturer\");\n"
        "                        }\n"
        "                        if ($subjectCode && $class) {\n"
        "                            $searchQueries[] = trim(\"$subjectCode $class\");\n"
        "                        }\n"
        "                        \n"
        "                        // Fallbacks\n"
        "                        if ($class && $lecturer) {\n"
        "                            $searchQueries[] = trim(\"$class $lecturer\");\n"
        "                        }\n"
        "                        if ($subjectCode) {\n"
        "                            $searchQueries[] = trim($subjectCode);\n"
        "                        }\n"
        "                        if ($class) {\n"
        "                            $searchQueries[] = trim($class);\n"
        "                        }\n"
        "                        \n"
        "                        $searchQueries = array_values(array_filter(array_unique($searchQueries)));\n"
        "                        \n"
        "                        $courseUrls = [];\n"
        "                        $lcmsSearchTerm = '';\n"
        "                        $htmlSearch = '';\n"
        "                        $currentUrl = '';\n"
        "                        \n"
        "                        foreach ($searchQueries as $searchTerm) {\n"
        "                            if (!$searchTerm) continue;\n"
        "                            \n"
        "                            \\Illuminate\\Support\\Facades\\Log::info(\"LCMS Search: \" . $searchTerm);\n"
        "                            $searchQuery = urlencode($searchTerm);\n"
        "                            $searchUrl = rtrim($lcmsUrl, '/') . '/course/search.php?areaids=core_course-course&q=' . $searchQuery;\n"
        "                            \n"
        "                            $resSearch = $client->get($searchUrl, [\n"
        "                                'allow_redirects' => ['track_redirects' => true]\n"
        "                            ]);\n"
        "                            $htmlSearch = (string)$resSearch->getBody();\n"
        "                            \n"
        "                            $redirectHistory = $resSearch->getHeader('X-Guzzle-Redirect-History');\n"
        "                            $currentUrl = empty($redirectHistory) ? $searchUrl : end($redirectHistory);\n"
        "                            \n"
        "                            if (strpos($currentUrl, 'course/view.php') !== false) {\n"
        "                                $courseUrls[] = $currentUrl;\n"
        "                                $lcmsSearchTerm = $searchTerm;\n"
        "                                break; // Found!\n"
        "                            } else {\n"
        "                                if (preg_match_all('/href=\"([^\"]+course\\/view\\.php\\?id=\\d+)[^\"]*\"/i', $htmlSearch, $mCourses)) {\n"
        "                                    $courseUrls = array_unique($mCourses[1]);\n"
        "                                    if ($courseUrls) {\n"
        "                                        $lcmsSearchTerm = $searchTerm;\n"
        "                                        break; // Found!\n"
        "                                    }\n"
        "                                }\n"
        "                            }\n"
        "                        }\n"
        "                        \n"
        "                        if (!empty($courseUrls)) {\n"
        "                            foreach (array_slice($courseUrls, 0, 10) as $courseUrl) {\n"
    )
    
    content = content[:start_idx] + new_logic + content[end_idx + len(end_marker):]
    
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Replaced!")
else:
    print("Not found! start:", start_idx, "end:", end_idx)
