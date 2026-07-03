const CHAT_STORAGE_KEY = 'nttu.ai-assistant.chat.v1';
const MAX_STORED_MESSAGES = 200;

function sanitizeMessages(messages) {
    if (!Array.isArray(messages)) {
        return [];
    }

    return messages
        .filter((message) => message
            && (message.role === 'user' || message.role === 'assistant')
            && typeof message.content === 'string'
            && message.content.trim() !== '')
        .slice(-MAX_STORED_MESSAGES);
}

function loadChatState() {
    const fallback = {
        searchMode: 'faq',
        chats: { faq: [], general: [] },
    };

    try {
        const raw = localStorage.getItem(CHAT_STORAGE_KEY);
        if (!raw) {
            return fallback;
        }

        const parsed = JSON.parse(raw);

        return {
            searchMode: parsed.searchMode === 'general' ? 'general' : 'faq',
            chats: {
                faq: sanitizeMessages(parsed.chats?.faq),
                general: sanitizeMessages(parsed.chats?.general),
            },
        };
    } catch {
        return fallback;
    }
}

function persistChatState(searchMode, chats) {
    try {
        localStorage.setItem(CHAT_STORAGE_KEY, JSON.stringify({
            searchMode,
            chats: {
                faq: sanitizeMessages(chats.faq),
                general: sanitizeMessages(chats.general),
            },
        }));
    } catch {
        // Ignore quota or private-mode errors.
    }
}

export function registerAiAssistant(Alpine) {
    const saved = loadChatState();

    Alpine.data('aiAssistantPage', (config = {}) => ({
        routes: config.routes || {},
        input: '',
        loading: false,
        searchMode: saved.searchMode,
        chats: saved.chats,
        messages: [...(saved.chats[saved.searchMode] || [])],

        init() {
            this.$watch('messages', () => {
                this.chats[this.searchMode] = [...this.messages];
                persistChatState(this.searchMode, this.chats);

                this.$nextTick(() => {
                    const el = this.$refs.scrollAnchor;
                    el?.scrollIntoView({ behavior: 'smooth' });
                });
            }, { deep: true });

            this.$watch('searchMode', () => {
                persistChatState(this.searchMode, this.chats);
            });
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        setMode(mode) {
            if (mode === this.searchMode) {
                return;
            }

            this.chats[this.searchMode] = [...this.messages];
            this.searchMode = mode;
            this.messages = [...(this.chats[mode] || [])];
            persistChatState(this.searchMode, this.chats);
        },

        clearChat() {
            this.messages = [];
            this.chats[this.searchMode] = [];
            persistChatState(this.searchMode, this.chats);
        },

        async sendMessage() {
            const question = this.input.trim();
            if (!question || this.loading) {
                return;
            }

            this.input = '';
            this.messages.push({ role: 'user', content: question });
            this.loading = true;

            try {
                const history = this.messages.slice(0, -1).map((m) => ({
                    role: m.role,
                    content: m.content,
                }));

                const res = await fetch(this.routes.ask, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({
                        question,
                        search_mode: this.searchMode,
                        history,
                    }),
                });

                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Lỗi kết nối AI');
                }

                this.messages.push({ role: 'assistant', content: data.answer || 'Không có phản hồi.' });
            } catch (err) {
                this.messages.push({
                    role: 'assistant',
                    content: err.message || 'Đã có lỗi xảy ra khi kết nối với trợ lý AI. Vui lòng thử lại sau.',
                });
            } finally {
                this.loading = false;
            }
        },
    }));
}
