import re

path = 'resources/js/language.js'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

tree_walker_func = '''
function translateTextNodes(lang, root = document) {
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
        } else {
            const expectedTranslation = t(key, lang);
            if (originalText !== expectedTranslation && originalText !== key) {
                key = originalText;
                node._i18nKey = key;
            }
        }

        if (hasTranslation(key, lang) || lang === 'vi') {
            const translated = t(key, lang);
            if (node.nodeValue.trim() !== translated) {
                node._isTranslating = true;
                const match = node.nodeValue.match(/^(\\s*)(.*?)(\\s*)$/);
                node.nodeValue = (match[1] || '') + translated + (match[3] || '');
                setTimeout(() => { node._isTranslating = false; }, 0);
            }
        }
    });
}
'''

if 'translateTextNodes(' not in content:
    content = tree_walker_func + content
    
    # insert translateTextNodes(lang, root) into applyI18n
    apply_pattern = r'(export function applyI18n\(lang = getLanguage\(\), root = document\) \{)'
    content = re.sub(apply_pattern, r'\1\n    translateTextNodes(lang, root);', content)
    
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Patched language.js")
else:
    print("Already patched")
