@props([
    'buildingOptions' => [],
])

<div class="ar-form mx-auto max-w-3xl space-y-6 p-6 text-black md:p-10">
    <div class="mb-8 flex flex-wrap items-center justify-center gap-3">
        <h2 class="text-2xl font-bold uppercase tracking-wide">Tiếp nhận Tài sản/Đồ vật</h2>
        <template x-if="!isViewMode">
            <div class="relative">
                <input type="file" id="ar-ai-upload" class="hidden" accept="image/*" @change="arExtractAi($event)">
                <input type="file" id="ar-ai-camera" class="hidden" accept="image/*" capture="environment" @change="arExtractAi($event)">
                
                <div class="relative inline-flex items-center gap-3 rounded-xl border border-gray-300 bg-white px-3 py-1.5 shadow-sm">
                    <button type="button" title="Chụp ảnh bằng Camera" @click="arOpenCamera()" 
                        class="text-[#D04B14] transition hover:scale-110 active:scale-95 disabled:opacity-50"
                        :disabled="arIsExtractingAi">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>

                    <button type="button" title="Tải ảnh lên" @click="document.getElementById('ar-ai-upload').click()" 
                        class="text-[#D04B14] transition hover:scale-110 active:scale-95 disabled:opacity-50"
                        :disabled="arIsExtractingAi">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </button>
                    
                    <div x-show="arIsExtractingAi" x-cloak class="absolute inset-0 z-10 flex items-center justify-center gap-1.5 rounded-xl bg-white/90">
                        <svg class="h-4 w-4 animate-spin text-[#D04B14]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span class="text-xs font-semibold text-[#D04B14]">Đang đọc...</span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:gap-6">
        <div class="flex min-w-0 flex-1 items-center gap-2">
            <span class="whitespace-nowrap font-semibold">Số tiếp nhận:</span>
            <input type="text"
                   class="h-8 w-full border-0 border-b border-dashed border-gray-400 bg-transparent px-0 text-base shadow-none focus:ring-0 read-only:cursor-default read-only:text-gray-700"
                   x-model="form.entry_number"
                   readonly
                   :placeholder="`KTNB-0001/{{ date('Y') }}`">
        </div>
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:max-w-sm">
            <span class="whitespace-nowrap font-semibold">Dãy nhà:</span>
            <template x-if="isViewMode">
                <span class="h-8 flex-1 border-0 border-b border-dashed border-gray-400 px-0 text-base" x-text="arBuildingLabel(form.building_block)"></span>
            </template>
            <template x-if="!isViewMode">
                <select class="h-8 w-full border-0 border-b border-dashed border-gray-400 bg-transparent px-0 text-base shadow-none focus:ring-0"
                        x-model="form.building_block"
                        @change="arEnsureBuildingBlock(form.building_block)">
                    <option value="">Chọn dãy nhà...</option>
                    <template x-for="opt in buildingOptions" :key="'ar-b-' + opt.value">
                        <option :value="opt.value" x-text="opt.label"></option>
                    </template>
                    <option value="Khác">Khác</option>
                </select>
            </template>
        </div>
    </div>

    <div class="ar-field mb-6">
        <span class="ar-label"><svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg> Họ và tên người giao nộp: <span class="text-red-500">*</span></span>
        <input type="text" class="ar-input" x-model="form.giver_name" :readonly="isViewMode" required>
    </div>

    <div class="grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-3">
        <div class="ar-field">
            <span class="ar-label"><svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg> Mã nhân viên:</span>
            <input type="text" class="ar-input" x-model="form.giver_employee_code" :readonly="isViewMode">
        </div>
        <div class="ar-field">
            <span class="ar-label"><svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg> Mã số Sinh viên: <span class="text-red-500">*</span></span>
            <input type="text" class="ar-input" x-model="form.giver_id" :readonly="isViewMode" required>
        </div>
        <div class="ar-field">
            <span class="ar-label"><svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/></svg> Lớp người giao nộp:</span>
            <input type="text" class="ar-input" x-model="form.giver_class" :readonly="isViewMode">
        </div>
        <div class="ar-field">
            <span class="ar-label"><svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg> Khoa/đơn vị/địa chỉ: <span class="text-red-500">*</span></span>
            <template x-if="isViewMode">
                <span class="ar-input border-0 bg-transparent px-0" x-text="form.giver_unit || '---'"></span>
            </template>
            <template x-if="!isViewMode">
                <select class="ar-input cursor-pointer"
                        x-model="form.giver_unit"
                        @change="arEnsureDepartment(form.giver_unit)"
                        required>
                    <option value="">Chọn khoa/đơn vị...</option>
                    <template x-for="opt in departmentOptions" :key="'ar-dept-' + opt.value">
                        <option :value="opt.value" x-text="opt.label"></option>
                    </template>
                </select>
            </template>
        </div>
        <div class="ar-field">
            <span class="ar-label"><svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg> Số điện thoại: <span class="text-red-500">*</span></span>
            <input type="text" class="ar-input" x-model="form.giver_phone" :readonly="isViewMode" required>
        </div>
        <div class="ar-field">
            <span class="ar-label"><svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> Tình trạng tài sản:</span>
            <input type="text" class="ar-input" x-model="form.asset_state" :readonly="isViewMode">
        </div>
        <div class="ar-field md:col-span-3">
            <span class="ar-label"><svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> Nội dung giao nộp:</span>
            <input type="text" class="ar-input" x-model="form.content" :readonly="isViewMode">
        </div>
        <div class="ar-field mt-4 md:col-span-3">
            <div class="flex items-center justify-between border-b border-dashed border-gray-300 pb-1">
                <span class="ar-label mb-0"><svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg> Minh chứng tiếp nhận</span>
                <button type="button" class="text-[10px] font-bold uppercase tracking-wider text-[var(--nttu-primary)] hover:opacity-80"
                    @click="arShowEvidence = !arShowEvidence"
                    x-text="arShowEvidence ? 'Thu gọn' : 'Mở rộng'"></button>
            </div>
            <div x-show="arShowEvidence" x-cloak class="mt-3" :class="isViewMode && 'pointer-events-none opacity-80'">
                <x-evidence-input-panel />
            </div>
        </div>
    </div>

    <div class="mt-6">
        <div class="mb-2 flex justify-end">
            <div class="text-center text-sm italic">
                <div class="flex items-center justify-center gap-0.5">
                    Ngày
                    <input type="text" class="ar-date-part w-12" x-model="form.reception_day" @input="arSyncReceptionDate()" :readonly="isViewMode" maxlength="2">
                    tháng
                    <input type="text" class="ar-date-part w-12" x-model="form.reception_month" @input="arSyncReceptionDate()" :readonly="isViewMode" maxlength="2">
                    năm
                    <input type="text" class="ar-date-part w-16" x-model="form.reception_year" @input="arSyncReceptionDate()" :readonly="isViewMode" maxlength="4">
                </div>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4 text-center sm:grid-cols-3">
            <div>
                <p class="font-bold">Người giao nộp</p>
                <p class="text-sm italic text-gray-600">(ký và ghi rõ họ tên)</p>
                <div class="mt-2">
                    <input type="text" class="ar-signature" x-model="form.giver_name" readonly>
                </div>
            </div>
            <div>
                <p class="font-bold">Cán bộ tiếp nhận</p>
                <p class="text-sm italic text-gray-600">(ký và ghi rõ họ tên)</p>
                <div class="mt-2">
                    <input type="text" class="ar-signature read-only:cursor-default read-only:bg-gray-50" x-model="form.receiving_staff" readonly>
                </div>
            </div>
            <div>
                <p class="font-bold">Người chứng kiến giao nộp</p>
                <p class="text-sm italic text-gray-600">(ký và ghi rõ họ tên)</p>
                <div class="mt-2">
                    <input type="text" class="ar-signature" x-model="form.witness" :readonly="isViewMode" placeholder="...">
                </div>
            </div>
        </div>
    </div>

    <div x-show="arCameraOpen" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center bg-black/90 p-4" @keydown.escape.window="arCloseCamera()">
        <div class="w-full max-w-md bg-slate-900 rounded-2xl overflow-hidden shadow-2xl border border-white/10" @click.outside="arCloseCamera()">
            <div class="p-4 border-b border-white/10 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <h3 class="text-white font-semibold whitespace-nowrap">Camera</h3>
                    <template x-if="arCameras.length > 1">
                        <select x-model="arSelectedCameraId" @change="arSwitchCamera()" class="h-8 rounded-md bg-slate-800 border-white/20 text-white text-xs max-w-[200px]">
                            <template x-for="cam in arCameras" :key="cam.deviceId">
                                <option :value="cam.deviceId" x-text="cam.label || 'Camera ' + ($index + 1)"></option>
                            </template>
                        </select>
                    </template>
                </div>
                <button type="button" @click="arCloseCamera()" class="text-white/70 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="relative bg-black aspect-[3/4] sm:aspect-square flex items-center justify-center">
                <video x-ref="arVideo" autoplay playsinline muted class="w-full h-full object-cover"></video>
                <div class="absolute inset-0 border-2 border-dashed border-white/30 pointer-events-none m-4 rounded-lg"></div>
            </div>
            <div class="p-4 flex justify-center bg-slate-900">
                <button type="button" @click="arCapturePhoto()" class="flex items-center gap-2 bg-[#D04B14] hover:bg-orange-600 text-white px-6 py-3 rounded-full font-bold shadow-lg transition transform hover:scale-105 active:scale-95">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"></circle></svg>
                    CHỤP & ĐỌC BẰNG AI
                </button>
            </div>
            <canvas x-ref="arCanvas" class="hidden"></canvas>
        </div>
    </div>
</div>
