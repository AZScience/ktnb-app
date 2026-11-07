import re

path = 'resources/js/daily-report.js'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

injection = "    text(key) { return window.t ? window.t(key) : key; },\n"
if "text(key)" not in content:
    content = content.replace("tab: 'in-person',", injection + "        tab: 'in-person',")
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)

path2 = 'resources/views/components/daily-report-viewer.blade.php'
with open(path2, 'r', encoding='utf-8') as f:
    content2 = f.read()

# Replace x-text for label and title
content2 = re.sub(r'x-text="tabDefinitions\[tabKey\]\?\.label"', 'x-text="text(tabDefinitions[tabKey]?.label)"', content2)
content2 = re.sub(r'x-text="\$\{tabDefinitions\[tabKey\]\?\.title\}', 'x-text="${text(tabDefinitions[tabKey]?.title)}', content2)
# also column labels
content2 = re.sub(r'x-text="col\.label"', 'x-text="text(col.label)"', content2)

with open(path2, 'w', encoding='utf-8') as f:
    f.write(content2)

print("Injected text() helper into daily-report")
