@props(['onlyCamera' => false])

<div class="space-y-4 rounded-md border bg-white/80 p-4" :class="isViewMode && 'pointer-events-none opacity-80'">
    @unless($onlyCamera)
    <div class="flex gap-1 rounded-lg border bg-slate-100 p-1 text-sm">
        <button type="button" @click="evidenceTab = 'upload'; evidenceStopStream()"
            class="flex-1 rounded-md px-3 py-2 font-medium transition"
            :class="evidenceTab === 'upload' ? 'bg-white shadow text-[var(--nttu-primary)]' : 'text-gray-600 hover:text-gray-900'">
            <span class="inline-flex items-center justify-center gap-1.5">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Tập tin
            </span>
        </button>
        <button type="button" @click="evidenceTab = 'camera'; evidenceStopStream()"
            class="flex-1 rounded-md px-3 py-2 font-medium transition"
            :class="evidenceTab === 'camera' ? 'bg-white shadow text-[var(--nttu-primary)]' : 'text-gray-600 hover:text-gray-900'">
            <span class="inline-flex items-center justify-center gap-1.5">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Camera
            </span>
        </button>
        <button type="button" @click="evidenceTab = 'url'; evidenceStopStream()"
            class="flex-1 rounded-md px-3 py-2 font-medium transition"
            :class="evidenceTab === 'url' ? 'bg-white shadow text-[var(--nttu-primary)]' : 'text-gray-600 hover:text-gray-900'">
            <span class="inline-flex items-center justify-center gap-1.5">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                URL
            </span>
        </button>
    </div>
    @endunless

    <div @if($onlyCamera) @else x-show="evidenceTab === 'camera'" x-cloak @endif class="space-y-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="space-y-3">
                <div class="rounded-md border bg-slate-50/80 p-3">
                    <p class="text-xs font-bold uppercase text-gray-500">Minh chứng bằng hình ảnh hay video?</p>
                    <div class="mt-2 flex gap-6 text-sm">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" value="photo" x-model="evidenceMediaMode" @change="evidenceStopStream()" class="text-[var(--nttu-primary)]">
                            Hình ảnh
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" value="video" x-model="evidenceMediaMode" @change="evidenceStopStream()" class="text-[var(--nttu-primary)]">
                            Quay video
                        </label>
                    </div>
                </div>
                @unless($onlyCamera)
                <div class="rounded-md border bg-slate-50/80 p-3">
                    <p class="text-xs font-bold uppercase text-gray-500">Lấy minh chứng bằng:</p>
                    <div class="mt-2 flex gap-6 text-sm">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" value="camera" x-model="evidenceSourceType" @change="evidenceStream && evidenceStartMedia()" class="text-[var(--nttu-primary)]">
                            Camera
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" value="screen" x-model="evidenceSourceType" @change="evidenceStream && evidenceStartMedia()" class="text-[var(--nttu-primary)]">
                            Màn hình
                        </label>
                    </div>
                </div>
                @endunless
                <template x-if="!evidenceStream">
                    <button type="button" @click="evidenceStartMedia()" :disabled="evidenceIsUploading || isViewMode"
                        class="w-full rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white disabled:opacity-50">
                        Bật thiết bị
                    </button>
                </template>
                <template x-if="evidenceStream">
                    <div class="flex gap-2">
                        <template x-if="evidenceMediaMode === 'photo'">
                            <button type="button" @click="evidenceCapturePhoto()" :disabled="evidenceIsUploading || isViewMode"
                                class="flex-1 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white disabled:opacity-50">
                                Chụp ngay
                            </button>
                        </template>
                        <template x-if="evidenceMediaMode === 'video' && !evidenceIsRecording">
                            <button type="button" @click="evidenceStartRecording()" :disabled="evidenceIsUploading || isViewMode"
                                class="flex-1 rounded-md bg-red-600 px-4 py-2 text-sm text-white disabled:opacity-50">
                                Bắt đầu quay
                            </button>
                        </template>
                        <template x-if="evidenceMediaMode === 'video' && evidenceIsRecording">
                            <button type="button" @click="evidenceStopRecording()"
                                class="flex-1 rounded-md bg-red-700 px-4 py-2 text-sm text-white">
                                Dừng quay (<span x-text="evidenceFormatTime(evidenceRecordingTime)"></span>)
                            </button>
                        </template>
                        <button type="button" @click="evidenceStopStream()" :disabled="evidenceIsUploading"
                            class="rounded-md border px-3 py-2 text-sm">×</button>
                    </div>
                </template>
            </div>
            <div class="relative aspect-video overflow-hidden rounded-md border bg-black shadow-inner">
                <video x-ref="evidenceVideo" autoplay muted playsinline
                    class="h-full w-full object-cover"
                    :class="evidenceSourceType === 'camera' && evidenceIsFrontCamera() && 'scale-x-[-1]'"></video>
                <canvas x-ref="evidenceCanvas" class="hidden"></canvas>
                <div x-show="evidenceIsRecording" x-cloak class="absolute left-2 top-2 rounded bg-red-600 px-2 py-1 text-[10px] font-bold tracking-wider text-white">
                    REC <span x-text="evidenceFormatTime(evidenceRecordingTime)"></span>
                </div>
                <div x-show="evidenceIsUploading" x-cloak class="absolute inset-0 z-20 flex items-center justify-center bg-black/55">
                    <span class="rounded-full bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-white backdrop-blur-sm">Đang tải lên</span>
                </div>
                <div x-show="evidenceSourceType === 'camera' && evidenceDevices.length" x-cloak class="absolute right-3 top-3 z-10 w-44">
                    <select x-model="evidenceDeviceId" @change="evidenceStream && evidenceStartMedia('camera', evidenceDeviceId)"
                        class="h-9 w-full rounded border border-white/30 bg-black/60 px-2 text-xs text-white backdrop-blur-md">
                        <template x-for="(device, idx) in evidenceDevices" :key="device.deviceId">
                            <option :value="device.deviceId" x-text="device.label || ('Camera ' + (idx + 1))"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>
    </div>

    @unless($onlyCamera)
    <div x-show="evidenceTab === 'upload'" x-cloak>
        <label class="relative block cursor-pointer rounded-md border-2 border-dashed p-8 text-center transition hover:bg-slate-50"
            :class="evidenceIsUploading && 'cursor-not-allowed bg-slate-100'">
            <input type="file" multiple accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.txt"
                class="absolute inset-0 cursor-pointer opacity-0 disabled:cursor-not-allowed"
                :disabled="evidenceIsUploading || isViewMode"
                @change="evidenceHandleFiles($event.target.files); $event.target.value = ''">
            <svg class="mx-auto mb-2 h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <p class="text-sm font-medium" x-text="evidenceIsUploading ? 'Đang tải tệp lên...' : 'Kéo thả tệp hoặc nhấn để chọn'"></p>
        </label>
    </div>

    <div x-show="evidenceTab === 'url'" x-cloak class="flex gap-2">
        <input type="url" x-model="evidencePendingLink" placeholder="Dán liên kết (https://...)" :readonly="isViewMode"
            @keydown.enter.prevent="evidenceAddLink()" class="nttu-form-control flex-1">
        <button type="button" @click="evidenceAddLink()" :disabled="!evidencePendingLink.trim() || evidenceIsUploading || isViewMode"
            class="rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white disabled:opacity-50">
            Thêm
        </button>
    </div>
    @endunless

    <hr class="border-slate-200">

    <div class="space-y-2">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-500">
            Danh sách minh chứng (<span x-text="evidenceItems.length"></span>)
        </p>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <template x-for="(item, index) in evidenceItems" :key="index + '-' + item">
                <div class="group flex items-center gap-2 overflow-hidden rounded-md border bg-slate-50 p-2 transition hover:bg-slate-100">
                    <div class="shrink-0 text-gray-500">
                        <template x-if="evidenceIsImage(evidenceParseItem(item).name, evidenceParseItem(item).data)">
                            <svg class="h-4 w-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </template>
                        <template x-if="evidenceIsVideo(evidenceParseItem(item).name, evidenceParseItem(item).data)">
                            <svg class="h-4 w-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </template>
                        <template x-if="!evidenceIsImage(evidenceParseItem(item).name, evidenceParseItem(item).data) && !evidenceIsVideo(evidenceParseItem(item).name, evidenceParseItem(item).data)">
                            <svg class="h-4 w-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        </template>
                    </div>
                    <div class="min-w-0 flex-1">
                        <template x-if="evidenceEditingIndex === index">
                            <div class="flex items-center gap-1">
                                <input type="text" x-model="evidenceEditingName" @keydown.enter="evidenceUpdateItemName(index, evidenceEditingName)" @keydown.escape="evidenceEditingIndex = null"
                                    class="h-6 w-full rounded border px-1 text-[10px]">
                                <button type="button" @click="evidenceUpdateItemName(index, evidenceEditingName)" class="text-green-600">✓</button>
                            </div>
                        </template>
                        <template x-if="evidenceEditingIndex !== index">
                            <p class="cursor-pointer truncate text-[10px] font-medium hover:text-[var(--nttu-primary)]"
                                @click="!isViewMode && (evidenceEditingIndex = index, evidenceEditingName = evidenceParseItem(item).name || ('Tep ' + (index + 1)))"
                                x-text="evidenceParseItem(item).name || evidenceParseItem(item).data"></p>
                        </template>
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="evidenceOpenPreview(item)" class="rounded-full p-1 text-gray-500 hover:bg-white" title="Xem">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                        <button type="button" x-show="!isViewMode" @click="evidenceRemoveItem(index)" class="rounded-full p-1 text-red-500 hover:bg-white" title="Xóa">×</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div x-show="evidencePreviewItem" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" @keydown.escape.window="evidenceClosePreview()">
        <div class="absolute inset-0 bg-black/60" @click="evidenceClosePreview()"></div>
        <div class="relative max-h-[90vh] w-full max-w-4xl overflow-auto rounded-xl bg-white p-4 shadow-2xl" @click.stop>
            <div class="mb-3 flex items-center justify-between">
                <h4 class="text-lg font-semibold">Xem minh chứng</h4>
                <button type="button" @click="evidenceClosePreview()" class="text-gray-400 hover:text-gray-600">×</button>
            </div>
            <div class="flex items-center justify-center rounded-md bg-slate-50 p-4">
                <template x-if="evidencePreviewItem && evidenceIsImage(evidenceParseItem(evidencePreviewItem).name, evidenceParseItem(evidencePreviewItem).data)">
                    <img :src="evidenceParseItem(evidencePreviewItem).data" alt="Preview" class="max-h-[70vh] max-w-full rounded-lg shadow-lg">
                </template>
                <template x-if="evidencePreviewItem && evidenceIsVideo(evidenceParseItem(evidencePreviewItem).name, evidenceParseItem(evidencePreviewItem).data)">
                    <video :src="evidenceParseItem(evidencePreviewItem).data" controls class="max-h-[70vh] max-w-full rounded-lg shadow-lg"></video>
                </template>
                <template x-if="evidencePreviewItem && !evidenceIsImage(evidenceParseItem(evidencePreviewItem).name, evidenceParseItem(evidencePreviewItem).data) && !evidenceIsVideo(evidenceParseItem(evidencePreviewItem).name, evidenceParseItem(evidencePreviewItem).data)">
                    <div class="py-10 text-center">
                        <p class="mb-4 text-sm text-gray-600">Minh chứng không thể xem trực tiếp hoặc là liên kết bên ngoài.</p>
                        <div class="flex justify-center gap-2">
                            <a :href="evidenceParseItem(evidencePreviewItem).data" target="_blank" rel="noreferrer" class="rounded-md border px-4 py-2 text-sm">Mở liên kết</a>
                            <a :href="evidenceParseItem(evidencePreviewItem).data" download class="rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white">Tải về tệp</a>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
