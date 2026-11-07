<?php
$file = __DIR__ . '/app/Http/Controllers/MonitoringScheduleController.php';
$content = file_get_contents($file);

$start_marker = '// Chú ý: điều kiện tìm kiếm trên LCMS ưu tiên dựa vào Lớp và Giảng viên';
$start_pos = strpos($content, $start_marker);

$end_marker = 'foreach (array_slice(';

$end_pos = strpos($content, $end_marker, $start_pos);

if ($start_pos !== false && $end_pos !== false) {
    $new_logic = <<<PHP
// 4 Cấp độ ưu tiên tìm kiếm trên LCMS
                        \ = \->input('department');
                        \ = '';
                        if (\) {
                            if (preg_match('/^([A-Z0-9]+)\\\\s*-/', \, \)) {
                                \ = \[1];
                            } else {
                                \ = explode(' ', \)[0];
                            }
                        }

                        \ = [];
                        if (\ && \ && \ && \) {
                            \[] = trim("\ \ \ \");
                        }
                        if (\ && \ && \) {
                            \[] = trim("\ \ \");
                        }
                        if (\ && \) {
                            \[] = trim("\ \");
                        }
                        
                        // Fallbacks
                        if (\ && \) {
                            \[] = trim("\ \");
                        }
                        if (\) {
                            \[] = trim(\);
                        }
                        if (\) {
                            \[] = trim(\);
                        }
                        
                        \ = array_values(array_filter(array_unique(\)));
                        
                        \ = [];
                        \ = '';
                        \ = '';
                        \ = '';
                        
                        foreach (\ as \) {
                            if (!\) continue;
                            
                            \Illuminate\Support\Facades\Log::info("LCMS Search: " . \);
                            \ = urlencode(\);
                            \ = rtrim(\, '/') . '/course/search.php?areaids=core_course-course&q=' . \;
                            
                            \ = \->get(\, [
                                'allow_redirects' => ['track_redirects' => true]
                            ]);
                            \ = (string)\->getBody();
                            
                            \ = \->getHeader('X-Guzzle-Redirect-History');
                            \ = empty(\) ? \ : end(\);
                            
                            if (strpos(\, 'course/view.php') !== false) {
                                \[] = \;
                                \ = \;
                                \Illuminate\Support\Facades\Log::info("LCMS Redirected exactly to course: " . \);
                                break; // Found!
                            } else {
                                if (preg_match_all('/href="([^"]+course\/view\.php\?id=\d+)[^"]*"/i', \, \)) {
                                    \ = array_unique(\[1]);
                                    if (!empty(\)) {
                                        \ = \;
                                        \Illuminate\Support\Facades\Log::info("LCMS Found " . count(\) . " course links");
                                        break; // Found!
                                    }
                                }
                            }
                        }
                        
                        
PHP;

    $content = substr_replace($content, $new_logic, $start_pos, $end_pos - $start_pos);
    file_put_contents($file, $content);
    echo "Successfully replaced.\n";
} else {
    echo "Markers not found.\n";
}
