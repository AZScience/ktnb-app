import os

file_path = 'resources/views/monitoring/requests/index.blade.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

target = "'list' => route('service-requests.index'),"
replacement = "'list' => route('requests.index'),"

content = content.replace(target, replacement)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Patched requests index blade")
