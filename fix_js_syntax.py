import re

path = 'resources/js/interactive-report.js'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace(
'''
        get creator_namesOptions() {
            return mergeOptions(this.config.filterOptions?.employees, uniqueOptions(this.currentRows, 'creator_name'));
        }

        get locationsOptions() {
            return mergeOptions(this.config.filterOptions?.buildings, uniqueOptions(this.currentRows, 'location'));
        }

        get departmentOptions() {
''',
'''
        get creator_namesOptions() {
            return mergeOptions(this.config.filterOptions?.employees, uniqueOptions(this.currentRows, 'creator_name'));
        },

        get locationsOptions() {
            return mergeOptions(this.config.filterOptions?.buildings, uniqueOptions(this.currentRows, 'location'));
        },

        get departmentOptions() {
'''
)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Fixed comma in interactive-report.js")
