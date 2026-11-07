import os
import json
import re

path_en = 'resources/js/locales/en.json'
with open(path_en, 'r', encoding='utf-8') as f:
    en_dict = json.load(f)

vi_texts = set()

# A rough regex to find Vietnamese texts in blade files
# We can look for data-i18n="...", or just >text<
pattern_i18n = re.compile(r'data-i18n="([^"]+)"')
pattern_text = re.compile(r'>\s*([^<]+?)\s*<')

def process_file(filepath):
    with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()
    
    # match data-i18n
    for match in pattern_i18n.findall(content):
        text = match.strip()
        if text and not text.isascii():
            vi_texts.add(text)
            
    # match >text<
    for match in pattern_text.findall(content):
        text = match.strip()
        # ignore alpine/js code in {{ }} or {} or <x- etc
        if text and not text.isascii() and '{' not in text and '}' not in text and '@' not in text:
            vi_texts.add(text)

for root, dirs, files in os.walk('resources/views'):
    for file in files:
        if file.endswith('.blade.php'):
            process_file(os.path.join(root, file))

missing = [t for t in vi_texts if t not in en_dict]
print(f"Found {len(missing)} missing Vietnamese strings.")

with open('missing.json', 'w', encoding='utf-8') as f:
    json.dump(missing, f, ensure_ascii=False, indent=2)

