@extends('layouts.nttu')
@section('title', 'Bảng thảo luận')
@section('page-section', 'Công cụ hỗ trợ')
@section('page-title', 'Bảng thảo luận')
@section('hide-page-heading', '')
@section('content')

<div
    x-data="discussionCollaborationBoard({
        sections: @js($sections),
        participant: @js($participant),
        routes: @js($routes),
        csrf: @js(csrf_token()),
    })"
    class="-m-4 min-h-[calc(100vh-4rem)] bg-slate-100 p-4 md:p-8 md:-m-6"
>
    <header class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="space-y-1">
            <div class="flex items-center gap-3">
                <x-nttu-page-heading-icon label="Bảng thảo luận" size="md" />
                <div>
                    <h1 class="text-2xl font-black text-slate-800 md:text-3xl">Collaboration Board</h1>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-sm">
                        <span class="text-slate-500">Chào mừng, <span class="font-bold text-[var(--nttu-primary)]" x-text="participant.name"></span></span>
                        <span class="rounded-full border px-2 py-0.5 text-[9px] font-black uppercase tracking-widest"
                            :class="roleBadgeClass(participant.role)"
                            x-text="participant.roleLabel"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <input type="search" x-model="searchQuery" placeholder="Tìm tên..."
                class="h-10 w-48 rounded-lg border border-slate-200 bg-white px-3 text-sm shadow-sm">
            <input type="date" x-model="searchDate"
                class="h-10 w-40 rounded-lg border border-slate-200 bg-white px-3 text-sm shadow-sm">
        </div>
    </header>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        <template x-for="section in filteredSections()" :key="section.id">
            <article class="flex flex-col overflow-hidden rounded-3xl border-2 border-slate-200 bg-white shadow-xl transition-shadow hover:shadow-2xl">
                <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50 px-5 py-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 border-white bg-[var(--nttu-primary)] text-xs font-black uppercase text-white shadow-sm"
                        x-text="initials(section.author_name)"></div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-black uppercase tracking-tighter text-slate-400">Tác giả bài viết</p>
                        <p class="truncate text-sm font-black text-slate-800" x-text="section.author_name || 'Ẩn danh'"></p>
                    </div>
                    <span class="text-[9px] font-bold text-slate-300" x-text="section.created_at"></span>
                </div>

                <div class="relative overflow-hidden border-b border-white/5 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
                    <div class="relative p-5">
                        <template x-if="editingTitleId === section.id">
                            <div class="flex gap-2">
                                <input type="text" x-model="editTitle" class="h-10 flex-1 rounded-lg border border-white/20 bg-white/10 px-3 text-sm font-bold text-white placeholder:text-white/30"
                                    @keydown.enter.prevent="saveTitle(section)">
                                <button type="button" @click="saveTitle(section)"
                                    class="rounded-lg bg-blue-500 px-4 text-[10px] font-black uppercase text-white shadow-lg hover:bg-blue-600">Lưu</button>
                            </div>
                        </template>
                        <template x-if="editingTitleId !== section.id">
                            <div class="group/title flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <h2 class="text-xl font-black uppercase leading-tight tracking-tighter text-white" x-text="section.title"></h2>
                                    <div class="mt-2 h-1 w-12 rounded-full bg-blue-500"></div>
                                </div>
                                <button type="button" x-show="section.can_edit_title" @click="startTitleEdit(section)"
                                    class="rounded-xl border border-white/10 bg-white/5 p-2 text-white/40 opacity-0 backdrop-blur-sm transition-all hover:bg-white/10 hover:text-white group-hover/title:opacity-100">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex flex-1 flex-col space-y-5 p-5">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-blue-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Nội dung chi tiết
                        </span>
                        <button type="button" x-show="section.can_edit_content && editingContentId === section.id"
                            @click="saveContent(section)"
                            class="rounded-lg bg-blue-500 px-3 py-1.5 text-[10px] font-black uppercase text-white shadow-lg hover:bg-blue-600">
                            Cập nhật nội dung
                        </button>
                    </div>

                    <template x-if="section.can_edit_content">
                        <div class="nttu-ckeditor-compose min-h-[220px] rounded-xl border border-slate-200 shadow-inner"
                            :id="'editor-content-' + section.id"
                            x-init="mountContentEditor(section)"></div>
                    </template>
                    <template x-if="!section.can_edit_content">
                        <div class="ck-content prose prose-sm max-w-none min-h-[220px] overflow-y-auto rounded-2xl border border-slate-100 bg-slate-50/50 p-4 text-sm leading-relaxed text-slate-700 shadow-inner"
                            x-html="section.student_content || '<p class=\'italic text-slate-400\'>Chưa có nội dung từ người dùng...</p>'"></div>
                    </template>

                    <template x-if="(section.comments || []).length || commentEditorId === section.id">
                        <div class="border-t border-slate-50 pt-4">
                            <div class="mb-4 space-y-4">
                                <template x-for="(comment, ci) in (section.comments || [])" :key="comment.id || ci">
                                    <div class="group/comment flex gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-white bg-rose-100 text-[10px] font-bold text-rose-600 shadow-sm"
                                            x-text="initials(comment.authorName)"></div>
                                        <div class="relative flex-1">
                                            <div class="rounded-2xl rounded-tl-none border border-rose-100 bg-white p-4 shadow-sm">
                                                <div class="mb-2 flex items-start justify-between gap-2">
                                                    <span class="text-[10px] font-black uppercase tracking-tight text-slate-800" x-text="comment.authorName"></span>
                                                    <span class="text-[9px] font-bold text-slate-300" x-text="formatCommentTime(comment.createdAt)"></span>
                                                </div>
                                                <div class="ck-content prose prose-sm max-w-none text-sm text-slate-600" x-html="comment.text"></div>
                                                <button type="button" x-show="canDeleteComment(comment, section)" @click="deleteComment(section, comment)"
                                                    class="absolute -right-2 -top-2 scale-75 rounded-full border border-slate-100 bg-white p-1 text-slate-300 opacity-0 shadow-md transition-all hover:text-red-500 group-hover/comment:scale-100 group-hover/comment:opacity-100">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <template x-if="commentEditorId === section.id">
                                <div class="space-y-3 rounded-3xl border-2 border-rose-100 bg-white p-4 shadow-xl">
                                    <div class="nttu-ckeditor-compose min-h-[120px] rounded-lg border border-rose-100"
                                        :id="'editor-comment-' + section.id"
                                        x-init="mountCommentEditor(section)"></div>
                                    <div class="flex justify-end gap-2 border-t border-slate-50 pt-2">
                                        <button type="button" @click="closeCommentEditor()"
                                            class="px-3 py-1.5 text-[10px] font-black uppercase text-slate-400">Hủy</button>
                                        <button type="button" @click="submitComment(section)"
                                            class="rounded-lg bg-rose-500 px-5 py-1.5 text-[10px] font-black uppercase text-white shadow-lg hover:bg-rose-600">
                                            Gửi phản hồi
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <div class="flex items-center justify-end gap-6 border-t border-slate-100 bg-white px-5 py-4">
                    <button type="button" @click="openCommentEditor(section)"
                        class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest transition-colors"
                        :class="section.can_comment ? 'cursor-pointer text-rose-500 hover:text-rose-600' : 'cursor-default text-rose-300 opacity-60'"
                        :disabled="!section.can_comment">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        Lời nhận xét (<span x-text="(section.comments || []).length"></span>)
                    </button>
                    <button type="button" x-show="section.can_delete" @click="deleteSection(section)"
                        class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-300 transition-colors hover:text-red-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Xóa bảng
                    </button>
                </div>
            </article>
        </template>

        <template x-if="isAddOpen">
            <div class="col-span-full">
                <div class="rounded-3xl border-2 border-[var(--nttu-primary)]/20 bg-white p-6 shadow-2xl md:p-8">
                    <div class="mb-6 flex items-center justify-between">
                        <h3 class="text-sm font-black uppercase tracking-widest text-slate-800">Tạo bảng thảo luận mới</h3>
                        <button type="button" @click="closeAddForm()" class="text-slate-400 hover:text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="space-y-5">
                        <div>
                            <label class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-400">Tiêu đề bảng</label>
                            <input type="text" x-model="newTitle" placeholder="Ví dụ: Thảo luận về Audit Q1..."
                                class="h-12 w-full rounded-lg border border-slate-200 px-4 text-lg font-bold">
                        </div>
                        <div>
                            <label class="mb-2 block text-[10px] font-black uppercase tracking-widest text-blue-500">Nội dung khởi tạo (Sinh viên)</label>
                            <div id="editor-new-section" class="nttu-ckeditor-compose min-h-[220px] rounded-xl border border-slate-200"
                                x-init="mountNewSectionEditor()"></div>
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="button" @click="createSection()" :disabled="saving"
                                class="h-12 rounded-lg bg-[var(--nttu-primary)] px-10 text-xs font-black uppercase text-white shadow-lg hover:opacity-90 disabled:opacity-50">
                                Xác nhận đăng bài
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="!isAddOpen">
            <button type="button" @click="openAddForm()"
                class="flex min-h-[300px] flex-col items-center justify-center rounded-2xl border-4 border-dashed border-slate-300 text-slate-400 transition-all hover:border-[var(--nttu-primary)] hover:bg-white hover:text-[var(--nttu-primary)] group">
                <svg class="mb-3 h-12 w-12 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-xs font-black uppercase tracking-widest">Thêm cột thảo luận</span>
            </button>
        </template>
    </div>

    <button type="button" @click="openAddForm(true)"
        class="fixed bottom-10 right-10 z-50 flex h-20 w-20 items-center justify-center rounded-full bg-[#e91e63] text-white shadow-[0_10px_40px_rgba(233,30,99,0.4)] transition-all hover:scale-105 hover:bg-[#d81b60] group"
        title="Đăng bài mới">
        <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span class="pointer-events-none absolute right-24 whitespace-nowrap rounded-2xl border-2 border-[#e91e63]/10 bg-white px-5 py-3 text-xs font-black text-[#e91e63] opacity-0 shadow-2xl transition-all translate-x-4 group-hover:translate-x-0 group-hover:opacity-100">
            ĐĂNG BÀI MỚI VÀO BẢNG
        </span>
    </button>
</div>
@endsection
