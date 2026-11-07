<?php
$html = file_get_contents("https://lcms.ntt.edu.vn/course/search.php?search=20DTH1");
echo substr($html, 0, 500);
if (preg_match_all('/href="([^"]+course\/view\.php\?id=\d+)[^"]*"/i', $html, $m)) {
    print_r(array_unique($m[1]));
} else {
    echo "No courses found";
}

