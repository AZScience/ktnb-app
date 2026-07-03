import { applyI18n, getLanguage, setLanguage, t } from './language.js';

function formatTimeAgo(iso) {
    if (!iso) return '';
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return '';

    const now = new Date();
    const diffSec = Math.floor((now - date) / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);

    if (diffSec < 60) return t('Vừa xong');
    if (diffMin < 60) return `${diffMin} ${getLanguage() === 'en' ? 'min ago' : 'phút trước'}`;
    if (diffHour < 24) return `${diffHour} ${getLanguage() === 'en' ? 'hr ago' : 'giờ trước'}`;
    if (diffDay < 7) return `${diffDay} ${getLanguage() === 'en' ? 'days ago' : 'ngày trước'}`;

    const d = String(date.getDate()).padStart(2, '0');
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const y = date.getFullYear();
    const h = String(date.getHours()).padStart(2, '0');
    const min = String(date.getMinutes()).padStart(2, '0');

    return `${d}/${m}/${y} ${h}:${min}`;
}

function initials(name) {
    return (name || '?').trim().charAt(0).toUpperCase();
}

export function registerHeader(Alpine) {
    Alpine.data('nttuHeader', (config = {}) => ({
        notifOpen: false,
        userMenuOpen: false,
        langMenuOpen: false,
        unreadCount: config.unreadCount || 0,
        notifications: config.notifications || [],
        language: 'vi',
        routes: config.routes || {},
        pollTimer: null,

        init() {
            this.language = getLanguage();
            applyI18n(this.language);

            this.$nextTick(() => {
                document.addEventListener('click', (event) => {
                    if (!this.$el.contains(event.target)) {
                        this.closeMenus();
                    }
                });
            });

            if (this.routes.notifications) {
                const loadNotifications = () => this.refreshNotifications();
                if ('requestIdleCallback' in window) {
                    window.requestIdleCallback(loadNotifications, { timeout: 2500 });
                } else {
                    window.setTimeout(loadNotifications, 1200);
                }
            }
        },

        destroy() {
            if (this.pollTimer) {
                window.clearInterval(this.pollTimer);
            }
        },

        formatTimeAgo,
        initials,
        t,

        closeMenus() {
            this.notifOpen = false;
            this.userMenuOpen = false;
            this.langMenuOpen = false;
        },

        toggleNotif() {
            const opening = !this.notifOpen;
            this.closeMenus();
            this.notifOpen = opening;
            if (opening) {
                this.refreshNotifications();
                if (!this.pollTimer) {
                    this.pollTimer = window.setInterval(() => {
                        if (this.notifOpen) {
                            this.refreshNotifications();
                        }
                    }, 60000);
                }
            } else if (this.pollTimer) {
                window.clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },

        toggleUserMenu() {
            const opening = !this.userMenuOpen;
            this.closeMenus();
            this.userMenuOpen = opening;
        },

        toggleLangMenu() {
            const opening = !this.langMenuOpen;
            this.closeMenus();
            this.langMenuOpen = opening;
        },

        openMessage(readUrl) {
            if (readUrl) {
                window.location.href = readUrl;
            }
        },

        async refreshNotifications() {
            const url = this.routes.notifications;
            if (!url) return;

            try {
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;

                const data = await res.json();
                this.unreadCount = data.unread_count || 0;
                this.notifications = data.items || [];
            } catch {
                // ignore
            }
        },

        async markAllRead() {
            const url = this.routes.markAllRead;
            if (!url) return;

            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token || '',
                    },
                });
                if (!res.ok) return;

                this.unreadCount = 0;
                this.notifications = [];
            } catch {
                // ignore
            }
        },

        chooseLanguage(lang) {
            this.language = lang;
            setLanguage(lang);
            this.langMenuOpen = false;
        },
    }));
}
