@props([
    'fieldKey' => 'avatar_url',
    'label' => 'Hình đại diện',
])

<div
    class="space-y-4"
    x-data="avatarInput(@js($fieldKey), @js($label))"
    x-init="init()"
    @modal-closed.window="destroy()"
    :class="disabled ? 'opacity-80 pointer-events-none' : ''"
>
    <div class="flex flex-col items-center gap-6 rounded-xl border bg-slate-50 p-4 shadow-sm md:flex-row">
        <div class="group relative shrink-0">
            <div class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-full border-4 border-white bg-slate-100 shadow-xl ring-2 ring-cyan-500/10">
                <img x-show="preview" :src="preview" alt="" class="h-full w-full object-cover">
                <svg x-show="!preview" class="h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <button
                type="button"
                x-show="preview && !disabled"
                @click="clearAvatar()"
                class="absolute -right-1 -top-1 flex h-7 w-7 items-center justify-center rounded-full bg-red-600 text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="flex-1 space-y-2 text-center md:text-left">
            <h4 class="text-lg font-bold text-[var(--nttu-primary)]" x-text="label"></h4>
            <p class="text-sm leading-relaxed text-gray-500" x-text="disabled ? 'Đang ở chế độ xem chi tiết.' : 'Tải lên từ máy tính, sử dụng liên kết hoặc chụp trực tiếp từ camera.'"></p>
            <div x-show="!disabled" class="flex flex-wrap justify-center gap-2 pt-1 md:justify-start">
                <span class="rounded-full border bg-white px-2 py-0.5 text-[10px] font-bold uppercase tracking-tighter text-gray-600">PNG, JPG, WEBP</span>
                <span class="rounded-full border bg-white px-2 py-0.5 text-[10px] font-bold uppercase tracking-tighter text-gray-600">Max 5MB</span>
            </div>
        </div>
    </div>

    <p x-show="message" x-text="message?.text" :class="message?.type === 'error' ? 'text-red-600' : 'text-green-600'" class="text-sm"></p>

    <div x-show="!disabled" class="space-y-4">
        <div class="grid h-12 grid-cols-3 gap-1 rounded-lg bg-slate-100 p-1">
            <button type="button" @click="switchTab('url')" :class="tab === 'url' ? 'bg-white shadow-sm' : ''" class="flex h-10 items-center justify-center gap-2 rounded-md text-sm font-medium text-gray-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                URL
            </button>
            <button type="button" @click="switchTab('upload')" :class="tab === 'upload' ? 'bg-white shadow-sm' : ''" class="flex h-10 items-center justify-center gap-2 rounded-md text-sm font-medium text-gray-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Tải tệp
            </button>
            <button type="button" @click="switchTab('capture')" :class="tab === 'capture' ? 'bg-white shadow-sm' : ''" class="flex h-10 items-center justify-center gap-2 rounded-md text-sm font-medium text-gray-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Chụp ảnh
            </button>
        </div>

        <div x-show="tab === 'url'" class="space-y-2">
            <x-filter-label class="px-1 uppercase">Đường dẫn hình ảnh</x-filter-label>
            <div class="flex gap-2">
                <input
                    type="text"
                    class="w-full rounded-md border-gray-300 bg-slate-50 text-sm shadow-sm"
                    placeholder="https://example.com/image.jpg"
                    :value="urlDraft"
                    @input="onUrlInput($event)"
                >
                <x-nttu-button type="button" action="refresh" size="sm" class="shrink-0" @click="checkUrl()">Kiểm tra</x-nttu-button>
            </div>
        </div>

        <div x-show="tab === 'upload'">
            <label class="relative block cursor-pointer rounded-xl border-2 border-dashed p-8 text-center transition-all hover:border-cyan-400 hover:bg-slate-50">
                <input type="file" accept="image/*" class="absolute inset-0 z-10 cursor-pointer opacity-0" @change="onFileSelected($event)">
                <div class="space-y-3">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-cyan-50 text-[var(--nttu-primary)]">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-800">Kéo thả hoặc nhấp để chọn ảnh</p>
                        <p class="mt-1 text-xs text-gray-500">Hỗ trợ các định dạng ảnh phổ biến</p>
                    </div>
                </div>
            </label>
        </div>

        <div x-show="tab === 'capture'" class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="space-y-4">
                <div class="space-y-3 rounded-xl border bg-slate-50 p-4">
                    <div class="space-y-2">
                        <x-filter-label class="uppercase tracking-wider">Nguồn hình ảnh</x-filter-label>
                        <div class="flex gap-2">
                            <button type="button" @click="setSourceType('camera')" :class="sourceType === 'camera' ? 'bg-[var(--nttu-table-head)] text-white' : 'border bg-white text-gray-700'" class="flex flex-1 items-center justify-center gap-2 rounded-md px-3 py-2 text-sm">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Camera
                            </button>
                            <button type="button" @click="setSourceType('screen')" :class="sourceType === 'screen' ? 'bg-[var(--nttu-table-head)] text-white' : 'border bg-white text-gray-700'" class="flex flex-1 items-center justify-center gap-2 rounded-md px-3 py-2 text-sm">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                Màn hình
                            </button>
                        </div>
                    </div>

                    <button type="button" x-show="!stream" class="mt-2 flex w-full items-center justify-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm font-medium text-white shadow-sm" @click="startStream()">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Bật Camera
                    </button>

                    <div x-show="stream" class="mt-2 flex gap-2">
                        <button type="button" class="flex flex-1 items-center justify-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm font-bold text-white shadow-lg" @click="capturePhoto()">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/></svg>
                            Chụp & Lưu
                        </button>
                        <button type="button" class="flex h-10 w-10 items-center justify-center rounded-md border bg-white text-gray-700 hover:bg-red-50 hover:text-red-600" @click="stopStream()">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <p class="px-1 text-[10px] italic text-gray-500">* Lưu ý: Cho phép trình duyệt truy cập Camera để sử dụng tính năng này.</p>
            </div>

            <div class="relative aspect-square overflow-hidden rounded-2xl border-4 border-white bg-slate-950 shadow-2xl">
                <video
                    x-ref="video"
                    autoplay
                    muted
                    playsinline
                    class="h-full w-full object-cover transition-opacity duration-500"
                    :class="[
                        stream ? 'opacity-100' : 'opacity-0',
                        sourceType === 'camera' && isFrontCamera ? 'scale-x-[-1]' : '',
                    ]"
                ></video>
                <canvas x-ref="canvas" class="hidden"></canvas>

                <div x-show="!stream" class="absolute inset-0 flex flex-col items-center justify-center space-y-2 text-white/30">
                    <svg class="h-12 w-12 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/></svg>
                    <span class="text-[10px] font-bold uppercase tracking-tighter">Camera Off</span>
                </div>

                <div x-show="stream && sourceType === 'camera' && devices.length > 0" class="absolute right-4 top-4 z-20 w-48">
                    <select class="h-9 w-full rounded-md border border-white/20 bg-black/60 px-2 text-[10px] text-white backdrop-blur-md" :value="selectedDeviceId" @change="changeDevice($event)">
                        <template x-for="device in devices" :key="device.deviceId">
                            <option :value="device.deviceId" x-text="device.label || ('Camera ' + (devices.indexOf(device) + 1))"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
