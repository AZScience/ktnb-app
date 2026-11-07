import re

path = 'resources/js/language.js'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

# I will replace the entire translateTextNodes function

old_func_start = "function translateTextNodes(lang, root = document) {"
old_func_end = "});\n}\n"

# find the block
start_idx = content.find(old_func_start)
if start_idx != -1:
    end_idx = content.find(old_func_end, start_idx) + len(old_func_end)
    
    new_func = """function translateTextNodes(lang, root = document) {
    const walker = document.createTreeWalker(
        root === document ? document.body : root,
        NodeFilter.SHOW_TEXT,
        {
            acceptNode: function(node) {
                const parent = node.parentElement;
                if (!parent) return NodeFilter.FILTER_REJECT;
                if (['SCRIPT', 'STYLE', 'NOSCRIPT', 'TEXTAREA'].includes(parent.tagName)) return NodeFilter.FILTER_REJECT;
                if (parent.closest && parent.closest('[data-i18n-skip]')) return NodeFilter.FILTER_REJECT;
                if (!node.nodeValue.trim()) return NodeFilter.FILTER_REJECT;
                return NodeFilter.FILTER_ACCEPT;
            }
        }
    );

    const nodes = [];
    let currentNode;
    while (currentNode = walker.nextNode()) {
        nodes.push(currentNode);
    }

    nodes.forEach(node => {
        if (node._isTranslating) return;

        let originalText = node.nodeValue.trim();
        if (!originalText) return;

        let key = node._i18nKey;
        if (!key) {
            key = originalText;
            node._i18nKey = key;
            node._lastTranslatedText = originalText;
        } else {
            if (originalText !== node._lastTranslatedText && originalText !== key) {
                key = originalText;
                node._i18nKey = key;
                node._lastTranslatedText = originalText;
            }
        }

        if (hasTranslation(key, lang) || lang === 'vi') {
            const translated = t(key, lang);
            if (node.nodeValue.trim() !== translated) {
                node._isTranslating = true;
                const match = node.nodeValue.match(/^(\\s*)([\\s\\S]*?)(\\s*)$/);
                node.nodeValue = (match[1] || '') + translated + (match[3] || '');
                node._lastTranslatedText = translated;
                setTimeout(() => { node._isTranslating = false; }, 0);
            }
        }
    });
}
"""
    content = content[:start_idx] + new_func + content[end_idx:]
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Fixed translateTextNodes function")
else:
    print("Could not find function")

