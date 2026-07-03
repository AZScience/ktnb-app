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
    x-data="periodScheduleWidget({
        uploadUrl: @js(route('dashboard.period-schedule.store')),
        canEdit: @js($canEdit),
    })"
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

    <div class="overflow-auto bg-slate-50/60 p-3 sm:p-4">
        <img
            src="{{ $previewUrl }}"
            alt="Bảng tiết học theo giờ"
            class="mx-auto w-full rounded-lg border border-cyan-100 bg-white shadow-sm"
            loading="lazy"
        >
    </div>
</div>
