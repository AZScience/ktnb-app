@extends('layouts.nttu')
@section('title', 'Hộp thư')
@section('page-section', 'Công cụ hỗ trợ')
@section('page-title', 'Hộp thư nội bộ')
@section('hide-page-heading', '')
@section('content')

@php $msgPerms = $nttuPage('/messaging'); @endphp

<div
    x-data="messagingPage({
        folder: @js($folder),
        messages: @js($messages),
        selected: @js($selected),
        recipients: @js($recipients),
        currentUserId: @js($currentUserId ?? auth()->id()),
        unreadCount: @js($unreadCount),
        routes: {
            index: @js(route('messaging.index')),
            readTemplate: @js(route('messaging.read', ['message' => '__ID__'])),
        },
        canAdd: @js($msgPerms['add']),
        canEdit: @js($msgPerms['edit']),
        canDelete: @js($msgPerms['delete']),
    })"
    class="-m-4 flex flex-col md:-m-6"
    style="height: calc(100vh - 4rem);"
    @keydown.escape.window="composeOpen = false; recipientMenuOpen = false"
>
    {{-- Header --}}
    <div class="shrink-0 border-b bg-white px-4 py-4 md:px-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <x-nttu-page-heading-icon label="Hộp thư nội bộ" size="md" />
                <div>
                    <h1 class="text-xl font-bold uppercase tracking-tight text-gray-900">Hộp thư nội bộ</h1>
                    <p class="text-xs text-gray-500">Trao đổi thông tin giữa các thành viên trong hệ thống.</p>
                </div>
            </div>
            <button type="button" x-show="canAdd" x-cloak @click="openCompose()"
                class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm font-medium text-white shadow-md hover:opacity-90">
                <x-form-field-icon name="add" tone="lime" class="h-4 w-4" />
                Soạn tin mới
            </button>
        </div>
    </div>

  @if (session('success'))
        <div class="mx-4 mt-3 rounded-md border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800 md:mx-6">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex min-h-0 flex-1 flex-col overflow-hidden bg-white sm:flex-row">
        {{-- Sidebar folders --}}
        <aside class="hidden w-56 shrink-0 flex-col border-r bg-slate-50/80 pt-4 sm:flex md:w-64">
            <div class="space-y-1 px-3">
                <a :href="folderUrl('inbox')"
                    class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium transition-all"
                    :class="folder === 'inbox' ? 'bg-[var(--nttu-table-head)] text-white shadow-md' : 'text-gray-600 hover:bg-slate-100'">
                    <span class="flex items-center gap-3">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                        Hộp thư đến
                    </span>
                    <span x-show="unreadCount > 0" x-cloak
                        class="min-w-[20px] rounded-full px-1.5 py-0.5 text-center text-[10px] font-bold"
                        :class="folder === 'inbox' ? 'bg-white/20 text-white' : 'bg-red-500 text-white'"
                        x-text="unreadCount"></span>
                </a>
                <a :href="folderUrl('sent')"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all"
                    :class="folder === 'sent' ? 'bg-[var(--nttu-table-head)] text-white shadow-md' : 'text-gray-600 hover:bg-slate-100'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Đã gửi
                </a>
                <a :href="folderUrl('trash')"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all"
                    :class="folder === 'trash' ? 'bg-[var(--nttu-table-head)] text-white shadow-md' : 'text-gray-600 hover:bg-slate-100'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Thùng rác
                </a>
            </div>
        </aside>

        {{-- Mobile folder tabs --}}
        <div class="flex w-full shrink-0 border-b sm:hidden">
            @foreach (['inbox' => 'Đến', 'sent' => 'Gửi', 'trash' => 'Rác'] as $key => $label)
                <a href="{{ route('messaging.index', ['folder' => $key]) }}"
                    class="flex-1 py-2 text-center text-xs font-medium {{ $folder === $key ? 'border-b-2 border-[var(--nttu-table-head)] text-[var(--nttu-table-head)]' : 'text-gray-500' }}">
                    {{ $label }}
                    @if ($key === 'inbox' && $unreadCount > 0)
                        <span class="ml-1 rounded-full bg-red-500 px-1.5 text-[10px] text-white">{{ $unreadCount }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="flex min-h-0 flex-1 overflow-hidden">
        {{-- Message list --}}
        <div class="flex w-full shrink-0 flex-col border-r sm:w-80 md:w-96" :class="selected && 'hidden sm:flex'">
            <div class="border-b p-4">
                <div class="relative">
                    <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="searchQuery" placeholder="Tìm kiếm tin nhắn..."
                        class="w-full rounded-md border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm focus:border-[var(--nttu-primary)] focus:ring-[var(--nttu-primary)]/20">
                </div>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto">
                <template x-if="filteredMessages.length === 0">
                    <div class="p-8 text-center text-gray-500">
                        <x-table-empty-state
                            icon="inbox"
                            filters-active="searchQuery.trim() !== ''"
                            clear-action="searchQuery = ''"
                        />
                    </div>
                </template>
                <template x-for="msg in filteredMessages" :key="msg.id">
                    <a :href="messageUrl(msg.id)"
                        class="block w-full border-b border-l-4 p-4 text-left transition-all hover:bg-slate-50"
                        :class="[
                            selected?.id === msg.id ? 'border-l-[var(--nttu-table-head)] bg-cyan-50/50' : 'border-l-transparent',
                            !msg.is_read && folder === 'inbox' ? 'font-semibold' : ''
                        ]">
                        <div class="mb-1 flex items-center justify-between gap-2">
                            <p class="max-w-[140px] truncate text-sm text-[var(--nttu-primary)]" x-text="msg.sender_name"></p>
                            <span class="text-[10px] font-normal text-gray-400" x-text="formatTimeAgo(msg.sent_at)"></span>
                        </div>
                        <h4 class="mb-1 truncate text-sm text-gray-900" x-text="msg.subject"></h4>
                        <div class="flex items-center justify-between gap-2">
                            <p class="line-clamp-2 flex-1 text-xs leading-relaxed text-gray-500" x-text="msg.body_preview"></p>
                            <span x-show="msg.position_name" x-cloak
                                class="whitespace-nowrap rounded border border-slate-200 px-1 text-[10px] font-normal text-gray-500"
                                x-text="msg.position_name"></span>
                        </div>
                    </a>
                </template>
            </div>
        </div>

        {{-- Detail --}}
        <main class="flex min-w-0 flex-1 flex-col" :class="!selected && 'hidden sm:flex'">
            <template x-if="selected">
                <div class="flex h-full flex-col">
                    <div class="flex items-start justify-between gap-3 border-b p-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <a href="{{ route('messaging.index', ['folder' => $folder]) }}" class="shrink-0 rounded-md p-1 text-gray-500 hover:bg-slate-100 sm:hidden">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </a>
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full border bg-slate-100 text-lg font-bold text-[var(--nttu-primary)] shadow-sm">
                                <img x-show="selected.sender_avatar" :src="selected.sender_avatar" :alt="selected.sender_name" class="h-full w-full object-cover">
                                <span x-show="!selected.sender_avatar" x-text="initials(selected.sender_name)"></span>
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-lg font-bold leading-tight text-gray-900" x-text="selected.subject"></h2>
                                <p class="text-sm text-gray-500">
                                    Từ: <span class="font-medium text-gray-800" x-text="selected.sender_name"></span>
                                    <span class="mx-2">•</span>
                                    <span x-text="formatTimeAgo(selected.sent_at)"></span>
                                </p>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            @if ($folder === 'trash')
                                @if($msgPerms['edit'])
                                <form method="POST" action="{{ $selected ? route('messaging.restore', $selected['id']) : '#' }}">
                                    @csrf
                                    <button type="submit" class="rounded-md px-2 py-1 text-sm text-green-600 hover:bg-green-50">Khôi phục</button>
                                </form>
                                @endif
                                @if($msgPerms['delete'])
                                <form method="POST" action="{{ $selected ? route('messaging.destroy', $selected['id']) : '#' }}">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="folder" value="trash">
                                    <button type="submit" class="rounded-md p-2 text-red-500 hover:bg-red-50" title="Xóa vĩnh viễn">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                                @endif
                            @else
                                @if($msgPerms['add'])
                                <button type="button" x-show="folder === 'inbox'" @click="replyTo(selected)"
                                    class="rounded-md px-3 py-1.5 text-sm font-medium text-[var(--nttu-primary)] hover:bg-cyan-50 border border-cyan-200">
                                    Trả lời
                                </button>
                                @endif
                                @if($msgPerms['delete'])
                                <form method="POST" action="{{ $selected ? route('messaging.trash', $selected['id']) : '#' }}">
                                    @csrf
                                    <input type="hidden" name="folder" value="{{ $folder }}">
                                    <button type="submit" class="rounded-md p-2 text-red-500 hover:bg-red-50" title="Chuyển vào thùng rác">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                                @endif
                            @endif
                        </div>
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto p-6">
                        <div class="prose prose-sm mx-auto max-w-3xl">
                            <div class="text-base leading-relaxed text-gray-800" x-html="selected.body"></div>
                            <template x-if="selected.attachments && selected.attachments.length > 0">
                                <div class="mt-8 border-t pt-6 not-prose">
                                    <h4 class="mb-3 flex items-center gap-2 text-sm font-bold text-gray-800">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        Tệp đính kèm (<span x-text="selected.attachments.length"></span>)
                                    </h4>
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        <template x-for="(file, idx) in selected.attachments" :key="idx">
                                            <a :href="file.url" target="_blank" rel="noopener noreferrer"
                                                class="group flex items-center gap-3 rounded-lg border bg-slate-50 p-3 transition-colors hover:bg-slate-100">
                                                <div class="flex h-10 w-10 items-center justify-center rounded border bg-white shadow-sm group-hover:bg-cyan-50">
                                                    <svg class="h-5 w-5 text-gray-400 group-hover:text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-sm font-medium" x-text="file.name"></p>
                                                    <p class="text-[10px] uppercase text-gray-400">Tải xuống</p>
                                                </div>
                                            </a>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
            <template x-if="!selected">
                <div class="flex flex-1 flex-col items-center justify-center p-12 text-gray-500">
                    <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-slate-100">
                        <svg class="h-10 w-10 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="mb-1 text-lg font-bold text-gray-700">Chọn một tin nhắn để xem</h3>
                    <p class="max-w-xs text-center text-sm">Dữ liệu được bảo mật và chỉ người nhận mới có thể xem nội dung.</p>
                </div>
            </template>
        </main>
        </div>
    </div>

    {{-- Compose modal --}}
    <div x-show="composeOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="closeCompose()"></div>
        <form method="POST" action="{{ route('messaging.store') }}" enctype="multipart/form-data"
            class="relative flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-lg bg-white shadow-xl"
            @submit="handleComposeSubmit($event)">
            @csrf
            <div class="relative border-b bg-slate-50 px-6 py-4 pr-12">
                <h3 class="flex items-center gap-2 text-lg font-semibold">
                    <svg class="h-5 w-5 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Soạn tin nhắn mới
                </h3>
                <button type="button"
                    class="absolute right-4 top-1/2 -translate-y-1/2 rounded-sm text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300"
                    @click="closeCompose(); resetCompose()"
                    aria-label="Đóng">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-6">
                <template x-for="uid in selectedRecipientIds" :key="uid">
                    <input type="hidden" name="recipient_user_ids[]" :value="uid">
                </template>

                <div class="space-y-2">
                    <x-form-label class="font-semibold" icon="users" tone="blue">Người nhận</x-form-label>
                    <div class="relative">
                        <button type="button" @click.stop="recipientMenuOpen = !recipientMenuOpen"
                            class="flex w-full items-center justify-between rounded-md border border-slate-200 bg-white px-3 py-2 text-left text-sm">
                            <span class="truncate" x-text="recipientLabel"></span>
                            <svg class="h-4 w-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </button>
                        <div x-show="recipientMenuOpen" x-cloak @click.stop
                            class="absolute z-20 mt-1 w-full overflow-hidden rounded-md border border-slate-200 bg-white shadow-lg">
                            <div class="border-b p-2">
                                <input type="text" x-model="recipientSearch" placeholder="Tìm theo tên, mã, email, chức vụ..."
                                    class="h-8 w-full rounded border border-slate-200 px-2 text-xs">
                            </div>
                            <div class="flex items-center justify-between border-b bg-slate-50 px-3 py-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Danh sách nhân viên</span>
                                <button type="button" @click="toggleAllRecipients()" class="text-xs text-[var(--nttu-primary)] hover:underline"
                                    x-text="selectedRecipientIds.length === selectableRecipients.length ? 'Bỏ chọn hết' : 'Chọn tất cả'"></button>
                            </div>
                            <div class="max-h-60 overflow-y-auto p-1">
                                <template x-if="filteredRecipients.length === 0">
                                    <p class="p-4 text-center text-xs text-gray-400">Không tìm thấy nhân viên có tài khoản hệ thống.</p>
                                </template>
                                <template x-for="emp in filteredRecipients" :key="emp.user_id">
                                    <label class="flex cursor-pointer items-center gap-2 rounded-md p-2 hover:bg-slate-50">
                                        <input type="checkbox" class="rounded border-gray-300"
                                            :checked="isRecipientSelected(emp.user_id)"
                                            @change="toggleRecipient(emp.user_id)">
                                        <img x-show="emp.avatar_url" :src="emp.avatar_url" :alt="emp.name" class="h-7 w-7 rounded-full object-cover">
                                        <div x-show="!emp.avatar_url" class="flex h-7 w-7 items-center justify-center rounded-full bg-cyan-100 text-[10px] font-bold text-[var(--nttu-primary)]"
                                            x-text="initials(emp.name)"></div>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium" x-text="emp.name"></p>
                                            <p class="truncate text-[10px] text-gray-400">
                                                <span x-show="emp.employee_id" x-text="emp.employee_id + ' • '"></span>
                                                <span x-text="emp.position || emp.email || ''"></span>
                                            </p>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div x-show="selectedRecipientIds.length > 0 && selectedRecipientIds.length < selectableRecipients.length" class="flex flex-wrap gap-1">
                        <template x-for="uid in selectedRecipientIds.slice(0, 8)" :key="'tag-' + uid">
                            <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-gray-600" x-text="recipientName(uid)"></span>
                        </template>
                        <span x-show="selectedRecipientIds.length > 8" class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-gray-600"
                            x-text="'+' + (selectedRecipientIds.length - 8)"></span>
                    </div>
                </div>

                <div class="space-y-2">
                    <x-form-label class="font-semibold" icon="file-text" tone="indigo">Tiêu đề</x-form-label>
                    <input type="text" name="subject" required x-model="subject" placeholder="Nhập tiêu đề tin nhắn..."
                        class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>

                <div class="space-y-2">
                    <x-form-label class="font-semibold">Nội dung</x-form-label>
                    <input type="hidden" name="body" :value="body">
                    <div class="nttu-ckeditor-compose rounded-md border border-slate-200 bg-white">
                        <div x-ref="composeEditor"></div>
                    </div>
                    <p x-show="composeEditorLoading" class="text-xs text-gray-400">Đang tải trình soạn thảo...</p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-semibold flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        Tệp đính kèm
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="(file, index) in attachmentFiles" :key="file.name + '-' + index">
                            <div class="flex max-w-full items-center gap-2 rounded-full border bg-slate-50 px-3 py-1.5 text-xs shadow-sm">
                                <svg x-show="!isImageFile(file)" class="h-3.5 w-3.5 shrink-0 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <img x-show="isImageFile(file)" :src="attachmentPreview(file)" class="h-6 w-6 rounded object-cover" :alt="file.name">
                                <span class="max-w-[140px] truncate font-medium" x-text="file.name"></span>
                                <span class="text-[10px] text-gray-400" x-text="formatFileSize(file.size)"></span>
                                <button type="button" @click="removeAttachment(index)" class="text-gray-400 hover:text-red-500" title="Xóa tệp">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                        <button type="button" @click="$refs.attachmentPicker.click()"
                            class="inline-flex h-8 items-center gap-1 rounded-full border border-dashed border-slate-300 px-3 text-xs text-gray-600 hover:bg-slate-50">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Đính kèm tệp
                        </button>
                    </div>
                    <input type="file" name="attachments[]" multiple x-ref="composeAttachments" class="hidden"
                        :accept="attachmentAccept" @change="addAttachments($event)">
                    <input type="file" multiple class="hidden" x-ref="attachmentPicker" :accept="attachmentAccept" @change="addAttachments($event)">
                    <p class="text-xs text-gray-400">Hỗ trợ ảnh, video, audio, PDF, Office, nén... Tối đa 10MB mỗi tệp.</p>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t bg-slate-50 px-6 py-4">
                <x-nttu-button type="button" action="undo" @click="resetCompose()">Hoàn tác</x-nttu-button>
                <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-6 py-2 text-sm font-medium text-white hover:opacity-90">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Gửi tin nhắn
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
