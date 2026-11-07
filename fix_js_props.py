import re

path = 'resources/js/interactive-report.js'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace(
'''
        get creator_namesOptions() {
            return mergeOptions(this.config.filterOptions?.employees, uniqueOptions(this.currentRows, 'creator_name'));
        },

        get locationsOptions() {
            return mergeOptions(this.config.filterOptions?.buildings, uniqueOptions(this.currentRows, 'location'));
        },
''',
'''
        get creator_namesOptions() {
            return mergeOptions(this.config.filterOptions?.creator_names, uniqueOptions(this.currentRows, 'creator_name'));
        },

        get locationsOptions() {
            return mergeOptions(this.config.filterOptions?.locations, uniqueOptions(this.currentRows, 'location'));
        },
'''
)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Fixed filter property names in JS")
