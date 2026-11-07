<?php
$appServiceFile = __DIR__ . '/app/Providers/AppServiceProvider.php';
$content = file_get_contents($appServiceFile);

// First injection
$hack = "\n        if (request()->is('reports/daily/export') && request()->isMethod('POST')) {\n            request()->setMethod('GET');\n        }\n";
$content = preg_replace('/public function boot\(\)\s*(:\s*void)?\s*\{/i', "\$hack", $content);
file_put_contents($appServiceFile, $content);
