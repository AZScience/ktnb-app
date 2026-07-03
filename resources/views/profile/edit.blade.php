@extends('layouts.nttu')
@section('title', 'Hồ sơ nhân viên')
@section('page-section', 'Hồ sơ nhân viên')
@section('page-title', 'Quản lý thông tin định danh và bảo mật tài khoản.')

@section('content')
@php
    $profileConfig = [
        'employee' => $employee ? [
            'nickname' => $employee->nickname,
            'phone' => $employee->phone,
            'address' => $employee->address,
            'birth_date' => $employee->birth_date,
            'avatar_url' => $employee->avatar_url,
        ] : null,
        'routes' => [
            'update' => route('profile.update'),
        ],
    ];
@endphp

<div
    x-data="profilePage(@js($profileConfig))"
    class="mx-auto max-w-6xl"
>
    @if (session('success'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif
    @if (session('status') === 'password-updated')
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">Đã đổi mật khẩu thành công.</div>
    @endif

    @if (!$employee)
        <div class="nttu-card p-12 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100 text-red-600">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Không tìm thấy thông tin hồ sơ</h3>
            <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">Tài khoản của bạn chưa được liên kết với hồ sơ nhân viên. Vui lòng liên hệ quản trị viên.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
            {{-- Sidebar --}}
            <div class="lg:col-span-1">
                <div class="nttu-card overflow-hidden border-t-4 border-t-[var(--nttu-primary)]">
                    <div class="flex flex-col items-center px-6 pb-6 pt-8">
                        <button type="button" @click="openAvatarDialog()" class="group relative mb-6 transition-transform hover:scale-105">
                            <div class="flex h-32 w-32 items-center justify-center overflow-hidden rounded-full border-4 border-white bg-slate-100 text-4xl font-bold text-[var(--nttu-primary)] shadow-2xl ring-2 ring-[var(--nttu-primary)]/10">
                                <img x-show="form.avatar_url" :src="form.avatar_url" alt="{{ $employee->name }}" class="h-full w-full object-cover">
                                <span x-show="!form.avatar_url">{{ mb_strtoupper(mb_substr($employee->name, 0, 1)) }}</span>
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center rounded-full bg-black/40 opacity-0 transition-opacity group-hover:opacity-100">
                                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/></svg>
                            </div>
                        </button>

                        <h2 class="px-2 text-center text-xl font-bold text-gray-900">{{ $employee->name }}</h2>
                        <p class="mb-4 text-center text-sm text-gray-500">{{ $positionName ?? 'Thành viên' }}</p>
                        <span class="mb-6 rounded-full bg-[var(--nttu-primary)]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-[var(--nttu-primary)]">{{ $roleName }}</span>

                        <div class="w-full space-y-4 border-t pt-6">
                            <div class="flex items-center gap-4">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100">
                                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Email định danh</p>
                                    <p class="truncate text-sm font-semibold text-gray-800">{{ $employee->email ?? $user->email }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100">
                                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Số điện thoại</p>
                                    <p class="truncate text-sm font-semibold text-gray-800" x-text="form.phone || '---'"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100">
                                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Mã nhân viên</p>
                                    <p class="truncate text-sm font-semibold text-gray-800">{{ $employee->employee_id ?? '---' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main tabs --}}
            <div class="lg:col-span-3">
                <div class="nttu-card flex h-full flex-col">
                    <div class="border-b px-6 pt-4">
                        <div class="flex gap-8">
                            <button type="button" @click="tab = 'personal'"
                                class="border-b-2 pb-4 text-[11px] font-bold uppercase tracking-wider transition"
                                :class="tab === 'personal' ? 'border-[var(--nttu-primary)] text-[var(--nttu-primary)]' : 'border-transparent text-gray-500 hover:text-gray-700'">
                                Thông tin cá nhân
                            </button>
                            <button type="button" @click="tab = 'security'"
                                class="border-b-2 pb-4 text-[11px] font-bold uppercase tracking-wider transition"
                                :class="tab === 'security' ? 'border-[var(--nttu-primary)] text-[var(--nttu-primary)]' : 'border-transparent text-gray-500 hover:text-gray-700'">
                                Bảo mật tài khoản
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 p-6">
                        <div x-show="tab === 'personal'" class="space-y-8">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="flex items-center gap-1 text-sm font-semibold text-gray-900">Họ và tên <span class="text-red-500">*</span></label>
                                    <input type="text" value="{{ $employee->name }}" disabled class="w-full cursor-not-allowed rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-gray-700">
                                    <p class="text-[10px] text-gray-400">Liên hệ nhân sự nếu cần đổi tên định danh.</p>
                                </div>
                                <div class="space-y-2">
                                    <x-form-label class="font-semibold text-gray-900">Biệt danh / Tên gọi khác</x-form-label>
                                    <input type="text" x-model="form.nickname" placeholder="Ví dụ: Phúc Nguyễn" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                                </div>
                                <div class="space-y-2">
                                    <x-form-label class="font-semibold text-gray-900">Số điện thoại liên lạc</x-form-label>
                                    <input type="text" x-model="form.phone" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                                </div>
                                <div class="space-y-2">
                                    <x-form-label class="font-semibold text-gray-900">Ngày sinh</x-form-label>
                                    <input type="date" x-model="form.birth_date" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                                </div>
                            </div>
                            <div class="space-y-2">
                                <x-form-label class="font-semibold text-gray-900">Địa chỉ thường trú</x-form-label>
                                <input type="text" x-model="form.address" placeholder="Nhập địa chỉ nhà..." class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                            </div>
                            <div class="flex justify-end gap-2 border-t pt-6">
                                <x-nttu-button type="button" action="undo" @click="revertForm()">Hoàn tác</x-nttu-button>
                                <button type="button" @click="saveProfile()" :disabled="saving"
                                    class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-8 py-2 text-sm font-bold text-white shadow-lg hover:opacity-90 disabled:opacity-60">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                    <span x-text="saving ? 'Đang xử lý...' : 'Cập nhật hồ sơ'"></span>
                                </button>
                            </div>
                        </div>

                        <div x-show="tab === 'security'" x-cloak class="mx-auto max-w-md space-y-6">
                            <div class="mb-8 space-y-2 text-center">
                                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-[var(--nttu-primary)]/10 text-[var(--nttu-primary)]">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">Đổi mật khẩu truy cập</h3>
                                <p class="text-xs text-gray-500">Để đảm bảo an toàn, vui lòng cập nhật mật khẩu định kỳ.</p>
                            </div>

                            <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                                @csrf
                                @method('PUT')
                                <div class="space-y-2">
                                    <x-form-label class="font-semibold text-gray-900" icon="lock" tone="orange">Mật khẩu hiện tại</x-form-label>
                                    <input type="password" name="current_password" required placeholder="••••••••" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                                    @if ($errors->updatePassword->has('current_password'))
                                        <p class="text-xs text-red-600">{{ $errors->updatePassword->first('current_password') }}</p>
                                    @endif
                                </div>
                                <div class="space-y-2">
                                    <x-form-label class="font-semibold text-gray-900" icon="key" tone="amber">Mật khẩu mới</x-form-label>
                                    <input type="password" name="password" required placeholder="••••••••" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                                    @if ($errors->updatePassword->has('password'))
                                        <p class="text-xs text-red-600">{{ $errors->updatePassword->first('password') }}</p>
                                    @endif
                                </div>
                                <div class="space-y-2">
                                    <x-form-label class="font-semibold text-gray-900" icon="check" tone="green">Xác nhận mật khẩu mới</x-form-label>
                                    <input type="password" name="password_confirmation" required placeholder="••••••••" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                                </div>
                                <button type="submit" class="mt-4 flex w-full items-center justify-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2.5 text-sm font-bold text-white hover:opacity-90">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                    Xác nhận thay đổi
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                </div>
            </div>

        {{-- Avatar dialog --}}
        <div x-show="avatarDialogOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="closeAvatarDialog()"></div>
            <div class="relative flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-lg bg-white shadow-xl" @click.stop>
                <div class="border-b px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Cập nhật Ảnh đại diện</h3>
                    <p class="mt-1 text-sm text-gray-500">Sử dụng liên kết, tải tệp từ máy tính hoặc chụp ảnh trực tiếp.</p>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-6">
                    <x-avatar-input field-key="avatar_url" label="Ảnh đại diện" />
                </div>
                <div class="flex justify-end gap-2 border-t bg-slate-50 px-6 py-4">
                    <x-nttu-button type="button" action="cancel" @click="closeAvatarDialog()">Hủy</x-nttu-button>
                    <button type="button" @click="saveProfile(true)" :disabled="saving"
                        class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-60">
                        <span x-text="saving ? 'Đang lưu...' : 'Lưu thay đổi'"></span>
                    </button>
                </div>
            </div>
        </div>
    @endif
    </div>
@endsection
