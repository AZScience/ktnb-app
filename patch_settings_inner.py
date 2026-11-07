import re

path = 'resources/views/components/partials/report-settings-column-inner.blade.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace(
    '<svg class="h-5 w-5" fill="none" stroke="currentColor"',
    '<svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor"'
)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated settings inner blade")
