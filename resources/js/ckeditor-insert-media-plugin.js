import { ButtonView, IconMedia, Plugin } from 'ckeditor5';

import {
    insertUploadedContent,
    pickLocalFile,
    uploadToServer,
} from './ckeditor-upload-utils.js';

function removeModal(overlay) {
    overlay?.remove();
}

function showInsertMediaModal({ editor, uploadUrl }) {
    const overlay = document.createElement('div');
    overlay.className = 'ck-insert-media-overlay';
    overlay.style.cssText = [
        'position:fixed',
        'inset:0',
        'z-index:100050',
        'display:flex',
        'align-items:center',
        'justify-content:center',
        'padding:16px',
        'background:rgba(15,23,42,.45)',
    ].join(';');

    overlay.innerHTML = `
        <div role="dialog" aria-modal="true" class="ck-insert-media-panel" style="width:min(100%,480px);background:#fff;border-radius:12px;box-shadow:0 20px 50px rgba(15,23,42,.25);overflow:hidden;">
            <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e2e8f0;padding:14px 16px;">
                <h3 style="margin:0;font-size:16px;font-weight:700;color:#0f172a;">Chèn media</h3>
                <button type="button" data-close style="border:none;background:transparent;font-size:22px;line-height:1;color:#64748b;cursor:pointer;">&times;</button>
            </div>
            <div style="padding:16px;">
                <div style="display:flex;gap:8px;margin-bottom:16px;border-bottom:1px solid #e2e8f0;padding-bottom:8px;">
                    <button type="button" data-tab="url" style="flex:1;padding:8px 12px;border:1px solid #cbd5e1;border-radius:8px;background:#1877F2;color:#fff;cursor:pointer;font-size:13px;">Từ URL</button>
                    <button type="button" data-tab="upload" style="flex:1;padding:8px 12px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#0f172a;cursor:pointer;font-size:13px;">Tải file lên</button>
                </div>
                <div data-panel="url">
                    <p style="margin:0 0 8px;font-size:13px;color:#334155;">Dán liên kết YouTube, Vimeo hoặc nguồn media khác.</p>
                    <input type="url" data-url-input placeholder="https://www.youtube.com/watch?v=..." style="width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;margin-bottom:12px;">
                    <button type="button" data-url-submit style="width:100%;padding:10px 12px;border:none;border-radius:8px;background:#1877F2;color:#fff;cursor:pointer;font-size:13px;font-weight:600;">Chèn từ URL</button>
                    <p data-url-error style="display:none;margin:10px 0 0;font-size:12px;color:#dc2626;"></p>
                </div>
                <div data-panel="upload" hidden>
                    <p style="margin:0 0 8px;font-size:13px;color:#334155;">Chọn ảnh, video, audio hoặc tài liệu từ máy tính.</p>
                    <button type="button" data-pick-file style="width:100%;padding:24px 16px;border:2px dashed #cbd5e1;border-radius:10px;background:#f8fafc;cursor:pointer;font-size:13px;color:#0f172a;">
                        <div style="font-weight:600;margin-bottom:4px;">Bấm để chọn file</div>
                        <div style="font-size:12px;color:#64748b;">JPG, PNG, MP4, MP3, PDF, DOC, XLS, PPT, ZIP… (tối đa 50MB)</div>
                    </button>
                    <p data-file-name style="display:none;margin:10px 0 0;font-size:12px;color:#334155;"></p>
                    <p data-upload-status style="display:none;margin:10px 0 0;font-size:12px;color:#334155;"></p>
                    <p data-upload-error style="display:none;margin:10px 0 0;font-size:12px;color:#dc2626;"></p>
                </div>
            </div>
        </div>
    `;

    const panel = overlay.querySelector('.ck-insert-media-panel');
    const urlInput = overlay.querySelector('[data-url-input]');
    const urlError = overlay.querySelector('[data-url-error]');
    const fileName = overlay.querySelector('[data-file-name]');
    const uploadStatus = overlay.querySelector('[data-upload-status]');
    const uploadError = overlay.querySelector('[data-upload-error]');

    const close = () => removeModal(overlay);

    const setActiveTab = (tab) => {
        overlay.querySelectorAll('[data-tab]').forEach((button) => {
            const isActive = button.dataset.tab === tab;
            button.style.background = isActive ? '#1877F2' : '#fff';
            button.style.color = isActive ? '#fff' : '#0f172a';
        });
        overlay.querySelector('[data-panel="url"]').hidden = tab !== 'url';
        overlay.querySelector('[data-panel="upload"]').hidden = tab !== 'upload';
    };

    const insertFromUpload = async (file) => {
        uploadError.style.display = 'none';
        uploadStatus.style.display = 'block';
        uploadStatus.textContent = 'Đang tải lên...';

        try {
            const result = await uploadToServer(file, uploadUrl);
            insertUploadedContent(editor, {
                url: result.url,
                fileName: result.fileName || file.name,
                mimeType: result.mimeType || file.type,
            });
            close();
        } catch (error) {
            uploadStatus.style.display = 'none';
            uploadError.style.display = 'block';
            uploadError.textContent = error.message || 'Không thể tải tệp lên.';
        }
    };

    overlay.querySelector('[data-close]').addEventListener('click', close);
    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            close();
        }
    });
    panel.addEventListener('click', (event) => {
        event.stopPropagation();
    });

    overlay.querySelectorAll('[data-tab]').forEach((button) => {
        button.addEventListener('click', () => {
            setActiveTab(button.dataset.tab);
        });
    });

    overlay.querySelector('[data-url-submit]').addEventListener('click', () => {
        const url = String(urlInput.value || '').trim();
        urlError.style.display = 'none';

        if (!url) {
            urlError.style.display = 'block';
            urlError.textContent = 'Vui lòng nhập liên kết media.';
            return;
        }

        if (!editor.commands.get('mediaEmbed')) {
            urlError.style.display = 'block';
            urlError.textContent = 'Chức năng nhúng media chưa sẵn sàng.';
            return;
        }

        editor.execute('mediaEmbed', url);
        close();
    });

    urlInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            overlay.querySelector('[data-url-submit]').click();
        }
    });

    overlay.querySelector('[data-pick-file]').addEventListener('click', async () => {
        uploadError.style.display = 'none';
        uploadStatus.style.display = 'none';

        const file = await pickLocalFile();
        if (!file) {
            return;
        }

        fileName.style.display = 'block';
        fileName.textContent = `Đã chọn: ${file.name}`;
        await insertFromUpload(file);
    });

    document.body.appendChild(overlay);
    setActiveTab('url');
    urlInput.focus();
}

export function createInsertMediaPlugin(uploadUrl) {
    return class InsertMedia extends Plugin {
        static get pluginName() {
            return 'InsertMedia';
        }

        init() {
            this.editor.ui.componentFactory.add('insertMedia', (locale) => {
                const view = new ButtonView(locale);

                view.set({
                    label: 'Chèn media',
                    icon: IconMedia,
                    tooltip: true,
                });

                view.on('execute', () => {
                    showInsertMediaModal({
                        editor: this.editor,
                        uploadUrl,
                    });
                });

                return view;
            });
        }
    };
}
