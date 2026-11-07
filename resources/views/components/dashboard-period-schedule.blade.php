@props([
    'periodSchedule' => null,
    'defaultImageUrl',
    'canEdit' => false,
])

@php
    $scheduleVersion = $periodSchedule['updated_at'] ?? null;
    $previewUrl = $periodSchedule
        ? route('dashboard.period-schedule.file').($scheduleVersion ? '?v='.urlencode($scheduleVersion) : '')
        : $defaultImageUrl;
    $downloadUrl = $periodSchedule
        ? route('dashboard.period-schedule.file', ['download' => 1])
        : $defaultImageUrl;
@endphp

<div
    class="nttu-card overflow-hidden border border-cyan-200 shadow-md"
    x-data="Object.assign(periodScheduleWidget({
        uploadUrl: @js(route('dashboard.period-schedule.store')),
        canEdit: @js($canEdit),
    }), { lightbox: false })"
>
    <div class="flex flex-col gap-3 border-b border-cyan-100 bg-gradient-to-r from-cyan-50/90 to-sky-50/80 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2 min-w-0">
            <svg class="h-5 w-5 text-cyan-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-gray-800 i18n-auto">Bảng tiết học</h2>
                <p class="text-[11px] text-gray-500 i18n-auto">Khung giờ các tiết trong ngày</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2 w-full sm:w-auto">
            @if($periodSchedule)
                <p class="mb-0 w-full text-right text-xs text-gray-500 sm:mr-2 sm:w-auto sm:text-left">
                    Cập nhật:
                    {{ \Illuminate\Support\Carbon::parse($periodSchedule['updated_at'])->diffForHumans() }}
                    bởi {{ $periodSchedule['updated_by_name'] ?? 'Hệ thống' }}
                </p>
            @endif

            <input
                type="file"
                x-ref="fileInput"
                class="hidden"
                accept="image/jpeg,image/png,image/webp,image/gif"
                @change="onFileSelected($event)"
            >

            <button
                type="button"
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50"
                title="Phóng to xem rõ"
                aria-label="Phóng to xem rõ"
                @click="lightbox = true"
            >
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
            </button>

            @if($canEdit)
            <button
                type="button"
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="uploading"
                title="Đổi hình"
                aria-label="Đổi hình"
                @click="pickFile()"
            >
                <svg x-show="!uploading" class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <svg x-show="uploading" x-cloak class="h-4 w-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            </button>
            @endif

            <a
                href="{{ $downloadUrl }}"
                @if($periodSchedule) download @endif
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-cyan-600 text-white shadow-sm hover:bg-cyan-700"
                title="Tải về"
                aria-label="Tải về"
            >
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            </a>
        </div>
    </div>

    <div class="overflow-x-auto bg-slate-50/60 p-2 sm:p-4 min-w-0 w-full max-w-full">
        <img
            src="{{ $previewUrl }}"
            alt="Bảng tiết học theo giờ"
            class="mx-auto w-full max-w-full h-auto rounded-lg border border-cyan-100 bg-white shadow-sm cursor-zoom-in hover:opacity-95 transition"
            loading="lazy"
            @click="lightbox = true"
        >
        <p class="text-center text-[11px] text-gray-500 mt-1 md:hidden">Chạm vào hình để xem phóng to</p>
    </div>

    {{-- Lightbox phóng to --}}
    <div
        x-show="lightbox"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 p-2 sm:p-4 backdrop-blur-sm"
        @click="lightbox = false"
        @keydown.escape.window="lightbox = false"
    >
        <div class="relative max-h-[92vh] max-w-[96vw] overflow-auto rounded-xl bg-white p-2 shadow-2xl" @click.stop>
            <div class="flex items-center justify-between border-b pb-2 mb-2 px-2">
                <span class="font-semibold text-sm text-gray-800">Bảng tiết học theo giờ</span>
                <button type="button" @click="lightbox = false" class="rounded-full bg-gray-100 p-1.5 text-gray-600 hover:bg-gray-200 transition" title="Đóng">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <img src="{{ $previewUrl }}" alt="Bảng tiết học theo giờ" class="max-h-[82vh] max-w-full w-auto object-contain mx-auto rounded">
        </div>
    </div>
</div>
