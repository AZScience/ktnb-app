<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ScheduleExcelService;
use Illuminate\Http\UploadedFile;

$paths = [
    realpath(__DIR__.'/../../public/templates/daily_schedule_template.xlsx'),
];

foreach (glob(__DIR__.'/../storage/app/*.xlsx') ?: [] as $p) {
    $paths[] = $p;
}

$svc = app(ScheduleExcelService::class);

foreach (array_filter($paths) as $path) {
    echo "=== File: {$path}\n";
    if (! is_readable($path)) {
        echo "NOT READABLE\n\n";
        continue;
    }
    echo 'Size: '.filesize($path)." bytes\n";
    try {
        $file = new UploadedFile($path, basename($path), null, null, true);
        $result = $svc->importPreview($file);
        echo 'rawCount: '.$result['rawCount']."\n";
        echo 'preview: '.count($result['preview'])."\n";
        echo 'merged: '.count($result['merged'])."\n";
        if (! empty($result['preview'][0])) {
            echo 'sample: '.json_encode($result['preview'][0], JSON_UNESCAPED_UNICODE)."\n";
        }
    } catch (Throwable $e) {
        echo 'ERROR: '.$e->getMessage()."\n";
        echo $e->getFile().':'.$e->getLine()."\n";
    }
    echo "\n";
}
