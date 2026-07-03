@extends('layouts.nttu')
@section('title', 'Trợ lý Tra cứu')
@section('page-section', 'Công cụ hỗ trợ')
@section('page-title', 'Trợ lý Tra cứu')
@section('page-description', 'Tìm kiếm thông tin quy định và dữ liệu nội bộ bằng AI.')
@section('content')
<div x-data="aiAssistantPage(@js($pageConfig))" class="mx-auto flex max-w-5xl flex-col" style="height: calc(100vh - 8rem);">
  <div class="nttu-card flex min-h-0 flex-1 flex-col overflow-hidden border-2 shadow-xl">
    <div class="flex flex-col gap-3 border-b bg-slate-50/80 px-4 py-3 md:flex-row md:items-center md:justify-between">
      <h2 class="flex items-center gap-2 text-lg font-semibold text-[var(--nttu-primary)]">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        Hỏi đáp thông minh
      </h2>
      <div class="flex flex-wrap items-center gap-2">
        <div class="flex rounded-lg bg-slate-200/60 p-1">
          <button type="button" @click="setMode('faq')" class="rounded-md px-3 py-1.5 text-xs font-semibold transition"
            :class="searchMode === 'faq' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600'">Hỏi đáp thủ tục</button>
          <button type="button" @click="setMode('general')" class="rounded-md px-3 py-1.5 text-xs font-semibold transition"
            :class="searchMode === 'general' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600'">Kiến thức chung</button>
        </div>
        <button type="button" x-show="messages.length > 0" x-cloak @click="clearChat()"
          class="rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
          title="Xóa lịch sử chat trên tab hiện tại">Xóa lịch sử</button>
      </div>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto p-4">
      <template x-if="messages.length === 0">
        <div class="space-y-4 py-10 text-center">
          <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[var(--nttu-primary)]/10">
            <svg class="h-8 w-8 animate-pulse text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
          </div>
          <div class="mx-auto max-w-md space-y-2">
            <h3 class="text-xl font-bold" x-text="searchMode === 'faq' ? 'Tra cứu thủ tục nội bộ' : 'Chào bạn! Tôi có thể giúp gì cho bạn?'"></h3>
            <p class="text-sm text-gray-500" x-text="searchMode === 'faq'
              ? 'Nhập câu hỏi về thủ tục, quy định — hệ thống sẽ tra cứu từ Google Sheet (tab FAQ) và trả về câu trả lời tương ứng.'
              : 'Tôi có thể trả lời các kiến thức chung hoặc hỗ trợ bạn sử dụng hệ thống bằng trí tuệ nhân tạo.'"></p>
          </div>
        </div>
      </template>

      <div class="space-y-4">
        <template x-for="(msg, index) in messages" :key="index">
          <div class="flex max-w-[85%] gap-3" :class="msg.role === 'user' ? 'ml-auto flex-row-reverse' : 'mr-auto'">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border"
              :class="msg.role === 'user' ? 'bg-[var(--nttu-primary)] text-white' : 'bg-white text-[var(--nttu-primary)]'">
              <svg x-show="msg.role === 'user'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              <svg x-show="msg.role !== 'user'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div class="rounded-2xl p-3 text-sm shadow-sm whitespace-pre-wrap"
              :class="msg.role === 'user' ? 'rounded-tr-none bg-[var(--nttu-primary)] text-white' : 'rounded-tl-none border bg-white text-gray-800'"
              x-text="msg.content"></div>
          </div>
        </template>

        <div x-show="loading" class="mr-auto flex max-w-[85%] gap-3">
          <div class="flex h-8 w-8 items-center justify-center rounded-full border bg-white text-[var(--nttu-primary)]">
            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
          </div>
          <div class="flex items-center gap-2 rounded-2xl rounded-tl-none border bg-white p-3 text-sm"
            x-text="searchMode === 'faq' ? 'Đang tra cứu FAQ...' : 'Đang suy nghĩ...'"></div>
        </div>
        <div x-ref="scrollAnchor"></div>
      </div>
    </div>

    <form @submit.prevent="sendMessage()" class="flex gap-2 border-t bg-slate-50/80 p-4">
      <input type="text" x-model="input" :disabled="loading" placeholder="Nhập câu hỏi của bạn tại đây..."
        class="h-11 flex-1 rounded-md border border-slate-200 px-3 text-sm shadow-inner focus:border-[var(--nttu-primary)] focus:ring-[var(--nttu-primary)]/20">
      <button type="submit" :disabled="loading || !input.trim()"
        class="flex h-11 w-11 items-center justify-center rounded-full bg-[var(--nttu-primary)] text-white shadow-lg disabled:opacity-50">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
      </button>
    </form>
  </div>
</div>
@endsection
