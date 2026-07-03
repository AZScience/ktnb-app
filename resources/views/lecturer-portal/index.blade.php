@extends('layouts.public')
@section('title', 'Cổng Giảng viên — Check-in TH ngoài')
@section('content')
<div class="min-h-screen p-4 md:p-8 max-w-xl mx-auto"
    x-data="lecturerPortalPage({
        searchUrl: @js(route('lecturer-portal.search')),
        authUrl: @js(route('lecturer-portal.auth.google')),
        logoutUrl: @js(route('lecturer-portal.logout')),
        submitUrl: @js(route('lecturer-portal.submit')),
        evidenceUploadUrl: @js(route('lecturer-portal.evidence.upload')),
        googleClientId: @js($googleClientId),
        googleUser: @js($googleUser),
        appUrl: @js($appUrl ?? ''),
        csrfToken: @js(csrf_token()),
    })"
    x-init="@if(session('success')) onSubmitSuccess() @endif">

    <template x-if="submitted">
        <div class="text-center py-12 space-y-4">
            <div class="mx-auto h-20 w-20 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 text-4xl">✓</div>
            <h2 class="text-2xl font-black text-emerald-700">Gửi check-in thành công!</h2>
            <p class="text-sm text-slate-600">Phòng Kiểm tra sẽ xem xét và duyệt trong mục Giám sát thực hành.</p>
            <button type="button" @click="resetForm()" class="inline-flex items-center gap-2 rounded-xl bg-[var(--nttu-table-head)] text-white font-bold px-8 py-3 mt-4">
                <x-form-field-icon name="refresh" tone="green" class="h-4 w-4 text-white" />
                Check-in lớp khác
            </button>
        </div>
    </template>

    <template x-if="!submitted && !googleUser">
        <div class="bg-white rounded-2xl shadow-lg border overflow-hidden">
            <div class="text-center px-6 pt-8 pb-4">
                <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 mb-3">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <h1 class="text-2xl font-black text-[var(--nttu-primary)]">CỔNG CHECK-IN GIẢNG VIÊN</h1>
                <p class="text-sm text-gray-600 mt-1">Thực hành ngoài trường — NTTU</p>
            </div>

            <div class="px-6 pb-8 space-y-4">
                <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-600 leading-relaxed">
                    Giảng viên <strong>không cần đăng nhập ứng dụng nội bộ</strong>. Vui lòng xác thực bằng
                    <strong>tài khoản Google</strong> (email trường hoặc email đã đăng ký trong hệ thống).
                </div>

                <template x-if="!googleClientId">
                    <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 text-sm leading-relaxed">
                        Chưa cấu hình <strong>Google Client ID</strong>.
                        Quản trị viên vào <strong>Tham số hệ thống → Cổng Giảng viên</strong> để thiết lập.
                    </div>
                </template>

                <template x-if="googleClientId">
                    <div class="flex flex-col items-center gap-3 py-4">
                        <div class="w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-950 leading-relaxed">
                            <p class="font-bold mb-1">Nếu Google báo lỗi 400 origin_mismatch</p>
                            <p class="mb-2">Thêm <strong>đúng</strong> các URL sau vào
                                <a href="https://console.cloud.google.com/auth/clients" target="_blank" rel="noopener" class="underline font-medium">Google Console → Clients</a>
                                → OAuth client Web → <em>Authorized JavaScript origins</em> (không có <code>/</code> cuối, không có path):</p>
                            <ul class="space-y-1 font-mono text-[11px]">
                                <template x-for="origin in requiredOrigins" :key="origin">
                                    <li class="rounded bg-white/80 px-2 py-1 border border-amber-100" x-text="origin"></li>
                                </template>
                            </ul>
                            <p class="mt-2 text-amber-800">Sau khi Save, đợi 1–2 phút rồi mở lại trang bằng <strong>cùng URL</strong> đã khai báo.</p>
                        </div>
                        <div x-ref="googleButton" class="min-h-[44px]"></div>
                        <p x-show="pageOrigin" class="text-xs text-slate-500 text-center max-w-sm">
                            Origin hiện tại: <code class="rounded bg-slate-100 px-1 font-semibold text-slate-800" x-text="pageOrigin"></code>
                        </p>
                        <p x-show="authError" x-text="authError" class="text-sm text-red-600 text-center"></p>
                        <p x-show="authLoading" class="text-sm text-slate-500">Đang xác thực Google...</p>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <template x-if="!submitted && googleUser">
        <div>
            <div class="text-center mb-4">
                <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 mb-3">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <h1 class="text-2xl font-black text-[var(--nttu-primary)]">CỔNG CHECK-IN GIẢNG VIÊN</h1>
                <p class="text-sm text-gray-600 mt-1">Thực hành ngoài trường — NTTU</p>
            </div>

            <div class="mb-4 flex items-center justify-between gap-3 rounded-xl border bg-white px-4 py-3 shadow-sm">
                <div class="flex items-center gap-3 min-w-0">
                    <img x-show="googleUser.picture" :src="googleUser.picture" alt="" class="h-10 w-10 rounded-full border shrink-0">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-slate-800 truncate" x-text="googleUser.name"></p>
                        <p class="text-xs text-slate-500 truncate" x-text="googleUser.email"></p>
                    </div>
                </div>
                <form method="POST" action="{{ route('lecturer-portal.logout') }}">
                    @csrf
                    <x-nttu-button type="submit" action="logout" size="sm" class="text-xs font-bold whitespace-nowrap">Đăng xuất</x-nttu-button>
                </form>
            </div>

            <form method="POST" action="{{ route('lecturer-portal.submit') }}" class="space-y-3" @submit="submitCheckin($event)">
                @csrf

                <div class="bg-white rounded-xl shadow border overflow-hidden">
                    <button type="button" @click="toggleSection(1)" class="w-full flex items-center justify-between px-5 py-4 font-bold text-left">
                        <span class="inline-flex items-center gap-2">
                            <x-form-field-icon name="search" tone="blue" class="h-4 w-4" />
                            1. Tra cứu lớp học
                        </span>
                        <span x-text="openSection === 1 ? '−' : '+'"></span>
                    </button>
                    <div x-show="openSection === 1" class="px-5 pb-5 space-y-3 border-t pt-4">
                        <div class="flex items-end gap-2">
                            <div class="flex-1 min-w-0">
                                <x-filter-label>Mã lớp</x-filter-label>
                                <input type="text" x-model="searchClass" @keydown.enter.prevent="search()" class="w-full rounded-md border px-3 py-2 text-sm mt-1 uppercase" placeholder="VD: CNTT01">
                            </div>
                            <x-nttu-button type="button" action="find" class="shrink-0 mb-px" @click="search()" x-bind:disabled="loadingClass">
                                <span x-text="loadingClass ? 'Đang tìm...' : 'Tìm lớp'"></span>
                            </x-nttu-button>
                        </div>
                        <template x-if="classInfo">
                            <div class="bg-blue-50 rounded-lg p-3 text-sm space-y-2 border border-blue-100">
                                <p class="flex items-center gap-1.5"><x-form-field-icon name="graduation" tone="blue" class="h-3.5 w-3.5" /><strong>Lớp:</strong> <span x-text="classInfo.Class"></span></p>
                                <p class="flex items-center gap-1.5"><x-form-field-icon name="book-user" tone="teal" class="h-3.5 w-3.5" /><strong>Môn:</strong> <span x-text="classInfo.Course"></span></p>
                                <p class="flex items-center gap-1.5"><x-form-field-icon name="user" tone="cyan" class="h-3.5 w-3.5" /><strong>GV:</strong> <span x-text="classInfo.Lecturer"></span></p>
                                <p class="flex items-center gap-1.5"><x-form-field-icon name="door" tone="yellow" class="h-3.5 w-3.5" /><strong>Phòng:</strong> <span x-text="(classInfo.Building || '') + ' - ' + (classInfo.Room || '')"></span></p>
                                <input type="hidden" name="class_id" :value="searchClass">
                                <input type="hidden" name="class" :value="classInfo.Class">
                                <input type="hidden" name="class_name" :value="classInfo.Course">
                                <input type="hidden" name="schedule_date" :value="searchDate">
                                <input type="hidden" name="lecturer" :value="classInfo.Lecturer">
                                <input type="hidden" name="building" :value="classInfo.Building || ''">
                                <input type="hidden" name="room" :value="classInfo.Room || ''">
                                <input type="hidden" name="period" :value="classInfo.Period || ''">
                                <input type="hidden" name="student_count" :value="classInfo.StudentCount || ''">
                            </div>
                        </template>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow border overflow-hidden">
                    <button type="button" @click="toggleSection(2)" class="w-full flex items-center justify-between px-5 py-4 font-bold text-left">
                        <span class="inline-flex items-center gap-2">
                            <x-form-field-icon name="users" tone="blue" class="h-4 w-4" />
                            2. Sĩ số & việc phát sinh
                        </span>
                        <span x-text="openSection === 2 ? '−' : '+'"></span>
                    </button>
                    <div x-show="openSection === 2" class="px-5 pb-5 space-y-3 border-t pt-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <x-filter-label>Số SV có mặt thực tế</x-filter-label>
                                <input type="number" name="actual_student_count" min="0" class="w-full rounded-md border px-3 py-2 text-sm mt-1">
                            </div>
                            <div>
                                <x-filter-label>Việc phát sinh</x-filter-label>
                                <select name="incident" class="w-full rounded-md border px-3 py-2 text-sm mt-1">
                                    <option value="none">Không có</option>
                                    @foreach ($incidents as $inc)
                                        <option value="{{ $inc->name }}">{{ $inc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <x-filter-label>Chi tiết sự việc</x-filter-label>
                            <textarea name="incident_detail" rows="2" class="w-full rounded-md border px-3 py-2 text-sm mt-1" placeholder="Chi tiết sự việc (nếu có)"></textarea>
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <x-form-field-icon name="megaphone" tone="amber" class="h-3.5 w-3.5" />
                            <input type="checkbox" name="is_notification" value="1" class="rounded border-gray-300">
                            <span>Cần thông báo Phòng Kiểm tra</span>
                        </label>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow border overflow-hidden">
                    <button type="button" @click="toggleSection(3)" class="w-full flex items-center justify-between px-5 py-4 font-bold text-left">
                        <span class="inline-flex items-center gap-2">
                            <x-form-field-icon name="map-pin" tone="rose" class="h-4 w-4" />
                            3. Vị trí GPS <span class="text-red-500 text-xs font-bold">*</span>
                        </span>
                        <span x-text="openSection === 3 ? '−' : '+'"></span>
                    </button>
                    <div x-show="openSection === 3" class="px-5 pb-5 space-y-3 border-t pt-4">
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Trình duyệt sẽ hỏi quyền <strong>Vị trí</strong>. Chọn <strong>Cho phép</strong> để ghi nhận tọa độ thực hành ngoài.
                        </p>
                        <x-nttu-button type="button" action="gps" class="w-full" @click="getLocation()" x-bind:disabled="locationLoading">
                            <span x-text="locationLoading ? 'Đang lấy GPS...' : (locationDenied ? 'Thử lại GPS' : 'Lấy tọa độ GPS')"></span>
                        </x-nttu-button>
                        <p class="text-xs text-center" :class="lat && lng ? 'text-emerald-600 font-medium' : 'text-slate-500'" x-text="locationText"></p>
                        <div x-show="locationError" x-cloak class="rounded-lg border border-red-200 bg-red-50 px-3 py-3 text-xs text-red-800 leading-relaxed space-y-2">
                            <p x-text="locationError"></p>
                            <template x-if="locationDenied">
                                <ul class="list-disc pl-4 space-y-1 text-red-700">
                                    <li><strong>Chrome / Edge:</strong> Bấm biểu tượng 🔒 hoặc ⓘ trên thanh địa chỉ → Vị trí → <em>Cho phép</em> → tải lại trang.</li>
                                    <li><strong>Safari (iPhone):</strong> Cài đặt → Quyền riêng tư → Dịch vụ định vị → bật cho Safari; hoặc trong Safari chọn <em>Cho phép</em> khi được hỏi.</li>
                                    <li><strong>Android:</strong> Cài đặt → Ứng dụng → Trình duyệt → Quyền → Vị trí → Cho phép.</li>
                                </ul>
                            </template>
                        </div>
                        <input type="hidden" name="latitude" :value="lat">
                        <input type="hidden" name="longitude" :value="lng">
                        <iframe x-show="mapEmbedUrl" :src="mapEmbedUrl" class="w-full h-40 rounded-lg border" loading="lazy"></iframe>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow border overflow-hidden">
                    <button type="button" @click="toggleSection(4)" class="w-full flex items-center justify-between px-5 py-4 font-bold text-left">
                        <span class="inline-flex items-center gap-2">
                            <x-form-field-icon name="camera" tone="blue" class="h-5 w-5" />
                            4. Hình ảnh minh chứng <span class="text-red-500 text-xs font-bold">*</span>
                        </span>
                        <span x-text="openSection === 4 ? '−' : '+'"></span>
                    </button>
                    <div x-show="openSection === 4" class="px-5 pb-5 border-t pt-4">
                        <x-evidence-input-panel :only-camera="true" />
                        <input type="hidden" name="evidence" :value="evidenceItems.join('|')">
                    </div>
                </div>

                <div x-show="toastMessage" x-cloak class="rounded-lg border px-3 py-2 text-sm"
                    :class="toastType === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
                    x-text="toastMessage"></div>

                <x-nttu-button type="submit" action="send" class="w-full font-black py-4 text-lg shadow-lg" x-bind:disabled="!canSubmit">
                    <span x-text="submitLabel"></span>
                </x-nttu-button>
            </form>
        </div>
    </template>
</div>

@if ($googleClientId)
    <script src="https://accounts.google.com/gsi/client" async defer></script>
@endif
@endsection
