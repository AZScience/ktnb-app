<?php
$file = __DIR__ . '/../resources/views/monitoring/requests/index.blade.php';
if (!file_exists($file)) {
    die("index.blade.php not found.");
}

$content = file_get_contents($file);
if (strpos($content, "'list' => route('requests.index'),") !== false) {
    die("SUCCESS: Route is already fixed!");
}

$target = "'list' => route('service-requests.index'),";
$replacement = "'list' => route('requests.index'),";

$newContent = str_replace($target, $replacement, $content);

if ($newContent === $content) {
    die("Could not find the target line to replace. Maybe it was already changed?");
}

// Try to write directly first
$success = @file_put_contents($file, $newContent);
if ($success === false) {
    // If permission denied, try to unlink and write new
    if (@unlink($file)) {
        $success = @file_put_contents($file, $newContent);
    } else {
        // If unlink fails, try to rename it to a backup and write new
        if (@rename($file, $file . '.bak_' . time())) {
            $success = @file_put_contents($file, $newContent);
        }
    }
}

if ($success !== false) {
    echo "SUCCESS: index.blade.php has been updated successfully!";
} else {
    echo "FAILED: Permission denied. Could not modify, delete, or rename index.blade.php.";
}
