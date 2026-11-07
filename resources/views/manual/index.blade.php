@extends('layouts.nttu')
@section('title', 'Hướng dẫn sử dụng - Hệ thống KTNB NTTU')

@section('content')
<div x-data="manualApp()" class="space-y-4">
    <!-- Thanh tiêu đề & Tác vụ hướng dẫn -->
    <div class="nttu-card p-4 sm:p-6 bg-gradient-to-r from-blue-900 via-indigo-900 to-blue-950 text-white shadow-lg rounded-xl flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-400 text-slate-900 shadow-md">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl sm:text-2xl font-black uppercase tracking-wide text-white">Cẩm Nang Hướng Dẫn Sử Dụng</h1>
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-400/20 border border-amber-300/40 px-2.5 py-0.5 text-xs font-bold text-amber-300">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Admin Editor
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-blue-200 mt-0.5">
                    Hệ thống Quản trị & Giám sát Kiểm tra Nội bộ - Đại học Nguyễn Tất Thành • 
                    <span>Cập nhật: <strong x-text="updatedAtDisplay">{{ $updatedAt }}</strong></span>
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Nút Lưu Nhanh Trên Header -->
            <button
                type="button"
                @click="saveFromContent()"
                :disabled="isSaving"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-white font-bold text-xs sm:text-sm shadow-md transition"
                title="Lưu tất cả nội dung vừa sửa trực tiếp trên trang"
            >
                <svg x-show="!isSaving" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                <svg x-show="isSaving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span x-text="isSaving ? 'Đang lưu...' : '💾 Lưu thay đổi (Ctrl+S)'"></span>
            </button>

            <!-- Nút Mở Trình Soạn Thảo Markdown -->
            <button
                type="button"
                @click="openMarkdownModal()"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs sm:text-sm shadow transition"
                title="Mở trình soạn thảo mã nguồn Markdown chuyên sâu"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                </svg>
                <span>Soạn thảo Markdown</span>
            </button>

            <!-- Nút Tải file MD -->
            <a href="{{ route('manual.download') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-white font-medium text-xs sm:text-sm border border-white/20 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Tải .md</span>
            </a>

            <!-- Nút In / Xuất PDF -->
            <button type="button" @click="window.print()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-white font-medium text-xs sm:text-sm border border-white/20 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>In / PDF</span>
            </button>
        </div>
    </div>

    <!-- BẢNG ĐIỀU KHIỂN QUẢN TRỊ VIÊN RÕ RÀNG NGAY ĐẦU NỘI DUNG -->
    <div class="p-4 sm:p-5 rounded-xl bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 text-slate-950 shadow-md border-2 border-amber-300">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-950 text-amber-300 font-black text-2xl shadow">
                    ✏️
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-black uppercase tracking-wide text-slate-950">
                        Chế độ Quản trị: Sửa Chữ & Thay Hình Trực Tiếp Trên Trang
                    </h2>
                    <ul class="text-xs sm:text-sm font-semibold text-slate-900 mt-1 space-y-0.5">
                        <li>• <strong>Sửa chữ trực tiếp:</strong> Nhấp chuột thẳng vào bất kỳ tiêu đề, đoạn văn, danh sách bước nào bên dưới để gõ, xóa, viết thêm nội dung.</li>
                        <li>• <strong>Thay hình trực tiếp:</strong> Nhấp vào nút <strong>"📷 Thay ảnh này"</strong> đè trên mỗi bức ảnh (hoặc kéo thả ảnh từ máy tính thả thẳng vào vị trí ảnh đó).</li>
                        <li>• <strong>Lưu lại:</strong> Nhấn nút <strong>"LƯU THAY ĐỔI NGAY"</strong> (hoặc bấm <code>Ctrl + S</code>) để hệ thống tự động lưu file gốc trên máy chủ.</li>
                    </ul>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button
                    type="button"
                    @click="saveFromContent()"
                    :disabled="isSaving"
                    class="px-5 py-3 rounded-xl bg-slate-950 hover:bg-slate-900 disabled:opacity-50 text-amber-300 font-black text-sm shadow-xl flex items-center gap-2 transform active:scale-95 transition"
                >
                    <svg x-show="!isSaving" class="h-5 w-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    <span x-text="isSaving ? 'Đang lưu...' : '💾 LƯU THAY ĐỔI NGAY'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Khung Nội dung Tài liệu (Hỗ trợ nhấp chuột sửa trực tiếp) -->
    <div class="nttu-card p-4 sm:p-8 md:p-10 bg-white rounded-xl shadow-sm border border-slate-200 relative">
        <div
            id="manual-content"
            class="manual-prose max-w-none text-slate-800 leading-relaxed"
            x-html="htmlContent"
        ></div>
    </div>

    <!-- Input tệp ẩn để tải ảnh thay thế -->
    <input
        type="file"
        id="image-file-input"
        class="hidden"
        accept="image/png,image/jpeg,image/webp,image/gif"
        @change="handleImageSelected($event)"
    >

    <!-- THANH NỔI LƯU THAY ĐỔI CỐ ĐỊNH Ở GÓC DƯỚI (LUÔN ĐI THEO KHI CUỘN) -->
    <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-2 bg-slate-950/90 backdrop-blur-md text-white px-4 py-2.5 rounded-full shadow-2xl border border-amber-400/50">
        <span class="flex h-2.5 w-2.5 relative">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
        </span>
        <span class="text-xs sm:text-sm font-semibold text-amber-300">Nhấp vào chữ để sửa • Nhấp vào ảnh để đổi</span>
        <button
            type="button"
            @click="saveFromContent()"
            :disabled="isSaving"
            class="ml-2 px-3 py-1.5 rounded-full bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-white font-black text-xs shadow transition flex items-center gap-1.5"
        >
            <span x-text="isSaving ? 'Đang lưu...' : '💾 Lưu nhanh'"></span>
        </button>
    </div>

    <!-- MODAL SOẠN THẢO MARKDOWN TOÀN DIỆN (NẾU MUỐN SỬA MÃ NGUỒN) -->
    <div
        x-show="showEditorModal"
        x-cloak
        class="fixed inset-0 z-[120] flex items-center justify-center bg-black/75 p-2 sm:p-4 backdrop-blur-sm"
        @keydown.escape.window="if (!isSaving) showEditorModal = false"
    >
        <div class="relative flex flex-col h-[94vh] w-[98vw] max-w-7xl rounded-xl bg-white shadow-2xl overflow-hidden border border-slate-300">
            <!-- Header Modal -->
            <div class="flex items-center justify-between px-4 py-3 bg-slate-900 text-white shrink-0">
                <div class="flex items-center gap-3">
                    <div class="p-1.5 bg-amber-400 text-slate-950 rounded-lg">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-base sm:text-lg">Trình Soạn Thảo Toàn Văn (Mã Nguồn Markdown)</h2>
                        <p class="text-xs text-slate-300">Sửa trực tiếp từng bước, từng phân hệ, thêm bảng biểu hoặc hộp lưu ý</p>
                    </div>
                </div>

                <!-- Nút tác vụ lưu & đóng -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="saveManual(editorMarkdown)"
                        :disabled="isSaving"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold text-xs sm:text-sm shadow transition"
                    >
                        <svg x-show="!isSaving" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        <svg x-show="isSaving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span x-text="isSaving ? 'Đang lưu...' : 'Lưu Thay Đổi (Ctrl+S)'"></span>
                    </button>
                    <button
                        type="button"
                        @click="showEditorModal = false"
                        class="p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition"
                        title="Đóng cửa sổ"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <!-- Thanh công cụ định dạng nhanh & Nhảy phân hệ -->
            <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2 bg-slate-100 border-b border-slate-200 text-xs shrink-0">
                <!-- Dropdown nhảy nhanh đến phân hệ -->
                <div class="flex items-center gap-2">
                    <label class="font-bold text-slate-700 whitespace-nowrap">📍 Đi đến phân hệ:</label>
                    <select
                        @change="jumpToSection($event.target.value)"
                        class="rounded border-slate-300 text-xs py-1 px-2.5 font-medium bg-white shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    >
                        <option value="">-- Chọn phân hệ cần xem / sửa --</option>
                        <option value="## 5.2. Giám sát Sinh hoạt Chủ nhiệm">5.2. Sinh hoạt Chủ nhiệm & CVHT</option>
                        <option value="## 5.3. Giám sát Lớp học Trực tuyến">5.3. Lớp học trực tuyến (Online Teams/Zoom)</option>
                        <option value="## 5.4. Giám sát Giảng dạy Trực tiếp">5.4. Giảng dạy trực tiếp & Thực hành ngoài</option>
                        <option value="## 5.1. Bàn giao & Tiếp nhận Tài sản">5.1. Bàn giao & Tiếp nhận tài sản</option>
                        <option value="## 5.5. Khảo sát">5.5. Khảo sát & Đánh giá ý kiến</option>
                        <option value="## 5.6. Quản lý Sinh viên">5.6. Hồ sơ Sinh viên & Lập Biên bản vi phạm</option>
                        <option value="## 5.7. Tổng hợp Báo cáo">5.7. Báo cáo thống kê & Xuất Excel</option>
                        <option value="## 5.8. Thiết lập Hệ thống">5.8. Thiết lập hệ thống & Danh mục</option>
                        <option value="## 1. TỔNG QUAN">1. Tổng quan & Bối cảnh</option>
                        <option value="## 2. CÔNG NGHỆ">2. Công nghệ & Chi phí</option>
                    </select>
                </div>

                <!-- Các nút định dạng nhanh Markdown -->
                <div class="flex flex-wrap items-center gap-1">
                    <button type="button" @click="insertText('**', '**', 'chữ in đậm')" class="px-2 py-1 rounded bg-white hover:bg-slate-200 border border-slate-300 font-bold" title="In đậm">B</button>
                    <button type="button" @click="insertText('*', '*', 'chữ in nghiêng')" class="px-2 py-1 rounded bg-white hover:bg-slate-200 border border-slate-300 italic" title="In nghiêng">I</button>
                    <button type="button" @click="insertLine('### ')" class="px-2 py-1 rounded bg-white hover:bg-slate-200 border border-slate-300 font-semibold" title="Tiêu đề cấp 3">H3</button>
                    <button type="button" @click="insertLine('#### ')" class="px-2 py-1 rounded bg-white hover:bg-slate-200 border border-slate-300 font-semibold" title="Tiêu đề cấp 4 (Bước)">Bước</button>
                    <button type="button" @click="insertLine('- ')" class="px-2 py-1 rounded bg-white hover:bg-slate-200 border border-slate-300" title="Danh sách gạch đầu dòng">• Danh sách</button>
                    <button type="button" @click="insertLine('1. ')" class="px-2 py-1 rounded bg-white hover:bg-slate-200 border border-slate-300" title="Danh sách số thứ tự">1. Thứ tự</button>
                    <button type="button" @click="insertAlert()" class="px-2 py-1 rounded bg-amber-50 hover:bg-amber-100 border border-amber-300 text-amber-900 font-semibold" title="Hộp lưu ý quan trọng">⚠️ Hộp Lưu ý</button>
                    <button type="button" @click="insertTable()" class="px-2 py-1 rounded bg-blue-50 hover:bg-blue-100 border border-blue-300 text-blue-900 font-semibold" title="Chèn bảng">📊 Bảng mẫu</button>
                    <button type="button" @click="triggerUploadForInsert()" class="px-2 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-bold flex items-center gap-1 shadow-sm" title="Tải ảnh từ máy tính chèn vào tài liệu">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>+ Tải ảnh mới chèn vào</span>
                    </button>
                </div>
            </div>

            <!-- Khu vực Soạn thảo Textarea -->
            <div class="flex-1 p-3 overflow-hidden bg-slate-900">
                <textarea
                    id="markdown-textarea"
                    x-model="editorMarkdown"
                    class="w-full h-full p-4 font-mono text-sm bg-slate-950 text-slate-100 rounded-lg border border-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none leading-relaxed"
                    placeholder="Nội dung cẩm nang Markdown..."
                    @keydown.ctrl.s.prevent="saveManual(editorMarkdown)"
                ></textarea>
            </div>

            <!-- Footer Modal -->
            <div class="flex items-center justify-between px-4 py-2.5 bg-slate-100 border-t border-slate-200 text-xs text-slate-600 shrink-0">
                <div class="flex items-center gap-2">
                    <span>💡 <strong>Mẹo:</strong> Nhấn <code>Ctrl + S</code> để lưu nhanh. Hệ thống tự động sao lưu 1 bản dự phòng mỗi khi bấm Lưu.</span>
                </div>
                <div class="flex items-center gap-3">
                    <span x-text="'Độ dài: ' + (editorMarkdown ? editorMarkdown.length.toLocaleString() : 0) + ' ký tự'"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Phóng to Ảnh khi nhấp vào -->
    <div x-show="zoomImg" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center bg-black/85 p-4" @click="zoomImg = null" @keydown.escape.window="zoomImg = null">
        <div class="relative max-h-[95vh] max-w-[95vw] overflow-auto rounded-lg bg-white p-2 shadow-2xl">
            <button type="button" class="absolute top-3 right-3 rounded-full bg-slate-900/70 p-2 text-white hover:bg-slate-900 transition" @click="zoomImg = null">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <img :src="zoomImg" alt="Phóng to minh chứng" class="max-h-[90vh] w-auto rounded object-contain">
        </div>
    </div>

    <!-- Nút Cuộn lên đầu trang -->
    <button
        type="button"
        x-show="showBackToTop"
        x-transition
        @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
        class="fixed bottom-20 right-6 z-40 flex h-11 w-11 items-center justify-center rounded-full bg-[var(--nttu-primary)] text-white shadow-xl hover:bg-blue-800 transition transform hover:scale-110"
        title="Cuộn lên đầu trang"
    >
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
        </svg>
    </button>
