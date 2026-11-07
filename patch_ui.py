import os

blade_path = 'resources/views/components/project-files-manager.blade.php'
with open(blade_path, 'r', encoding='utf-8') as f:
    content = f.read()

target = '''<label x-show="canAdd" x-cloak class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-md border border-blue-200 bg-blue-50 px-3 text-sm font-medium text-blue-700 hover:bg-blue-100">'''
replacement = '''<label x-show="canAdd" x-cloak class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-md border border-orange-200 bg-orange-50 px-2 text-sm text-orange-700 hover:bg-orange-100" title="Ghi đè nếu file đã tồn tại trên server">
                    <input type="checkbox" x-model="overwriteUpload" class="rounded border-orange-400 text-orange-600 focus:ring-orange-500 h-4 w-4">
                    <span class="hidden sm:inline">Ghi đè</span>
                </label>
                <label x-show="canAdd" x-cloak class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-md border border-blue-200 bg-blue-50 px-3 text-sm font-medium text-blue-700 hover:bg-blue-100">'''

content = content.replace(target, replacement)
with open(blade_path, 'w', encoding='utf-8') as f:
    f.write(content)

js_path = 'resources/js/project-files.js'
with open(js_path, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace(
    "currentPath: '',",
    "currentPath: '',\n        overwriteUpload: false,"
)

content = content.replace(
    "formData.append('path', this.currentPath || '');",
    "formData.append('path', this.currentPath || '');\n            if (this.overwriteUpload) formData.append('overwrite', '1');"
)

with open(js_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Patched UI and JS")
