<?php
$url = 'https://github.com/AZScience/ktnb-app/archive/refs/heads/kiemtranoibo.zip';
$zipFile = __DIR__.'/kiemtranoibo.zip';

$options = [
    "http" => [
        "header" => "User-Agent: PHP\r\n"
    ]
];
$context = stream_context_create($options);

echo "Đang tải mã nguồn giao diện...\n";
$content = file_get_contents($url, false, $context);
if ($content) {
    file_put_contents($zipFile, $content);
    
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === TRUE) {
        // Giải nén thư mục public/build
        for($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (strpos($filename, 'ktnb-app-kiemtranoibo/public/build/') === 0) {
                // Lấy đường dẫn đích
                $destPath = __DIR__ . '/' . str_replace('ktnb-app-kiemtranoibo/public/', '', $filename);
                
                if (substr($filename, -1) == '/') {
                    if (!is_dir($destPath)) mkdir($destPath, 0755, true);
                } else {
                    $dir = dirname($destPath);
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    $fileContent = $zip->getFromIndex($i);
                    file_put_contents($destPath, $fileContent);
                }
            }
        }
        $zip->close();
        unlink($zipFile);
        echo "<h1>✅ ĐÃ CẬP NHẬT GIAO DIỆN THÀNH CÔNG!</h1>";
    } else {
        echo "<h1>❌ LỖI GIẢI NÉN ZIP</h1>";
    }
} else {
    echo "<h1>❌ LỖI TẢI ZIP</h1>";
}
