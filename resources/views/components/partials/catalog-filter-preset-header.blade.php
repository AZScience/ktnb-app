@props([
    'title' => 'Bộ lọc nâng cao',
    'titleAlpine' => null,
    'showPresets' => true,
])

<div class="flex items-center justify-between border-b bg-slate-50/80 px-4 py-3 pr-12">
    <div class="flex items-center gap-3">
        <svg class="h-5 w-5 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        @if ($showPresets)
            <div class="relative">
                <button type="button" @click="presetMenuOpen = !presetMenuOpen" class="flex items-center gap-2 text-lg font-bold text-gray-900 hover:opacity-80">
                    @if ($titleAlpine)
                        <span x-text="{{ $titleAlpine }}"></span>
                    @else
                        {{ $title }}
                    @endif
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="presetMenuOpen" @click.outside="presetMenuOpen = false" x-cloak
                    class="absolute left-0 top-full z-[60] mt-1 w-64 rounded-md border bg-white py-2 shadow-lg text-sm">
                    <p class="px-3 py-1 text-xs font-bold uppercase tracking-wider text-gray-500 flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Bộ lọc đã lưu
                    </p>
                    @include('components.partials.filter-preset-menu-items')
                </div>
            </div>
        @else
            @if ($titleAlpine)
                <h3 class="text-lg font-bold text-gray-900" x-text="{{ $titleAlpine }}"></h3>
            @else
                <h3 class="text-lg font-bold text-gray-900">{{ $title }}</h3>
            @endif
        @endif
    </div>
    @if ($showPresets)
        <div class="flex items-center gap-1">
            <template x-if="presetNaming">
                <div class="flex items-center gap-1">
                    <input type="text" x-model="presetName" placeholder="Tên bộ lọc..." class="h-8 w-32 rounded border-gray-300 text-xs px-2"
                        @keydown.enter.prevent="saveFilterPreset(presetName)">
                    <button type="button" class="h-8 px-2 rounded bg-[var(--nttu-table-head)] text-white text-xs" @click="saveFilterPreset(presetName)">✓</button>
                    <button type="button" class="h-8 px-2 text-gray-500" @click="presetNaming = false; presetName = ''">×</button>
                </div>
            </template>
            <template x-if="!presetNaming">
                <button type="button" class="inline-flex items-center gap-1.5 text-[10px] font-bold text-[var(--nttu-primary)] hover:bg-cyan-50 px-2 py-1.5 rounded" @click="presetNaming = true; presetName = ''">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    Lưu hiện tại
                </button>
            </template>
        </div>
    @endif
</div>
