@props(['field'])

<div class="md:col-span-2 space-y-4" x-show="fieldVisible('{{ $field['key'] }}')" x-cloak>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-7 md:items-start">
        <div class="md:col-span-3 space-y-1">
            <label class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-tight text-gray-500">
                <x-form-field-icon name="user" tone="blue" />
                <span>Ảnh chân dung</span>
            </label>
            <div class="relative flex h-[100px] w-full flex-col items-center justify-center overflow-hidden rounded-lg border-2 border-dashed transition-all"
                 :class="form.portrait_photo ? 'border-[var(--nttu-primary)]/50 bg-cyan-50/30' : 'border-gray-300'">
                <template x-if="form.portrait_photo">
                    <img :src="form.portrait_photo" alt="Chân dung" class="h-full w-full object-cover">
                </template>
                <template x-if="!form.portrait_photo && !isViewMode">
                    <div class="flex flex-col items-center gap-1 p-2 text-center text-gray-400">
                        <div class="flex gap-2">
                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200" @click="violationOpenPhotoCamera('portrait_photo', 'Chân dung')">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </button>
                            <label class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200">
                                <input type="file" accept="image/*" class="hidden" @change="violationPickPhoto('portrait_photo', $event)">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </label>
                        </div>
                        <span class="text-[9px] font-medium">Chân dung</span>
                    </div>
                </template>
                <button type="button" x-show="form.portrait_photo && !isViewMode" @click="violationClearPhoto('portrait_photo')"
                        class="absolute right-1 top-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-white shadow">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <div class="flex flex-col items-center justify-center pt-5 md:col-span-1">
            <button type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border-2 shadow-sm transition-all"
                    :class="{
                        'border-gray-200 text-gray-400 hover:border-[var(--nttu-primary)]': violationCompareStatus === 'idle',
                        'border-blue-500 animate-pulse text-blue-500': violationCompareStatus === 'loading',
                        'border-green-500 bg-green-50 text-green-600': violationCompareStatus === 'success',
                        'border-red-500 bg-red-50 text-red-600': violationCompareStatus === 'error'
                    }"
                    :disabled="isViewMode || violationCompareStatus === 'loading'"
                    @click="violationCompareFaces()"
                    title="Đối soát khuôn mặt">
                <svg x-show="violationCompareStatus !== 'loading'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <svg x-show="violationCompareStatus === 'loading'" x-cloak class="h-5 w-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </button>
            <span x-show="violationCompareConfidence !== null && violationCompareStatus !== 'loading' && violationCompareStatus !== 'idle'" x-cloak
                  class="mt-1 text-[9px] font-bold"
                  :class="violationCompareStatus === 'success' ? 'text-green-600' : 'text-red-600'"
                  x-text="violationCompareConfidence + '%'"></span>
        </div>

        <div class="md:col-span-3 space-y-1">
            <label class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-tight text-gray-500">
                <x-form-field-icon name="id-card" tone="blue" />
                <span>Ảnh giấy tờ</span>
            </label>
            <div class="relative flex h-[100px] w-full flex-col items-center justify-center overflow-hidden rounded-lg border-2 border-dashed transition-all"
                 :class="form.document_photo ? 'border-[var(--nttu-primary)]/50 bg-cyan-50/30' : 'border-gray-300'">
                <template x-if="form.document_photo">
                    <img :src="form.document_photo" alt="Giấy tờ" class="h-full w-full object-cover">
                </template>
                <template x-if="!form.document_photo && !isViewMode">
                    <div class="flex flex-col items-center gap-1 p-2 text-center text-gray-400">
                        <div class="flex gap-2">
                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200" @click="violationOpenPhotoCamera('document_photo', 'Giấy tờ')">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </button>
                            <label class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200">
                                <input type="file" accept="image/*" class="hidden" @change="violationPickPhoto('document_photo', $event)">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </label>
                        </div>
                        <span class="text-[9px] font-medium">Giấy tờ</span>
                    </div>
                </template>
                <button type="button" x-show="form.document_photo && !isViewMode" @click="violationClearPhoto('document_photo')"
                        class="absolute right-1 top-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-white shadow">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <button type="button"
                    x-show="form.document_photo && !isViewMode"
                    x-cloak
                    class="inline-flex items-center gap-1.5 rounded-md border border-[var(--nttu-primary)] px-2.5 py-1 text-[10px] font-medium text-[var(--nttu-primary)] hover:bg-cyan-50 disabled:opacity-50"
                    :disabled="violationDocumentExtracting"
                    @click="violationExtractFromDocumentPhoto()">
                <svg x-show="!violationDocumentExtracting" class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <svg x-show="violationDocumentExtracting" x-cloak class="h-3.5 w-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span x-text="violationDocumentExtracting ? 'Đang trích xuất...' : 'Đọc lại từ ảnh'"></span>
            </button>
        </div>
    </div>
</div>
