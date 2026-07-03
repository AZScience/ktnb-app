export const MEDIA_FILE_ACCEPT = [
    'image/*',
    'video/*',
    'audio/*',
    '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.rtf,.csv,.zip,.rar,.7z',
].join(',');

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

export function buildInsertHtml({ url, fileName, mimeType }) {
    const safeUrl = escapeHtml(url);
    const safeName = escapeHtml(fileName || 'Tệp đính kèm');

    if (mimeType?.startsWith('image/')) {
        return `<figure class="image"><img src="${safeUrl}" alt="${safeName}"></figure>`;
    }

    if (mimeType?.startsWith('video/')) {
        return `<figure class="media"><video controls src="${safeUrl}" style="max-width:100%;height:auto;"></video></figure>`;
    }

    if (mimeType?.startsWith('audio/')) {
        return `<p><audio controls src="${safeUrl}"></audio></p>`;
    }

    return `<p><a href="${safeUrl}" target="_blank" rel="noopener noreferrer">${safeName}</a></p>`;
}

export async function uploadToServer(file, uploadUrl) {
    if (!uploadUrl) {
        throw new Error('Chưa cấu hình URL tải lên.');
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const formData = new FormData();
    formData.append('upload', file);

    const response = await fetch(uploadUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
        },
    });

    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
        const message = payload.message
            || Object.values(payload.errors || {}).flat().join(', ')
            || `Không thể tải tệp lên (mã ${response.status}).`;

        throw new Error(message);
    }

    if (!payload.url) {
        throw new Error('Máy chủ không trả về URL tệp.');
    }

    return payload;
}

export function insertHtmlContent(editor, html) {
    const viewFragment = editor.data.processor.toView(html);
    const modelFragment = editor.data.toModel(viewFragment);

    editor.model.change(() => {
        editor.model.insertContent(
            modelFragment,
            editor.model.document.selection,
        );
    });
}

export function insertUploadedContent(editor, { url, fileName, mimeType }) {
    if (mimeType?.startsWith('image/') && editor.commands.get('insertImage')) {
        editor.execute('insertImage', { source: url });
        return;
    }

    insertHtmlContent(editor, buildInsertHtml({ url, fileName, mimeType }));
}

export function pickLocalFile(accept = MEDIA_FILE_ACCEPT) {
    return new Promise((resolve) => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = accept;
        input.style.display = 'none';
        document.body.appendChild(input);

        input.addEventListener('change', () => {
            const file = input.files?.[0] || null;
            input.remove();
            resolve(file);
        }, { once: true });

        input.click();
    });
}
