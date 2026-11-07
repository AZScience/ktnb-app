import re

path = 'resources/views/components/daily-report-viewer.blade.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace(
    '<span x-text="${tabDefinitions[tabKey]?.title} ()"></span>',
    '<span x-text="tabDefinitions[tabKey]?.title"></span> <span>(</span><span x-text="processedItems(tabKey).length"></span><span>)</span>'
)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Split concatenated span in daily-report-viewer.blade.php")
