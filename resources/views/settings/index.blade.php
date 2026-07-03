@extends('layouts.nttu')
@section('title', 'Cài đặt hệ thống')
@section('page-section', 'Thiết lập hệ thống')
@section('page-title', 'Cài đặt hệ thống')
@section('page-description', 'Tùy chỉnh giao diện và cách thức hoạt động của ứng dụng.')
@section('content')
<div x-data="settingsPage(@js($securityConfig))" class="mx-auto max-w-4xl space-y-6">
  {{-- Giao diện & Ngôn ngữ --}}
  <div class="nttu-card shadow-sm">
    <div class="flex items-center gap-4 border-b px-6 py-5">
      <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-orange-500/10">
        <svg class="h-6 w-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
      </div>
      <div>
        <h2 class="text-lg font-bold text-gray-900" data-i18n="Giao diện & Ngôn ngữ">Giao diện & Ngôn ngữ</h2>
        <p class="text-sm text-gray-500" data-i18n="Tùy chỉnh cách ứng dụng hiển thị với bạn.">Tùy chỉnh cách ứng dụng hiển thị với bạn.</p>
      </div>
    </div>
    <div class="space-y-4 p-6">
      {{-- Ngôn ngữ --}}
      <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border bg-slate-50/80 p-4">
        <div class="flex min-w-0 items-center gap-3">
          <svg class="h-5 w-5 shrink-0 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
          <div>
            <p class="text-sm font-bold text-gray-900" data-i18n="Ngôn ngữ hiển thị">Ngôn ngữ hiển thị</p>
            <p class="text-xs text-gray-500" data-i18n="Chọn ngôn ngữ ưu tiên cho giao diện.">Chọn ngôn ngữ ưu tiên cho giao diện.</p>
          </div>
        </div>
        <div class="relative w-full sm:w-[200px]" x-ref="langPicker">
          <button type="button" @click.stop="langMenuOpen = !langMenuOpen"
            class="flex w-full items-center justify-between rounded-md border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm hover:bg-slate-50">
            <span class="flex items-center gap-2">
              <template x-if="language === 'vi'">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 600" width="20" height="14" class="shrink-0"><path fill="#da251d" d="M0 0h900v600H0z"/><path fill="#ff0" d="M450 150l52.5 162.5H675l-135 97.5 52.5 162.5L450 460l-142.5 112.5 52.5-162.5-135-97.5h172.5z"/></svg>
              </template>
              <template x-if="language === 'en'">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 30" width="20" height="14" class="shrink-0"><clipPath id="settings-en-s"><path d="M0 0v30h60V0z"/></clipPath><clipPath id="settings-en-t"><path d="M30 15h30v15H30zV15h-30v-15H30z"/></clipPath><g clip-path="url(#settings-en-s)"><path d="M0 0v30h60V0z" fill="#012169"/><path d="M0 0l60 30m0-30L0 30" stroke="#fff" stroke-width="6"/><path d="M0 0l60 30m0-30L0 30" clip-path="url(#settings-en-t)" stroke="#C8102E" stroke-width="4"/><path d="M30 0v30M0 15h60" stroke="#fff" stroke-width="10"/><path d="M30 0v30M0 15h60" stroke="#C8102E" stroke-width="6"/></g></svg>
              </template>
              <span x-text="languageLabel()"></span>
            </span>
            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div x-show="langMenuOpen" x-cloak @click.stop
            class="absolute right-0 z-20 mt-1 w-full overflow-hidden rounded-md border bg-white py-1 shadow-lg">
            <button type="button" @click="chooseLanguage('vi')" class="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-slate-50" :class="language === 'vi' ? 'bg-slate-50 font-semibold' : ''">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 600" width="20" height="14"><path fill="#da251d" d="M0 0h900v600H0z"/><path fill="#ff0" d="M450 150l52.5 162.5H675l-135 97.5 52.5 162.5L450 460l-142.5 112.5 52.5-162.5-135-97.5h172.5z"/></svg>
              <span data-i18n="Tiếng Việt">Tiếng Việt</span>
            </button>
            <button type="button" @click="chooseLanguage('en')" class="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-slate-50" :class="language === 'en' ? 'bg-slate-50 font-semibold' : ''">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 30" width="20" height="14"><clipPath id="settings-en-s2"><path d="M0 0v30h60V0z"/></clipPath><clipPath id="settings-en-t2"><path d="M30 15h30v15H30zV15h-30v-15H30z"/></clipPath><g clip-path="url(#settings-en-s2)"><path d="M0 0v30h60V0z" fill="#012169"/><path d="M0 0l60 30m0-30L0 30" stroke="#fff" stroke-width="6"/><path d="M0 0l60 30m0-30L0 30" clip-path="url(#settings-en-t2)" stroke="#C8102E" stroke-width="4"/><path d="M30 0v30M0 15h60" stroke="#fff" stroke-width="10"/><path d="M30 0v30M0 15h60" stroke="#C8102E" stroke-width="6"/></g></svg>
              <span data-i18n="English">English</span>
            </button>
          </div>
        </div>
      </div>

      {{-- Chế độ tối --}}
      <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border bg-slate-50/80 p-4">
        <div class="flex min-w-0 items-center gap-3">
          <svg class="h-5 w-5 shrink-0 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
          <div>
            <p class="text-sm font-bold text-gray-900" data-i18n="Chế độ tối">Chế độ tối</p>
            <p class="text-xs text-gray-500" data-i18n="Sử dụng giao diện tối để bảo vệ mắt.">Sử dụng giao diện tối để bảo vệ mắt.</p>
          </div>
        </div>
        <div class="flex items-center gap-1 rounded-lg bg-slate-200/60 p-1">
          <button type="button" @click="setTheme('light')"
            class="inline-flex h-8 items-center gap-1.5 rounded-md px-3 text-xs font-semibold transition"
            :class="theme === 'light' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <span data-i18n="Sáng">Sáng</span>
          </button>
          <button type="button" @click="setTheme('dark')"
            class="inline-flex h-8 items-center gap-1.5 rounded-md px-3 text-xs font-semibold transition"
            :class="theme === 'dark' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            <span data-i18n="Tối">Tối</span>
          </button>
          <button type="button" @click="setTheme('system')"
            class="inline-flex h-8 items-center gap-1.5 rounded-md px-3 text-xs font-semibold transition"
            :class="theme === 'system' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <span data-i18n="Hệ thống">Hệ thống</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Thông báo --}}
  <div class="nttu-card shadow-sm">
    <div class="flex items-center gap-4 border-b px-6 py-5">
      <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-500/10">
        <svg class="h-6 w-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
      </div>
      <div>
        <h2 class="text-lg font-bold text-gray-900" data-i18n="Thông báo">Thông báo</h2>
        <p class="text-sm text-gray-500" data-i18n="Quản lý cách bạn nhận thông báo từ hệ thống.">Quản lý cách bạn nhận thông báo từ hệ thống.</p>
      </div>
    </div>
    <div class="space-y-0 p-6">
      <div class="flex items-center justify-between gap-4 py-3">
        <div class="space-y-0.5">
          <p class="text-sm font-bold text-gray-900" data-i18n="Thông báo trình duyệt">Thông báo trình duyệt</p>
          <p class="text-xs text-gray-500" data-i18n="Hiển thị thông báo khi có tin nhắn mới hoặc sự cố.">Hiển thị thông báo khi có tin nhắn mới hoặc sự cố.</p>
        </div>
        <button type="button" role="switch" :aria-checked="browserNotifications" @click="toggleBrowserNotifications()"
          class="nttu-switch" :class="browserNotifications ? 'nttu-switch-on' : ''">
          <span class="nttu-switch-thumb"></span>
        </button>
      </div>
      <div class="flex items-center justify-between gap-4 border-t py-3">
        <div class="space-y-0.5">
          <p class="text-sm font-bold text-gray-900" data-i18n="Âm thanh thông báo">Âm thanh thông báo</p>
          <p class="text-xs text-gray-500" data-i18n="Phát âm thanh khi có thông báo mới.">Phát âm thanh khi có thông báo mới.</p>
        </div>
        <button type="button" role="switch" :aria-checked="notificationSound" @click="toggleNotificationSound()"
          class="nttu-switch" :class="notificationSound ? 'nttu-switch-on' : ''">
          <span class="nttu-switch-thumb"></span>
        </button>
      </div>
    </div>
  </div>

  {{-- Bảo mật nâng cao --}}
  <div class="nttu-card shadow-sm">
    <div class="flex items-center gap-4 border-b px-6 py-5">
      <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-500/10">
        <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
      </div>
      <div>
        <h2 class="text-lg font-bold text-gray-900" data-i18n="Bảo mật nâng cao">Bảo mật nâng cao</h2>
        <p class="text-sm text-gray-500" data-i18n="Các thiết lập bảo mật cấp cao cho tài khoản của bạn.">Các thiết lập bảo mật cấp cao cho tài khoản của bạn.</p>
      </div>
    </div>

    <div class="space-y-6 p-6">
      {{-- Tóm tắt tài khoản --}}
      <div class="grid grid-cols-1 gap-4 rounded-xl border bg-slate-50/80 p-4 sm:grid-cols-2">
        <div>
          <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400" data-i18n="Email đăng nhập">Email đăng nhập</p>
          <p class="mt-1 text-sm font-semibold text-gray-900" x-text="security.account.email"></p>
        </div>
        <div>
          <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400" data-i18n="Hoạt động gần nhất">Hoạt động gần nhất</p>
          <p class="mt-1 text-sm font-semibold text-gray-900" x-text="formatDateTime(security.account.last_seen_at) || '—'"></p>
        </div>
        <div>
          <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400" data-i18n="Tài khoản tạo ngày">Tài khoản tạo ngày</p>
          <p class="mt-1 text-sm font-semibold text-gray-900" x-text="formatDateTime(security.account.created_at, true) || '—'"></p>
        </div>
        <div>
          <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400" data-i18n="Xác minh email">Xác minh email</p>
          <p class="mt-1 text-sm font-semibold" :class="security.account.email_verified ? 'text-emerald-600' : 'text-amber-600'"
            x-text="security.account.email_verified ? verifiedLabel() : unverifiedLabel()"></p>
        </div>
      </div>

      {{-- Đổi mật khẩu --}}
      <div class="rounded-xl border p-4">
        <div class="mb-4 flex items-start gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[var(--nttu-primary)]/10 text-[var(--nttu-primary)]">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          </div>
          <div>
            <h3 class="text-sm font-bold text-gray-900" data-i18n="Đổi mật khẩu truy cập">Đổi mật khẩu truy cập</h3>
            <p class="text-xs text-gray-500" data-i18n="Để đảm bảo an toàn, vui lòng cập nhật mật khẩu định kỳ.">Để đảm bảo an toàn, vui lòng cập nhật mật khẩu định kỳ.</p>
          </div>
        </div>
        <form @submit.prevent="changePassword()" class="grid max-w-md gap-4">
          <div class="space-y-1">
            <x-form-label class="font-semibold text-gray-900" icon="lock" tone="orange" data-i18n="Mật khẩu hiện tại">Mật khẩu hiện tại</x-form-label>
            <input type="password" x-model="passwordForm.current_password" placeholder="••••••••" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            <p class="text-xs text-red-600" x-show="passwordErrors.current_password" x-text="passwordErrors.current_password"></p>
          </div>
          <div class="space-y-1">
            <x-form-label class="font-semibold text-gray-900" icon="key" tone="amber" data-i18n="Mật khẩu mới">Mật khẩu mới</x-form-label>
            <input type="password" x-model="passwordForm.password" placeholder="••••••••" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            <p class="text-xs text-red-600" x-show="passwordErrors.password" x-text="passwordErrors.password"></p>
          </div>
          <div class="space-y-1">
            <x-form-label class="font-semibold text-gray-900" icon="check" tone="green" data-i18n="Xác nhận mật khẩu mới">Xác nhận mật khẩu mới</x-form-label>
            <input type="password" x-model="passwordForm.password_confirmation" placeholder="••••••••" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
          </div>
          <div>
            <button type="submit" :disabled="changingPassword"
              class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-5 py-2 text-sm font-bold text-white hover:opacity-90 disabled:opacity-60">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
              <span x-text="changingPassword ? changingPasswordLabel() : confirmChangeLabel()"></span>
            </button>
            <p class="mt-2 text-xs text-emerald-600" x-show="passwordSuccess" x-text="passwordSuccess"></p>
          </div>
        </form>
      </div>

      {{-- Phiên đăng nhập --}}
      <div class="rounded-xl border p-4">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h3 class="text-sm font-bold text-gray-900" data-i18n="Phiên đăng nhập đang hoạt động">Phiên đăng nhập đang hoạt động</h3>
            <p class="text-xs text-gray-500" data-i18n="Quản lý các thiết bị đang đăng nhập tài khoản của bạn.">Quản lý các thiết bị đang đăng nhập tài khoản của bạn.</p>
          </div>
          <button type="button" @click="destroyOtherSessions()" :disabled="revokingSessions || otherSessionsCount() === 0"
            class="rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 disabled:opacity-50"
            data-i18n="Đăng xuất tất cả thiết bị khác">
            Đăng xuất tất cả thiết bị khác
          </button>
        </div>
        <div class="space-y-2">
          <template x-for="session in security.sessions" :key="session.id">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-white px-4 py-3">
              <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900" x-text="session.device"></p>
                <p class="text-xs text-gray-500">
                  <span x-text="session.ip_address || '—'"></span>
                  <span class="mx-1">·</span>
                  <span data-i18n="Hoạt động (phiên)">Hoạt động (phiên)</span>:
                  <span x-text="formatDateTime(session.last_active)"></span>
                </p>
              </div>
              <div class="flex items-center gap-2">
                <span x-show="session.is_current" class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700" data-i18n="Phiên hiện tại">Phiên hiện tại</span>
                <button type="button" x-show="!session.is_current" @click="destroySession(session.id)" :disabled="revokingSessions"
                  class="rounded-md border border-slate-200 px-2 py-1 text-xs font-medium text-gray-600 hover:bg-slate-50 disabled:opacity-50"
                  data-i18n="Thu hồi">
                  Thu hồi
                </button>
              </div>
            </div>
          </template>
          <p x-show="security.sessions.length === 0" class="py-4 text-center text-sm text-gray-500" data-i18n="Không có phiên đăng nhập nào.">Không có phiên đăng nhập nào.</p>
        </div>
      </div>

      {{-- Nhật ký bảo mật --}}
      <div class="rounded-xl border p-4">
        <h3 class="mb-1 text-sm font-bold text-gray-900" data-i18n="Nhật ký bảo mật gần đây">Nhật ký bảo mật gần đây</h3>
        <p class="mb-4 text-xs text-gray-500" data-i18n="Theo dõi đăng nhập, đổi mật khẩu và thao tác phiên.">Theo dõi đăng nhập, đổi mật khẩu và thao tác phiên.</p>
        <div class="space-y-2">
          <template x-for="(item, index) in security.recent_activity" :key="index">
            <div class="flex flex-wrap items-start justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2.5">
              <div class="min-w-0">
                <p class="text-sm font-medium text-gray-900" x-text="item.action"></p>
                <p class="text-xs text-gray-500" x-show="item.details" x-text="item.details"></p>
              </div>
              <div class="shrink-0 text-right text-xs text-gray-400">
                <p x-text="formatDateTime(item.logged_at)"></p>
                <p x-show="item.ip_address" x-text="item.ip_address"></p>
              </div>
            </div>
          </template>
          <p x-show="security.recent_activity.length === 0" class="py-4 text-center text-sm italic text-gray-500" data-i18n="Chưa có hoạt động bảo mật nào được ghi nhận.">Chưa có hoạt động bảo mật nào được ghi nhận.</p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
