
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
                const match = node.nodeValue.match(/^(\s*)([\s\S]*?)(\s*)$/);
                node.nodeValue = (match[1] || '') + translated + (match[3] || '');
                node._lastTranslatedText = translated;
                setTimeout(() => { node._isTranslating = false; }, 0);
            }
        }
    });
}
import vi from './locales/vi.json';
import en from './locales/en.json';

const translations = { vi, en };

let observerStarted = false;

export function getLanguage() {
    try {
        return localStorage.getItem('language') || 'vi';
    } catch {
        return 'vi';
    }
}

export function hasTranslation(key, lang = getLanguage()) {
    if (!key) return false;
    const dict = translations[lang] || translations.vi;
    return dict[key] !== undefined;
}

export function t(key, lang = getLanguage()) {
    if (!key) return '';

    const dict = translations[lang] || translations.vi;
    if (dict[key] !== undefined) {
        return dict[key];
    }

    return key;
}

/** Translate user-facing messages (alerts, toasts, confirms). */
export function translateMessage(message, lang = getLanguage()) {
    if (!message) return '';
    const text = String(message).trim();
    return hasTranslation(text, lang) ? t(text, lang) : text;
}

export function alertMessage(message, lang = getLanguage()) {
    window.alert(translateMessage(message, lang));
}

export function confirmMessage(message, lang = getLanguage()) {
    return window.confirm(translateMessage(message, lang));
}

function resolveElementKey(el) {
    const explicit = el.getAttribute('data-i18n');
    const stored = el.getAttribute('data-i18n-key');
    if (explicit) {
        if (stored !== explicit) {
            el.setAttribute('data-i18n-key', explicit);
        }
        return explicit;
    }
    if (stored) {
        return stored;
    }
    const text = (el.textContent || '').trim();
    if (text) {
        el.setAttribute('data-i18n-key', text);
    }
    return text;
}

function translateElementText(el, lang) {
    const key = resolveElementKey(el);
    if (key && (hasTranslation(key, lang) || lang === "vi")) {
        el.textContent = t(key, lang);
    }
}

function translatePlaceholder(el, lang) {
    const explicit = el.getAttribute('data-i18n-placeholder');
    const key = explicit || el.getAttribute('data-i18n-placeholder-key');
    if (key && hasTranslation(key, lang)) {
        if (!el.getAttribute('data-i18n-placeholder-key')) {
            el.setAttribute('data-i18n-placeholder-key', key);
        }
        el.setAttribute('placeholder', t(key, lang));
        return;
    }

    const placeholder = el.getAttribute('placeholder');
    if (!placeholder || el.hasAttribute('data-i18n-placeholder-skip')) {
        return;
    }

    if (hasTranslation(placeholder, lang)) {
        el.setAttribute('data-i18n-placeholder-key', placeholder);
        el.setAttribute('placeholder', t(placeholder, lang));
    }
}

function translateTitle(el, lang) {
    const explicit = el.getAttribute('data-i18n-title');
    const key = explicit || el.getAttribute('data-i18n-title-key');
    if (key && hasTranslation(key, lang)) {
        if (!el.getAttribute('data-i18n-title-key')) {
            el.setAttribute('data-i18n-title-key', key);
        }
        el.title = t(key, lang);
        return;
    }

    const title = el.getAttribute('title');
    if (!title || el.hasAttribute('data-i18n-title-skip')) {
        return;
    }

    if (hasTranslation(title, lang)) {
        el.setAttribute('data-i18n-title-key', title);
        el.title = t(title, lang);
    }
}

function translateAriaLabel(el, lang) {
    const explicit = el.getAttribute('data-i18n-aria-label');
    const key = explicit || el.getAttribute('data-i18n-aria-label-key');
    if (key && hasTranslation(key, lang)) {
        if (!el.getAttribute('data-i18n-aria-label-key')) {
            el.setAttribute('data-i18n-aria-label-key', key);
        }
        el.setAttribute('aria-label', t(key, lang));
        return;
    }

    const label = el.getAttribute('aria-label');
    if (!label || el.hasAttribute('data-i18n-aria-label-skip')) {
        return;
    }

    if (hasTranslation(label, lang)) {
        el.setAttribute('data-i18n-aria-label-key', label);
        el.setAttribute('aria-label', t(label, lang));
    }
}

export function applyI18n(lang = getLanguage(), root = document) {
    translateTextNodes(lang, root);
    root.querySelectorAll('[data-i18n]').forEach((el) => translateElementText(el, lang));

    root.querySelectorAll('.sidebar-label, .page-section-title, .i18n-auto, .nttu-footer-text').forEach((el) => {
        if (!el.hasAttribute('data-i18n')) {
            translateElementText(el, lang);
        }
    });

    root.querySelectorAll('[data-i18n-placeholder], [placeholder]').forEach((el) => translatePlaceholder(el, lang));
    root.querySelectorAll('[data-i18n-title], [title]').forEach((el) => translateTitle(el, lang));
    root.querySelectorAll('[data-i18n-aria-label], [aria-label]').forEach((el) => translateAriaLabel(el, lang));

    document.documentElement.lang = lang === 'en' ? 'en' : 'vi';

    const titleKey = document.documentElement.getAttribute('data-page-title-key');
    if (titleKey && hasTranslation(titleKey, lang)) {
        document.title = t(titleKey, lang);
    } else if (hasTranslation(document.title, lang)) {
        document.title = t(document.title, lang);
    }
}

function startMutationObserver() {
    if (observerStarted || typeof MutationObserver === 'undefined') {
        return;
    }

    observerStarted = true;
    let timer = null;

    const observer = new MutationObserver(() => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => applyI18n(getLanguage()), 120);
    });

    observer.observe(document.body, { childList: true, subtree: true, characterData: false });
}

export function setLanguage(lang) {
    try {
        localStorage.setItem('language', lang);
    } catch {
        // ignore
    }

    applyI18n(lang);
    window.dispatchEvent(new CustomEvent('nttu-language-changed', { detail: { language: lang } }));
}

export function registerLanguage(Alpine) {
    window.t = t;
    window.translateMessage = translateMessage;
    window.alertMessage = alertMessage;
    window.confirmMessage = confirmMessage;
    window.getLanguage = getLanguage;
    window.applyI18n = applyI18n;

    if (Alpine) {
        Alpine.magic('t', () => t);
    }

    document.addEventListener('DOMContentLoaded', () => {
        applyI18n();
        startMutationObserver();
    });

    window.addEventListener('nttu-language-changed', (event) => {
        const lang = event.detail?.language || getLanguage();
        applyI18n(lang);
    });
}
