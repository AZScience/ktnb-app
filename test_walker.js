// Mock DOM text node
class TextNode {
    constructor(text) {
        this.nodeValue = text;
        this.parentElement = { tagName: 'SPAN', closest: () => null };
    }
}

let node = new TextNode('  Xin chào  ');
let lang = 'en';

function t(key, l) {
    if (l === 'en' && key === 'Xin chào') return 'Hello';
    if (l === 'en' && key === 'Giao diện & Ngôn ngữ') return 'Appearance & Language';
    return key;
}

function processNode(node, lang) {
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

    if (lang === 'en' || lang === 'vi') {
        const translated = t(key, lang);
        if (node.nodeValue.trim() !== translated) {
            const match = node.nodeValue.match(/^(\s*)([\s\S]*?)(\s*)$/);
            node.nodeValue = (match[1] || '') + translated + (match[3] || '');
            node._lastTranslatedText = translated;
        }
    }
}

console.log("Initial:", node.nodeValue);
processNode(node, 'en');
console.log("To EN:", node.nodeValue, "| Key:", node._i18nKey);
processNode(node, 'vi');
console.log("To VI:", node.nodeValue, "| Key:", node._i18nKey);

// What if text is missing in dictionary?
let node2 = new TextNode('Thiếu từ điển');
processNode(node2, 'en');
console.log("\nMissing To EN:", node2.nodeValue, "| Key:", node2._i18nKey);
processNode(node2, 'vi');
console.log("Missing To VI:", node2.nodeValue, "| Key:", node2._i18nKey);
