@extends('layouts.public')
@section('title', 'Bảng thảo luận')
@section('content')

<div
    x-data="discussionBoardPage({
        sections: @js($sections),
        highlightId: @js($highlightId),
        needsLink: @js($needsLink ?? false),
        participant: @js($participant ?? ['email' => '', 'name' => 'Khách', 'role' => 'guest', 'roleLabel' => 'Sinh viên / Khách']),
        moderatorKey: @js(request('key', '')),
        guestName: @js(old('author_name', '')),
        commentUrlTemplate: @js(route('discussion.comment', ['section' => '__ID__'])),
        destroyUrlTemplate: @js(route('discussion.destroy', ['section' => '__ID__'])),
        updateUrlTemplate: @js(route('discussion.update', ['section' => '__ID__'])),
        commentDestroyUrlTemplate: @js(route('discussion.comment.destroy', ['section' => '__SECTION__', 'commentId' => '__COMMENT__'])),
    })"
    class="max-w-3xl mx-auto px-4 py-6 sm:py-10"
>
    <div class="mb-6">
        <div class="flex items-center gap-3">
            <x-nttu-page-heading-icon label="Bảng thảo luận" />
            <div>
                <h1 class="text-2xl font-black text-slate-900">Bảng thảo luận</h1>
                <p class="text-sm text-slate-500">Collaboration Board — không cần đăng nhập</p>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Chưa có link --}}
    <div x-show="needsLink" class="nttu-card p-10 text-center">
        <div class="mx-auto mb-4 h-14 w-14 rounded-full bg-rose-50 flex items-center justify-center">
            <svg class="h-7 w-7 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
        </div>
        <h2 class="text-lg font-bold text-slate-800 mb-2">Mở link từ giảng viên</h2>
        <p class="text-sm text-slate-500 max-w-md mx-auto leading-relaxed">
            Sinh viên và giảng viên tham gia thảo luận qua link được chia trong tiết học trực tuyến.
            Giảng viên tạo bảng bằng <strong>Extension Online</strong> — không cần đăng nhập ứng dụng này.
        </p>
    </div>

    {{-- Vai trò --}}
    <div x-show="!needsLink" class="mb-4 flex flex-wrap items-center gap-2 rounded-lg border bg-slate-50 px-4 py-3 text-sm">
        <span class="font-semibold text-slate-700">Vai trò của bạn:</span>
        <span class="rounded-full border px-2.5 py-0.5 text-xs font-bold"
            :class="roleBadgeClass(participant.role)"
            x-text="participant.roleLabel"></span>
        <span class="text-slate-500 text-xs" x-show="participant.role === 'lecturer'">
            — Quản trị phiên: sửa/xóa chủ đề, xóa bình luận.
        </span>
        <span class="text-slate-500 text-xs" x-show="participant.role === 'guest'">
            — Nhập họ tên hoặc mã sinh viên trước khi bình luận.
        </span>
    </div>

    <div x-show="!needsLink" class="space-y-6 pb-8">
        <template x-for="section in sections" :key="section.id">
            <div class="nttu-card overflow-hidden flex flex-col shadow-md" :id="'section-' + section.id">
                <div class="bg-gradient-to-r from-slate-800 to-slate-700 px-4 sm:px-5 py-4 text-white">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <h2 class="font-bold text-base leading-snug" x-show="editingId !== section.id" x-text="section.title"></h2>
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                <span x-show="section.is_moderator"
                                    class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide bg-amber-400/90 text-slate-900 px-2 py-0.5 rounded-full">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    Quản trị phiên
                                </span>
                                <span x-show="section.class_id" class="text-[10px] bg-white/15 px-2 py-0.5 rounded-full" x-text="`Lớp ${section.class_id}`"></span>
                            </div>
                        </div>
                        <div class="flex gap-1 shrink-0" x-show="isModerator(section) && editingId !== section.id">
                            <button type="button" @click="startEdit(section)" class="inline-flex items-center gap-1 text-[10px] bg-white/20 hover:bg-white/30 px-2 py-1 rounded">
                                <x-form-field-icon name="edit" tone="cyan" class="h-3 w-3 text-white" />
                                Sửa
                            </button>
                            <form :action="destroyUrl(section.id)" method="POST" onsubmit="return confirm('Xóa chủ đề này?')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="key" :value="moderatorKey">
                                <button type="submit" class="inline-flex items-center gap-1 text-[10px] bg-red-500/80 hover:bg-red-500 px-2 py-1 rounded">
                                    <x-form-field-icon name="trash" tone="destructive" class="h-3 w-3 text-white" />
                                    Xóa
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mt-3">
                        <div class="h-8 w-8 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold" x-text="initials(section.author_name)"></div>
                        <div>
                            <p class="text-xs font-medium" x-text="section.author_name"></p>
                            <p class="text-[10px] text-white/70" x-text="section.created_at"></p>
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-5 flex-1 flex flex-col">
                    <template x-if="editingId === section.id">
                        <form :action="updateUrl(section.id)" method="POST" class="space-y-3 mb-4">
                            @csrf @method('PUT')
                            <input type="hidden" name="key" :value="moderatorKey">
                            <input type="text" name="title" required x-model="editForm.title" class="w-full border rounded-lg px-3 py-2 text-sm font-semibold">
                            <textarea name="student_content" rows="5" x-model="editForm.student_content" class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                            <div class="flex gap-2">
                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-rose-600 px-4 py-2 text-xs text-white font-bold">
                                    <x-form-field-icon name="save" tone="green" class="h-3.5 w-3.5 text-white" />
                                    Lưu
                                </button>
                                <button type="button" @click="cancelEdit()" class="inline-flex items-center gap-1 rounded-lg border px-4 py-2 text-xs">
                                    <x-form-field-icon name="x" tone="destructive" class="h-3.5 w-3.5" />
                                    Hủy
                                </button>
                            </div>
                        </form>
                    </template>
                    <div x-show="editingId !== section.id" class="prose prose-sm max-w-none text-slate-700 text-sm mb-5 flex-1" x-html="section.student_content"></div>

                    <div class="border-t pt-4 space-y-3">
                        <p class="text-[10px] font-bold uppercase text-rose-400 tracking-wider">Bình luận</p>

                        <template x-if="!(section.comments || []).length">
                            <p class="text-sm text-slate-400 italic py-2">Chưa có bình luận — hãy là người đầu tiên!</p>
                        </template>

                        <template x-for="(comment, ci) in (section.comments || [])" :key="comment.id || ci">
                            <div class="bg-rose-50 rounded-lg p-3 text-sm border border-rose-100 relative group">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="font-semibold text-rose-800 text-xs" x-text="comment.authorName || 'Ẩn danh'"></p>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded border font-medium"
                                        :class="roleBadgeClass(comment.authorRole || 'student')"
                                        x-text="comment.authorRoleLabel || 'Sinh viên'"></span>
                                </div>
                                <p class="text-slate-700 mt-1 whitespace-pre-wrap" x-text="comment.text"></p>
                                <form x-show="canDeleteComment(comment, section)" :action="commentDestroyUrl(section.id, comment.id)" method="POST"
                                    class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity" onsubmit="return confirm('Xóa bình luận?')">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="key" :value="moderatorKey">
                                    <button type="submit" class="inline-flex items-center gap-0.5 text-[10px] text-red-500 hover:underline">
                                        <x-form-field-icon name="trash" tone="destructive" class="h-3 w-3" />
                                        Xóa
                                    </button>
                                </form>
                            </div>
                        </template>

                        <form :action="commentUrl(section.id)" method="POST" class="flex flex-col sm:flex-row flex-wrap gap-2 mt-3 pt-3 border-t border-slate-100">
                            @csrf
                            <input type="hidden" name="key" :value="moderatorKey">
                            <input x-show="needsGuestName()" x-model="guestName" type="text" name="author_name"
                                :required="needsGuestName()"
                                placeholder="Họ tên / mã SV..."
                                class="w-full sm:w-auto sm:min-w-[160px] border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-rose-200 focus:border-rose-400">
                            <input type="text" name="content" required placeholder="Viết bình luận..."
                                value="{{ old('content') }}"
                                class="flex-1 min-w-[160px] border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-slate-200">
                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 px-4 py-2.5 text-sm text-white font-bold shrink-0">
                                <x-form-field-icon name="send" tone="green" class="h-4 w-4 text-white" />
                                Gửi
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
@endsection
