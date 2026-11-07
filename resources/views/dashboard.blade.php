@extends('layouts.nttu')

@section('title', 'Tổng quan')
@section('page-section', 'Tổng quan')
@section('page-title', 'Chào mừng bạn quay trở lại hệ thống Kiểm tra nội bộ.')

@section('content')
@php
    $dashPerms = $nttuPage('/dashboard');
    $msgCanAdd = $nttuCan('/messaging', 'add');
@endphp
<div
    class="space-y-5 min-w-0 w-full max-w-full"
    x-data="{ birthdayModal: false, birthdayAction: '', birthdayBody: '', birthdayName: '' }"
>

    {{-- Sinh nhật hôm nay --}}
    @if($birthdays->count())
    <div class="rounded-xl border border-pink-200 bg-gradient-to-r from-pink-100 via-fuchsia-50 to-purple-100 p-3 sm:p-4 shadow-sm min-w-0 w-full max-w-full overflow-hidden">
        <p class="text-xs font-bold uppercase text-pink-700 mb-3 flex items-center gap-2">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
            <span class="i18n-auto">Sinh nhật hôm nay</span>
        </p>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($birthdays as $emp)
                <div class="flex items-center gap-3 bg-white rounded-xl px-4 py-3 shadow-sm border border-pink-100">
                    @if($emp->avatar_url)
                        <img src="{{ $emp->avatar_url }}" alt="{{ $emp->name }}" class="h-10 w-10 rounded-full object-cover border-2 border-pink-300">
                    @else
                        <div class="h-10 w-10 rounded-full bg-pink-100 flex items-center justify-center text-pink-600 font-bold text-sm border-2 border-pink-300">{{ mb_substr($emp->name, 0, 1) }}</div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-sm text-gray-800 truncate">{{ $emp->name }}</p>
                        <p class="text-xs text-pink-600">
                            @if($emp->position_name){{ $emp->position_name }}@else<span class="i18n-auto">Chưa có chức vụ</span>@endif
                        </p>
                    </div>
                    @if($emp->user_id && $msgCanAdd)
                        <button
                            type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-pink-600 text-white hover:bg-pink-700 transition"
                            @click="
                                birthdayModal = true;
                                birthdayAction = '{{ route('dashboard.birthdays.send', $emp) }}';
                                birthdayName = @js($emp->name);
                                birthdayBody = @js('Chúc mừng sinh nhật '.$emp->name.'! Chúc bạn tuổi mới nhiều sức khỏe, niềm vui và thành công.');
                            "
                            title="Gửi lời chúc sinh nhật"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Thông báo nội bộ --}}
    @if($announcements->count())
    <div class="rounded-xl border border-amber-200 bg-gradient-to-r from-amber-50 via-yellow-50 to-orange-50 p-3 sm:p-4 shadow-sm min-w-0 w-full max-w-full overflow-hidden">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <p class="text-xs font-bold uppercase text-amber-800 flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                <span class="i18n-auto">Thông báo</span>
            </p>
            @if($nttuCan('/tools/announcements', 'add'))
            <a href="{{ route('announcements.index') }}" class="text-xs font-medium text-amber-800 hover:underline i18n-auto">Đăng thông báo →</a>
            @endif
        </div>
        <div class="space-y-3">
            @foreach($announcements as $announcement)
                <article class="rounded-xl border border-amber-100 bg-white px-4 py-3 shadow-sm">
                    <h3 class="font-semibold text-sm text-gray-900">{{ $announcement->title }}</h3>
                    <div class="ck-content prose prose-sm max-w-none mt-2 text-gray-700 leading-relaxed min-w-0 w-full max-w-full overflow-x-auto break-words">{!! $announcement->body !!}</div>
                    <p class="mt-2 text-[11px] text-gray-400">
                        {{ $announcement->created_by_name ?: 'Hệ thống' }}
                        @if($announcement->created_at)
                            · {{ $announcement->created_at->format('d/m/Y H:i') }}
                        @endif
                    </p>
                </article>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Main grid: 8 + 4 --}}
    <div class="grid gap-5 lg:grid-cols-12 min-w-0 w-full max-w-full">

        {{-- Left col: Tổng qua hoạt động + Tra cứu lịch + Lịch trực --}}
        <div class="lg:col-span-8 space-y-5 min-w-0 w-full max-w-full">

            {{-- DailyActivitySummary --}}
            <div class="nttu-card overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span class="font-semibold text-gray-800 i18n-auto">Tổng quan hoạt động trong ngày</span>
                        <span class="text-xs text-gray-400 ml-1"><span class="i18n-auto">hôm nay, ngày</span> {{ $today }}</span>
                    </div>
                    @if($nttuCan('/settings/schedule', 'access'))
                    <a href="{{ route('schedules.index') }}" class="text-xs text-blue-600 hover:underline i18n-auto">Xem lịch →</a>
                    @endif
                </div>
                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5 gap-3">
                    @foreach($modules as $key => $info)
                        <details class="nttu-card border-t-4 {{ $info['border'] }} p-4 hover:shadow-md transition group">
                            <summary class="list-none cursor-pointer">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs {{ $info['text'] }} font-medium mb-2 i18n-auto">{{ $info['label'] }}</p>
                                        <p class="text-3xl font-black text-gray-800">{{ $moduleCounts[$key] }}</p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <span class="text-green-600 font-medium">{{ $moduleHandled[$key] }} <span class="i18n-auto">đã giám sát</span></span>
                                            @if($moduleCounts[$key] - $moduleHandled[$key] > 0)
                                                · <span class="text-orange-500">{{ $moduleCounts[$key] - $moduleHandled[$key] }} <span class="i18n-auto">chưa</span></span>
                                            @endif
                                        </p>
                                    </div>
                                    <svg class="h-4 w-4 text-gray-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </summary>

                            <div class="mt-3 space-y-2 border-t pt-3">
                                @forelse(($moduleBreakdowns[$key] ?? collect()) as $department => $meta)
                                    <div class="rounded-lg {{ $info['bg'] }} p-2">
                                        <div class="mb-1 flex items-center justify-between gap-2">
                                            <span class="text-xs font-semibold text-gray-700 truncate">{{ $department }}</span>
                                            <span class="rounded-full bg-white/80 px-2 py-0.5 text-[11px] font-bold {{ $info['text'] }}">{{ $meta['total'] }}</span>
                                        </div>
                                        <div class="space-y-1">
                                            @foreach($meta['buildings'] as $building => $count)
                                                <div class="flex items-center justify-between text-[11px] text-gray-600">
                                                    <span class="truncate">{{ $building }}</span>
                                                    <span class="font-semibold">{{ $count }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400 i18n-auto">Chưa có dữ liệu.</p>
                                @endforelse
                                @if($nttuCan(\App\Support\PagePermissionFlags::dashboardMonitoringModule($key), 'access'))
                                <a href="{{ route("monitoring.{$key}.index") }}" class="inline-flex items-center text-xs font-medium text-blue-600 hover:underline i18n-auto">Mở danh sách →</a>
                                @endif
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>

            <x-dashboard-schedule-viewer
                :rows="$scheduleLookupRows"
                :master-data="$scheduleMasterData"
                :initial-date="$today"
            />

            {{-- Lịch trực (ShiftScheduleWidget) --}}
            @php
                $shiftScheduleVersion = $shiftSchedule['updated_at'] ?? null;
                $shiftSchedulePreviewUrl = $shiftSchedule
                    ? route('dashboard.shift-schedule.file').($shiftScheduleVersion ? '?v='.urlencode($shiftScheduleVersion) : '')
                    : null;
            @endphp
            <div
                class="nttu-card overflow-hidden border border-blue-200 shadow-md transition-all duration-300"
                x-data="Object.assign(shiftScheduleWidget({
                    uploadUrl: @js(route('dashboard.shift-schedule.store')),
                    downloadUrl: @js($shiftSchedule ? route('dashboard.shift-schedule.file', ['download' => 1]) : null),
                    hasSchedule: @js((bool) $shiftSchedule),
                    canEdit: @js($dashPerms['edit']),
                }), { imageModal: false })"
            >
                <div
                    class="flex flex-col gap-3 border-b border-blue-100 bg-slate-50/80 px-4 py-3 sm:flex-row sm:items-center sm:justify-between cursor-pointer hover:bg-slate-100/80 transition-colors"
                    @click="expanded = !expanded"
                >
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="text-lg font-semibold text-gray-800">Lịch trực</span>
                        <svg class="h-5 w-5 text-gray-400 transition-transform duration-200" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-2 w-full sm:w-auto" @click.stop>
                        @if($shiftSchedule)
                            <p class="mb-0 w-full text-right text-xs text-gray-500 sm:mr-2 sm:w-auto sm:text-left">
                                Cập nhật:
                                {{ \Illuminate\Support\Carbon::parse($shiftSchedule['updated_at'])->diffForHumans() }}
                                bởi {{ $shiftSchedule['updated_by_name'] ?? 'Hệ thống' }}
                            </p>
                        @endif

                        <input
                            type="file"
                            x-ref="fileInput"
                            class="hidden"
                            accept=".pdf,image/*"
                            @change="onFileSelected($event)"
                        >

                        @if($dashPerms['edit'])
                        <button
                            type="button"
                            class="inline-flex h-8 shrink-0 items-center rounded-md border border-gray-300 bg-white px-3 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="uploading"
                            @click="pickFile()"
                        >
                            <svg x-show="!uploading" class="mr-2 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            <svg x-show="uploading" x-cloak class="mr-2 h-4 w-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <span x-text="uploading ? 'Đang tải...' : (hasSchedule ? 'Cập nhật' : 'Tải lên lịch')"></span>
                        </button>
                        @endif

                        @if($shiftSchedule)
                            <a
                                href="{{ route('dashboard.shift-schedule.file', ['download' => 1]) }}"
                                class="inline-flex h-8 shrink-0 items-center rounded-md bg-blue-600 px-3 text-xs font-medium text-white shadow-sm hover:bg-blue-700"
                            >
                                <svg class="mr-2 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Tải về
                            </a>
                        @endif
                    </div>
                </div>

                <div x-show="expanded" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="min-w-0 w-full max-w-full overflow-hidden">
                    @if($shiftSchedule)
                        <div class="relative flex min-h-[300px] md:min-h-[650px] items-center justify-center bg-slate-50/60 overflow-hidden min-w-0 w-full max-w-full">
                            @if(($shiftSchedule['preview_kind'] ?? '') === 'image')
                                <div class="w-full text-center p-2 cursor-pointer overflow-x-auto" @click="imageModal = true">
                                    <img
                                        src="{{ $shiftSchedulePreviewUrl }}"
                                        alt="{{ $shiftSchedule['name'] }}"
                                        class="max-h-[800px] w-auto max-w-full object-contain mx-auto p-2 rounded cursor-zoom-in hover:opacity-95 transition"
                                    >
                                    <p class="text-xs text-blue-600 mt-1 md:hidden">Chạm vào hình để xem phóng to</p>
                                </div>
                                <div
                                    x-show="imageModal"
                                    x-cloak
                                    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 p-2 sm:p-4 backdrop-blur-sm"
                                    @click="imageModal = false"
                                    @keydown.escape.window="imageModal = false"
                                >
                                    <div class="relative max-h-[92vh] max-w-[96vw] overflow-auto rounded-xl bg-white p-2 shadow-2xl" @click.stop>
                                        <div class="flex items-center justify-between border-b pb-2 mb-2 px-2">
                                            <span class="font-semibold text-sm text-gray-800">{{ $shiftSchedule['name'] }}</span>
                                            <button type="button" @click="imageModal = false" class="rounded-full bg-gray-100 p-1.5 text-gray-600 hover:bg-gray-200 transition" title="Đóng">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                        <img src="{{ $shiftSchedulePreviewUrl }}" alt="{{ $shiftSchedule['name'] }}" class="max-h-[82vh] max-w-full w-auto object-contain mx-auto rounded">
                                    </div>
                                </div>
                            @elseif(($shiftSchedule['preview_kind'] ?? '') === 'pdf')
                                {{-- Giao diện trên điện thoại / Mobile: Nhúng trình xem PDF.js Canvas trực quan, responsive 100% --}}
                                <div class="block md:hidden w-full bg-slate-50 border-b border-slate-200" x-data="mobilePdfViewer('{{ $shiftSchedulePreviewUrl }}')">
                                    <!-- Thanh điều khiển PDF trên Mobile -->
                                    <div class="flex items-center justify-between px-3 py-2 bg-slate-800 text-white text-xs select-none shadow-sm">
                                        <div class="flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="prevPage()"
                                                :disabled="pageNum <= 1 || loading"
                                                class="p-1.5 rounded hover:bg-slate-700 disabled:opacity-30 disabled:hover:bg-transparent transition"
                                                title="Trang trước"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                            </button>
                                            <span class="font-semibold text-slate-200 min-w-[55px] text-center">
                                                <span x-text="pageNum">1</span> / <span x-text="totalPages || 1">1</span>
                                            </span>
                                            <button
                                                type="button"
                                                @click="nextPage()"
                                                :disabled="pageNum >= totalPages || loading"
                                                class="p-1.5 rounded hover:bg-slate-700 disabled:opacity-30 disabled:hover:bg-transparent transition"
                                                title="Trang kế"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                        </div>

                                        <div class="flex items-center gap-1">
                                            <button
                                                type="button"
                                                @click="zoomOut()"
                                                :disabled="scale <= 0.6 || loading"
                                                class="p-1.5 rounded hover:bg-slate-700 disabled:opacity-30 transition"
                                                title="Thu nhỏ"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                            </button>
                                            <button
                                                type="button"
                                                @click="fitWidth()"
                                                class="px-2 py-1 rounded bg-slate-700 hover:bg-slate-600 text-[11px] font-bold text-amber-300 transition"
                                                title="Vừa màn hình"
                                            >
                                                Vừa khung
                                            </button>
                                            <button
                                                type="button"
                                                @click="zoomIn()"
                                                :disabled="scale >= 2.5 || loading"
                                                class="p-1.5 rounded hover:bg-slate-700 disabled:opacity-30 transition"
                                                title="Phóng to"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            </button>
                                            <button
                                                type="button"
                                                @click="openFullscreen()"
                                                class="p-1.5 rounded bg-blue-600 hover:bg-blue-500 text-white transition ml-1"
                                                title="Xem toàn màn hình"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Khung hiển thị Canvas PDF -->
                                    <div class="p-2 sm:p-3 overflow-x-auto min-h-[350px] flex items-center justify-center relative bg-slate-200/60">
                                        <!-- Loading spinner -->
                                        <div x-show="loading" class="flex flex-col items-center justify-center py-12 text-slate-500">
                                            <svg class="animate-spin h-8 w-8 text-blue-600 mb-2" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                            <span class="text-xs font-semibold">Đang tải lịch trực PDF...</span>
                                        </div>

                                        <!-- Lỗi fallback -->
                                        <div x-show="error && !loading" x-cloak class="p-4 text-center max-w-sm">
                                            <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-xl bg-red-100 text-red-600">
                                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            </div>
                                            <p class="text-xs text-slate-700 font-medium mb-3" x-text="error"></p>
                                            <a href="{{ $shiftSchedulePreviewUrl }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-bold shadow">
                                                Mở file PDF trực tiếp
                                            </a>
                                        </div>

                                        <!-- Canvas chứa trang PDF -->
                                        <div x-show="!loading && !error" class="w-full flex justify-center cursor-pointer" @click="openFullscreen()" title="Chạm để phóng to toàn màn hình">
                                            <canvas x-ref="pdfCanvas" class="shadow-lg rounded-lg bg-white border border-slate-300 max-w-full transition-transform"></canvas>
                                        </div>
                                    </div>

                                    <!-- Hướng dẫn & Nút tải về phía dưới -->
                                    <div class="flex items-center justify-between px-3 py-2 bg-slate-100 border-t border-slate-200 text-xs text-slate-500">
                                        <span class="flex items-center gap-1 text-[11px] text-blue-600 font-medium">
                                            💡 Chạm vào để xem toàn màn hình
                                        </span>
                                        <a href="{{ route('dashboard.shift-schedule.file', ['download' => 1]) }}" class="inline-flex items-center gap-1 font-bold text-blue-600 hover:text-blue-800">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            Tải PDF về máy
                                        </a>
                                    </div>

                                    <!-- Modal Xem Toàn Màn Hình trên Mobile -->
                                    <div
                                        x-show="fullscreenModal"
                                        x-cloak
                                        class="fixed inset-0 z-[120] flex flex-col bg-slate-950 text-white"
                                        @keydown.escape.window="fullscreenModal = false"
                                    >
                                        <!-- Header modal -->
                                        <div class="flex items-center justify-between px-3 py-2.5 bg-slate-900 border-b border-slate-800">
                                            <span class="font-bold text-xs truncate max-w-[200px] text-slate-200">{{ $shiftSchedule['name'] }}</span>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-slate-400 font-mono" x-text="pageNum + ' / ' + totalPages"></span>
                                                <button type="button" @click="fullscreenModal = false" class="p-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                        <!-- Vùng hiển thị canvas trong modal -->
                                        <div class="flex-1 overflow-auto p-2 flex items-center justify-center bg-black/90" @click="fullscreenModal = false">
                                            <canvas x-ref="modalCanvas" class="shadow-2xl rounded max-w-full" @click.stop></canvas>
                                        </div>
                                        <!-- Footer modal điều khiển -->
                                        <div class="flex items-center justify-between px-4 py-2.5 bg-slate-900 border-t border-slate-800 text-xs">
                                            <button type="button" @click="prevModalPage()" :disabled="pageNum <= 1" class="px-3 py-1.5 rounded bg-slate-800 disabled:opacity-30 font-bold">◀ Trước</button>
                                            <div class="flex items-center gap-2">
                                                <button type="button" @click="zoomModalOut()" class="p-1.5 rounded bg-slate-800 font-bold">➖</button>
                                                <button type="button" @click="zoomModalIn()" class="p-1.5 rounded bg-slate-800 font-bold">➕</button>
                                            </div>
                                            <button type="button" @click="nextModalPage()" :disabled="pageNum >= totalPages" class="px-3 py-1.5 rounded bg-slate-800 disabled:opacity-30 font-bold">Sau ▶</button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Giao diện trên Máy tính / Desktop: nhúng Iframe xem trực tiếp --}}
                                <div class="hidden md:block w-full max-w-full overflow-hidden">
                                    <iframe
                                        src="{{ $shiftSchedulePreviewUrl }}#view=FitH"
                                        class="h-[650px] w-full max-w-full border-0 lg:h-[800px]"
                                        title="Lịch trực"
                                    ></iframe>
                                </div>
                            @else
                                <div class="p-8 text-center text-gray-500">
                                    <svg class="mx-auto mb-4 h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="font-medium">{{ $shiftSchedule['name'] }}</p>
                                    <a href="{{ route('dashboard.shift-schedule.file', ['download' => 1]) }}" class="mt-2 inline-flex text-blue-600 hover:underline">Tải file</a>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="flex h-[300px] flex-col items-center justify-center bg-slate-50/40">
                            <svg class="mb-4 h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p class="mb-4 text-sm text-gray-500">Chưa có file lịch trực nào được tải lên.</p>
                            @if($dashPerms['edit'])
                            <button
                                type="button"
                                class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60"
                                :disabled="uploading"
                                @click="pickFile()"
                            >
                                <svg x-show="!uploading" class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                <svg x-show="uploading" x-cloak class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span x-text="uploading ? 'Đang tải...' : 'Tải lên ngay'"></span>
                            </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- Right col: UserEngagement + RecentActivity --}}
        <div class="lg:col-span-4 space-y-5 min-w-0 w-full max-w-full">

            <x-dashboard-period-schedule
                :period-schedule="$periodSchedule"
                :default-image-url="$defaultPeriodScheduleUrl"
                :can-edit="$dashPerms['edit']"
            />

            {{-- UserEngagement (Thống kê truy cập) --}}
            <div class="nttu-card overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
                    <svg class="h-4 w-4 text-cyan-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="font-semibold text-sm text-gray-800">Thống kê truy cập</span>
                </div>
                <div class="p-4 grid grid-cols-2 gap-3">
                    @foreach([
                        ['Đang online', $visitStats['online'], 'text-green-600'],
                        ['Hôm nay', $visitStats['today'], 'text-blue-600'],
                        ['Tuần này', $visitStats['weekly'], 'text-purple-600'],
                        ['Tháng này', $visitStats['monthly'], 'text-orange-600'],
                    ] as [$label, $value, $color])
                        <div class="rounded-lg bg-gray-50 p-4 text-center">
                            <p class="text-2xl font-bold {{ $color }}">{{ $value }}</p>
                            <p class="text-xs text-gray-500 mt-1 i18n-auto">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- RecentActivity --}}
            <div class="nttu-card overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="font-semibold text-sm text-gray-800 i18n-auto">Hoạt động gần đây</span>
                    </div>
                    @if($nttuCan('/settings/access-log', 'access'))
                    <a href="{{ route('activity-logs.index') }}" class="text-xs text-blue-600 hover:underline i18n-auto">Xem tất cả →</a>
                    @endif
                </div>
                <div class="divide-y max-h-80 overflow-y-auto">
                    @forelse($recentLogs as $log)
                        <div class="px-4 py-2.5 flex items-start gap-2.5">
                            <div class="h-7 w-7 rounded-full bg-cyan-100 flex items-center justify-center text-cyan-700 font-bold text-[10px] shrink-0 mt-0.5">
                                {{ mb_strtoupper(mb_substr($log->user_email ?? 'S', 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium text-gray-800 truncate">{{ $log->action }}</p>
                                <p class="text-[11px] text-gray-400 truncate">
                                    {{ $log->user_email }}
                                    @if($log->logged_at) · {{ optional($log->logged_at)->diffForHumans() }} @endif
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-6 text-xs text-gray-400 text-center i18n-auto">Không có hoạt động nào gần đây.</div>
                    @endforelse
                </div>
            </div>

            {{-- Tổng quan hệ thống --}}
            <div class="nttu-card overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
                    <svg class="h-4 w-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                    <span class="font-semibold text-sm text-gray-800 i18n-auto">Tổng quan hệ thống</span>
                </div>
                <div class="p-4 space-y-1.5 text-sm">
                    @foreach($totals as [$label, $count, $url, $color])
                        @php
                            $totalModule = match (true) {
                                str_contains($url, 'student-violations') => '/monitoring/student-violations',
                                str_contains($url, 'reports/comprehensive') => '/reports/comprehensive',
                                str_contains($url, 'asset-check') => '/monitoring/asset-check',
                                str_contains($url, 'petitions') => '/monitoring/petitions',
                                default => null,
                            };
                        @endphp
                        @if($totalModule === null || $nttuCan($totalModule, 'access'))
                        <a href="{{ $url }}" class="flex justify-between hover:bg-slate-50 px-2 py-1.5 rounded">
                            <span class="text-gray-600 i18n-auto">{{ $label }}</span>
                            <span class="font-bold {{ $color }}">{{ $count }}</span>
                        </a>
                        @endif
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    {{-- Tổng quan hệ thống — biểu đồ --}}
    <div
        id="system-overview-charts"
        class="nttu-card overflow-hidden border-t-4 border-t-indigo-500"
        data-charts='@json($systemOverviewCharts)'
    >
        <div class="bg-gradient-to-r from-indigo-600 via-violet-600 to-fuchsia-600 px-5 py-4 text-white">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-indigo-100 i18n-auto">Phân tích tổng hợp</p>
                    <h2 class="text-lg font-bold i18n-auto">Tổng quan hệ thống theo Khoa &amp; Dãy nhà</h2>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @foreach($totals as [$label, $count, $url, $color])
                        <a href="{{ $url }}" class="rounded-lg bg-white/15 backdrop-blur px-3 py-2 text-center hover:bg-white/25 transition">
                            <p class="text-lg font-bold leading-none">{{ $count }}</p>
                            <p class="text-[10px] mt-1 text-indigo-100 leading-tight i18n-auto">{{ $label }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="p-5 grid lg:grid-cols-2 gap-6">
            <div class="rounded-xl border border-indigo-100 bg-gradient-to-br from-white to-indigo-50/40 p-4 shadow-sm">
                <div class="mb-3 flex items-center gap-2 border-l-4 border-indigo-500 pl-3">
                    <svg class="h-5 w-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <div>
                        <p class="text-sm font-bold text-gray-800">Theo Khoa</p>
                        <p class="text-[11px] text-gray-500">Tiếp nhận tài sản &amp; đơn thư gom nhóm "Không theo khoa"</p>
                    </div>
                </div>
                <div class="relative h-[340px]">
                    <canvas data-chart="department" class="!h-full !w-full"></canvas>
                    <div data-empty-chart="byDepartment" class="hidden absolute inset-0 flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-indigo-200 bg-white/80 text-center p-6">
                        <svg class="h-10 w-10 text-indigo-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <p class="text-sm font-medium text-gray-600">Chưa có dữ liệu theo khoa</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-fuchsia-100 bg-gradient-to-br from-white to-fuchsia-50/40 p-4 shadow-sm">
                <div class="mb-3 flex items-center gap-2 border-l-4 border-fuchsia-500 pl-3">
                    <svg class="h-5 w-5 text-fuchsia-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <div>
                        <p class="text-sm font-bold text-gray-800">Theo Dãy nhà</p>
                        <p class="text-[11px] text-gray-500">Top dãy nhà có số liệu cao nhất</p>
                    </div>
                </div>
                <div class="relative h-[340px]">
                    <canvas data-chart="building" class="!h-full !w-full"></canvas>
                    <div data-empty-chart="byBuilding" class="hidden absolute inset-0 flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-fuchsia-200 bg-white/80 text-center p-6">
                        <svg class="h-10 w-10 text-fuchsia-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <p class="text-sm font-medium text-gray-600">Chưa có dữ liệu theo dãy nhà</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- IncidentChart --}}
    @if($todayIncidents->count())
    <div class="nttu-card overflow-hidden border-t-4 border-t-red-500">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
            <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span class="font-semibold text-red-600">Các phát sinh việc không phù hợp ({{ $todayIncidents->count() }} trường hợp)</span>
        </div>
        <div class="p-4 grid lg:grid-cols-2 gap-6">
            <div>
                <p class="text-sm font-semibold text-gray-700 mb-3 border-l-4 border-blue-500 pl-2">Sự cố theo Giảng viên</p>
                <div class="space-y-2">
                    @foreach($incidentByLecturer as $name => $count)
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-600 w-28 truncate">{{ $name ?: 'Chưa rõ' }}</span>
                            <div class="flex-1 bg-gray-100 rounded-full h-5 overflow-hidden">
                                <div class="h-full {{ $loop->first ? 'bg-red-500' : 'bg-blue-500' }} rounded-full flex items-center justify-end pr-2"
                                     style="width: {{ round($count / $maxLecturer * 100) }}%">
                                    <span class="text-[10px] text-white font-bold">{{ $count }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-700 mb-3 border-l-4 border-orange-500 pl-2">Sự cố theo Lớp (Sinh viên)</p>
                <div class="space-y-2">
                    @foreach($incidentByClass as $name => $count)
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-600 w-28 truncate">{{ $name ?: 'Chưa rõ' }}</span>
                            <div class="flex-1 bg-gray-100 rounded-full h-5 overflow-hidden">
                                <div class="h-full {{ $loop->first ? 'bg-orange-500' : 'bg-purple-500' }} rounded-full flex items-center justify-end pr-2"
                                     style="width: {{ round($count / $maxClass * 100) }}%">
                                    <span class="text-[10px] text-white font-bold">{{ $count }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <div
        x-show="birthdayModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        @keydown.escape.window="birthdayModal = false"
    >
        <div class="w-full max-w-2xl rounded-xl bg-white shadow-2xl" @click.outside="birthdayModal = false">
            <div class="flex items-center justify-between border-b px-5 py-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Gửi lời chúc sinh nhật</h3>
                    <p class="text-sm text-gray-500" x-text="birthdayName"></p>
                </div>
                <button type="button" class="text-gray-400 hover:text-gray-600" @click="birthdayModal = false">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form :action="birthdayAction" method="POST" class="p-5 space-y-4">
                @csrf
                <textarea
                    name="body"
                    x-model="birthdayBody"
                    rows="6"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-pink-500 focus:ring-pink-500"
                ></textarea>
                <div class="flex justify-end gap-2">
                    <x-nttu-button type="button" action="cancel" @click="birthdayModal = false">Hủy</x-nttu-button>
                    <button type="submit" class="rounded-md bg-pink-600 px-4 py-2 text-sm font-medium text-white hover:bg-pink-700">Gửi lời chúc</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function mobilePdfViewer(pdfUrl) {
    return {
        url: pdfUrl,
        pdfDoc: null,
        pageNum: 1,
        totalPages: 1,
        scale: 1.0,
        modalScale: 1.5,
        loading: true,
        error: null,
        fullscreenModal: false,
        rendering: false,
        pendingPage: null,

        async init() {
            if (!this.url) {
                this.loading = false;
                this.error = 'Không tìm thấy liên kết tệp PDF.';
                return;
            }

            if (typeof pdfjsLib === 'undefined') {
                await this.loadScript('{{ asset("js/pdf.min.js") }}', 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js');
            }

            if (typeof pdfjsLib !== 'undefined') {
                pdfjsLib.GlobalWorkerOptions.workerSrc = '{{ asset("js/pdf.worker.min.js") }}';
                this.loadDocument();
            } else {
                this.loading = false;
                this.error = 'Không thể nạp thư viện xem PDF.';
            }

            window.addEventListener('resize', () => {
                if (this.pdfDoc && !this.loading) {
                    this.renderCurrentPage();
                }
            });
        },

        loadScript(src, fallbackCdn) {
            return new Promise((resolve) => {
                const s = document.createElement('script');
                s.src = src;
                s.onload = () => resolve();
                s.onerror = () => {
                    if (fallbackCdn) {
                        const cdnScript = document.createElement('script');
                        cdnScript.src = fallbackCdn;
                        cdnScript.onload = () => resolve();
                        cdnScript.onerror = () => resolve();
                        document.head.appendChild(cdnScript);
                    } else {
                        resolve();
                    }
                };
                document.head.appendChild(s);
            });
        },

        async loadDocument() {
            try {
                this.loading = true;
                this.error = null;
                const task = pdfjsLib.getDocument({
                    url: this.url,
                    cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                    cMapPacked: true,
                });
                this.pdfDoc = await task.promise;
                this.totalPages = this.pdfDoc.numPages || 1;
                this.pageNum = 1;
                this.$nextTick(() => {
                    this.renderCurrentPage();
                });
            } catch (err) {
                console.error('Lỗi khi nạp PDF:', err);
                this.error = 'Không thể xem trực tiếp tệp PDF trên trình duyệt này.';
            } finally {
                this.loading = false;
            }
        },

        async renderPageToCanvas(canvas, pageNum, zoomScale = 1.0, isFitWidth = true) {
            if (!this.pdfDoc || !canvas) return;
            try {
                const page = await this.pdfDoc.getPage(pageNum);
                const ctx = canvas.getContext('2d');

                let targetWidth = canvas.parentElement ? canvas.parentElement.clientWidth - 16 : window.innerWidth - 32;
                if (targetWidth <= 0) targetWidth = 360;

                const baseViewport = page.getViewport({ scale: 1.0 });
                let finalScale = zoomScale;

                if (isFitWidth) {
                    finalScale = (targetWidth / baseViewport.width) * zoomScale;
                }

                finalScale = Math.max(0.4, Math.min(finalScale, 3.5));
                const viewport = page.getViewport({ scale: finalScale });

                const outputScale = window.devicePixelRatio || 1;
                canvas.width = Math.floor(viewport.width * outputScale);
                canvas.height = Math.floor(viewport.height * outputScale);
                canvas.style.width = Math.floor(viewport.width) + 'px';
                canvas.style.height = Math.floor(viewport.height) + 'px';

                ctx.setTransform(outputScale, 0, 0, outputScale, 0, 0);

                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport,
                };
                await page.render(renderContext).promise;
            } catch (e) {
                console.error('Lỗi render trang PDF:', e);
            }
        },

        async renderCurrentPage() {
            if (this.rendering) {
                this.pendingPage = this.pageNum;
                return;
            }
            this.rendering = true;

            const canvas = this.$refs.pdfCanvas;
            if (canvas) {
                await this.renderPageToCanvas(canvas, this.pageNum, this.scale, true);
            }

            if (this.fullscreenModal && this.$refs.modalCanvas) {
                await this.renderPageToCanvas(this.$refs.modalCanvas, this.pageNum, this.modalScale, false);
            }

            this.rendering = false;
            if (this.pendingPage !== null) {
                const next = this.pendingPage;
                this.pendingPage = null;
                this.renderCurrentPage();
            }
        },

        prevPage() {
            if (this.pageNum <= 1) return;
            this.pageNum--;
            this.renderCurrentPage();
        },

        nextPage() {
            if (this.pageNum >= this.totalPages) return;
            this.pageNum++;
            this.renderCurrentPage();
        },

        zoomIn() {
            this.scale = Math.min(this.scale + 0.25, 2.5);
            this.renderCurrentPage();
        },

        zoomOut() {
            this.scale = Math.max(this.scale - 0.25, 0.6);
            this.renderCurrentPage();
        },

        fitWidth() {
            this.scale = 1.0;
            this.renderCurrentPage();
        },

        openFullscreen() {
            this.fullscreenModal = true;
            this.modalScale = 1.5;
            this.$nextTick(() => {
                if (this.$refs.modalCanvas) {
                    this.renderPageToCanvas(this.$refs.modalCanvas, this.pageNum, this.modalScale, false);
                }
            });
        },

        zoomModalIn() {
            this.modalScale = Math.min(this.modalScale + 0.3, 3.5);
            if (this.$refs.modalCanvas) {
                this.renderPageToCanvas(this.$refs.modalCanvas, this.pageNum, this.modalScale, false);
            }
        },

        zoomModalOut() {
            this.modalScale = Math.max(this.modalScale - 0.3, 0.8);
            if (this.$refs.modalCanvas) {
                this.renderPageToCanvas(this.$refs.modalCanvas, this.pageNum, this.modalScale, false);
            }
        },

        prevModalPage() {
            if (this.pageNum <= 1) return;
            this.pageNum--;
            this.openFullscreen();
        },

        nextModalPage() {
            if (this.pageNum >= this.totalPages) return;
            this.pageNum++;
            this.openFullscreen();
        }
    };
}
</script>

@endsection
