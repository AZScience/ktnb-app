import { destroyComposeEditor, mountComposeEditor } from './ckeditor-compose.js';



let ckeditorComposePreloadPromise = null;



// CKEditor instances must not live on Alpine reactive state — proxies break editor internals.

const catalogEditorStore = new WeakMap();

const catalogMountState = new WeakMap();



export function preloadCatalogCkeditorCompose() {

    if (!ckeditorComposePreloadPromise) {

        ckeditorComposePreloadPromise = import('./ckeditor-compose.js').catch(() => {});

    }



    return ckeditorComposePreloadPromise;

}



export function catalogCkeditorKeys(formFields) {

    return (formFields || []).filter((field) => field.type === 'ckeditor').map((field) => field.key);

}



export function isCkeditorContentEmpty(html) {

    const raw = String(html || '').trim();

    if (!raw) {

        return true;

    }



    const text = raw.replace(/<[^>]*>/g, '').replace(/&nbsp;/gi, ' ').trim();

    if (text !== '') {

        return false;

    }



    return !/(<img\b|<video\b|<audio\b|<iframe\b|<figure\b|<oembed\b)/i.test(raw);

}



function editorMap(component) {

    let map = catalogEditorStore.get(component);

    if (!map) {

        map = Object.create(null);

        catalogEditorStore.set(component, map);

    }



    return map;

}



function mountState(component) {

    let state = catalogMountState.get(component);

    if (!state) {

        state = { promise: null };

        catalogMountState.set(component, state);

    }



    return state;

}



export function resetCatalogCkeditorState(component) {

    catalogEditorStore.delete(component);

    const state = catalogMountState.get(component);

    if (state) {

        state.promise = null;

    }

}



function catalogModalRoot(component) {

    return component.$refs?.catalogModalForm ?? component.$el;

}



async function waitForVisibleContainer(root, key, maxAttempts = 16) {

    for (let attempt = 0; attempt < maxAttempts; attempt += 1) {

        const container = root?.querySelector?.(`[data-catalog-ckeditor-field="${key}"]`);

        if (container) {

            const visible = container.offsetParent !== null

                || container.getClientRects().length > 0;

            if (visible || attempt >= 8) {

                return container;

            }

        }



        await new Promise((resolve) => {

            requestAnimationFrame(resolve);

        });

    }



    return root?.querySelector?.(`[data-catalog-ckeditor-field="${key}"]`) ?? null;

}



export function catalogCkeditorMethods(ckeditorFieldKeys, formFields) {

    return {

        ckeditorMounting: false,



        scheduleCatalogCkeditorMount() {

            void preloadCatalogCkeditorCompose();

            this.$nextTick(() => {

                requestAnimationFrame(() => {

                    void this.mountCatalogCkeditors();

                });

            });

        },



        async mountCatalogCkeditors() {

            const state = mountState(this);

            if (state.promise) {

                return state.promise;

            }



            this.ckeditorMounting = true;

            state.promise = (async () => {

                await this.destroyCatalogCkeditors();



                if (this.dialogMode === 'view') {

                    return;

                }



                await preloadCatalogCkeditorCompose();



                const root = catalogModalRoot(this);

                const editors = editorMap(this);



                for (const key of ckeditorFieldKeys) {

                    const container = await waitForVisibleContainer(root, key);

                    if (!container) {

                        console.error('CKEditor container not found', key);

                        continue;

                    }



                    if (container.querySelector('.ck-editor')) {

                        container.innerHTML = '';

                    }



                    const field = formFields.find((item) => item.key === key);



                    try {

                        editors[key] = await mountComposeEditor(container, {

                            placeholder: field?.placeholder || 'Nhập nội dung...',

                            initialData: this.form[key] || '',

                            onChange: (html) => {

                                this.form = { ...this.form, [key]: html };

                            },

                        });

                    } catch (error) {

                        console.error('CKEditor init failed', key, error);

                    }

                }



                this.syncCatalogCkeditorsToForm();

            })();



            try {

                await state.promise;

            } finally {

                this.ckeditorMounting = false;

                state.promise = null;

            }

        },



        async ensureCatalogCkeditorsReady() {

            if (this.dialogMode === 'view') {

                return;

            }



            const editors = editorMap(this);

            const missingEditor = ckeditorFieldKeys.some((key) => !editors[key]);

            if (!missingEditor) {

                return;

            }



            const state = mountState(this);

            if (state.promise) {

                await state.promise;

                return;

            }



            await this.mountCatalogCkeditors();

        },



        async destroyCatalogCkeditors() {

            const editors = editorMap(this);

            const instances = ckeditorFieldKeys

                .map((key) => editors[key])

                .filter(Boolean);



            catalogEditorStore.delete(this);



            await Promise.all(

                instances.map(async (editor) => {

                    try {

                        await Promise.race([

                            destroyComposeEditor(editor),

                            new Promise((resolve) => {

                                setTimeout(resolve, 3000);

                            }),

                        ]);

                    } catch {

                        // ignore

                    }

                }),

            );

        },



        syncCatalogCkeditorsToForm() {

            const editors = editorMap(this);



            for (const key of ckeditorFieldKeys) {

                const editor = editors[key];

                if (editor) {

                    this.form[key] = editor.getData();

                }

            }

        },

    };

}


