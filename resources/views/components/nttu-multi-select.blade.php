@props([
    'field',
    'placeholder' => 'Chọn...',
    'searchPlaceholder' => 'Tìm kiếm...',
    'emptyText' => 'Không tìm thấy',
    'allowCreate' => true,
    'chipMode' => 'chips',
    'countSuffix' => 'đã chọn',
    'size' => 'md',
])

@php
    $triggerClass = $size === 'sm'
        ? 'flex min-h-9 w-full items-center justify-between gap-2 overflow-hidden rounded-md border border-gray-200 bg-white px-3 py-1 text-left text-sm text-gray-900 shadow-sm hover:border-gray-300'
        : 'flex min-h-10 w-full items-center justify-between gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-left text-sm hover:bg-slate-50';
    $chipClass = $size === 'sm'
        ? 'inline-flex max-w-[150px] shrink-0 items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-800'
        : 'inline-flex max-w-[150px] shrink-0 items-center gap-1 rounded-md border bg-slate-100 px-2 py-0.5 text-xs';
    $searchClass = $size === 'sm'
        ? 'h-7 w-full rounded border-gray-200 bg-white px-2 text-xs text-gray-900'
        : 'w-full border-b px-3 py-2 text-sm';
    $optionClass = $size === 'sm'
        ? 'flex w-full items-center gap-2 rounded px-2 py-1.5 text-left text-xs text-gray-900 hover:bg-gray-50'
        : 'flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-slate-50';
@endphp

<div class="nttu-multi-select relative">
    <button type="button"
        data-ms-trigger="{{ $field }}"
        @click.stop="nttuMultiToggleOpen(@js($field))"
        {{ $attributes->merge(['class' => $triggerClass]) }}>
        <div class="flex min-w-0 flex-1 flex-wrap gap-1 items-center">
            @if ($chipMode === 'count')
                <span x-show="!nttuMultiValues(@js($field)).length" class="truncate text-gray-400">{{ $placeholder }}</span>
                <span x-show="nttuMultiValues(@js($field)).length" x-cloak
                    class="inline-flex items-center rounded-md border bg-slate-100 px-2 py-0.5 text-xs"
                    x-text="nttuMultiValues(@js($field)).length + ' {{ $countSuffix }}'"></span>
            @else
                <template x-if="!nttuMultiValues(@js($field)).length">
                    <span class="truncate text-gray-400">{{ $placeholder }}</span>
                </template>
                <template x-for="val in nttuMultiValues(@js($field))" :key="val">
                    <span class="{{ $chipClass }}">
                        <span class="truncate" x-text="nttuMultiLabel(@js($field), val)"></span>
                        <button type="button" class="shrink-0 text-gray-400 hover:text-red-500" @click.stop="nttuMultiRemove(@js($field), val, $event)">×</button>
                    </span>
                </template>
            @endif
        </div>
        <svg class="h-4 w-4 shrink-0 text-gray-400 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <template x-teleport="body">
        <div x-show="multiSelectOpenField === @js($field)"
            x-nttu-multi-panel="'{{ $field }}'"
            data-ms-panel="{{ $field }}"
            x-cloak
            class="nttu-multi-select-panel overflow-hidden rounded-md border border-gray-200 bg-white text-gray-900 shadow-xl"
            style="min-width: 280px;">
            <div class="{{ $size === 'sm' ? 'border-b bg-gray-50 p-2' : 'border-b' }}">
                <input type="text"
                    x-model="multiSelectSearch[@js($field)]"
                    @keydown="nttuMultiOnSearchKeydown(@js($field), $event)"
                    placeholder="{{ $searchPlaceholder }}"
                    class="{{ $searchClass }}">
            </div>
            <div class="max-h-[min(250px,calc(100vh-8rem))] overflow-y-auto {{ $size === 'sm' ? 'p-1' : '' }}">
                @if ($allowCreate)
                <button type="button" x-show="nttuMultiCanAdd(@js($field))" x-cloak
                    @click="nttuMultiAddCustom(@js($field))"
                    class="flex w-full items-center gap-2 border-b border-dashed border-blue-200 bg-blue-50/60 px-3 py-2 text-left text-xs font-medium text-blue-700 hover:bg-blue-50">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Thêm "<span x-text="nttuMultiSearchText(@js($field))"></span>"</span>
                </button>
                @endif
                <template x-if="nttuMultiFilteredOptions(@js($field)).length === 0 && !nttuMultiCanAdd(@js($field))">
                    <p class="px-3 py-4 text-center text-xs text-gray-400">{{ $emptyText }}</p>
                </template>
                <template x-for="opt in nttuMultiFilteredOptions(@js($field))" :key="opt.value">
                    <button type="button" @click="nttuMultiToggle(@js($field), opt.value)"
                        class="{{ $optionClass }}"
                        :class="nttuMultiIsSelected(@js($field), opt.value) && '{{ $size === 'sm' ? 'bg-blue-50 text-blue-800 font-medium' : '' }}'">
                        <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-sm border text-[10px]"
                            :class="nttuMultiIsSelected(@js($field), opt.value) ? 'border-[var(--nttu-primary)] bg-[var(--nttu-primary)] text-white' : 'border-gray-300 bg-white text-transparent'">✓</span>
                        <span class="truncate" x-text="opt.label"></span>
                    </button>
                </template>
            </div>
            <div x-show="nttuMultiValues(@js($field)).length > 0" x-cloak class="border-t bg-gray-50 p-1">
                <button type="button" @click="nttuMultiClear(@js($field))"
                    class="w-full rounded px-2 py-1 text-[10px] text-gray-600 hover:bg-red-50 hover:text-red-600">
                    Xóa tất cả (<span x-text="nttuMultiValues(@js($field)).length"></span>)
                </button>
            </div>
        </div>
    </template>
</div>
