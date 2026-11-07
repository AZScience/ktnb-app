import re

path = 'resources/views/components/daily-report-viewer.blade.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace('x-text="text(tabDefinitions[tabKey]?.label)"', 'x-text="tabDefinitions[tabKey]?.label"')
content = content.replace('x-text="${text(tabDefinitions[tabKey]?.title)}', 'x-text="${tabDefinitions[tabKey]?.title}')
content = content.replace('x-text="text(col.label)"', 'x-text="col.label"')

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

path2 = 'resources/views/components/monitoring-schedule-table.blade.php'
with open(path2, 'r', encoding='utf-8') as f:
    content2 = f.read()

content2 = re.sub(r'x-text="text\(([^)]+)\)"', r'x-text="\1"', content2)

with open(path2, 'w', encoding='utf-8') as f:
    f.write(content2)

print("Undid text() in alpine x-text")
