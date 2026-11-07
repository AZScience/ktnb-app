import re

path = 'resources/js/language.js'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace('node.nodeValue.match(/^(\\s*)(.*?)(\\s*)$/);', 'node.nodeValue.match(/^(\\s*)([\\s\\S]*?)(\\s*)$/);')

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
