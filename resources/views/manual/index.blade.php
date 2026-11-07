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
                        Admin
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-blue-200 mt-0.5">
                    Hệ thống Quản trị & Giám sát Kiểm tra Nội bộ - Đại học Nguyễn Tất Thành • 
                    <span>Cập nhật: <strong x-text="updatedAtDisplay">{{ $updatedAt }}</strong></span>
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- NÚT BẬT / TẮT CHẾ ĐỘ CHỈNH SỬA -->
            <button
                type="button"
                @click="toggleEditMode()"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg font-bold text-xs sm:text-sm shadow-md transition transform active:scale-95"
                :class="editMode ? 'bg-emerald-500 hover:bg-emerald-400 text-white ring-2 ring-emerald-300' : 'bg-amber-500 hover:bg-amber-400 text-slate-950 ring-1 ring-amber-300'"
                :title="editMode ? 'Bấm để tắt chế độ chỉnh sửa (trở về chế độ xem)' : 'Bấm để bật chế độ chỉnh sửa: thêm chữ, sửa chữ, thêm ảnh, thay ảnh'"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span x-text="editMode ? 'Đang bật Sửa (Bấm để Tắt)' : '✏️ Bật Chế Độ Sửa / Thêm'"></span>
            </button>

            <!-- Nút Lưu khi đang sửa (hiện khi editMode = true) -->
            <button
                type="button"
                x-show="editMode"
                x-cloak
                @click="saveFromContent()"
                :disabled="isSaving"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold text-xs sm:text-sm shadow-md transition"
                title="Lưu tất cả nội dung vừa sửa, thêm chữ, thêm ảnh trực tiếp"
            >
                <svg x-show="!isSaving" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                <svg x-show="isSaving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span x-text="isSaving ? 'Đang lưu...' : '💾 Lưu thay đổi (Ctrl+S)'"></span>
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

    <!-- BANNER HƯỚNG DẪN KHI ĐANG BẬT CHẾ ĐỘ SỬA TRỰC TIẾP -->
    <div x-show="editMode" x-cloak class="p-4 sm:p-5 rounded-xl bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 text-slate-950 shadow-md border-2 border-amber-300 transition">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-950 text-amber-300 font-black text-2xl shadow">
                    ✏️
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-black uppercase tracking-wide text-slate-950">
                        Chế độ Chỉnh sửa & Bổ sung Vị trí Bất kỳ Đang Bật
                    </h2>
                    <ul class="text-xs sm:text-sm font-semibold text-slate-900 mt-1 space-y-0.5">
                        <li>• <strong>Nhấp vào bất kỳ đoạn nào:</strong> Xuất hiện thanh công cụ nhỏ cho phép <strong>Thêm trước</strong>, <strong>Thêm sau</strong> hoặc <strong>Chèn ảnh tại vị trí đó</strong>!</li>
                        <li>• <strong>Dán ảnh trực tiếp:</strong> Chụp màn hình (nhấn <code>Win + Shift + S</code>), nhấp vào vị trí bất kỳ rồi nhấn <strong>Ctrl + V</strong> để dán ảnh tức thì!</li>
                        <li>• <strong>Lưu lại:</strong> Nhấn <strong>"LƯU THAY ĐỔI NGAY"</strong> (hoặc nhấn <code>Ctrl + S</code>).</li>
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
                <button
                    type="button"
                    @click="toggleEditMode()"
                    class="px-3.5 py-3 rounded-xl bg-white hover:bg-slate-100 text-slate-800 font-bold text-xs sm:text-sm border border-slate-300 shadow transition"
                >
                    ❌ Tắt sửa
                </button>
            </div>
        </div>
    </div>

    <!-- Khung Nội dung Tài liệu -->
    <div class="nttu-card p-4 sm:p-8 md:p-10 bg-white rounded-xl shadow-sm border border-slate-200 relative">
        <div
            id="manual-content"
            class="manual-prose max-w-none text-slate-800 leading-relaxed"
            x-html="htmlContent"
        ></div>
    </div>

    <!-- Input tệp ẩn để tải ảnh thay thế hoặc chèn mới -->
    <input
        type="file"
        id="image-file-input"
        class="hidden"
        accept="image/png,image/jpeg,image/webp,image/gif"
        @change="handleImageSelected($event)"
    >

    <!-- THANH CÔNG CỤ NHỎ ĐI THEO KHỐI ĐANG CHỌN (MINI BLOCK TOOLBAR) -->
    <div
        id="block-toolbar"
        x-show="editMode && activeBlockNode"
        x-cloak
        class="fixed z-[60] flex items-center gap-1 bg-slate-950 text-white p-1.5 rounded-lg shadow-2xl border border-amber-400 text-xs transition-all duration-150"
        :style="blockToolbarStyle"
    >
        <span class="text-[11px] font-bold text-amber-300 px-1.5">Khối đang chọn:</span>
        <button
            type="button"
            @click="insertRelative('before', 'text')"
            class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold"
            title="Thêm đoạn văn bản mới vào ngay phía trên khối này"
        >
            ⬆️ Chèn chữ trước
        </button>
        <button
            type="button"
            @click="insertRelative('after', 'text')"
            class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold"
            title="Thêm đoạn văn bản mới vào ngay phía dưới khối này"
        >
            ⬇️ Chèn chữ sau
        </button>
        <button
            type="button"
            @click="insertRelative('after', 'image')"
            class="px-2.5 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-bold flex items-center gap-1 shadow-sm"
            title="Tải ảnh từ máy tính chèn vào ngay sau khối này"
        >
            <svg style="width:13px;height:13px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>🖼️ + Chèn ảnh tại đây</span>
        </button>
        <button
            type="button"
            @click="insertRelative('after', 'step')"
            class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold"
            title="Thêm một bước thực hiện mới (#### Bước ...)"
        >
            📋 + Bước mới
        </button>
        <button
            type="button"
            @click="insertRelative('after', 'note')"
            class="px-2 py-1 rounded bg-amber-500/30 hover:bg-amber-500/50 text-amber-200 font-semibold"
            title="Thêm một hộp lưu ý nghiệp vụ"
        >
            ⚠️ + Lưu ý
        </button>
        <button
            type="button"
            @click="deleteActiveBlock()"
            class="px-2 py-1 rounded bg-red-600/80 hover:bg-red-600 text-white font-semibold"
            title="Xóa khối nội dung này"
        >
            🗑️ Xóa
        </button>
        <button
            type="button"
            @click="activeBlockNode = null"
            class="p-1 rounded text-slate-400 hover:text-white"
            title="Đóng thanh công cụ"
        >
            ✕
        </button>
    </div>

    <!-- THANH NỔI LƯU THAY ĐỔI (CHỈ HIỆN KHI ĐANG BẬT SỬA) -->
    <div x-show="editMode" x-cloak class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-2 bg-slate-950/90 backdrop-blur-md text-white px-4 py-2.5 rounded-full shadow-2xl border border-amber-400/50">
        <span class="flex h-2.5 w-2.5 relative">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
        </span>
        <span class="text-xs sm:text-sm font-semibold text-emerald-300">Đang bật Sửa / Thêm</span>
        <button
            type="button"
            @click="saveFromContent()"
            :disabled="isSaving"
            class="ml-2 px-3.5 py-1.5 rounded-full bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-white font-black text-xs shadow transition flex items-center gap-1.5"
        >
            <span x-text="isSaving ? 'Đang lưu...' : '💾 Lưu thay đổi (Ctrl+S)'"></span>
        </button>
        <button
            type="button"
            @click="toggleEditMode()"
            class="ml-1 px-2.5 py-1 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs"
        >
            Đóng
        </button>
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
.manual-prose a {
    color: #0284C7;
    text-decoration: underline;
    cursor: pointer;
    transition: color 0.15s ease;
}
.manual-prose a:hover {
    color: #0369A1;
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
    cursor: zoom-in;
    transition: all 0.2s ease;
}

