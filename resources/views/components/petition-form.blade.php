<div class="pt-form space-y-8 p-6 md:p-10">
    <div class="mb-8 pt-2 flex flex-wrap items-center justify-center gap-4">
        <h1 class="text-2xl font-black uppercase tracking-tight text-slate-900">PHIẾU TIẾP NHẬN VÀ XỬ LÝ ĐƠN THƯ</h1>
        
        <template x-if="!isViewMode">
            <div class="relative">
                <input type="file" id="pt-ai-upload" class="hidden" accept="image/*" @change="ptExtractAi($event)">
                <input type="file" id="pt-ai-camera" class="hidden" accept="image/*" capture="environment" @change="ptExtractAi($event)">
                
                <div class="relative inline-flex items-center gap-3 rounded-xl border border-gray-300 bg-white px-3 py-1.5 shadow-sm">
                    <button type="button" title="Chụp ảnh bằng Camera" @click="ptOpenCamera()" 
                        class="text-[#D04B14] transition hover:scale-110 active:scale-95 disabled:opacity-50"
                        :disabled="ptIsExtractingAi">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>

                    <button type="button" title="Tải ảnh lên" @click="document.getElementById('pt-ai-upload').click()" 
                        class="text-[#D04B14] transition hover:scale-110 active:scale-95 disabled:opacity-50"
                        :disabled="ptIsExtractingAi">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </button>
                    
                    <div x-show="ptIsExtractingAi" x-cloak class="absolute inset-0 z-10 flex items-center justify-center gap-1.5 rounded-xl bg-white/90">
                        <svg class="h-4 w-4 animate-spin text-[#D04B14]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span class="text-xs font-semibold text-[#D04B14]">Đang đọc...</span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- THÔNG TIN CÔNG DÂN --}}
    <div class="space-y-4 border-l-2 border-slate-200 pl-6 md:pl-8">
        <button type="button" class="flex w-full items-center justify-between text-left" @click="ptSections.citizen = !ptSections.citizen">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-blue-50 p-2">
                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                    <h2 class="font-bold uppercase leading-none text-slate-900">THÔNG TIN CÔNG DÂN</h2>
                    <p class="mt-1 text-[10px] italic text-slate-500">(Thông tin người gửi đơn và tiếp nhận)</p>
                </div>
            </div>
            <svg class="h-5 w-5 text-slate-400 transition-transform" :class="ptSections.citizen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div x-show="ptSections.citizen" x-cloak class="grid grid-cols-1 gap-x-8 gap-y-6 pt-2 md:grid-cols-3">
            <div class="pt-field">
                <label class="pt-label">Ngày tiếp nhận</label>
                <input type="text" class="pt-input" x-model="form.reception_date" :readonly="isViewMode" placeholder="dd/mm/yyyy">
            </div>
            <div class="pt-field">
                <label class="pt-label">Họ và tên công dân</label>
                <input type="text" class="pt-input" x-model="form.citizen_name" :readonly="isViewMode" required placeholder="...">
            </div>
            <div class="pt-field">
                <label class="pt-label">MSSV / CCCD</label>
                <input type="text" class="pt-input" x-model="form.citizen_id" :readonly="isViewMode" placeholder="...">
            </div>
            <div class="pt-field">
                <label class="pt-label">Điện thoại</label>
                <input type="text" class="pt-input" x-model="form.citizen_phone" :readonly="isViewMode" placeholder="...">
            </div>
            <div class="pt-field">
                <label class="pt-label">Địa chỉ</label>
                <input type="text" class="pt-input" x-model="form.citizen_address" :readonly="isViewMode" placeholder="...">
            </div>
            <div class="pt-field">
                <label class="pt-label">Dãy nhà</label>
                <select class="pt-input pt-select" x-model="form.building_block" :disabled="isViewMode">
                    <option value="">Chọn dãy nhà...</option>
                    <template x-for="opt in buildingOptions" :key="opt.value">
                        <option :value="opt.value" x-text="opt.label"></option>
                    </template>
                    <option value="Khác">Khác</option>
                </select>
            </div>
            <div class="pt-field">
                <label class="pt-label">Cán bộ tiếp nhận</label>
                <input type="text" class="pt-input" x-model="form.recipient" :readonly="isViewMode" placeholder="...">
            </div>
        </div>
    </div>

    {{-- NỘI DUNG ĐƠN --}}
    <div class="space-y-4 border-l-2 border-slate-200 pl-6 md:pl-8">
        <button type="button" class="flex w-full items-center justify-between text-left" @click="ptSections.content = !ptSections.content">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-orange-50 p-2">
                    <svg class="h-5 w-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <h2 class="font-bold uppercase leading-none text-slate-900">NỘI DUNG ĐƠN</h2>
                    <p class="mt-1 text-[10px] italic text-slate-500">(Tóm tắt vụ việc và hướng xử lý)</p>
                </div>
            </div>
            <svg class="h-5 w-5 text-slate-400 transition-transform" :class="ptSections.content ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div x-show="ptSections.content" x-cloak class="space-y-8 pt-2">
            <div class="overflow-x-auto rounded-lg border border-slate-200 shadow-sm">
                <table class="w-full min-w-[600px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50">
                            <th class="w-[60%] border-r border-slate-200 py-4 text-center font-bold text-slate-900">Tóm tắt nội dung vụ việc</th>
                            <th class="w-[25%] border-r border-slate-200 py-4 text-center font-bold text-slate-900">Phân loại đơn</th>
                            <th class="w-[15%] py-4 text-center font-bold text-slate-900">Số người</th>
                        </tr>
                        <tr class="border-b border-slate-200 bg-slate-50/50">
                            <th class="border-r border-slate-200 py-1 text-center text-[10px] text-slate-400">(4)</th>
                            <th class="border-r border-slate-200 py-1 text-center text-[10px] text-slate-400">(5)</th>
                            <th class="py-1 text-center text-[10px] text-slate-400">(6)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border-r border-slate-200 p-0 align-top">
                                <textarea class="min-h-[120px] w-full resize-none border-0 bg-transparent p-4 text-sm font-bold leading-relaxed text-slate-800 placeholder:text-slate-300 focus:ring-0"
                                          placeholder="Nhập tóm tắt..."
                                          x-model="form.summary"
                                          :readonly="isViewMode"
                                          required></textarea>
                            </td>
                            <td class="border-r border-slate-200 p-3 align-top">
                                <select class="h-8 w-full border-0 bg-transparent p-0 text-sm font-bold text-slate-800 shadow-none focus:ring-0"
                                        x-model="form.petition_type"
                                        :disabled="isViewMode">
                                    <option value="Khiếu nại">Khiếu nại</option>
                                    <option value="Tố cáo">Tố cáo</option>
                                    <option value="Kiến nghị">Kiến nghị</option>
                                    <option value="Phản ánh">Phản ánh</option>
                                </select>
                            </td>
                            <td class="p-3 align-top">
                                <input type="number" min="1"
                                       class="h-8 w-full border-0 bg-transparent p-0 text-center text-sm font-bold text-slate-800 shadow-none focus:ring-0"
                                       x-model="form.number_of_people"
                                       :readonly="isViewMode"
                                       placeholder="1">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="overflow-x-auto rounded-lg border border-slate-200 shadow-sm">
                <table class="w-full min-w-[800px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50">
                            <th class="w-[20%] border-r border-slate-200 py-4 text-center font-bold text-slate-900">Cơ quan đã giải quyết (nếu có)</th>
                            <th class="border-r border-slate-200 p-0">
                                <div class="border-b border-slate-200 py-2 text-center font-bold text-slate-900">Hướng xử lý</div>
                                <div class="grid grid-cols-3">
                                    <div class="flex min-h-[60px] items-center justify-center border-r border-slate-200 p-2 text-[10px] leading-tight">Thụ lý để giải quyết</div>
                                    <div class="flex min-h-[60px] items-center justify-center border-r border-slate-200 p-2 text-[10px] leading-tight">Trả lại đơn và hướng dẫn</div>
                                    <div class="flex min-h-[60px] items-center justify-center p-2 text-[10px] leading-tight">Chuyển đơn</div>
                                </div>
                            </th>
                            <th class="w-[25%] border-r border-slate-200 py-4 text-center font-bold text-slate-900">Theo dõi kết quả giải quyết</th>
                            <th class="w-[15%] py-4 text-center font-bold text-slate-900">Ghi chú</th>
                        </tr>
                        <tr class="border-b border-slate-200 bg-slate-50/50">
                            <th class="border-r border-slate-200 py-1 text-center text-[10px] text-slate-400">(7)</th>
                            <th class="border-r border-slate-200 p-0">
                                <div class="grid grid-cols-3">
                                    <div class="border-r border-slate-200 py-1 text-center text-[10px] text-slate-400">(8)</div>
                                    <div class="border-r border-slate-200 py-1 text-center text-[10px] text-slate-400">(9)</div>
                                    <div class="py-1 text-center text-[10px] text-slate-400">(10)</div>
                                </div>
                            </th>
                            <th class="border-r border-slate-200 py-1 text-center text-[10px] text-slate-400">(11)</th>
                            <th class="py-1 text-center text-[10px] text-slate-400">(12)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border-r border-slate-200 p-3 align-top">
                                <textarea class="min-h-[80px] w-full resize-none border-0 bg-transparent p-0 text-sm font-bold leading-tight text-slate-800 placeholder:text-slate-300 focus:ring-0"
                                          x-model="form.previous_authority"
                                          :readonly="isViewMode"
                                          placeholder="..."></textarea>
                            </td>
                            <td class="border-r border-slate-200 p-0 align-top">
                                <div class="grid min-h-[100px] grid-cols-3">
                                    <div class="flex cursor-pointer items-center justify-center border-r border-slate-200 hover:bg-slate-50"
                                         :class="isViewMode && 'cursor-default'"
                                         @click="ptToggleAccepted()">
                                        <span class="pt-check" :class="form.is_accepted && 'pt-check--on'">
                                            <svg x-show="form.is_accepted" class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </span>
                                    </div>
                                    <div class="flex cursor-pointer items-center justify-center border-r border-slate-200 hover:bg-slate-50"
                                         :class="isViewMode && 'cursor-default'"
                                         @click="ptToggleReturned()">
                                        <span class="pt-check" :class="form.is_returned && 'pt-check--on'">
                                            <svg x-show="form.is_returned" class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </span>
                                    </div>
                                    <div class="flex cursor-pointer items-center justify-center hover:bg-slate-50"
                                         :class="isViewMode && 'cursor-default'"
                                         @click="ptToggleForwarded()">
                                        <span class="pt-check" :class="form.is_forwarded && 'pt-check--on'">
                                            <svg x-show="form.is_forwarded" class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="border-r border-slate-200 p-3 align-top">
                                <textarea class="min-h-[100px] w-full resize-none border-0 bg-transparent p-0 text-sm font-bold leading-tight text-slate-800 placeholder:text-slate-300 focus:ring-0"
                                          x-model="form.resolution_follow_up"
                                          :readonly="isViewMode"
                                          placeholder="..."></textarea>
                            </td>
                            <td class="p-3 align-top">
                                <textarea class="min-h-[100px] w-full resize-none border-0 bg-transparent p-0 text-sm font-bold italic leading-tight text-slate-800 placeholder:text-slate-300 focus:ring-0"
                                          x-model="form.note"
                                          :readonly="isViewMode"
                                          placeholder="..."></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-show="ptCameraOpen" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center bg-black/90 p-4" @keydown.escape.window="ptCloseCamera()">
        <div class="w-full max-w-md bg-slate-900 rounded-2xl overflow-hidden shadow-2xl border border-white/10" @click.outside="ptCloseCamera()">
            <div class="p-4 border-b border-white/10 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <h3 class="text-white font-semibold whitespace-nowrap">Camera</h3>
                    <template x-if="ptCameras.length > 1">
                        <select x-model="ptSelectedCameraId" @change="ptSwitchCamera()" class="h-8 rounded-md bg-slate-800 border-white/20 text-white text-xs max-w-[200px]">
                            <template x-for="cam in ptCameras" :key="cam.deviceId">
                                <option :value="cam.deviceId" x-text="cam.label || 'Camera ' + ($index + 1)"></option>
                            </template>
                        </select>
                    </template>
                </div>
                <button type="button" @click="ptCloseCamera()" class="text-white/70 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="relative bg-black aspect-[3/4] sm:aspect-square flex items-center justify-center">
                <video x-ref="ptVideo" autoplay playsinline muted class="w-full h-full object-cover"></video>
                <div class="absolute inset-0 border-2 border-dashed border-white/30 pointer-events-none m-4 rounded-lg"></div>
            </div>
            <div class="p-4 flex justify-center bg-slate-900">
                <button type="button" @click="ptCapturePhoto()" class="flex items-center gap-2 bg-[#D04B14] hover:bg-orange-600 text-white px-6 py-3 rounded-full font-bold shadow-lg transition transform hover:scale-105 active:scale-95">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"></circle></svg>
                    CHỤP & ĐỌC BẰNG AI
                </button>
            </div>
            <canvas x-ref="ptCanvas" class="hidden"></canvas>
        </div>
    </div>
</div>
