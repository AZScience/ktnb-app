import { alertMessage, getLanguage } from './language.js';

export function registerProfilePage(Alpine) {
    Alpine.data('profilePage', (config = {}) => ({
        tab: 'personal',
        isProfilePage: true,
        dialogMode: 'edit',
        avatarDialogOpen: false,
        saving: false,
        snapshot: null,
        form: {
            nickname: config.employee?.nickname || '',
            phone: config.employee?.phone || '',
            address: config.employee?.address || '',
            birth_date: config.employee?.birth_date || '',
            avatar_url: config.employee?.avatar_url || '',
        },
        routes: config.routes || {},

        init() {
            this.snapshotForm();
        },

        snapshotForm() {
            this.snapshot = JSON.parse(JSON.stringify(this.form));
        },

        revertForm() {
            if (!this.snapshot) return;
            this.form = JSON.parse(JSON.stringify(this.snapshot));
        },

        initials(name) {
            return (name || '?').trim().charAt(0).toUpperCase();
        },

        openAvatarDialog() {
            this.avatarDialogOpen = true;
        },

        closeAvatarDialog() {
            this.avatarDialogOpen = false;
            window.dispatchEvent(new CustomEvent('modal-closed'));
        },

        async saveProfile(fromAvatar = false) {
            if (this.saving) return;

            this.saving = true;
            const token = document.querySelector('meta[name="csrf-token"]')?.content;

            try {
                const res = await fetch(this.routes.update, {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token || '',
                    },
                    body: JSON.stringify(this.form),
                });

                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    const msg = data.message || 'Không thể cập nhật hồ sơ.';
                    alertMessage(msg, getLanguage());
                    return;
                }

                this.snapshotForm();
                if (fromAvatar) {
                    this.closeAvatarDialog();
                }
                window.location.reload();
            } catch {
                alertMessage('Lỗi khi cập nhật hồ sơ.', getLanguage());
            } finally {
                this.saving = false;
            }
        },
    }));
}
