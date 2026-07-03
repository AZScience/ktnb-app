import { destroyComposeEditor, mountComposeEditor } from './ckeditor-compose.js';

function csrfHeaders(csrf) {
    return {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
    };
}

function urlFromTemplate(template, id) {
    return (template || '').replace('__ID__', id);
}

function commentDestroyUrl(template, sectionId, commentId) {
    return (template || '')
        .replace('__SECTION__', sectionId)
        .replace('__COMMENT__', commentId);
}

export function registerDiscussionBoard(Alpine) {
    Alpine.data('discussionBoardPage', (config) => ({
        sections: config.sections || [],
        highlightId: config.highlightId || '',
        needsLink: Boolean(config.needsLink),
        participant: config.participant || { email: '', name: 'Khách', role: 'guest', roleLabel: 'Sinh viên / Khách' },
        moderatorKey: config.moderatorKey || '',
        guestName: '',
        editingId: null,
        editForm: { title: '', student_content: '' },
        commentUrlTemplate: config.commentUrlTemplate || '',
        destroyUrlTemplate: config.destroyUrlTemplate || '',
        updateUrlTemplate: config.updateUrlTemplate || '',
        commentDestroyUrlTemplate: config.commentDestroyUrlTemplate || '',

        init() {
            if (!this.guestName) {
                this.guestName = localStorage.getItem('discussion_guest_name') || '';
            }
            this.$watch('guestName', (value) => {
                const trimmed = String(value || '').trim();
                if (trimmed) {
                    localStorage.setItem('discussion_guest_name', trimmed);
                }
            });
            if (this.highlightId) {
                this.$nextTick(() => {
                    document.getElementById('section-' + this.highlightId)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            }
        },

        commentUrl(id) {
            return (this.commentUrlTemplate || '').replace('__ID__', id);
        },

        destroyUrl(id) {
            return (this.destroyUrlTemplate || '').replace('__ID__', id);
        },

        updateUrl(id) {
            return (this.updateUrlTemplate || '').replace('__ID__', id);
        },

        commentDestroyUrl(sectionId, commentId) {
            return commentDestroyUrl(this.commentDestroyUrlTemplate, sectionId, commentId);
        },

        initials(name) {
            const parts = String(name || '?').trim().split(/\s+/);
            if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
            return (parts[0]?.[0] || '?').toUpperCase();
        },

        isModerator(section) {
            return Boolean(section.is_moderator);
        },

        canDeleteComment(_comment, section) {
            return this.isModerator(section);
        },

        needsGuestName() {
            return this.participant.role === 'guest';
        },

        roleBadgeClass(role) {
            if (role === 'lecturer' || role === 'admin') return 'bg-rose-50 text-rose-600 border-rose-100';
            if (role === 'employee') return 'bg-amber-50 text-amber-700 border-amber-100';
            return 'bg-blue-50 text-blue-600 border-blue-100';
        },

        startEdit(section) {
            this.editingId = section.id;
            this.editForm = {
                title: section.title || '',
                student_content: section.student_content || '',
            };
        },

        cancelEdit() {
            this.editingId = null;
        },
    }));

    Alpine.data('discussionCollaborationBoard', (config) => ({
        sections: config.sections || [],
        participant: config.participant || {},
        routes: config.routes || {},
        csrf: config.csrf || '',
        searchQuery: '',
        searchDate: '',
        isAddOpen: false,
        saving: false,
        editingTitleId: null,
        editingContentId: null,
        commentEditorId: null,
        editTitle: '',
        contentDrafts: {},
        commentDrafts: {},
        newTitle: '',
        newContent: '',
        editors: {},

        filteredSections() {
            const q = this.searchQuery.trim().toLowerCase();
            return this.sections.filter((section) => {
                const matchesTitle = !q || String(section.title || '').toLowerCase().includes(q);
                const matchesDate = !this.searchDate || section.created_at_iso === this.searchDate;
                return matchesTitle && matchesDate;
            });
        },

        roleBadgeClass(role) {
            if (role === 'lecturer' || role === 'admin') return 'bg-rose-50 text-rose-600 border-rose-100';
            if (role === 'employee') return 'bg-amber-50 text-amber-700 border-amber-100';
            return 'bg-blue-50 text-blue-600 border-blue-100';
        },

        initials(name) {
            const parts = String(name || '?').trim().split(/\s+/);
            if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
            return (parts[0]?.[0] || '?').toUpperCase();
        },

        formatCommentTime(value) {
            if (!value) return '';
            try {
                const d = new Date(value);
                return d.toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit' });
            } catch {
                return '';
            }
        },

        replaceSection(updated) {
            if (!updated?.id) return;
            const idx = this.sections.findIndex((s) => s.id === updated.id);
            if (idx >= 0) {
                this.sections[idx] = updated;
            }
        },

        async apiJson(url, method, body) {
            const response = await fetch(url, {
                method,
                headers: csrfHeaders(this.csrf),
                body: body ? JSON.stringify(body) : undefined,
                credentials: 'same-origin',
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || 'Yêu cầu thất bại');
            }
            return data;
        },

        openAddForm(scroll) {
            this.isAddOpen = true;
            if (scroll) {
                this.$nextTick(() => window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' }));
            }
        },

        closeAddForm() {
            this.isAddOpen = false;
            this.newTitle = '';
            this.newContent = '';
            this.destroyEditor('new');
        },

        async mountNewSectionEditor() {
            await this.$nextTick();
            const el = document.getElementById('editor-new-section');
            if (!el || this.editors.new) return;
            this.editors.new = await mountComposeEditor(el, {
                placeholder: 'Nhập nội dung thảo luận ban đầu...',
                initialData: this.newContent,
                onChange: (val) => { this.newContent = val; },
            });
        },

        async mountContentEditor(section) {
            await this.$nextTick();
            const key = `content-${section.id}`;
            const el = document.getElementById(`editor-content-${section.id}`);
            if (!el || this.editors[key]) return;
            this.contentDrafts[section.id] = section.student_content || '';
            this.editingContentId = section.id;
            this.editors[key] = await mountComposeEditor(el, {
                placeholder: 'Mời bạn nhập nội dung thảo luận tại đây...',
                initialData: section.student_content || '',
                onChange: (val) => { this.contentDrafts[section.id] = val; },
            });
        },

        async mountCommentEditor(section) {
            await this.$nextTick();
            const key = `comment-${section.id}`;
            const el = document.getElementById(`editor-comment-${section.id}`);
            if (!el || this.editors[key]) return;
            this.commentDrafts[section.id] = '';
            this.editors[key] = await mountComposeEditor(el, {
                placeholder: 'Viết nhận xét của bạn...',
                initialData: '',
                onChange: (val) => { this.commentDrafts[section.id] = val; },
            });
        },

        async destroyEditor(key) {
            if (this.editors[key]) {
                await destroyComposeEditor(this.editors[key]);
                delete this.editors[key];
            }
        },

        startTitleEdit(section) {
            this.editingTitleId = section.id;
            this.editTitle = section.title || '';
        },

        async saveTitle(section) {
            if (!this.editTitle.trim()) return;
            try {
                const data = await this.apiJson(urlFromTemplate(this.routes.updateTemplate, section.id), 'PUT', {
                    title: this.editTitle.trim(),
                    student_content: section.student_content || '',
                });
                if (data.section) this.replaceSection(data.section);
                this.editingTitleId = null;
            } catch (e) {
                alert(e.message);
            }
        },

        async saveContent(section) {
            const content = this.contentDrafts[section.id] ?? section.student_content ?? '';
            try {
                const data = await this.apiJson(urlFromTemplate(this.routes.updateTemplate, section.id), 'PUT', {
                    title: section.title,
                    student_content: content,
                });
                if (data.section) this.replaceSection(data.section);
            } catch (e) {
                alert(e.message);
            }
        },

        openCommentEditor(section) {
            if (!section.can_comment) return;
            this.commentEditorId = section.id;
        },

        closeCommentEditor() {
            const id = this.commentEditorId;
            this.commentEditorId = null;
            if (id) this.destroyEditor(`comment-${id}`);
        },

        async submitComment(section) {
            const content = this.commentDrafts[section.id] || '';
            if (!content.trim()) return;
            try {
                const data = await this.apiJson(urlFromTemplate(this.routes.commentTemplate, section.id), 'POST', {
                    content,
                });
                if (data.section) this.replaceSection(data.section);
                this.closeCommentEditor();
            } catch (e) {
                alert(e.message);
            }
        },

        canDeleteComment(comment, section) {
            if (section.can_delete) return true;
            const email = String(this.participant.email || '').toLowerCase();
            const authorEmail = String(comment.authorEmail || '').toLowerCase();
            return email !== '' && email === authorEmail;
        },

        async deleteComment(section, comment) {
            if (!confirm('Xóa nhận xét này?')) return;
            try {
                const data = await this.apiJson(
                    commentDestroyUrl(this.routes.commentDestroyTemplate, section.id, comment.id),
                    'DELETE',
                );
                if (data.section) this.replaceSection(data.section);
            } catch (e) {
                alert(e.message);
            }
        },

        async deleteSection(section) {
            if (!confirm('Bạn có chắc chắn muốn xóa bảng thảo luận này không?')) return;
            try {
                await this.apiJson(urlFromTemplate(this.routes.destroyTemplate, section.id), 'DELETE');
                this.sections = this.sections.filter((s) => s.id !== section.id);
            } catch (e) {
                alert(e.message);
            }
        },

        async createSection() {
            if (!this.newTitle.trim()) return;
            this.saving = true;
            try {
                const data = await this.apiJson(this.routes.store, 'POST', {
                    title: this.newTitle.trim(),
                    student_content: this.newContent || '',
                });
                if (data.section) {
                    this.sections.unshift(data.section);
                }
                this.closeAddForm();
            } catch (e) {
                alert(e.message);
            } finally {
                this.saving = false;
            }
        },
    }));
}
