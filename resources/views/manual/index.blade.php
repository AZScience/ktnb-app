@extends('layouts.nttu')
@section('title', 'Hướng dẫn sử dụng - Hệ thống KTNB NTTU')

@section('content')
<div x-data="manualViewer()" class="space-y-4">
    <!-- Thanh tiêu đề & Tác vụ hướng dẫn -->
    <div class="nttu-card p-4 sm:p-6 bg-gradient-to-r from-blue-900 via-indigo-900 to-blue-950 text-white shadow-lg rounded-xl flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-400 text-slate-900 shadow-md">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-black uppercase tracking-wide text-white">Cẩm Nang Hướng Dẫn Sử Dụng</h1>
                <p class="text-xs sm:text-sm text-blue-200 mt-0.5">Hệ thống Quản trị & Giám sát Kiểm tra Nội bộ - Đại học Nguyễn Tất Thành • Cập nhật: {{ $updatedAt }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Nút Tải file MD -->
            <a href="{{ route('manual.download') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs sm:text-sm shadow transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Tải file .md</span>
            </a>

            <!-- Nút In / Xuất PDF -->
            <button type="button" @click="window.print()" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-white font-medium text-xs sm:text-sm border border-white/20 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>In / Xuất PDF</span>
            </button>
        </div>
    </div>

    <!-- Khung Nội dung Tài liệu -->
    <div class="nttu-card p-4 sm:p-8 md:p-10 bg-white rounded-xl shadow-sm border border-slate-200">
        <div id="manual-content" class="manual-prose max-w-none text-slate-800 leading-relaxed">
            {!! $htmlContent !!}
        </div>
    </div>

    <!-- Modal Phóng to Ảnh khi nhấp vào -->
    <div x-show="zoomImg" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 p-4" @click="zoomImg = null" @keydown.escape.window="zoomImg = null">
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
        class="fixed bottom-6 right-6 z-40 flex h-11 w-11 items-center justify-center rounded-full bg-[var(--nttu-primary)] text-white shadow-xl hover:bg-blue-800 transition transform hover:scale-110"
        title="Cuộn lên đầu trang"
    >
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
        </svg>
    </button>
</div>

<style>
/* Tùy chỉnh hiển thị Markdown đẹp mắt chuẩn NTTU */
.manual-prose h1 {
    font-size: 1.85rem;
    font-weight: 800;
    color: #003B7A;
    margin-top: 2rem;
    margin-bottom: 1.25rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #003B7A;
}
.manual-prose h2 {
    font-size: 1.45rem;
    font-weight: 700;
    color: #003B7A;
    margin-top: 1.75rem;
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
    margin-top: 1rem;
    margin-bottom: 0.5rem;
}
.manual-prose p {
    margin-bottom: 0.85rem;
    line-height: 1.65;
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
    cursor: zoom-in;
    transition: transform 0.2s ease;
}
.manual-prose img:hover {
    transform: scale(1.01);
}
.manual-prose code {
    background-color: #F1F5F9;
    color: #C8102E;
    padding: 0.15rem 0.35rem;
    border-radius: 0.25rem;
    font-size: 0.85em;
    font-family: monospace;
}
.manual-prose pre {
    background-color: #0F172A;
    color: #F8FAFC;
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin-bottom: 1.25rem;
}
.manual-prose hr {
    border: 0;
    height: 1px;
    background: #E2E8F0;
    margin: 2rem 0;
}

@media print {
    body {
        background: white !important;
    }
    aside, header, button, .sticky {
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
function manualViewer() {
    return {
        zoomImg: null,
        showBackToTop: false,
        init() {
            window.addEventListener('scroll', () => {
                this.showBackToTop = window.scrollY > 400;
            });
            // Gán sự kiện click phóng to cho tất cả ảnh trong bài viết
            this.$nextTick(() => {
                document.querySelectorAll('#manual-content img').forEach(img => {
                    img.addEventListener('click', () => {
                        this.zoomImg = img.src;
                    });
                });
            });
        }
    };
}
</script>
@endsection
