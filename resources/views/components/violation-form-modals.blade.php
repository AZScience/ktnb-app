{{-- Modals for student violation form (scanner, photo, signature) --}}
<div>
    {{-- QR / Barcode scanner modal (live scan) --}}
    <div x-show="violationScannerOpen" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center bg-black/80 p-4"
         @keydown.escape.window="violationCloseScanner()" @click.self="violationCloseScanner()">
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-slate-950 shadow-2xl" @click.stop>
            <div class="border-b border-white/10 px-4 py-3">
                <h4 class="font-semibold text-white">Quét thẻ (TSV/CCCD)</h4>
                <p class="mt-1 text-xs text-white/70">Tự động bám và lấy nét mã QR/Barcode. Nếu không quét được: chụp ảnh để quét mã (hồng) hoặc trích xuất thông tin trực tiếp (tím).</p>
            </div>

            <div class="p-4">
                <div class="violation-scanner-panel relative mx-auto w-full max-w-sm cursor-crosshair overflow-hidden rounded-3xl border-4 bg-black shadow-2xl"
                     :class="violationScannerSuccess ? 'border-green-400' : (violationScannerFocusActive ? 'border-cyan-400' : 'border-white/20')"
                     :style="violationScannerMode === 'barcode' ? 'aspect-ratio: 16/9' : 'aspect-ratio: 1/1'"
                     title="Chạm vào mã QR/Barcode để lấy nét"
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

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-white/10 px-4 py-3">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border transition"
                            :class="violationScannerMode === 'qr' ? 'border-cyan-400 bg-cyan-500/25' : 'border-white/20 bg-white/5 hover:bg-white/10'"
                            title="Quét QR"
                            @click="violationSetScannerMode('qr')">
                        <svg class="h-5 w-5" :class="violationScannerMode === 'qr' ? 'text-cyan-300' : 'text-cyan-400'" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm13-2h3v3h-3v-3zm-4 0h3v3h-3v-3zm4 4h3v3h-3v-3zm-4 0h3v3h-3v-3z"/>
                        </svg>
                    </button>
                    <button type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border transition"
                            :class="violationScannerMode === 'barcode' ? 'border-amber-400 bg-amber-500/25' : 'border-white/20 bg-white/5 hover:bg-white/10'"
                            title="Quét Barcode"
                            @click="violationSetScannerMode('barcode')">
                        <svg class="h-5 w-5" :class="violationScannerMode === 'barcode' ? 'text-amber-300' : 'text-amber-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-width="2" d="M4 7h1v10H4V7zm3 0h1v10H7V7zm3 0h2v10h-2V7zm4 0h1v10h-1V7zm3 0h2v10h-2V7z"/>
                        </svg>
                    </button>
                    <button type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 bg-white/5 transition hover:bg-sky-500/20 disabled:opacity-50"
                            title="Đổi camera"
                            :disabled="violationScannerLoading || violationScannerDecoding || violationScannerExtracting"
                            @click.stop="violationToggleScannerCamera()">
                        <x-form-field-icon name="refresh" tone="sky" class="h-5 w-5" />
                    </button>
                    <label class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg border border-white/20 bg-white/5 transition hover:bg-green-500/20"
                           title="Tải ảnh">
                        <x-form-field-icon name="upload" tone="green" class="h-5 w-5" />
                        <input type="file" accept="image/*" class="hidden" @change="violationScanFromFile($event)">
                    </label>
                    <button type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-400/50 bg-rose-500/15 transition hover:bg-rose-500/30 disabled:opacity-50"
                            title="Chụp và quét mã QR/Barcode"
                            :disabled="violationScannerLoading || violationScannerDecoding || violationScannerExtracting"
                            @click="violationCaptureAndScanFromCamera()">
                        <x-form-field-icon name="camera" tone="rose" class="h-5 w-5" />
                    </button>
                    <button type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-violet-400/50 bg-violet-500/15 transition hover:bg-violet-500/30 disabled:opacity-50"
                            title="Chụp và trích xuất thông tin (không cần mã)"
                            :disabled="violationScannerLoading || violationScannerDecoding || violationScannerExtracting"
                            @click="violationCaptureAndExtractFromCamera()">
                        <x-form-field-icon name="camera" tone="violet" class="h-5 w-5" />
                    </button>
                </div>
                <button type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 bg-white/5 transition hover:bg-rose-500/20"
                        title="Đóng"
                        @click="violationCloseScanner()">
                    <x-form-field-icon name="close" tone="destructive" class="h-5 w-5" />
                </button>
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
