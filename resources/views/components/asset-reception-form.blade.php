@props([
    'buildingOptions' => [],
])

<div class="ar-form mx-auto max-w-3xl space-y-6 p-6 text-black md:p-10">
    <div class="mb-8 text-center">
        <h2 class="text-2xl font-bold uppercase tracking-wide">Tiếp nhận Tài sản/Đồ vật</h2>
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
</div>