</div>

<!-- Nạp thư viện Turndown để chuyển đổi HTML sang Markdown chuẩn xác -->
<script src="{{ asset('js/turndown.js') }}"></script>
<script>
if (typeof TurndownService === 'undefined') {
    document.write('<script src="https://unpkg.com/turndown/dist/turndown.js"><\/script>');
}
</script>

<style>
/* Tùy chỉnh hiển thị Markdown đẹp mắt chuẩn NTTU */
.manual-prose h1 {
    font-size: 1.85rem;
    font-weight: 800;
    color: #003B7A;
    margin-top: 2.25rem;
    margin-bottom: 1.25rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #003B7A;
}
.manual-prose h2 {
    font-size: 1.45rem;
    font-weight: 700;
    color: #003B7A;
    margin-top: 2rem;
    margin-bottom: 1rem;
    padding-bottom: 0.35rem;
    border-bottom: 1px solid #E2E8F0;
}
.manual-prose h3 {
    font-size: 1.2rem;
    font-weight: 700;
    color: #C8102E;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}
.manual-prose h4 {
    font-size: 1.05rem;
    font-weight: 600;
    color: #1E293B;
    margin-top: 1.25rem;
    margin-bottom: 0.5rem;
}
.manual-prose p {
    margin-bottom: 0.85rem;
    line-height: 1.7;
}
.manual-prose ul {
    list-style-type: disc;
    padding-left: 1.75rem;
    margin-bottom: 1rem;
}
.manual-prose ol {
    list-style-type: decimal;
    padding-left: 1.75rem;
    margin-bottom: 1rem;
}
.manual-prose li {
    margin-bottom: 0.35rem;
}
.manual-prose table {
    width: 100%;
    margin-top: 1rem;
    margin-bottom: 1.5rem;
    border-collapse: collapse;
    font-size: 0.875rem;
    border: 1px solid #CBD5E1;
    border-radius: 0.5rem;
    overflow: hidden;
}
.manual-prose th {
    background-color: #F1F5F9;
    color: #0F172A;
    font-weight: 700;
    padding: 0.65rem 0.85rem;
    border: 1px solid #CBD5E1;
    text-align: left;
}
.manual-prose td {
    padding: 0.55rem 0.85rem;
    border: 1px solid #E2E8F0;
    vertical-align: top;
}
.manual-prose tr:nth-child(even) {
    background-color: #F8FAFC;
}
.manual-prose blockquote {
    border-left: 4px solid #F59E0B;
    background-color: #FFFBEB;
    padding: 0.85rem 1.25rem;
    margin: 1.25rem 0;
    border-radius: 0 0.5rem 0.5rem 0;
}
.manual-prose img {
    max-width: 100%;
    height: auto;
    border-radius: 0.75rem;
    border: 1px solid #E2E8F0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    margin: 1rem auto;
    display: block;
    transition: all 0.2s ease;
}

/* Kiểu hiển thị trực quan khi gõ sửa văn bản trực tiếp */
#manual-content [contenteditable="true"]:hover {
    outline: 1px dashed #93C5FD;
    border-radius: 4px;
    background-color: rgba(239, 246, 255, 0.3);
}
#manual-content [contenteditable="true"]:focus {
    outline: 2px solid #2563EB !important;
    border-radius: 4px;
    background-color: rgba(239, 246, 255, 0.6);
}

