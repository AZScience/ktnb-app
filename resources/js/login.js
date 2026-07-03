import { t, getLanguage } from './language.js';

export function registerLoginPage(Alpine) {
    Alpine.data('loginPage', () => ({
        showPassword: false,
        resetDialogOpen: false,
        rememberMeChecked: false,
        isSubmitting: false,
        currentLang: getLanguage(),

        get submitLabel() {
            void this.currentLang;
            return this.isSubmitting ? t('Đang xử lý...', this.currentLang) : t('Đăng nhập', this.currentLang);
        },

        init() {
            const form = this.$el.querySelector('form[action]');
            this.defaultEmail = form?.dataset.defaultEmail || 'ntt-00000@ntt.edu.vn';
            this.defaultPassword = form?.dataset.defaultPassword || 'NTT-00000';

            const remembered = localStorage.getItem('rememberedEmail');
            if (remembered && this.$refs.email) {
                this.$refs.email.value = remembered;
                this.rememberMeChecked = true;
            }

            window.addEventListener('nttu-language-changed', (event) => {
                this.currentLang = event.detail?.language || getLanguage();
            });
        },

        onSubmit() {
            const emailEl = this.$refs.email;
            const passwordEl = this.$refs.password;

            if (emailEl && !emailEl.value.trim()) {
                emailEl.value = this.defaultEmail;
            }
            if (passwordEl && !passwordEl.value.trim()) {
                passwordEl.value = this.defaultPassword;
            }

            const email = emailEl?.value?.trim() || '';
            if (this.rememberMeChecked && email) {
                localStorage.setItem('rememberedEmail', email);
            } else {
                localStorage.removeItem('rememberedEmail');
            }
            this.isSubmitting = true;
        },
    }));
}
