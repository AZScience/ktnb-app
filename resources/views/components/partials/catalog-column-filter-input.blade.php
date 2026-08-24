<div class="border-t p-2">
    <div class="relative">
        <svg class="pointer-events-none absolute left-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        <input type="text" class="h-8 w-full rounded border-gray-300 pl-8 text-xs"
               :placeholder="filterPlaceholder(key)"
               :value="filters[key] || ''"
               @input="setFilter(key, $event.target.value)"
               @keydown.enter="headerPopover = null">
    </div>
    <button type="button" x-show="isFiltered(key)" class="nttu-popover-clear-action mt-1 flex h-8 w-full items-center gap-2 rounded px-2 text-xs" @click="clearColumnFilter(key)">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        <span x-text="text('Xóa bộ lọc')"></span>
    </button>
</div>