/* Khung bọc ảnh kèm nút Thay ảnh trực tiếp */
.manual-img-container {
    position: relative;
    display: block;
    width: 100%;
    margin: 1.5rem 0;
}
.manual-img-container img {
    margin: 0 auto;
}
.manual-img-overlay {
    position: absolute;
    top: 14px;
    right: 14px;
    display: flex;
    gap: 8px;
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(4px);
    padding: 6px 12px;
    border-radius: 10px;
    box-shadow: 0 6px 16px rgba(0,0,0,0.3);
    z-index: 20;
}
.manual-img-overlay button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    padding: 5px 10px;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
}
.manual-img-overlay .btn-replace-img {
    background-color: #F59E0B;
    color: #0F172A;
}
.manual-img-overlay .btn-replace-img:hover {
    background-color: #FBBF24;
    transform: scale(1.05);
}
.manual-img-overlay .btn-zoom-img {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
}
.manual-img-overlay .btn-zoom-img:hover {
    background-color: rgba(255, 255, 255, 0.35);
}
.manual-img-container.is-dragover img {
    border: 3px dashed #3B82F6 !important;
    opacity: 0.6;
}

@media print {
    body {
        background: white !important;
    }
    aside, header, button, .manual-img-overlay, .sticky, .fixed {
        display: none !important;
    }
    .nttu-card {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
    }
}
</style>

