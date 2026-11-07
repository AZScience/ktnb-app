file_path = 'app/Services/ActivityLogService.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace(
    'dispatch(static function () use (): void {\n            ActivityLog::create();\n        })->afterResponse();',
    'ActivityLog::create();'
)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)
print('Done!')
