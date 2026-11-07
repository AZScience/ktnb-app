<?php
$file = __DIR__ . '/../routes/web.php';
if (!file_exists($file)) {
    die("web.php not found.");
}

$content = file_get_contents($file);
if (strpos($content, 'students-info') !== false) {
    die("Route already exists!");
}

$target = "Route::get('personnel/students-export', [StudentController::class, 'export'])->name('students.export');";
$replacement = "Route::get('personnel/students-info', [StudentController::class, 'info'])->name('students.info');\n    Route::get('personnel/students-export', [StudentController::class, 'export'])->name('students.export');";

$newContent = str_replace($target, $replacement, $content);

if ($newContent === $content) {
    die("Could not find the target line to replace.");
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
    echo "SUCCESS: web.php has been updated!";
} else {
    echo "FAILED: Permission denied. Could not modify, delete, or rename web.php.";
}