/* Kiểu hiển thị trực quan khi gõ sửa văn bản trực tiếp */
.manual-editing-active [contenteditable="true"]:hover {
    outline: 1px dashed #93C5FD;
    border-radius: 4px;
    background-color: rgba(239, 246, 255, 0.35);
}
.manual-editing-active [contenteditable="true"]:focus,
.manual-editing-active .manual-active-block {
    outline: 2px solid #2563EB !important;
    border-radius: 4px;
    background-color: rgba(239, 246, 255, 0.65);
}

/* Khung bọc ảnh kèm nút Thay ảnh & Xóa ảnh trực tiếp */
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
    background: rgba(15, 23, 42, 0.88);
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
.manual-img-overlay .btn-delete-img {
    background-color: #EF4444;
    color: white;
}
.manual-img-overlay .btn-delete-img:hover {
    background-color: #DC2626;
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

/* Khung Chèn Thêm Chữ & Thêm Ảnh Nhanh Giữa Các Phần */
.manual-insert-bar {
    margin: 1.25rem 0;
    padding: 0.6rem 0.85rem;
    border-radius: 0.6rem;
    border: 2px dashed #CBD5E1;
    background-color: #F8FAFC;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    font-size: 0.75rem;
    transition: all 0.2s ease;
}
.manual-insert-bar:hover {
    border-color: #F59E0B;
    background-color: #FFFBEB;
}
.manual-insert-bar button {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 700;
    cursor: pointer;
    border: 1px solid #CBD5E1;
    background-color: white;
    color: #1E293B;
    transition: all 0.15s ease;
}
.manual-insert-bar button:hover {
    background-color: #F1F5F9;
    border-color: #94A3B8;
}
.manual-insert-bar .btn-insert-img {
    background-color: #059669;
    color: white;
    border-color: #047857;
}
.manual-insert-bar .btn-insert-img:hover {
    background-color: #10B981;
}

@media print {
    body {
        background: white !important;
    }
    aside, header, button, .manual-img-overlay, .manual-insert-bar, #block-toolbar, .sticky, .fixed {
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
        editMode: false,
        isSaving: false,
        updatedAtDisplay: '{{ $updatedAt }}',
        htmlContent: {!! json_encode($htmlContent) !!},
        targetOldSrc: null,
        activeBlockNode: null,
        blockToolbarStyle: 'display: none;',
        insertDirection: 'after', // 'before' | 'after'
        insertTargetNode: null,
        isInsertingNewImage: false,

        init() {
            window.addEventListener('scroll', () => {
                this.showBackToTop = window.scrollY > 400;
                if (this.activeBlockNode) {
                    this.updateToolbarPosition();
                }
            });

            window.addEventListener('resize', () => {
                if (this.activeBlockNode) {
                    this.updateToolbarPosition();
                }
            });

            this.$nextTick(() => {
                this.setupTableOfContents();
                this.applyEditModeState();
                this.setupBlockSelectionAndPaste();
            });

            // Lắng nghe phím tắt Ctrl + S để lưu nhanh
            window.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                    if (this.editMode) {
                        e.preventDefault();
                        this.saveFromContent();
                    }
                }
            });
        },

        toggleEditMode() {
            this.editMode = !this.editMode;
            this.activeBlockNode = null;
            this.applyEditModeState();
        },

        applyEditModeState() {
            const container = document.getElementById('manual-content');
            if (!container) return;

            if (this.editMode) {
                container.classList.add('manual-editing-active');
            } else {
                container.classList.remove('manual-editing-active');
            }

            // Bật/tắt contenteditable cho các thẻ văn bản
            const editableTags = container.querySelectorAll('h1, h2, h3, h4, h5, p, li, td, th');
            editableTags.forEach(el => {
                if (el.closest('.manual-toc') || el.querySelector('a[href^="#"]')) {
                    el.removeAttribute('contenteditable');
                } else {
                    if (this.editMode) {
                        el.setAttribute('contenteditable', 'true');
                        el.setAttribute('spellcheck', 'false');
                    } else {
                        el.removeAttribute('contenteditable');
                    }
                }
            });

            this.enhanceImages();
            this.renderInsertBars();
        },

        setupBlockSelectionAndPaste() {
            const container = document.getElementById('manual-content');
            if (!container) return;

            // 1. Nhấp chuột vào bất kỳ khối nào để chọn khối đó
            container.addEventListener('click', (e) => {
                if (!this.editMode) return;
                if (e.target.closest('.manual-insert-bar') || e.target.closest('.manual-img-overlay')) return;

                const block = e.target.closest('p, h1, h2, h3, h4, blockquote, .manual-img-container, table, li');
                if (block && container.contains(block)) {
                    this.setActiveBlock(block);
                }
            });

            // 2. HỖ TRỢ DÁN ẢNH TRỰC TIẾP TỪ CLIPBOARD (Ctrl + V)
            container.addEventListener('paste', async (e) => {
                if (!this.editMode) return;
                const items = (e.clipboardData || e.originalEvent.clipboardData)?.items;
                if (!items) return;

                for (const item of items) {
                    if (item.type.indexOf('image') === 0) {
                        e.preventDefault();
                        const file = item.getAsFile();
                        if (file) {
                            const target = this.activeBlockNode || e.target.closest('p, h1, h2, h3, h4, blockquote, .manual-img-container') || container.lastElementChild;
                            await this.uploadAndInsertNewImage(file, target, 'after');
                            break;
                        }
                    }
                }
            });
        },

        setActiveBlock(block) {
            if (this.activeBlockNode) {
                this.activeBlockNode.classList.remove('manual-active-block');
            }
            this.activeBlockNode = block;
            this.activeBlockNode.classList.add('manual-active-block');
            this.updateToolbarPosition();
        },

        updateToolbarPosition() {
            if (!this.activeBlockNode || !this.editMode) {
                this.blockToolbarStyle = 'display: none;';
                return;
            }

            const rect = this.activeBlockNode.getBoundingClientRect();
            // Đặt thanh công cụ nổi ngay phía trên hoặc phía dưới khối đang chọn
            let top = rect.top - 46;
            if (top < 70) {
                top = rect.bottom + 8;
            }

            let left = rect.left;
            if (left + 500 > window.innerWidth) {
                left = window.innerWidth - 520;
            }
            if (left < 10) left = 10;

            this.blockToolbarStyle = `display: flex; top: ${top}px; left: ${left}px;`;
        },

        insertRelative(direction, type) {
            const target = this.activeBlockNode;
            if (!target) return;

            if (type === 'text') {
                const p = document.createElement('p');
                p.setAttribute('contenteditable', 'true');
                p.setAttribute('spellcheck', 'false');
                p.innerText = 'Nhấp chuột vào đây để gõ văn bản mới...';

                if (direction === 'before') {
                    target.parentNode.insertBefore(p, target);
                } else {
                    target.parentNode.insertBefore(p, target.nextSibling);
                }
                this.setActiveBlock(p);
                p.focus();
            } else if (type === 'step') {
                const h4 = document.createElement('h4');
                h4.setAttribute('contenteditable', 'true');
                h4.setAttribute('spellcheck', 'false');
                h4.innerText = 'Bước mới: Tiêu đề bước thực hiện';

                const p = document.createElement('p');
                p.setAttribute('contenteditable', 'true');
                p.setAttribute('spellcheck', 'false');
                p.innerText = 'Mô tả chi tiết các thao tác thực hiện trong bước này...';

                if (direction === 'before') {
                    target.parentNode.insertBefore(h4, target);
                    h4.parentNode.insertBefore(p, h4.nextSibling);
                } else {
                    target.parentNode.insertBefore(h4, target.nextSibling);
                    h4.parentNode.insertBefore(p, h4.nextSibling);
                }
                this.setActiveBlock(h4);
                h4.focus();
            } else if (type === 'note') {
                const bq = document.createElement('blockquote');
                bq.setAttribute('contenteditable', 'true');
                bq.setAttribute('spellcheck', 'false');
                bq.innerHTML = '<strong>LƯU Ý NGHIỆP VỤ:</strong> Nhập nội dung lưu ý quan trọng tại đây...';

                if (direction === 'before') {
                    target.parentNode.insertBefore(bq, target);
                } else {
                    target.parentNode.insertBefore(bq, target.nextSibling);
                }
                this.setActiveBlock(bq);
                bq.focus();
            } else if (type === 'image') {
                this.insertTargetNode = target;
                this.insertDirection = direction;
                this.isInsertingNewImage = true;
                this.targetOldSrc = null;
                const fileInput = document.getElementById('image-file-input');
                if (fileInput) {
                    fileInput.value = '';
                    fileInput.click();
                }
            }
        },

        deleteActiveBlock() {
            if (!this.activeBlockNode) return;
            if (confirm('Thầy có chắc chắn muốn xóa khối nội dung đang chọn này?')) {
                const prev = this.activeBlockNode.previousElementSibling;
                this.activeBlockNode.remove();
                this.activeBlockNode = prev || null;
                this.updateToolbarPosition();
            }
        },

        setupTableOfContents() {
            const container = document.getElementById('manual-content');
            if (!container) return;

            const headings = container.querySelectorAll('h1, h2, h3, h4');
            headings.forEach(h => {
                const text = h.textContent.trim();
                if (!h.id) {
                    const plainSlug = text.toLowerCase()
                        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                    h.id = plainSlug;
                }
            });

            const links = container.querySelectorAll('a[href^="#"]');
            links.forEach(a => {
                if (a.dataset.tocBound) return;
                a.dataset.tocBound = 'true';

                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const href = a.getAttribute('href');
                    if (!href || href === '#') return;
                    const targetId = decodeURIComponent(href.substring(1)).toLowerCase().trim();

                    let targetEl = document.getElementById(targetId);

                    if (!targetEl) {
                        const unaccentedTarget = targetId.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                        targetEl = document.getElementById(unaccentedTarget);
                    }

                    if (!targetEl) {
                        const linkText = a.textContent.trim().toLowerCase();
                        const matchNum = linkText.match(/^([0-9\.]+|phần\s+[ivx0-9]+)/i);
                        const prefix = matchNum ? matchNum[1].toLowerCase() : null;

                        for (const h of headings) {
                            const hText = h.textContent.trim().toLowerCase();
                            if (hText === linkText || hText.includes(linkText) || (prefix && hText.startsWith(prefix))) {
                                targetEl = h;
                                break;
                            }
                        }
                    }

                    if (targetEl) {
                        const navOffset = 90;
                        const elementPosition = targetEl.getBoundingClientRect().top;
                        const offsetPosition = elementPosition + window.pageYOffset - navOffset;

                        window.scrollTo({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });

                        targetEl.style.transition = 'background-color 0.4s ease';
                        targetEl.style.backgroundColor = '#FEF08A';
                        setTimeout(() => {
                            targetEl.style.backgroundColor = '';
                        }, 1600);
                    }
                });
            });
        },

        enhanceImages() {
            const container = document.getElementById('manual-content');
            if (!container) return;

            const imgs = container.querySelectorAll('img');
            imgs.forEach(img => {
                const parent = img.parentElement;
                let wrapper = parent.classList.contains('manual-img-container') ? parent : null;

                if (!wrapper) {
                    wrapper = document.createElement('div');
                    wrapper.className = 'manual-img-container';
                    img.parentNode.insertBefore(wrapper, img);
                    wrapper.appendChild(img);

                    const overlay = document.createElement('div');
                    overlay.className = 'manual-img-overlay';

                    const replaceBtn = document.createElement('button');
                    replaceBtn.type = 'button';
                    replaceBtn.className = 'btn-replace-img';
                    replaceBtn.innerHTML = `
                        <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>📷 Thay ảnh</span>
                    `;
                    replaceBtn.onclick = (e) => {
                        e.stopPropagation();
                        this.triggerImageUpload(img);
                    };

                    const deleteBtn = document.createElement('button');
                    deleteBtn.type = 'button';
                    deleteBtn.className = 'btn-delete-img';
                    deleteBtn.innerHTML = `
                        <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Xóa</span>
                    `;
                    deleteBtn.onclick = (e) => {
                        e.stopPropagation();
                        if (confirm('Thầy có chắc chắn muốn xóa bức ảnh minh họa này khỏi tài liệu?')) {
                            wrapper.remove();
                        }
                    };

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
                    overlay.appendChild(deleteBtn);
                    overlay.appendChild(zoomBtn);
                    wrapper.appendChild(overlay);

                    img.addEventListener('click', (e) => {
                        if (!this.editMode) {
                            this.zoomImg = img.src;
                        }
                    });

                    wrapper.addEventListener('dragover', (e) => {
                        if (!this.editMode) return;
                        e.preventDefault();
                        wrapper.classList.add('is-dragover');
                    });
                    wrapper.addEventListener('dragleave', () => {
                        wrapper.classList.remove('is-dragover');
                    });
                    wrapper.addEventListener('drop', (e) => {
                        if (!this.editMode) return;
                        e.preventDefault();
                        wrapper.classList.remove('is-dragover');
                        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                            this.uploadImageFile(e.dataTransfer.files[0], img.getAttribute('src'), img);
                        }
                    });
                }

                const overlay = wrapper.querySelector('.manual-img-overlay');
                if (overlay) {
                    overlay.style.display = this.editMode ? 'flex' : 'none';
                }
            });
        },

        renderInsertBars() {
            const container = document.getElementById('manual-content');
            if (!container) return;

            container.querySelectorAll('.manual-insert-bar').forEach(el => el.remove());

            if (!this.editMode) return;

            const anchors = container.querySelectorAll('h2, h3, .manual-img-container, blockquote, hr');
            anchors.forEach(node => {
                const bar = document.createElement('div');
                bar.className = 'manual-insert-bar';
                bar.innerHTML = `
                    <span class="font-bold text-amber-950 flex items-center gap-1">
                        <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Chèn vào đây:
                    </span>
                    <div class="flex items-center gap-1.5">
                        <button type="button" class="btn-insert-p">➕ Thêm chữ</button>
                        <button type="button" class="btn-insert-step">📋 Thêm bước mới</button>
                        <button type="button" class="btn-insert-note">⚠️ Thêm hộp lưu ý</button>
                        <button type="button" class="btn-insert-img">
                            <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>🖼️ + Thêm ảnh mới</span>
                        </button>
                    </div>
                `;

                bar.querySelector('.btn-insert-p').onclick = () => {
                    const p = document.createElement('p');
                    p.setAttribute('contenteditable', 'true');
                    p.setAttribute('spellcheck', 'false');
                    p.innerText = 'Nhấp chuột vào đây để nhập nội dung chữ mới...';
                    bar.parentNode.insertBefore(p, bar.nextSibling);
                    p.focus();
                };

                bar.querySelector('.btn-insert-step').onclick = () => {
                    const h4 = document.createElement('h4');
                    h4.setAttribute('contenteditable', 'true');
                    h4.setAttribute('spellcheck', 'false');
                    h4.innerText = 'Bước mới: Tiêu đề bước thực hiện';

                    const p = document.createElement('p');
                    p.setAttribute('contenteditable', 'true');
                    p.setAttribute('spellcheck', 'false');
                    p.innerText = 'Mô tả chi tiết các thao tác thực hiện trong bước này...';

                    bar.parentNode.insertBefore(h4, bar.nextSibling);
                    h4.parentNode.insertBefore(p, h4.nextSibling);
                    h4.focus();
                };

                bar.querySelector('.btn-insert-note').onclick = () => {
                    const bq = document.createElement('blockquote');
                    bq.setAttribute('contenteditable', 'true');
                    bq.setAttribute('spellcheck', 'false');
                    bq.innerHTML = '<strong>LƯU Ý NGHIỆP VỤ:</strong> Nhập nội dung lưu ý quan trọng tại đây...';
                    bar.parentNode.insertBefore(bq, bar.nextSibling);
                    bq.focus();
                };

                bar.querySelector('.btn-insert-img').onclick = () => {
                    this.insertTargetNode = bar;
                    this.insertDirection = 'after';
                    this.isInsertingNewImage = true;
                    this.targetOldSrc = null;
                    const fileInput = document.getElementById('image-file-input');
                    if (fileInput) {
                        fileInput.value = '';
                        fileInput.click();
                    }
                };

                node.parentNode.insertBefore(bar, node.nextSibling);
            });
        },

        triggerImageUpload(imgElement) {
            let src = imgElement.getAttribute('src') || imgElement.src || '';
            src = src.replace(/\?v=\d+.*$/, '');
            try {
                const url = new URL(src, window.location.origin);
                if (url.origin === window.location.origin) {
                    src = url.pathname.replace(/^\/+/, '');
                }
            } catch (e) {}

            this.targetOldSrc = src;
            this.currentImgElement = imgElement;
            this.isInsertingNewImage = false;
            this.insertTargetNode = null;
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
                this.uploadAndInsertNewImage(file, this.insertTargetNode, this.insertDirection);
            } else {
                this.uploadImageFile(file, this.targetOldSrc, this.currentImgElement);
            }
        },

        async uploadAndInsertNewImage(file, targetNode, direction = 'after') {
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
                    const img = document.createElement('img');
                    img.src = data.url;
                    img.setAttribute('src', data.relative_path);
                    img.alt = 'Ảnh minh chứng';

                    if (targetNode && targetNode.parentNode) {
                        if (direction === 'before') {
                            targetNode.parentNode.insertBefore(img, targetNode);
                        } else {
                            targetNode.parentNode.insertBefore(img, targetNode.nextSibling);
                        }
                    } else {
                        document.getElementById('manual-content')?.appendChild(img);
                    }

                    this.enhanceImages();
                    alert('✅ ĐÃ THÊM ẢNH MỚI THÀNH CÔNG!');
                } else {
                    alert('❌ Lỗi: ' + (data.message || 'Không thể upload ảnh'));
                }
            } catch (err) {
                console.error(err);
                alert('❌ Lỗi kết nối khi tải ảnh!');
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
                        imgElement.setAttribute('src', data.relative_path);
                        imgElement.src = data.url;
                        this.targetOldSrc = data.relative_path;

                        const container = imgElement.closest('.manual-img-container');
                        if (container) {
                            const zoomBtn = container.querySelector('.btn-zoom-img');
                            if (zoomBtn) {
                                zoomBtn.onclick = (e) => {
                                    e.stopPropagation();
                                    this.zoomImg = data.url;
                                };
                            }
                        }
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

        saveFromContent() {
            const container = document.getElementById('manual-content');
            if (!container) return;

            const clone = container.cloneNode(true);
            clone.querySelectorAll('.manual-img-overlay').forEach(el => el.remove());
            clone.querySelectorAll('.manual-insert-bar').forEach(el => el.remove());
            clone.querySelectorAll('.manual-active-block').forEach(el => el.classList.remove('manual-active-block'));

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
                turndown.addRule('images', {
                    filter: 'img',
                    replacement: function (content, node) {
                        let src = node.getAttribute('src') || '';
                        src = src.replace(/\?v=\d+.*$/, '');
                        try {
                            const url = new URL(src, window.location.origin);
                            if (url.origin === window.location.origin) {
                                src = url.pathname.replace(/^\/+/, '');
                            }
                        } catch (e) {}
                        const alt = node.getAttribute('alt') || 'Ảnh minh chứng';
                        return `\n\n![${alt}](${src})\n\n`;
                    }
                });
                markdown = turndown.turndown(clone);
            } else {
                alert('Đang nạp bộ chuyển đổi, vui lòng thử lại sau 2 giây...');
                return;
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
                    this.updatedAtDisplay = data.updatedAt;
                    this.editMode = false;
                    this.activeBlockNode = null;
                    this.$nextTick(() => {
                        this.setupTableOfContents();
                        this.applyEditModeState();
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
        }
    };
}
</script>
@endsection
