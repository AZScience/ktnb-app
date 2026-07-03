import { ButtonView, IconFileUpload, Plugin } from 'ckeditor5';

import {
    insertUploadedContent,
    pickLocalFile,
    uploadToServer,
} from './ckeditor-upload-utils.js';

export function createFileUploadPlugin(uploadUrl) {
    return class FileUpload extends Plugin {
        static get pluginName() {
            return 'FileUpload';
        }

        init() {
            this.editor.ui.componentFactory.add('uploadFile', (locale) => {
                const view = new ButtonView(locale);

                view.set({
                    label: 'Chèn tệp',
                    icon: IconFileUpload,
                    tooltip: true,
                });

                view.on('execute', async () => {
                    if (!uploadUrl) {
                        console.error('CKEditor upload URL not configured');
                        return;
                    }

                    const file = await pickLocalFile();
                    if (!file) {
                        return;
                    }

                    try {
                        const result = await uploadToServer(file, uploadUrl);
                        insertUploadedContent(this.editor, {
                            url: result.url,
                            fileName: result.fileName || file.name,
                            mimeType: result.mimeType || file.type,
                        });
                    } catch (error) {
                        console.error('CKEditor file upload failed', error);
                        window.alert(error.message || 'Không thể tải tệp lên.');
                    }
                });

                return view;
            });
        }
    };
}
