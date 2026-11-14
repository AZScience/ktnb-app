{{-- Shared sort/filter popover for daily report column headers. Expects Alpine scope: tabKey, col --}}
<div class="space-y-1 p-1.5">
    <button type="button" class="flex h-9 w-full items-center justify-between rounded px-2 text-left text-xs font-medium text-gray-700 hover:bg-slate-50"
        @click="requestSort(tabKey, col.key, 'ascending')">
        <span class="flex items-center gap-2">
            <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
            <span>Tăng dần</span>
        </span>
        <span x-show="isSortActive(tabKey, col.key, 'ascending')" x-text="sortPriorityBadge(tabKey, col.key)" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200"></span>
    </button>
    <button type="button" class="flex h-9 w-full items-center justify-between rounded px-2 text-left text-xs font-medium text-gray-700 hover:bg-slate-50"
        @click="requestSort(tabKey, col.key, 'descending')">
        <span class="flex items-center gap-2">
            <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            <span>Giảm dần</span>
        </span>
        <span x-show="isSortActive(tabKey, col.key, 'descending')" x-text="sortPriorityBadge(tabKey, col.key)" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200"></span>
    </button>

    <template x-if="sortStateFor(tabKey, col.key)">
        <button type="button" class="nttu-popover-clear-action flex h-9 w-full items-center gap-2 rounded border-t px-2 text-left text-xs font-medium text-rose-600 hover:bg-rose-50 mt-1 pt-2"
            @click="removeSort(tabKey, col.key)">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            Xóa sắp xếp cột này
        </button>
    </template>
    <button type="button" x-show="stateFor(tabKey).sortConfig.length > 1" @click="clearSort(tabKey)" class="nttu-popover-clear-action flex w-full items-center gap-2 px-2 py-1 rounded text-left text-[11px] text-gray-500 hover:bg-gray-100">
        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        <span>Xóa tất cả sắp xếp</span>
    </button>
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
