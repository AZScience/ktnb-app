import {
    Alignment,
    BlockQuote,
    Bold,
    ClassicEditor,
    Essentials,
    Font,
    GeneralHtmlSupport,
    Heading,
    Image,
    ImageCaption,
    ImageResize,
    ImageStyle,
    ImageToolbar,
    ImageUpload,
    Indent,
    Italic,
    Link,
    List,
    MediaEmbed,
    Paragraph,
    SimpleUploadAdapter,
    Table,
    TableToolbar,
    Undo,
} from 'ckeditor5';

import 'ckeditor5/ckeditor5.css';

import { createFileUploadPlugin } from './ckeditor-file-upload-plugin.js';
import { createInsertMediaPlugin } from './ckeditor-insert-media-plugin.js';

const COMPOSE_PLUGINS = [
    Essentials,
    Paragraph,
    Bold,
    Italic,
    Heading,
    Link,
    List,
    Indent,
    BlockQuote,
    Table,
    TableToolbar,
    Alignment,
    Font,
    Undo,
    Image,
    ImageCaption,
    ImageResize,
    ImageStyle,
    ImageToolbar,
    ImageUpload,
    SimpleUploadAdapter,
    MediaEmbed,
    GeneralHtmlSupport,
];

const DEFAULT_TOOLBAR = [
    'heading', '|',
    'bold', 'italic', '|',
    'fontSize', 'fontColor', 'fontBackgroundColor', '|',
    'alignment', '|',
    'link', 'uploadImage', 'insertMedia', 'uploadFile', '|',
    'bulletedList', 'numberedList', '|',
    'outdent', 'indent', '|',
    'blockQuote', 'insertTable', '|',
    'undo', 'redo',
];

const editorConfig = {
    licenseKey: 'GPL',
    plugins: COMPOSE_PLUGINS,
    toolbar: {
        items: DEFAULT_TOOLBAR,
        shouldNotGroupWhenFull: true,
    },
    fontSize: {
        options: [10, 11, 12, 13, 14, 15, 16, 18, 20, 22, 24, 26, 28, 36],
        supportAllValues: true,
    },
    fontColor: {
        colors: [
            { color: '#000000', label: 'Đen' },
            { color: '#dc2626', label: 'Đỏ' },
            { color: '#ea580c', label: 'Cam' },
            { color: '#ca8a04', label: 'Vàng' },
            { color: '#16a34a', label: 'Xanh lá' },
            { color: '#2563eb', label: 'Xanh dương' },
            { color: '#7c3aed', label: 'Tím' },
            { color: '#6b7280', label: 'Xám' },
        ],
        columns: 8,
    },
    fontBackgroundColor: {
        colors: [
            { color: '#ffffff', label: 'Trắng' },
            { color: '#fef08a', label: 'Vàng nhạt' },
            { color: '#bbf7d0', label: 'Xanh nhạt' },
            { color: '#bfdbfe', label: 'Xanh dương nhạt' },
            { color: '#fecaca', label: 'Đỏ nhạt' },
        ],
        columns: 5,
    },
    alignment: {
        options: ['left', 'center', 'right', 'justify'],
    },
    table: {
        contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'],
    },
    image: {
        toolbar: [
            'imageTextAlternative',
            '|',
            'imageStyle:inline',
            'imageStyle:block',
            'imageStyle:side',
            '|',
            'resizeImage',
        ],
    },
    mediaEmbed: {
        previewsInData: true,
    },
    htmlSupport: {
        allow: [
            {
                name: 'img',
                attributes: ['src', 'alt', 'width', 'height', 'style'],
                classes: true,
                styles: true,
            },
            {
                name: 'video',
                attributes: ['src', 'controls', 'style', 'width', 'height', 'poster'],
                classes: true,
                styles: true,
            },
            {
                name: 'audio',
                attributes: ['src', 'controls', 'style'],
                classes: true,
                styles: true,
            },
            {
                name: 'source',
                attributes: ['src', 'type'],
            },
            {
                name: 'oembed',
                attributes: ['url'],
            },
            {
                name: 'figure',
                attributes: true,
                classes: true,
                styles: true,
            },
            {
                name: 'a',
                attributes: ['href', 'target', 'rel', 'download'],
                classes: true,
            },
        ],
    },
};

function resolveUploadUrl(provided) {
    if (provided) {
        return provided;
    }

    const fromMeta = document.querySelector('meta[name="ckeditor-upload-url"]')?.content?.trim();
    if (fromMeta) {
        return fromMeta;
    }

    return `${window.location.origin}/tools/ckeditor/upload`;
}

function uploadHeaders() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    return {
        'X-Requested-With': 'XMLHttpRequest',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
    };
}

function toolbarWithoutUpload(items) {
    return items.filter((item) => !['uploadImage', 'insertMedia', 'uploadFile'].includes(item));
}

export async function mountComposeEditor(container, {
    placeholder = 'Viết nội dung tin nhắn tại đây...',
    initialData = '',
    onChange,
    toolbar,
    uploadUrl,
} = {}) {
    if (!container) {
        return null;
    }

    const resolvedUploadUrl = resolveUploadUrl(uploadUrl);
    const plugins = [
        ...COMPOSE_PLUGINS,
        createInsertMediaPlugin(resolvedUploadUrl),
        createFileUploadPlugin(resolvedUploadUrl),
    ];

    const toolbarItems = toolbar || DEFAULT_TOOLBAR;
    const config = {
        ...editorConfig,
        plugins,
        placeholder,
        toolbar: {
            items: resolvedUploadUrl ? toolbarItems : toolbarWithoutUpload(toolbarItems),
            shouldNotGroupWhenFull: true,
        },
        simpleUpload: {
            uploadUrl: resolvedUploadUrl,
            withCredentials: true,
            headers: uploadHeaders(),
        },
    };

    const editor = await ClassicEditor.create(container, config);

    editor.setData(initialData || '');
    editor.model.document.on('change:data', () => {
        if (typeof onChange === 'function') {
            onChange(editor.getData());
        }
    });

    return editor;
}

export async function destroyComposeEditor(editor) {
    if (!editor) {
        return;
    }

    try {
        await editor.destroy();
    } catch {
        // ignore
    }
}
