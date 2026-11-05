<?php
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(dirname(__DIR__));
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $user = \App\Models\User::find(3) ?: \App\Models\User::first();
    auth()->login($user);
    echo "Logged in as user: " . $user->name . " (ID: " . $user->id . ")\n";

    $request = \Illuminate\Http\Request::create('/monitoring/online', 'GET', ['date' => '05/11/2026']);
    $controller = app(\App\Http\Controllers\MonitoringScheduleController::class);
    
    echo "Calling online()...\n";
    $res = $controller->online($request);
    echo "online() returned type: " . get_class($res) . "\n";
    $content = $res instanceof \Illuminate\View\View ? $res->render() : (string)$res->getContent();
    echo "Rendered length: " . strlen($content) . " bytes\n";
    echo "SUCCESS: /monitoring/online rendered cleanly!\n";

    echo "Calling inPerson()...\n";
    $requestInPerson = \Illuminate\Http\Request::create('/monitoring/in-person', 'GET', ['date' => '05/11/2026']);
    $res2 = $controller->inPerson($requestInPerson);
    $content2 = $res2 instanceof \Illuminate\View\View ? $res2->render() : (string)$res2->getContent();
    echo "inPerson() rendered length: " . strlen($content2) . " bytes\n";
    echo "SUCCESS: /monitoring/in-person rendered cleanly!\n";

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
