<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/monitoring/fetch-meet-link', 'POST', ['module'=>'online', 'source'=>'email']);
$response = $kernel->handle($request);
echo $response->status() . "\n";
echo $response->getContent() . "\n";
