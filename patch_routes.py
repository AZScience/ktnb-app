file_path = 'routes/web.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

target = "Route::get('personnel/students-export', [StudentController::class, 'export'])->name('students.export');"
replacement = "Route::get('personnel/students-info', [StudentController::class, 'info'])->name('students.info');\n    Route::get('personnel/students-export', [StudentController::class, 'export'])->name('students.export');"

content = content.replace(target, replacement)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)
print('Done!')
