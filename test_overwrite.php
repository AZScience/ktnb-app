<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$targetDir = storage_path('app');
$fileName = 'test_overwrite.txt';
$target = $targetDir . DIRECTORY_SEPARATOR . $fileName;

file_put_contents($target, "OLD CONTENT");

// Create a fake uploaded file
$fakeTemp = storage_path('app/temp_upload.txt');
file_put_contents($fakeTemp, "NEW CONTENT");

$uploaded = new \Illuminate\Http\UploadedFile($fakeTemp, 'temp_upload.txt', 'text/plain', null, true);
$uploaded->move($targetDir, $fileName);

echo file_get_contents($target);
