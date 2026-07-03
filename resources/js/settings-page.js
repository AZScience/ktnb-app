import { applyI18n, confirmMessage, getLanguage, setLanguage, t } from './language.js';

const STORAGE_KEYS = {
    theme: 'theme',
    browserNotifications: 'nttu_browser_notifications',
    notificationSound: 'nttu_notification_sound',
};

export function registerSettingsPage(Alpine) {
    Alpine.data('settingsPage', (securityConfig = {}) => ({
        language: 'vi',
        theme: 'light',
        langMenuOpen: false,
        browserNotifications: true,
        notificationSound: true,
        security: {
            account: securityConfig.account || {},
            sessions: securityConfig.sessions || [],
            recent_activity: securityConfig.recent_activity || [],
            routes: securityConfig.routes || {},
        },
        passwordForm: {
            current_password: '',
            password: '',
            password_confirmation: '',
        },
        passwordErrors: {},
        passwordSuccess: '',
        changingPassword: false,
        revokingSessions: false,

        init() {
            this.language = getLanguage();
            try {
                this.theme = localStorage.getItem(STORAGE_KEYS.theme) || 'light';
                const browser = localStorage.getItem(STORAGE_KEYS.browserNotifications);
                const sound = localStorage.getItem(STORAGE_KEYS.notificationSound);
                if (browser !== null) this.browserNotifications = browser === 'true';
                if (sound !== null) this.notificationSound = sound === 'true';
            } catch {
                // ignore
            }
            this.applyTheme();
            applyI18n(this.language);

            document.addEventListener('click', (event) => {
                if (!this.$refs.langPicker?.contains(event.target)) {
                    this.langMenuOpen = false;
                }
            });
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        languageLabel() {
            return this.language === 'en' ? 'English' : 'Tiếng Việt';
        },

        verifiedLabel() {
            return t('Đã xác minh', this.language);
        },

        unverifiedLabel() {
            return t('Chưa xác minh', this.language);
        },

        changingPasswordLabel() {
            return t('Đang xử lý...', this.language);
        },

        confirmChangeLabel() {
            return t('Xác nhận thay đổi', this.language);
        },

        chooseLanguage(lang) {
            this.language = lang;
            setLanguage(lang);
            this.langMenuOpen = false;
        },

        setTheme(theme) {
            this.theme = theme;
            try {
                localStorage.setItem(STORAGE_KEYS.theme, theme);
            } catch {
                // ignore
            }
            this.applyTheme();
        },

        applyTheme() {
            const root = document.documentElement;
            root.classList.remove('light', 'dark');

            if (this.theme === 'dark') {
                root.classList.add('dark');
            } else if (this.theme === 'light') {
                root.classList.add('light');
            } else {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                root.classList.add(prefersDark ? 'dark' : 'light');
            }
        },

        toggleBrowserNotifications() {
            this.browserNotifications = !this.browserNotifications;
            try {
                localStorage.setItem(STORAGE_KEYS.browserNotifications, String(this.browserNotifications));
            } catch {
                // ignore
            }

            if (this.browserNotifications && 'Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission().catch(() => {});
            }
        },

        toggleNotificationSound() {
            this.notificationSound = !this.notificationSound;
            try {
                localStorage.setItem(STORAGE_KEYS.notificationSound, String(this.notificationSound));
            } catch {
                // ignore
            }
        },

        formatDateTime(value, dateOnly = false) {
            if (!value) return '';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return '';

            const locale = this.language === 'en' ? 'en-US' : 'vi-VN';
            if (dateOnly) {
                return date.toLocaleDateString(locale, { day: '2-digit', month: '2-digit', year: 'numeric' });
            }

            return date.toLocaleString(locale, {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        otherSessionsCount() {
            return this.security.sessions.filter((session) => !session.is_current).length;
        },

        applySecurityPayload(payload) {
            if (!payload) return;
            this.security.account = payload.account || this.security.account;
            this.security.sessions = payload.sessions || [];
            this.security.recent_activity = payload.recent_activity || [];
        },

        async refreshSecurity() {
            const url = this.security.routes.refresh;
            if (!url) return;

            const res = await fetch(url, {
                headers: { Accept: 'application/json' },
            });

            if (res.ok) {
                const data = await res.json();
                this.applySecurityPayload(data);
            }
        },

        async changePassword() {
            if (this.changingPassword) return;

            this.passwordErrors = {};
            this.passwordSuccess = '';

            const url = this.security.routes.password;
            if (!url) return;

            this.changingPassword = true;

            try {
                const res = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify(this.passwordForm),
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    const errors = data.errors || {};
                    this.passwordErrors = {
                        current_password: errors.current_password?.[0] || '',
                        password: errors.password?.[0] || errors.password_confirmation?.[0] || '',
                    };
                    if (!this.passwordErrors.current_password && !this.passwordErrors.password && data.message) {
                        this.passwordErrors.password = data.message;
                    }
                    return;
                }

                this.passwordForm = {
                    current_password: '',
                    password: '',
                    password_confirmation: '',
                };
                this.passwordSuccess = data.message || t('Đã đổi mật khẩu thành công.', this.language);
                await this.refreshSecurity();
            } catch {
                this.passwordErrors.password = t('Lỗi khi đổi mật khẩu.', this.language);
            } finally {
                this.changingPassword = false;
            }
        },

        async destroyOtherSessions() {
            if (this.revokingSessions || this.otherSessionsCount() === 0) return;

            const confirmed = confirmMessage('Đăng xuất tất cả thiết bị khác đang đăng nhập?', this.language);
            if (!confirmed) return;

            const url = this.security.routes.destroy_other_sessions;
            if (!url) return;

            this.revokingSessions = true;

            try {
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                });

                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    this.applySecurityPayload(data.security);
                }
            } finally {
                this.revokingSessions = false;
            }
        },

        async destroySession(sessionId) {
            if (this.revokingSessions) return;

            const confirmed = confirmMessage('Thu hồi phiên đăng nhập trên thiết bị này?', this.language);
            if (!confirmed) return;

            const base = this.security.routes.destroy_session;
            if (!base) return;

            this.revokingSessions = true;

            try {
                const res = await fetch(`${base}/${encodeURIComponent(sessionId)}`, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                });

                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    this.applySecurityPayload(data.security);
                }
            } finally {
                this.revokingSessions = false;
            }
        },
    }));
}
