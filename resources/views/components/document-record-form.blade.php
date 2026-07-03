<div class="dr-form flex h-full min-h-0 w-full flex-col md:flex-row">
    {{-- Sidebar --}}
    <div class="flex w-full shrink-0 flex-col border-r border-slate-200 bg-slate-50 md:w-[320px]">
        <div class="sticky top-0 z-10 border-b bg-white/50 p-6 backdrop-blur-sm">
            <div class="flex items-center gap-3 text-lg">
                <div class="rounded-lg bg-[var(--nttu-primary)]/10 p-2">
                    <svg class="h-5 w-5 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div class="leading-tight">
                    <span class="font-bold text-slate-900" x-text="drModalTitle()"></span>
                    <span class="mt-1 block text-[10px] font-semibold uppercase tracking-widest text-slate-400">Document Explorer</span>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-8">
            <div class="space-y-5">
                <div class="flex items-center gap-2 border-b pb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                    Phân loại văn bản
                </div>
                <div class="space-y-2">
                    <label class="text-[11px] font-bold uppercase text-slate-500">Loại văn bản *</label>
                    <select class="dr-control" x-model="form.doc_type" @change="drOnDocTypeChange()" :disabled="isViewMode">
                        <option value="">Chọn loại...</option>
                        <template x-for="opt in docTypeOptions" :key="opt.value">
                            <option :value="opt.value" x-text="opt.label"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="space-y-5" x-show="drShouldShowField('issuing_body') || drShouldShowField('signer') || drShouldShowField('issue_date') || drShouldShowField('received_date')" x-cloak>
                <div class="flex items-center gap-2 border-b pb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                    Thông tin gốc
                </div>
                <div class="space-y-5">
                    <div class="space-y-2" x-show="drShouldShowField('issuing_body')" x-cloak>
                        <label class="text-[11px] font-bold uppercase text-slate-500">Cơ quan ban hành</label>
                        <input type="text" class="dr-control" x-model="form.issuing_body" :readonly="isViewMode" placeholder="VD: UBND Thành phố">
                    </div>
                    <div class="space-y-2" x-show="drShouldShowField('signer')" x-cloak>
                        <label class="text-[11px] font-bold uppercase text-slate-500">Người ký văn bản</label>
                        <input type="text" class="dr-control" x-model="form.signer" :readonly="isViewMode" placeholder="Họ và tên người ký">
                    </div>
                    <div class="space-y-2" x-show="drShouldShowField('issue_date')" x-cloak>
                        <label class="text-[11px] font-bold uppercase text-slate-500">Ngày ban hành</label>
                        <input type="date" class="dr-control" x-model="form.issue_date" :readonly="isViewMode">
                    </div>
                    <div class="space-y-2" x-show="drShouldShowField('received_date')" x-cloak>
                        <label class="text-[11px] font-bold uppercase text-slate-500">Ngày nhận/nhập</label>
                        <input type="date" class="dr-control" x-model="form.received_date" :readonly="isViewMode">
                    </div>
                </div>
            </div>

            <div class="space-y-4 rounded-xl border bg-white p-4 shadow-sm">
                <div class="space-y-3">
                    <label class="text-[10px] font-bold uppercase tracking-tighter text-slate-400">Độ khẩn</label>
                    <div class="flex flex-col gap-2">
                        <template x-for="opt in urgencyOptions" :key="opt">
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" class="text-orange-600" :value="opt" x-model="form.urgency" :disabled="isViewMode">
                                <span class="text-[11px] font-semibold" :class="form.urgency === opt ? 'text-orange-600' : 'text-slate-500'" x-text="opt"></span>
                            </label>
                        </template>
                    </div>
                </div>
                <hr class="border-slate-200">
                <div class="space-y-3">
                    <label class="text-[10px] font-bold uppercase tracking-tighter text-slate-400">Độ mật</label>
                    <div class="flex flex-col gap-2">
                        <template x-for="opt in confidentialityOptions" :key="opt">
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" class="text-red-600" :value="opt" x-model="form.confidentiality" :disabled="isViewMode">
                                <span class="text-[11px] font-semibold" :class="form.confidentiality === opt ? 'text-red-600' : 'text-slate-500'" x-text="opt"></span>
                            </label>
                        </template>
                    </div>
                    <div x-show="drShowFilePassword()" x-cloak class="mt-4 space-y-2 border-t border-red-100 pt-4">
                        <label class="flex items-center gap-1.5 text-[10px] font-bold uppercase text-red-600">Mật khẩu bảo vệ</label>
                        <input type="password" class="dr-control border-red-200 bg-red-50 text-xs text-red-700 placeholder:text-red-300" x-model="form.file_password" :readonly="isViewMode" placeholder="Nhập mật khẩu (tùy chọn)...">
                        <p class="text-[9px] italic text-red-400">User cần nhập mật khẩu này để xem hoặc tải file đính kèm.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main --}}
    <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden bg-white">
        <div class="flex shrink-0 items-center justify-between border-b px-8 py-4">
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 animate-pulse rounded-full bg-blue-500"></span>
                <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Main Content Area</span>
            </div>
            <div class="flex items-center gap-3">
                <span x-show="form.original_file" x-cloak class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">File Ready</span>
                <span x-show="drExtracting" x-cloak class="inline-flex items-center gap-1.5 rounded-full border border-purple-200 bg-purple-50 px-3 py-1 text-xs font-semibold text-purple-700 animate-pulse">AI Analyzing...</span>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-8 pb-20 space-y-10">
            <div class="space-y-6">
                <div class="border-l-4 border-[var(--nttu-primary)] pl-4 text-sm font-bold uppercase tracking-widest text-[var(--nttu-primary)]">Document Source & Upload</div>
                <div class="relative overflow-hidden rounded-2xl border-2 border-dashed p-8 transition-all"
                     :class="form.original_file ? 'border-emerald-200 bg-emerald-50/10' : 'border-slate-200 hover:border-[var(--nttu-primary)] hover:bg-blue-50/10'">
                    <div class="relative z-10 flex flex-col gap-8">
                        <div class="flex flex-col items-center gap-8 md:flex-row">
                            <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl text-white shadow-lg"
                                 :class="form.original_file ? 'bg-emerald-500' : 'bg-[var(--nttu-primary)]'">
                                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            </div>
                            <div class="flex-1 space-y-1.5 text-center md:text-left">
                                <h4 class="text-base font-bold text-slate-800">Tải lên hoặc nhập liên kết văn bản</h4>
                                <p class="text-sm text-slate-500">Hỗ trợ: PDF, DOCX, XLSX, TXT, PNG, JPG, ZIP, RAR...</p>
                                <span x-show="form.original_file" x-cloak class="mt-2 inline-block rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-800">Tệp tin đã sẵn sàng</span>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="inline-flex h-11 cursor-pointer items-center justify-center rounded-md px-8 text-sm font-semibold shadow-sm"
                                       :class="[
                                           form.original_file ? 'border border-slate-300 bg-white' : 'bg-[var(--nttu-table-head)] text-white',
                                           (isViewMode || drUploading || !form.doc_type) ? 'pointer-events-none opacity-50' : ''
                                       ]">
                                    <span x-text="drUploading ? 'Đang tải...' : (form.original_file ? 'Thay đổi tệp tin' : 'Chọn tệp từ máy')"></span>
                                    <input type="file" class="hidden" accept=".pdf,.doc,.docx,.xlsx,.txt,.jpg,.jpeg,.png,.zip,.rar" @change="drUploadFile($event)" :disabled="isViewMode || drUploading || !form.doc_type">
                                </label>
                                <button type="button" x-show="form.original_file" x-cloak @click="drViewFile()" class="h-8 text-sm font-medium text-blue-600 hover:underline">Xem file/liên kết</button>
                            </div>
                        </div>
                        <div x-show="drUploading" x-cloak class="space-y-2">
                            <div class="flex justify-between text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                <span>Đang tải lên hệ thống...</span>
                                <span x-text="`${Math.round(drUploadProgress)}%`"></span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded bg-slate-100">
                                <div class="h-full bg-[var(--nttu-primary)] transition-all" :style="`width:${drUploadProgress}%`"></div>
                            </div>
                        </div>
                        <div class="mt-4 space-y-2 border-t border-dashed border-slate-200 pt-4">
                            <label class="text-[11px] font-bold uppercase text-slate-400">Hoặc nhập liên kết (URL) trực tiếp</label>
                            <div class="flex gap-2">
                                <input type="text" class="dr-control h-11 flex-1"
                                       :value="form.original_file || ''"
                                       @input="drPatchForm({ original_file: $event.target.value })"
                                       @blur="drMaybeTriggerAiFromSource()"
                                       :readonly="isViewMode || !form.doc_type"
                                       :placeholder="!form.doc_type ? 'Vui lòng chọn Loại văn bản trước...' : 'https://example.com/document.pdf'">
                                <button type="button" x-show="form.original_file && !isViewMode" @click="drClearFile()" class="h-11 w-11 text-slate-400 hover:text-red-500">
                                    <svg class="mx-auto h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="border-l-4 border-[var(--nttu-primary)] pl-4 text-sm font-bold uppercase tracking-widest text-[var(--nttu-primary)]">Identification & Title</div>
                <div class="grid grid-cols-1 gap-8 md:grid-cols-12">
                    <div class="md:col-span-4 space-y-2.5" x-show="drShouldShowField('doc_number')" x-cloak>
                        <label class="flex items-center gap-1.5 text-[11px] font-bold uppercase text-slate-500">
                            Số / Ký hiệu
                            <svg x-show="drExtracting" x-cloak class="h-3 w-3 animate-spin text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </label>
                        <input type="text" class="dr-control font-bold"
                               :class="drExtracting ? 'animate-pulse bg-blue-50/50' : 'bg-slate-50/50'"
                               x-model="form.doc_number"
                               :readonly="isViewMode || drExtracting"
                               placeholder="VD: 123/QD-UBND">
                    </div>
                    <div class="md:col-span-8 space-y-2.5">
                        <label class="flex items-center gap-1.5 text-[11px] font-bold uppercase text-blue-700">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                            Tiêu đề chính văn bản *
                        </label>
                        <input type="text" class="dr-control text-lg font-bold shadow-sm"
                               :class="drExtracting ? 'animate-pulse bg-purple-50/50' : ''"
                               x-model="form.title"
                               :readonly="isViewMode || drExtracting"
                               :placeholder="drExtracting ? 'AI đang trích xuất tiêu đề...' : 'Nhập tên gọi chính thức của văn bản...'"
                               required>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="border-l-4 border-[var(--nttu-primary)] pl-4 text-sm font-bold uppercase tracking-widest text-[var(--nttu-primary)]">Content Summary</div>
                <div class="space-y-3" x-show="drShouldShowField('abstract')" x-cloak>
                    <label class="text-[11px] font-bold uppercase text-slate-500">Trích yếu nội dung văn bản</label>
                    <textarea class="dr-control min-h-[160px] resize-none bg-slate-50/30 p-6 text-base leading-relaxed shadow-inner" rows="6"
                              :value="form.abstract || ''"
                              @input="drPatchForm({ abstract: $event.target.value })"
                              :readonly="isViewMode"
                              placeholder="Tóm tắt ngắn gọn các nội dung chính, mục tiêu của văn bản..."></textarea>
                </div>
            </div>

            <div class="space-y-6">
                <div class="border-l-4 border-[var(--nttu-primary)] pl-4 text-sm font-bold uppercase tracking-widest text-[var(--nttu-primary)]">Workflow & Assignee</div>
                <div class="grid grid-cols-1 items-start gap-8 md:grid-cols-12">
                    <div class="md:col-span-4 space-y-3 relative" x-show="drShouldShowField('department')" x-cloak>
                        <label class="text-[11px] font-bold uppercase text-slate-500">Đơn vị xử lý chính</label>
                        <button type="button" class="dr-multi-trigger" @click="!isViewMode && (drDepartmentOpen = !drDepartmentOpen)" :disabled="isViewMode">
                            <span x-text="drSelectedDepartments().length ? drSelectedDepartments().join(', ') : 'Chọn phòng ban...'" class="truncate"></span>
                        </button>
                        <div x-show="drDepartmentOpen" @click.outside="drDepartmentOpen = false" x-cloak class="absolute z-20 mt-1 max-h-48 w-full overflow-y-auto rounded-md border bg-white p-2 shadow-lg">
                            <template x-for="opt in departmentOptions" :key="opt.value">
                                <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-slate-50">
                                    <input type="checkbox" :checked="drSelectedDepartments().includes(opt.value)" @change="drToggleDepartment(opt.value)">
                                    <span x-text="opt.label"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                    <div class="md:col-span-4 space-y-3 relative" x-show="drShouldShowField('assignee')" x-cloak>
                        <label class="text-[11px] font-bold uppercase text-slate-500">Nhân sự phụ trách</label>
                        <button type="button" class="dr-multi-trigger" @click="!isViewMode && (drAssigneeOpen = !drAssigneeOpen)" :disabled="isViewMode">
                            <span x-text="drSelectedAssignees().length ? drSelectedAssignees().join(', ') : 'Chọn người xử lý...'" class="truncate"></span>
                        </button>
                        <div x-show="drAssigneeOpen" @click.outside="drAssigneeOpen = false" x-cloak class="absolute z-20 mt-1 max-h-48 w-full overflow-y-auto rounded-md border bg-white p-2 shadow-lg">
                            <template x-for="opt in employeeOptions" :key="opt.value">
                                <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-slate-50">
                                    <input type="checkbox" :checked="drSelectedAssignees().includes(opt.value)" @change="drToggleAssignee(opt.value)">
                                    <span x-text="opt.label"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                    <div class="md:col-span-4 space-y-3" x-show="drShouldShowField('status')" x-cloak>
                        <label class="text-[11px] font-bold uppercase text-blue-700">Trạng thái hiện tại</label>
                        <select class="dr-control h-11 border-2 text-base font-bold" :class="drStatusClass()" x-model="form.status" :disabled="isViewMode">
                            <template x-for="opt in statusOptions" :key="opt">
                                <option :value="opt" x-text="opt"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-3xl border-2 border-purple-100 bg-purple-50/30 p-8 space-y-6">
                <div class="relative z-10 flex items-center justify-between">
                    <div>
                        <span class="text-sm font-bold uppercase tracking-widest text-purple-900">AI Intelligence Insights</span>
                        <span class="block text-[10px] font-bold uppercase tracking-tighter text-purple-500">Automatic Extraction & Summary</span>
                    </div>
                    <span class="rounded bg-purple-600 px-4 py-1 text-xs font-bold text-white">GEN-AI ACTIVE</span>
                </div>
                <div class="relative z-10 grid grid-cols-1 gap-8 md:grid-cols-2">
                    <div class="space-y-3">
                        <label class="text-[11px] font-bold uppercase tracking-widest text-purple-600">Tóm lược nội dung AI</label>
                        <div class="relative">
                            <textarea class="dr-control h-40 resize-none border-purple-200 bg-white/80 p-4 text-sm italic leading-relaxed text-slate-600 shadow-sm" readonly
                                      x-model="form.ai_summary"
                                      placeholder="Bản tóm tắt tự động từ AI sẽ xuất hiện tại đây sau khi phân tích tệp tin..."></textarea>
                            <div x-show="!form.ai_summary && !drExtracting" x-cloak class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-2 text-purple-300">
                                <svg class="h-8 w-8 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                <span class="text-[10px] font-bold uppercase tracking-widest opacity-40">Waiting for data</span>
                            </div>
                            <div x-show="drExtracting" x-cloak class="pointer-events-none absolute inset-0 flex items-center justify-center bg-white/70">
                                <span class="text-xs font-bold uppercase tracking-widest text-purple-600 animate-pulse">AI đang phân tích...</span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <label class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Dữ liệu thô trích xuất (OCR)</label>
                        <textarea class="dr-control h-40 resize-none border-slate-200 bg-slate-900/5 p-4 font-mono text-[10px] leading-normal text-slate-500" readonly
                                  x-model="form.extracted_text"
                                  placeholder="Raw OCR data stream..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex shrink-0 items-center justify-between border-t bg-white px-8 py-6 shadow-[0_-4px_20px_rgba(0,0,0,0.03)]">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Document ID</span>
                <code class="block text-xs font-mono font-bold text-slate-600" x-text="form.doc_code || 'PENDING'"></code>
            </div>
            <div class="flex items-center gap-3">
                <x-nttu-button type="button" action="undo" class="h-11 px-6" x-bind:disabled="!isChanged || isViewMode" @click="undoForm()">Hoàn tác</x-nttu-button>
                <button type="button" x-show="!isViewMode" class="inline-flex h-11 items-center gap-2 rounded-md bg-[#1877F2] px-10 text-base font-bold text-white shadow-lg hover:bg-[#166fe5] disabled:opacity-50"
                        :disabled="!drCanSave() || saving" @click="saveItem()">
                    <x-form-field-icon name="save" tone="green" class="h-4 w-4 text-white" />
                    <span x-text="saving ? 'Đang lưu...' : (drUploading ? 'Đang tải file...' : (drExtracting ? 'AI đang xử lý...' : 'Lưu hồ sơ'))"></span>
                </button>
                <x-nttu-button type="button" action="close" class="h-11 bg-slate-800 px-10" x-show="isViewMode" @click="closeModal()">Đóng</x-nttu-button>
            </div>
        </div>
    </div>
</div>
