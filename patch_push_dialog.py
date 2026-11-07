import re

path = 'resources/views/components/daily-report-viewer.blade.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace(
    '<p class="mt-1 text-sm text-gray-500" x-text="Dữ liệu từ bảng &quot;&quot; sẽ được đưa vào Google Sheet đã kết nối."></p>',
    '<p class="mt-1 text-sm text-gray-500"><span data-i18n="Dữ liệu từ bảng">Dữ liệu từ bảng</span> &quot;<span x-text="tabDefinitions[pushTabKey]?.title || \'\'"></span>&quot; <span data-i18n="sẽ được đưa vào Google Sheet đã kết nối.">sẽ được đưa vào Google Sheet đã kết nối.</span></p>'
)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Patched push dialog text concatenation")
