import re

path = 'resources/views/components/nttu-sidebar.blade.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace("'text-gray-500'", "'text-teal-500'")
content = content.replace("opacity-80", "")

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Changed gray icon to teal")
