import re

path = 'resources/js/language.js'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

# Fix translateElementText
content = re.sub(
    r'if \(key && hasTranslation\(key, lang\)\) \{\s*el\.textContent = t\(key, lang\);\s*\}',
    r'if (key && (hasTranslation(key, lang) || lang === "vi")) {\n        el.textContent = t(key, lang);\n    }',
    content
)

# Fix translatePlaceholder
content = re.sub(
    r'if \(key && hasTranslation\(key, lang\)\) \{\s*el\.setAttribute\(\'placeholder\', t\(key, lang\)\);\s*\}',
    r'if (key && (hasTranslation(key, lang) || lang === "vi")) {\n        el.setAttribute("placeholder", t(key, lang));\n    }',
    content
)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Fixed language.js translateElementText")
