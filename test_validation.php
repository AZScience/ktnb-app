<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$request = \Illuminate\Http\Request::create('/test', 'POST', ['overwrite' => '1']);
$data = $request->validate(['overwrite' => 'nullable|boolean']);
var_dump($data);
