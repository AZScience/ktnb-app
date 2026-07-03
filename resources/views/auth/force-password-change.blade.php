<!DOCTYPE html>
<html lang="vi" data-page-title-key="Đổi mật khẩu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Đổi mật khẩu lần đầu</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100/80 font-sans antialiased">
    <main class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-md rounded-xl border bg-white p-8 shadow-xl">
            <div class="mb-6 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-amber-50">
                    <svg class="h-7 w-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Thiết lập mật khẩu mới</h1>
                <p class="mt-2 text-sm text-gray-500">
                    Đây là lần đăng nhập đầu tiên. Vui lòng đặt mật khẩu mới để tiếp tục sử dụng hệ thống.
                </p>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.force.update') }}" class="space-y-4">
                @csrf

                <div class="space-y-1.5">
                    <label for="password" class="text-sm font-medium text-gray-700">Mật khẩu mới</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                        class="nttu-form-control w-full" placeholder="Tối thiểu 8 ký tự">
                </div>

                <div class="space-y-1.5">
                    <label for="password_confirmation" class="text-sm font-medium text-gray-700">Xác nhận mật khẩu</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                        class="nttu-form-control w-full">
                </div>

                <button type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2.5 text-sm font-medium text-white hover:opacity-90">
                    Lưu mật khẩu và tiếp tục
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
                @csrf
                <button type="submit" class="text-sm text-gray-500 underline hover:text-gray-700">Đăng xuất</button>
            </form>
        </div>
    </main>
</body>
</html>
