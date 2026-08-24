{{-- Modals for student violation form (scanner, photo, signature) --}}
<div>
    {{-- QR / Barcode scanner modal (live scan) --}}
    <div x-show="violationScannerOpen" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center bg-black/80 p-4"
         @keydown.escape.window="violationCloseScanner()" @click.self="violationCloseScanner()">
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-slate-950 shadow-2xl" @click.stop>
            <div class="border-b border-white/10 px-4 py-3">
                <h4 class="font-semibold text-white">Quét thẻ (TSV/CCCD)</h4>
                <p class="mt-1 text-xs text-white/70">Đưa thẻ vào khung hình và bấm <b>CHỤP VÀ ĐỌC BẰNG AI</b> để hệ thống tự động nhận diện thông tin (hỗ trợ cả Thẻ sinh viên và CCCD).</p>
            </div>

            <div class="p-4">
                <div class="violation-scanner-panel relative mx-auto w-full max-w-sm cursor-crosshair overflow-hidden rounded-3xl border-4 bg-black shadow-2xl"
                     :class="violationScannerSuccess ? 'border-green-400' : (violationScannerFocusActive ? 'border-cyan-400' : 'border-white/20')"
                     style="aspect-ratio: 4/3"
                     title="Chạm vào khung hình để lấy nét"
                     @click="violationTapToFocus($event)">
                    <div id="violation-qr-reader" class="violation-qr-reader absolute inset-0 h-full w-full"></div>
                    <div x-show="violationScannerFocusBox && !violationScannerLoading && !violationScannerDecoding && !violationScannerExtracting" x-cloak
                         class="pointer-events-none absolute z-[5] rounded-md border-2 border-green-400 shadow-[0_0_16px_rgba(74,222,128,0.85)] transition-all duration-150"
                         :class="violationScannerFocusActive ? 'animate-pulse' : ''"
                         :style="violationScannerFocusStyle()"></div>
                    <div x-show="!violationScannerLoading && !violationScannerSuccess && !violationScannerDecoding && !violationScannerExtracting" x-cloak
                         class="pointer-events-none absolute inset-0 overflow-hidden">
                        <div class="violation-scan-line absolute left-[10%] right-[10%] h-0.5 bg-green-400"></div>
                    </div>
                    <div x-show="violationScannerFocusActive && !violationScannerLoading && !violationScannerDecoding && !violationScannerExtracting" x-cloak
                         class="pointer-events-none absolute bottom-2 left-0 right-0 z-[6] text-center">
                        <span class="rounded-full bg-green-500/90 px-3 py-1 text-[10px] font-medium text-white">Đã nhận mã — đang đọc...</span>
                    </div>
                    <div x-show="violationScannerLoading || violationScannerDecoding || violationScannerExtracting" x-cloak
                         class="absolute inset-0 z-10 flex items-center justify-center bg-black/60">
                        <p class="rounded-full bg-black/70 px-4 py-2 text-sm text-white"
                           x-text="violationScannerExtracting ? 'Đang trích xuất thông tin...' : (violationScannerDecoding ? 'Đang đọc mã từ ảnh...' : 'Đang mở camera...')"></p>
                    </div>
                </div>
            </div>

            <div x-show="violationScannerError" x-cloak class="border-t border-rose-500/30 bg-rose-950/40 px-4 py-3 text-sm text-rose-200" x-text="violationScannerError"></div>

            <div class="flex flex-col gap-2 border-t border-white/10 px-4 py-3">
                <div class="flex flex-wrap items-center gap-2 w-full">
                    <button type="button"
                            class="flex-1 inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-white/20 bg-white/5 px-3 text-sm font-medium text-white transition hover:bg-sky-500/20 disabled:opacity-50"
                            :disabled="violationScannerLoading || violationScannerDecoding || violationScannerExtracting"
                            @click.stop="violationToggleScannerCamera()">
                        <x-form-field-icon name="refresh" tone="sky" class="h-4 w-4" />
                        Đổi Camera
                    </button>
                    <label class="flex-1 inline-flex h-10 cursor-pointer items-center justify-center gap-2 rounded-lg border border-white/20 bg-white/5 px-3 text-sm font-medium text-white transition hover:bg-green-500/20"
                           :class="violationScannerLoading || violationScannerDecoding || violationScannerExtracting ? 'opacity-50 pointer-events-none' : ''">
                        <x-form-field-icon name="upload" tone="green" class="h-4 w-4" />
                        Tải ảnh lên
                        <input type="file" accept="image/*" class="hidden" @change="violationScanFromFile($event)" :disabled="violationScannerLoading || violationScannerDecoding || violationScannerExtracting">
                    </label>
                </div>
                <div class="flex w-full gap-2">
                    <button type="button"
                            class="flex-1 inline-flex h-12 items-center justify-center gap-2 rounded-lg border-2 border-violet-500 bg-violet-600 px-4 text-sm font-bold text-white shadow-lg shadow-violet-500/20 transition hover:bg-violet-500 disabled:opacity-50"
                            :disabled="violationScannerLoading || violationScannerDecoding || violationScannerExtracting"
                            @click="violationCaptureAndExtractFromCamera()">
                        <x-form-field-icon name="camera" tone="white" class="h-5 w-5" />
                        CHỤP VÀ ĐỌC BẰNG AI
                    </button>
                    <button type="button"
                            class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-white/20 bg-rose-500/20 transition hover:bg-rose-500/40"
                            title="Đóng"
                            @click="violationCloseScanner()">
                        <x-form-field-icon name="close" tone="destructive" class="h-6 w-6 text-rose-400" />
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Photo capture modal --}}
    <div x-show="violationPhotoCameraOpen" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center bg-black/80 p-4" @keydown.escape.window="violationClosePhotoCamera()" @click.self="violationClosePhotoCamera()">
        <div class="w-full max-w-md overflow-hidden rounded-xl bg-slate-900" @click.stop>
            <div class="border-b border-white/10 px-4 py-3">
                <h4 class="font-semibold text-white" x-text="violationPhotoCameraLabel"></h4>
                <p class="mt-1 text-xs text-white/70" x-show="violationPhotoCameraField === 'document_photo'" x-cloak>
                    Chụp ảnh giấy tờ rõ nét. Hệ thống sẽ tự đọc mã QR/barcode và điền thông tin sinh viên.
                </p>
            </div>
            <div class="relative aspect-square w-full touch-none overflow-hidden md:aspect-video"
                 @pointermove="if (violationPhotoDragging) { const rect = $el.getBoundingClientRect(); const x = ($event.clientX - rect.left) / rect.width; const y = ($event.clientY - rect.top) / rect.height; violationPhotoFrameX = Math.min(Math.max(x, violationPhotoFrameW/2), 1 - violationPhotoFrameW/2); violationPhotoFrameY = Math.min(Math.max(y, violationPhotoFrameH/2), 1 - violationPhotoFrameH/2); }"
                 @pointerup="violationPhotoDragging = false"
                 @pointerleave="violationPhotoDragging = false">
                <video x-ref="violationPhotoVideo" autoplay playsinline muted class="h-full w-full object-cover"></video>
                <div class="absolute border-2 border-dashed border-white/80 shadow-[0_0_0_1000px_rgba(0,0,0,0.5)]"
                     :class="violationPhotoCameraLabel.toLowerCase().includes('chân dung') ? 'rounded-[50%]' : 'rounded-lg'"
                     :style="`width:${violationPhotoFrameW*100}%;height:${violationPhotoFrameH*100}%;left:${(violationPhotoFrameX - violationPhotoFrameW/2)*100}%;top:${(violationPhotoFrameY - violationPhotoFrameH/2)*100}%`"
                     @pointerdown.stop="violationPhotoDragging = true"></div>
            </div>
            <div class="flex justify-end gap-3 border-t border-white/10 p-4">
                <x-nttu-button type="button" action="cancel" class="border-white/20 text-white" @click="violationClosePhotoCamera()">Hủy</x-nttu-button>
                <button type="button" class="inline-flex items-center gap-2 rounded-md bg-rose-600 px-4 py-2 text-sm text-white hover:bg-rose-700 disabled:opacity-50"
                        :disabled="violationDocumentExtracting"
                        @click="violationCapturePhoto()">
                    <x-form-field-icon name="camera" tone="rose" class="h-4 w-4 text-white" />
                    <span x-text="violationPhotoCameraField === 'document_photo' ? 'Chụp và trích xuất' : 'Chụp ảnh'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Signature pad modal --}}
    <div x-show="violationSignatureOpen" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="violationSignatureOpen = false">
        <div class="w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-2xl" @click.stop>
            <div class="border-b px-4 py-3">
                <h4 class="font-semibold text-gray-900">Ký tên xác nhận</h4>
            </div>
            <div class="bg-white p-4">
                <canvas x-ref="violationSignatureCanvas" width="800" height="300" class="w-full touch-none rounded-lg border border-gray-200 bg-white"
                        @mousedown="violationStartSignature($event)"
                        @mousemove="violationDrawSignature($event)"
                        @mouseup="violationStopSignature()"
                        @mouseleave="violationStopSignature()"
                        @touchstart.prevent="violationStartSignature($event)"
                        @touchmove.prevent="violationDrawSignature($event)"
                        @touchend.prevent="violationStopSignature()"></canvas>
            </div>
            <div class="flex justify-end gap-2 border-t px-4 py-3">
                <x-nttu-button type="button" action="clear" @click="violationClearSignatureCanvas()">Xóa</x-nttu-button>
                <x-nttu-button type="button" action="cancel" @click="violationSignatureOpen = false">Hủy</x-nttu-button>
                <x-nttu-button type="button" action="confirm" @click="violationConfirmSignature()">Xác nhận</x-nttu-button>
            </div>
        </div>
    </div>
</div>
