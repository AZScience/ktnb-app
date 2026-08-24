@props([
    'resetClick' => 'resetAdvancedFilters()',
    'applyClick' => 'applyAdvancedFilters()',
    'resetLabel' => 'Xóa tất cả',
    'applyLabel' => 'Áp dụng bộ lọc',
    'resetAlpine' => null,
    'applyAlpine' => null,
    'showIcons' => true,
])

<div {{ $attributes->merge(['class' => 'flex justify-end gap-2 border-t bg-slate-50/80 px-6 py-4']) }}>
    <button type="button" @click="{{ $resetClick }}" class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm text-red-600 hover:bg-red-50">
        @if ($showIcons)
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        @endif
        @if ($resetAlpine)
            <span x-text="{{ $resetAlpine }}"></span>
        @else
            {{ $resetLabel }}
        @endif
    </button>
    <button type="button" @click="{{ $applyClick }}" class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white">
        @if ($showIcons)
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        @endif
        @if ($applyAlpine)
            <span x-text="{{ $applyAlpine }}"></span>
        @else
            {{ $applyLabel }}
        @endif
    </button>
</div>
