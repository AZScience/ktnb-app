{{-- Shared sort/filter popover for daily report column headers. Expects Alpine scope: tabKey, col --}}
<div class="space-y-1 p-1.5">
    <button type="button" class="flex h-9 w-full items-center gap-2 rounded px-2 text-left text-xs font-medium text-gray-700 hover:bg-slate-50"
        @click="requestSort(tabKey, col.key, 'ascending')">
        <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
        Sắp xếp tăng dần
    </button>
    <button type="button" class="flex h-9 w-full items-center gap-2 rounded px-2 text-left text-xs font-medium text-gray-700 hover:bg-slate-50"
        @click="requestSort(tabKey, col.key, 'descending')">
        <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        Sắp xếp giảm dần
    </button>
    <template x-if="sortStateFor(tabKey, col.key)">
        <button type="button" class="nttu-popover-clear-action flex h-9 w-full items-center gap-2 rounded border-t px-2 text-left text-xs font-medium"
            @click="clearSort(tabKey)">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            Xóa sắp xếp
        </button>
    </template>
</div>
<div class="space-y-2 border-t bg-gray-50/50 p-3">
    <div class="relative">
        <svg class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        <input type="text" class="h-9 w-full rounded-md border border-gray-100 bg-white pl-9 text-xs text-gray-900 shadow-sm"
            :placeholder="`Lọc ${col.label}...`"
            :value="stateFor(tabKey).filters[col.key] || ''"
            @input="setFilter(tabKey, col.key, $event.target.value)"
            @keydown.enter="stateFor(tabKey).openPopover = null">
    </div>
    <button type="button" x-show="isFiltered(tabKey, col.key)" x-cloak
        class="nttu-popover-clear-action flex h-8 w-full items-center gap-2 rounded px-2 text-left text-[11px] font-bold"
        @click="clearFilter(tabKey, col.key)">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        Xóa bộ lọc
    </button>
</div>