<script>
function manualApp() {
    return {
        zoomImg: null,
        showBackToTop: false,
        showEditorModal: false,
        isSaving: false,
        updatedAtDisplay: '{{ $updatedAt }}',
        htmlContent: {!! json_encode($htmlContent) !!},
        editorMarkdown: {!! json_encode($rawMarkdown) !!},
        targetOldSrc: null,
        isInsertingNewImage: false,

        init() {
            window.addEventListener('scroll', () => {
                this.showBackToTop = window.scrollY > 400;
            });

            this.$nextTick(() => {
                this.setupDirectEditing();
                this.enhanceImages();
            });

            // Lắng nghe phím tắt Ctrl + S để lưu nhanh
            window.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                    e.preventDefault();
                    if (this.showEditorModal) {
                        this.saveManual(this.editorMarkdown);
                    } else {
                        this.saveFromContent();
                    }
                }
            });
        },

        setupDirectEditing() {
            const container = document.getElementById('manual-content');
            if (!container) return;

            // Bật contenteditable cho tất cả các thẻ chữ để người dùng có thể nhấp chuột gõ sửa trực tiếp
            const editableTags = container.querySelectorAll('h1, h2, h3, h4, h5, p, li, td, th');
            editableTags.forEach(el => {
                el.setAttribute('contenteditable', 'true');
                el.setAttribute('spellcheck', 'false');
            });
        },

        enhanceImages() {
            const container = document.getElementById('manual-content');
            if (!container) return;

            const imgs = container.querySelectorAll('img');
            imgs.forEach(img => {
                const parent = img.parentElement;
                if (parent.classList.contains('manual-img-container')) return;

                const wrapper = document.createElement('div');
                wrapper.className = 'manual-img-container';
                img.parentNode.insertBefore(wrapper, img);
                wrapper.appendChild(img);

                const overlay = document.createElement('div');
                overlay.className = 'manual-img-overlay';

                // Nút thay ảnh
                const replaceBtn = document.createElement('button');
                replaceBtn.type = 'button';
                replaceBtn.className = 'btn-replace-img';
                replaceBtn.innerHTML = `
                    <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>📷 Thay ảnh này</span>
                `;
                replaceBtn.onclick = (e) => {
                    e.stopPropagation();
                    this.triggerImageUpload(img);
                };

                // Nút phóng to ảnh
                const zoomBtn = document.createElement('button');
                zoomBtn.type = 'button';
                zoomBtn.className = 'btn-zoom-img';
                zoomBtn.innerHTML = `
                    <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                    <span>Phóng to</span>
                `;
                zoomBtn.onclick = (e) => {
                    e.stopPropagation();
                    this.zoomImg = img.src;
                };

                overlay.appendChild(replaceBtn);
                overlay.appendChild(zoomBtn);
                wrapper.appendChild(overlay);

                // Kéo & Thả ảnh trực tiếp từ máy tính vào khung ảnh
                wrapper.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    wrapper.classList.add('is-dragover');
                });
                wrapper.addEventListener('dragleave', () => {
                    wrapper.classList.remove('is-dragover');
                });
                wrapper.addEventListener('drop', (e) => {
                    e.preventDefault();
                    wrapper.classList.remove('is-dragover');
                    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                        this.uploadImageFile(e.dataTransfer.files[0], img.getAttribute('src'), img);
                    }
                });
            });
        },

        triggerImageUpload(imgElement) {
            this.targetOldSrc = imgElement.getAttribute('src');
            this.currentImgElement = imgElement;
            this.isInsertingNewImage = false;
            const fileInput = document.getElementById('image-file-input');
            if (fileInput) {
                fileInput.value = '';
                fileInput.click();
            }
        },

        triggerUploadForInsert() {
            this.targetOldSrc = null;
            this.currentImgElement = null;
            this.isInsertingNewImage = true;
            const fileInput = document.getElementById('image-file-input');
            if (fileInput) {
                fileInput.value = '';
                fileInput.click();
            }
        },

        handleImageSelected(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (this.isInsertingNewImage) {
                this.uploadAndInsertImage(file);
            } else {
                this.uploadImageFile(file, this.targetOldSrc, this.currentImgElement);
            }
        },

        async uploadImageFile(file, oldSrc, imgElement) {
            const formData = new FormData();
            formData.append('image', file);
            if (oldSrc) {
                formData.append('old_src', oldSrc);
            }

            const token = document.querySelector('meta[name="csrf-token"]')?.content;

            try {
                const response = await fetch('{{ route("manual.upload_image") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const data = await response.json();
                if (data.success) {
                    if (imgElement) {
                        imgElement.src = data.url;
                        imgElement.setAttribute('src', data.relative_path);
                    }
                    if (oldSrc && this.editorMarkdown) {
                        this.editorMarkdown = this.editorMarkdown.split(oldSrc).join(data.relative_path);
                    }
                    alert('✅ ' + data.message);
                } else {
                    alert('❌ Lỗi tải ảnh: ' + (data.message || 'Không thể upload ảnh'));
                }
            } catch (err) {
                console.error(err);
                alert('❌ Lỗi kết nối máy chủ khi tải ảnh!');
            }
        },

        async uploadAndInsertImage(file) {
            const formData = new FormData();
            formData.append('image', file);
            const token = document.querySelector('meta[name="csrf-token"]')?.content;

            try {
                const response = await fetch('{{ route("manual.upload_image") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const data = await response.json();
                if (data.success) {
                    const tag = `\n\n![Ảnh minh chứng mới](${data.relative_path})\n\n`;
                    this.insertAtCursor(tag);
                    alert('✅ Đã tải ảnh lên và chèn vào tài liệu!');
                } else {
                    alert('❌ Lỗi: ' + (data.message || 'Không thể upload ảnh'));
                }
            } catch (err) {
                console.error(err);
                alert('❌ Lỗi kết nối khi tải ảnh!');
            }
        },

        saveFromContent() {
            const container = document.getElementById('manual-content');
            if (!container) return;

            // Nhân bản node để loại bỏ overlay trước khi chuyển sang Markdown
            const clone = container.cloneNode(true);
            clone.querySelectorAll('.manual-img-overlay').forEach(el => el.remove());

            let markdown = '';
            if (typeof TurndownService !== 'undefined') {
                const turndown = new TurndownService({
                    headingStyle: 'atx',
                    codeBlockStyle: 'fenced',
                    bulletListMarker: '-',
                });
                turndown.addRule('tables', {
                    filter: ['table'],
                    replacement: function (content, node) {
                        return '\n\n' + node.outerHTML + '\n\n';
                    }
                });
                markdown = turndown.turndown(clone);
            } else {
                // Nếu chưa nạp xong Turndown, dùng editorMarkdown hiện có
                markdown = this.editorMarkdown;
            }

            this.saveManual(markdown);
        },

        async saveManual(content) {
            if (this.isSaving) return;
            this.isSaving = true;

            const token = document.querySelector('meta[name="csrf-token"]')?.content;

            try {
                const response = await fetch('{{ route("manual.save") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        content: content,
                    }),
                });

                const data = await response.json();
                if (data.success) {
                    this.htmlContent = data.html;
                    this.editorMarkdown = content;
                    this.updatedAtDisplay = data.updatedAt;
                    this.showEditorModal = false;
                    this.$nextTick(() => {
                        this.setupDirectEditing();
                        this.enhanceImages();
                    });
                    alert('✅ ĐÃ LƯU THAY ĐỔI CẨM NANG HƯỚNG DẪN THÀNH CÔNG!');
                } else {
                    alert('❌ Không thể lưu: ' + (data.message || 'Lỗi không xác định'));
                }
            } catch (err) {
                console.error(err);
                alert('❌ Lỗi kết nối khi lưu tài liệu!');
            } finally {
                this.isSaving = false;
            }
        },

        openMarkdownModal() {
            this.showEditorModal = true;
            this.$nextTick(() => {
                const ta = document.getElementById('markdown-textarea');
                if (ta) ta.focus();
            });
        },

        jumpToSection(headingPrefix) {
            if (!headingPrefix) return;
            const ta = document.getElementById('markdown-textarea');
            if (!ta) return;

            const idx = this.editorMarkdown.indexOf(headingPrefix);
            if (idx !== -1) {
                ta.focus();
                ta.setSelectionRange(idx, idx + headingPrefix.length);
                const fullText = this.editorMarkdown.substring(0, idx);
                const lines = fullText.split('\n').length;
                const lineHeight = 20;
                ta.scrollTop = (lines - 4) * lineHeight;
            } else {
                alert('Không tìm thấy mục: ' + headingPrefix);
            }
        },

        insertText(before, after, defaultText) {
            const ta = document.getElementById('markdown-textarea');
            if (!ta) return;
            const start = ta.selectionStart;
            const end = ta.selectionEnd;
            const selected = ta.value.substring(start, end) || defaultText;
            const replacement = before + selected + after;
            this.editorMarkdown = ta.value.substring(0, start) + replacement + ta.value.substring(end);
            this.$nextTick(() => {
                ta.focus();
                ta.setSelectionRange(start + before.length, start + before.length + selected.length);
            });
        },

        insertLine(prefix) {
            const ta = document.getElementById('markdown-textarea');
            if (!ta) return;
            const start = ta.selectionStart;
            const replacement = '\n' + prefix;
            this.editorMarkdown = ta.value.substring(0, start) + replacement + ta.value.substring(start);
            this.$nextTick(() => {
                ta.focus();
                ta.setSelectionRange(start + replacement.length, start + replacement.length);
            });
        },

        insertAlert() {
            const snippet = '\n\n> [!NOTE]\n> **LƯU Ý NGHIỆP VỤ QUAN TRỌNG:**\n> Ghi nội dung lưu ý chi tiết cho thanh tra viên tại đây...\n\n';
            this.insertAtCursor(snippet);
        },

        insertTable() {
            const snippet = '\n\n| Tiêu chí | Hướng dẫn thao tác | Ghi chú |\n| :--- | :--- | :--- |\n| Bước 1 | Thực hiện tác vụ A | Lưu ý thời gian |\n| Bước 2 | Nhập thông tin B | Bắt buộc |\n\n';
            this.insertAtCursor(snippet);
        },

        insertAtCursor(text) {
            const ta = document.getElementById('markdown-textarea');
            if (!ta) return;
            const start = ta.selectionStart;
            const end = ta.selectionEnd;
            this.editorMarkdown = ta.value.substring(0, start) + text + ta.value.substring(end);
            this.$nextTick(() => {
                ta.focus();
                ta.setSelectionRange(start + text.length, start + text.length);
            });
        }
    };
}
</script>
@endsection
