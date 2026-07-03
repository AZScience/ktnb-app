@props([
    'requestTypeOptions' => [],
    'buildingOptions' => [],
])

<div class="sr-form space-y-6 p-6 md:p-10">
    <div class="text-center pt-2">
        <h1 class="text-xl font-black uppercase tracking-tight text-slate-900 md:text-2xl">PHIẾU THÔNG TIN HỖ TRỢ GIẢI QUYẾT</h1>
    </div>

    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 border-b border-dashed border-slate-200 pb-2">
        <div class="flex items-center gap-2">
            <svg class="h-4 w-4 shrink-0 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M5 9h14"/></svg>
            <span class="text-sm font-bold text-slate-800">Số:</span>
            <input type="text"
                   class="w-32 border-0 bg-transparent p-0 text-base font-bold text-slate-800 shadow-none focus:ring-0"
                   x-model="form.ticket_number"
                   :readonly="isViewMode"
                   placeholder="0001/PYC">
        </div>
        <div class="flex min-w-[240px] flex-1 items-center gap-2">
            <span class="shrink-0 text-sm font-bold text-slate-800">Loại yêu cầu:</span>
            <template x-if="isViewMode">
                <span class="text-base font-bold text-slate-800" x-text="srRequestTypeLabel(form.request_type)"></span>
            </template>
            <template x-if="!isViewMode">
                <select class="sr-input min-w-[180px] flex-1 cursor-pointer text-base font-bold text-slate-800"
                        x-model="form.request_type"
                        @change="srEnsureRequestType(form.request_type)">
                    <option value="">Chọn loại yêu cầu...</option>
                    <template x-for="opt in requestTypeOptions" :key="'sr-rt-' + opt.value">
                        <option :value="opt.value" x-text="opt.label"></option>
                    </template>
                </select>
            </template>
        </div>
        <div class="flex min-w-[200px] flex-1 items-center gap-2">
            <span class="shrink-0 text-sm font-bold text-slate-800">Dãy nhà:</span>
            <template x-if="isViewMode">
                <span class="text-base font-bold text-slate-800" x-text="srBuildingLabel(form.building_block)"></span>
            </template>
            <template x-if="!isViewMode">
                <select class="sr-input min-w-[140px] flex-1 cursor-pointer text-base font-bold text-slate-800"
                        x-model="form.building_block"
                        @change="srEnsureBuildingBlock(form.building_block)">
                    <option value="">Chọn dãy nhà...</option>
                    <template x-for="opt in buildingOptions" :key="'sr-b-' + opt.value">
                        <option :value="opt.value" x-text="opt.label"></option>
                    </template>
                    <option value="Khác">Khác</option>
                </select>
            </template>
        </div>
    </div>

    {{-- THÔNG TIN YÊU CẦU --}}
    <div class="border-l-2 border-slate-200 pl-6 md:pl-8 space-y-4">
        <button type="button" class="flex w-full items-center justify-between text-left" @click="srSections.info = !srSections.info">
            <div>
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <h2 class="text-sm font-bold uppercase text-slate-900">THÔNG TIN YÊU CẦU</h2>
                </div>
                <p class="text-xs italic text-slate-500">(Dành cho người yêu cầu).</p>
            </div>
            <svg class="h-5 w-5 text-slate-400 transition-transform" :class="srSections.info ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div x-show="srSections.info" x-cloak class="space-y-4">
            <div class="grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-3">
                <div class="sr-field">
                    <label class="sr-label"><svg class="h-4 w-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg> Họ và tên:</label>
                    <input type="text" class="sr-input" x-model="form.student_name" :readonly="isViewMode" required>
                </div>
                <div class="sr-field">
                    <label class="sr-label"><svg class="h-4 w-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg> MSSV:</label>
                    <input type="text" class="sr-input" x-model="form.student_id" :readonly="isViewMode">
                </div>
                <div class="sr-field">
                    <label class="sr-label"><svg class="h-4 w-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/></svg> Lớp:</label>
                    <input type="text" class="sr-input" x-model="form.class" :readonly="isViewMode">
                </div>
                <div class="sr-field">
                    <label class="sr-label"><svg class="h-4 w-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg> Khoa/ đơn vị:</label>
                    <template x-if="isViewMode">
                        <span class="text-sm font-medium text-slate-700" x-text="form.department || '---'"></span>
                    </template>
                    <template x-if="!isViewMode">
                        <select class="sr-input cursor-pointer"
                                x-model="form.department"
                                @change="srEnsureDepartment(form.department)">
                            <option value="">Chọn khoa/đơn vị...</option>
                            <template x-for="opt in departmentOptions" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                    </template>
                </div>
                <div class="sr-field">
                    <label class="sr-label"><svg class="h-4 w-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg> Số điện thoại:</label>
                    <input type="text" class="sr-input" x-model="form.phone" :readonly="isViewMode">
                </div>
            </div>

            <div class="sr-field">
                <label class="sr-label"><svg class="h-4 w-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> Nội dung yêu cầu hỗ trợ giải quyết:</label>
                <textarea class="sr-input min-h-[2.5rem] resize-none leading-relaxed" rows="2" x-model="form.content" :readonly="isViewMode" required></textarea>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between border-b border-dashed border-slate-200 pb-1">
                    <label class="sr-label mb-0"><svg class="h-4 w-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg> Hồ sơ kèm theo:</label>
                    <button type="button" class="text-[10px] font-bold uppercase tracking-wider text-sky-600 hover:text-sky-700" @click="srShowEvidence = !srShowEvidence" x-text="srShowEvidence ? 'Thu gọn' : 'Mở rộng'"></button>
                </div>
                <div x-show="srShowEvidence" x-cloak class="rounded-lg border bg-white p-3" :class="isViewMode && 'pointer-events-none opacity-80'">
                    <x-evidence-input-panel />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 text-center">
                <div class="flex flex-col items-center">
                    <p class="text-sm font-bold uppercase leading-none text-slate-800">Người yêu cầu</p>
                    <p class="mt-1 text-[10px] italic leading-none text-slate-400">(ký và ghi rõ họ tên)</p>
                    <div class="h-4"></div>
                    <p class="min-w-[120px] border-b border-dashed border-slate-200 px-4 text-center font-bold text-slate-700" x-text="form.student_name || '...'"></p>
                </div>
                <div class="flex flex-col items-center">
                    <div class="mb-1 flex items-center justify-center gap-0.5 text-[12px] italic text-slate-500">
                        Ngày
                        <input type="text" class="sr-date-part w-6" x-model="form.reception_day" @input="srSyncReceptionDate()" :readonly="isViewMode" maxlength="2">
                        tháng
                        <input type="text" class="sr-date-part w-6" x-model="form.reception_month" @input="srSyncReceptionDate()" :readonly="isViewMode" maxlength="2">
                        năm
                        <input type="text" class="sr-date-part w-10" x-model="form.reception_year" @input="srSyncReceptionDate()" :readonly="isViewMode" maxlength="4">
                    </div>
                    <p class="text-sm font-bold uppercase leading-none text-slate-800">Người tiếp nhận</p>
                    <p class="mt-1 text-[10px] italic leading-none text-slate-400">(ký và ghi rõ họ tên)</p>
                    <div class="h-4"></div>
                    <input type="text" class="min-w-[120px] border-0 border-b border-dashed border-slate-200 bg-transparent text-center text-sm font-bold text-slate-700 shadow-none focus:ring-0" x-model="form.recipient" :readonly="isViewMode" placeholder="...">
                </div>
            </div>
        </div>
    </div>

    {{-- KẾT QUẢ GIẢI QUYẾT --}}
    <div class="border-l-2 border-slate-200 pl-6 md:pl-8 space-y-4">
        <button type="button" class="flex w-full items-center justify-between text-left" @click="srSections.resolution = !srSections.resolution">
            <div>
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    <h2 class="text-sm font-bold uppercase text-slate-900">KẾT QUẢ GIẢI QUYẾT</h2>
                </div>
                <p class="text-xs italic text-slate-500">(Dành cho CB giải quyết).</p>
            </div>
            <svg class="h-5 w-5 text-slate-400 transition-transform" :class="srSections.resolution ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div x-show="srSections.resolution" x-cloak class="space-y-4">
            <div class="space-y-3">
                <label class="flex cursor-pointer items-center gap-3 text-sm text-slate-800" :class="isViewMode && 'cursor-default'">
                    <span class="sr-check" :class="form.is_processed_immediately && 'sr-check--on'" @click="srToggleProcessedImmediately()">
                        <svg x-show="form.is_processed_immediately" class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    Đã hỗ trợ xử lý ngay
                </label>

                <label class="flex cursor-pointer items-center gap-3 text-sm text-slate-800" :class="isViewMode && 'cursor-default'">
                    <span class="sr-check" :class="srAppointmentEnabled && 'sr-check--on'" @click="srToggleAppointment()">
                        <svg x-show="srAppointmentEnabled" class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <span class="flex flex-wrap items-center gap-1">
                        Hẹn trả lời ngày
                        <input type="text" class="sr-inline-date" x-model="form.appointment_date" :readonly="isViewMode || !srAppointmentEnabled" placeholder="dd/mm/yyyy">
                    </span>
                </label>

                <div class="flex items-start gap-3">
                    <span class="sr-check mt-1 shrink-0" :class="srOtherNoteEnabled && 'sr-check--on'" @click="srToggleOtherNote()">
                        <svg x-show="srOtherNoteEnabled" class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <div class="flex flex-1 items-start gap-2">
                        <span class="mt-0.5 shrink-0 text-sm text-slate-800">Khác:</span>
                        <input type="text" class="sr-inline-input flex-1" x-model="form.note" :readonly="isViewMode || !srOtherNoteEnabled" placeholder="...">
                    </div>
                </div>
            </div>

            <div class="flex flex-col items-center pt-4">
                <div class="mb-1 flex items-center justify-center gap-0.5 text-[12px] italic text-slate-500">
                    Ngày
                    <input type="text" class="sr-date-part w-6" x-model="form.resolution_day" @input="srSyncResolutionDate()" :readonly="isViewMode" maxlength="2">
                    tháng
                    <input type="text" class="sr-date-part w-6" x-model="form.resolution_month" @input="srSyncResolutionDate()" :readonly="isViewMode" maxlength="2">
                    năm
                    <input type="text" class="sr-date-part w-10" x-model="form.resolution_year" @input="srSyncResolutionDate()" :readonly="isViewMode" maxlength="4">
                </div>
                <p class="text-sm font-bold uppercase leading-none text-slate-800">Cán bộ giải quyết</p>
                <p class="mt-1 text-[10px] italic leading-none text-slate-400">(ký và ghi rõ họ tên)</p>
                <div class="h-4"></div>
                <input type="text" class="min-w-[120px] border-0 border-b border-dashed border-slate-200 bg-transparent text-center text-sm font-bold text-slate-700 shadow-none focus:ring-0" x-model="form.resolver_name" :readonly="isViewMode" placeholder="...">
            </div>
        </div>
    </div>

    {{-- Ý KIẾN PHẢN HỒI --}}
    <div class="border-l-2 border-slate-200 pl-6 md:pl-8 space-y-4">
        <button type="button" class="flex w-full items-center justify-between text-left" @click="srSections.feedback = !srSections.feedback">
            <div>
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <h2 class="text-sm font-bold uppercase text-slate-900">Ý KIẾN PHẢN HỒI</h2>
                </div>
                <p class="text-xs italic text-slate-500">(Dành cho người yêu cầu sau khi được giải quyết)</p>
            </div>
            <svg class="h-5 w-5 text-slate-400 transition-transform" :class="srSections.feedback ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div x-show="srSections.feedback" x-cloak>
            <textarea class="sr-input min-h-[100px] resize-none italic leading-relaxed placeholder:text-slate-300"
                      rows="4"
                      placeholder="Người yêu cầu nhập ý kiến phản hồi tại đây..."
                      x-model="form.feedback"
                      :readonly="isViewMode"></textarea>
        </div>
    </div>
</div>
