<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\DailyReportTemplateBuilder;
use App\Services\DailyReportTemplateSanitizer;

$sanitizer = app(DailyReportTemplateSanitizer::class);
$source = $sanitizer->sourcePath();
$target = $sanitizer->sanitizedPath();

if ($sanitizer->isUsableSource($source)) {
    $previousLimit = ini_get('memory_limit');
    ini_set('memory_limit', '768M');

    try {
        $sanitizer->sanitize($source, $target);
        echo 'Sanitized template: '.$target.' ('.filesize($target)." bytes)\n";

        $publicTarget = public_path('templates/daily_report_template.xlsx');
        $publicDir = dirname($publicTarget);
        if (is_dir($publicDir) && is_writable($publicDir)) {
            if (is_file($publicTarget)) {
                @chmod($publicTarget, 0666);
                @unlink($publicTarget);
            }

            if (copy($target, $publicTarget)) {
                echo 'Copied sanitized template: '.$publicTarget.' ('.filesize($publicTarget)." bytes)\n";
            }
        }
    } finally {
        if ($previousLimit !== false) {
            ini_set('memory_limit', (string) $previousLimit);
        }
    }

    exit(0);
}

$fallback = app(DailyReportTemplateBuilder::class)->writeTo();
echo 'Built fallback template: '.$fallback.' ('.filesize($fallback)." bytes)\n";
