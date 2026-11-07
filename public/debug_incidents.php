<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

$db = app('db');

echo "<pre>";

// Recognitions có chứa chữ 'thi'
$recs = $db->table('recognitions')->whereRaw("LOWER(name) LIKE '%thi%'")->get();
echo "=== RECOGNITIONS chứa 'thi' ===\n";
foreach ($recs as $r) {
    echo "  ID={$r->id} => {$r->name}\n";
}

// Tất cả IncidentCategories gom theo recognition_id
echo "\n=== INCIDENT CATEGORIES theo recognition_id ===\n";
$cats = $db->table('incident_categories')->orderBy('recognition_id')->orderBy('name')->get();
$grouped = [];
foreach ($cats as $c) {
    $grouped[$c->recognition_id ?? 'NULL'][] = $c->name;
}
foreach ($grouped as $recId => $names) {
    $recName = $db->table('recognitions')->where('id', $recId)->value('name') ?? "(NULL)";
    echo "  recognition_id={$recId} [{$recName}] - " . count($names) . " items:\n";
    foreach ($names as $n) echo "    - {$n}\n";
}

echo "\n=== KẾT QUẢ KHI LỌC 'Thi kết thúc môn' ===\n";
$recognition = $db->table('recognitions')->where('name', 'Thi kết thúc môn')->first();
if ($recognition) {
    echo "Found: ID={$recognition->id}\n";
    $filtered = $db->table('incident_categories')->where('recognition_id', $recognition->id)->orderBy('name')->pluck('name');
    echo count($filtered) . " items:\n";
    foreach ($filtered as $n) echo "  - {$n}\n";
} else {
    echo "KHÔNG TÌM THẤY 'Thi kết thúc môn'!\n";
    echo "Tất cả recognitions:\n";
    foreach ($db->table('recognitions')->orderBy('name')->get() as $r) {
        echo "  ID={$r->id} => '{$r->name}'\n";
    }
}

echo "</pre>";
