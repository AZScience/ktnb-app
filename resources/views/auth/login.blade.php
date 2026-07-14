<!DOCTYPE html>
<html lang="vi" data-page-title-key="Đăng nhập">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Đăng nhập</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100/80 font-sans antialiased">
    <main class="flex min-h-screen items-center justify-center p-4" x-data="loginPage()">
        <div
            class="grid w-full max-w-4xl min-h-[600px] overflow-hidden rounded-xl border bg-white shadow-xl lg:min-h-[800px] lg:grid-cols-2"
        >
            {{-- Trái: ảnh + quote --}}
            <div class="relative hidden lg:flex lg:flex-col lg:justify-end">
                <img src="{{ $loginImageUrl }}" alt="Login Background" class="absolute inset-0 h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-zinc-900/70 to-transparent"></div>
                <div class="relative z-10 space-y-2 p-10 text-white">
                    <blockquote class="text-lg font-medium">&ldquo;{{ $loginQuote }}&rdquo;</blockquote>
                    <footer class="text-sm font-medium">{{ $loginQuoteAuthor }}</footer>
                </div>
            </div>

            {{-- Phải: form --}}
            <div class="flex items-center justify-center px-6 py-10 sm:px-10">
                <div class="w-full max-w-[350px] space-y-6">
                    <header class="space-y-2 text-center">
                        <div class="mb-4 flex justify-center">
                            <img src="{{ $bannerUrl }}" alt="Logo" class="h-14 object-contain">
                        </div>
                        <h1 class="text-3xl font-bold uppercase text-[var(--nttu-primary)] i18n-auto">Đăng nhập</h1>
                        <p class="text-xs text-gray-500 i18n-auto">
                            Cổng thông tin Kiểm tra nội bộ - Trường Đại học Nguyễn Tất Thành
                        </p>
                    </header>

                    @if (session('status'))
                        <div class="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">{{ session('status') }}</div>
                    @endif

                    <div class="rounded-md border border-blue-100 bg-blue-50/80 px-3 py-2 text-xs text-blue-800 i18n-auto">
                        <strong>Lần đầu đăng nhập:</strong> dùng email công ty và <strong>mã nhân viên</strong> làm mật khẩu (vd. <code class="rounded bg-white/80 px-1">NTT-02715</code>). Sau đó hệ thống sẽ yêu cầu đổi mật khẩu.
                        <span class="mt-1 block text-blue-700/90">Quản trị viên: dùng email và mật khẩu admin đã cấp (không phải mã NV).</span>
                    </div>

                    <form method="POST" action="{{ route('login') }}" class="space-y-4"
                        data-default-email="{{ config('nttu.login_defaults.email') }}"
                        data-default-password="{{ config('nttu.login_defaults.password') }}"
                        @submit="onSubmit($event)">
                        @csrf

                        <div class="space-y-1.5">
                            <label for="email" class="text-sm font-medium text-gray-700 i18n-auto">Email</label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                <input x-ref="email" id="email" type="email" name="email" autofocus autocomplete="username"
                                    placeholder="example@ntt.edu.vn" value="{{ old('email') }}"
                                    class="nttu-form-control pl-10" data-i18n-placeholder-skip>
                            </div>
                            @error('email')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label for="password" class="text-sm font-medium text-gray-700 i18n-auto">Mật khẩu</label>
                                <button type="button" class="text-sm text-[var(--nttu-primary)] underline i18n-auto" @click="resetDialogOpen = true">Quên mật khẩu?</button>
                            </div>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                <input x-ref="password" id="password" :type="showPassword ? 'text' : 'password'" name="password" autocomplete="current-password"
                                    class="nttu-form-control pl-10 pr-10">
                                <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-gray-400 hover:text-gray-600"
                                    @click="showPassword = !showPassword" tabindex="-1" aria-label="Hiển thị">
                                    <svg x-show="!showPassword" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg x-show="showPassword" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                </button>
                            </div>
                            @error('password')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex items-center justify-between gap-3 pt-1">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                                <input type="checkbox" name="remember" x-model="rememberMeChecked" class="rounded border-gray-300 text-[var(--nttu-primary)]">
                                <span class="i18n-auto">Lưu thông tin</span>
                            </label>
                            <button type="submit" :disabled="isSubmitting"
                                class="inline-flex w-[140px] items-center justify-center gap-2 rounded-md bg-[var(--nttu-primary)] px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                                <span x-text="submitLabel"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Dialog quên mật khẩu --}}
        <div x-show="resetDialogOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="resetDialogOpen = false">
            <div class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-xl" @click.outside="resetDialogOpen = false">
                <button type="button" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600" @click="resetDialogOpen = false" aria-label="Đóng">✕</button>
                <h2 class="text-lg font-semibold i18n-auto">Quên mật khẩu</h2>
                <p class="mt-1 text-sm text-gray-500 i18n-auto">Nhập email của bạn để nhận liên kết đặt lại mật khẩu.</p>
                <form method="POST" action="{{ route('password.email') }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label for="reset-email" class="text-sm font-medium i18n-auto">Email đã đăng ký</label>
                        <input id="reset-email" type="email" name="email" required placeholder="example@ntt.edu.vn"
                            value="{{ old('email') }}" class="nttu-form-control mt-1" data-i18n-placeholder-skip>
                    </div>
                    <button type="submit" class="rounded-md bg-[var(--nttu-primary)] px-4 py-2 text-sm font-medium text-white hover:opacity-90 i18n-auto">
                        Gửi liên kết khôi phục
                    </button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
