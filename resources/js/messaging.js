import { destroyComposeEditor, mountComposeEditor } from './ckeditor-compose.js';
import { alertMessage, getLanguage } from './language.js';

function formatTimeAgo(iso) {
    if (!iso) return '';
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return '';

    const now = new Date();
    const diffMs = now - date;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);

    if (diffSec < 60) return 'Vừa xong';
    if (diffMin < 60) return `${diffMin} phút trước`;
    if (diffHour < 24) return `${diffHour} giờ trước`;
    if (diffDay < 7) return `${diffDay} ngày trước`;

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

function isImageFile(file) {
    return file?.type?.startsWith('image/');
}

function formatFileSize(bytes) {
    if (!bytes) return '';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

const ATTACHMENT_ACCEPT = [
    'image/*',
    'video/*',
    'audio/*',
    '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.rtf,.csv,.zip,.rar,.7z',
].join(',');

export function registerMessaging(Alpine) {
    Alpine.data('messagingPage', (config) => ({
        folder: config.folder || 'inbox',
        messages: config.messages || [],
        selected: config.selected || null,
        recipients: config.recipients || [],
        currentUserId: Number(config.currentUserId || 0),
        unreadCount: config.unreadCount || 0,
        routes: config.routes || {},
        canAdd: config.canAdd !== false,
        canEdit: config.canEdit !== false,
        canDelete: config.canDelete !== false,
        searchQuery: '',
        composeOpen: false,
        recipientSearch: '',
        selectedRecipientIds: [],
        recipientMenuOpen: false,
        subject: '',
        body: '',
        attachmentFiles: [],
        attachmentAccept: ATTACHMENT_ACCEPT,
        composeEditor: null,
        composeEditorLoading: false,
        composeSnapshot: null,

        get filteredMessages() {
            const q = this.searchQuery.trim().toLowerCase();
            if (!q) return this.messages;

            return this.messages.filter((m) => {
                return (
                    (m.subject || '').toLowerCase().includes(q) ||
                    (m.body_preview || '').toLowerCase().includes(q) ||
                    (m.sender_name || '').toLowerCase().includes(q)
                );
            });
        },

        get selectableRecipients() {
            return this.recipients.filter((r) => Number(r.user_id) !== this.currentUserId);
        },

        get filteredRecipients() {
            const q = this.recipientSearch.trim().toLowerCase();
            const list = this.selectableRecipients;
            if (!q) return list;

            return list.filter((r) => {
                return (
                    (r.name || '').toLowerCase().includes(q) ||
                    (r.position || '').toLowerCase().includes(q) ||
                    (r.email || '').toLowerCase().includes(q) ||
                    (r.employee_id || '').toLowerCase().includes(q)
                );
            });
        },

        get recipientLabel() {
            const total = this.selectableRecipients.length;
            if (this.selectedRecipientIds.length === 0) return 'Chọn người nhận...';
            if (total > 0 && this.selectedRecipientIds.length === total) return 'Tất cả nhân viên';

            return `Đã chọn ${this.selectedRecipientIds.length} người nhận`;
        },

        formatTimeAgo,
        initials,
        formatFileSize,
        isImageFile,

        attachmentPreview(file) {
            if (!file) return '';
            if (!file._previewUrl && isImageFile(file)) {
                file._previewUrl = URL.createObjectURL(file);
            }
            return file._previewUrl || '';
        },

        folderUrl(folder) {
            const base = this.routes.index || '/messaging';
            const url = new URL(base, window.location.origin);
            url.searchParams.set('folder', folder);

            return url.pathname + url.search;
        },

        messageUrl(id) {
            const tpl = this.routes.readTemplate || '';
            const path = tpl.replace('__ID__', encodeURIComponent(id));
            const url = new URL(path, window.location.origin);
            url.searchParams.set('folder', this.folder);

            return url.pathname + url.search;
        },

        toggleRecipient(userId) {
            const id = Number(userId);
            if (this.selectedRecipientIds.includes(id)) {
                this.selectedRecipientIds = this.selectedRecipientIds.filter((x) => x !== id);
            } else {
                this.selectedRecipientIds = [...this.selectedRecipientIds, id];
            }
        },

        toggleAllRecipients() {
            const ids = this.selectableRecipients.map((r) => r.user_id);
            if (this.selectedRecipientIds.length === ids.length) {
                this.selectedRecipientIds = [];
            } else {
                this.selectedRecipientIds = [...ids];
            }
        },

        isRecipientSelected(userId) {
            return this.selectedRecipientIds.includes(Number(userId));
        },

        recipientName(userId) {
            return this.recipients.find((r) => r.user_id === Number(userId))?.name || '';
        },

        init() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('compose') === '1') {
                this.openCompose();
            }
        },

        async openCompose() {
            if (!this.canAdd) return;
            this.composeOpen = true;
            await this.$nextTick();
            await this.initComposeEditor();
            this.snapshotCompose();
        },

        async replyTo(message) {
            if (!message) return;
            const subject = String(message.subject || '');
            const reSubject = subject.toLowerCase().startsWith('re:') ? subject : `Re: ${subject}`;
            this.selectedRecipientIds = message.sender_user_id ? [Number(message.sender_user_id)] : [];
            this.subject = reSubject;
            this.composeOpen = true;
            await this.$nextTick();
            await this.initComposeEditor();
            if (this.composeEditor) {
                const quote = message.body ? `<br><br><blockquote>${message.body}</blockquote>` : '';
                this.composeEditor.setData(`<p></p>${quote}`);
            }
            this.snapshotCompose();
        },

        async closeCompose() {
            await this.destroyComposeEditor();
            this.composeOpen = false;
            this.recipientMenuOpen = false;
        },

        async initComposeEditor() {
            if (this.composeEditor || this.composeEditorLoading) return;

            const container = this.$refs.composeEditor;
            if (!container) return;

            this.composeEditorLoading = true;
            try {
                this.composeEditor = await mountComposeEditor(container, {
                    placeholder: 'Viết nội dung tin nhắn tại đây...',
                    initialData: this.body,
                    onChange: (html) => {
                        this.body = html;
                    },
                });
            } catch (e) {
                console.error('CKEditor init failed', e);
            } finally {
                this.composeEditorLoading = false;
            }
        },

        async destroyComposeEditor() {
            await destroyComposeEditor(this.composeEditor);
            this.composeEditor = null;
        },

        snapshotCompose() {
            this.composeSnapshot = {
                selectedRecipientIds: [...this.selectedRecipientIds],
                subject: this.subject,
                body: this.body,
                attachmentFiles: [...this.attachmentFiles],
            };
        },

        async resetCompose() {
            if (!this.composeSnapshot) {
                this.snapshotCompose();
            }

            const snap = this.composeSnapshot;
            if (!snap) return;

            this.selectedRecipientIds = [...snap.selectedRecipientIds];
            this.subject = snap.subject;
            this.body = snap.body;
            this.attachmentFiles = [...snap.attachmentFiles];
            this.recipientSearch = '';
            this.recipientMenuOpen = false;
            this.syncAttachmentInput();

            if (this.composeEditor) {
                this.composeEditor.setData(snap.body || '');
            }
        },

        addAttachments(event) {
            const picked = Array.from(event.target.files || []);
            if (!picked.length) return;

            this.attachmentFiles = [...this.attachmentFiles, ...picked];
            this.syncAttachmentInput();
            event.target.value = '';
        },

        removeAttachment(index) {
            this.attachmentFiles.splice(index, 1);
            this.syncAttachmentInput();
        },

        syncAttachmentInput() {
            const input = this.$refs.composeAttachments;
            if (!input || typeof DataTransfer === 'undefined') return;

            const dt = new DataTransfer();
            this.attachmentFiles.forEach((file) => dt.items.add(file));
            input.files = dt.files;
        },

        async handleComposeSubmit(event) {
            if (this.composeEditor) {
                this.body = this.composeEditor.getData();
            }

            const plain = (this.body || '').replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
            if (this.selectedRecipientIds.length === 0) {
                event.preventDefault();
                alertMessage('Vui lòng chọn ít nhất một người nhận.', getLanguage());
                return;
            }

            if (!plain) {
                event.preventDefault();
                alertMessage('Vui lòng nhập nội dung tin nhắn.', getLanguage());
                return;
            }

            this.syncAttachmentInput();
        },
    }));
}
