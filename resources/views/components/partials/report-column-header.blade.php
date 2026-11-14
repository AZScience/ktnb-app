@props(['headerDisplay' => null])



<div class="relative">

    <template x-if="isSortable(col)">

        <button type="button" class="group nttu-th-sort-btn"

            :class="columnHeaderAlignClass(col)"

            @click.stop="headerPopover = headerPopover === col.key ? null : col.key">

            <span x-show="columnHeaderIconPath(col)" x-html="columnIconHtml(col)" class="report-header-icon shrink-0"></span>

            <span class="nttu-th-label-text" :class="columnHeaderTextClass(col)" x-text="columnLabel(col)">{{ $headerDisplay }}</span>

            <template x-if="sortDirection(col.key) === 'asc' && !isColumnFiltered(col.key)">
                <span class="nttu-header-badge-active" :title="sortPriority(col.key) ? `Sắp xếp tăng dần (ĐK ${sortPriority(col.key)})` : 'Sắp xếp tăng dần'">
                    <span x-show="sortPriority(col.key)" class="text-[9px] font-extrabold mr-0.5 leading-none" x-text="sortPriority(col.key)"></span>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                </span>
            </template>

            <template x-if="sortDirection(col.key) === 'desc' && !isColumnFiltered(col.key)">
                <span class="nttu-header-badge-active" :title="sortPriority(col.key) ? `Sắp xếp giảm dần (ĐK ${sortPriority(col.key)})` : 'Sắp xếp giảm dần'">
                    <span x-show="sortPriority(col.key)" class="text-[9px] font-extrabold mr-0.5 leading-none" x-text="sortPriority(col.key)"></span>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </span>
            </template>

            <template x-if="sortDirection(col.key) === 'asc' && isColumnFiltered(col.key)">
                <span class="nttu-header-badge-active" :title="sortPriority(col.key) ? `Tăng dần có lọc (ĐK ${sortPriority(col.key)})` : 'Tăng dần có lọc'">
                    <span x-show="sortPriority(col.key)" class="text-[9px] font-extrabold mr-0.5 leading-none" x-text="sortPriority(col.key)"></span>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l7-7 7 7M5 19h14"/></svg>
                </span>
            </template>

            <template x-if="sortDirection(col.key) === 'desc' && isColumnFiltered(col.key)">
                <span class="nttu-header-badge-active" :title="sortPriority(col.key) ? `Giảm dần có lọc (ĐK ${sortPriority(col.key)})` : 'Giảm dần có lọc'">
                    <span x-show="sortPriority(col.key)" class="text-[9px] font-extrabold mr-0.5 leading-none" x-text="sortPriority(col.key)"></span>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 5h14M5 11l7 7 7-7"/></svg>
                </span>
            </template>

            <template x-if="!sortDirection(col.key) && isColumnFiltered(col.key)">
                <span class="nttu-header-badge-active" title="Cột đang có bộ lọc">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 12h14"/></svg>
                </span>
            </template>

            <svg x-show="!sortDirection(col.key) && !isColumnFiltered(col.key)" class="ml-1 h-3 w-3 shrink-0 opacity-30 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>

        </button>

    </template>

    <template x-if="!isSortable(col)">

        <div class="nttu-th-label"

            :class="columnHeaderAlignClass(col)">

            <span x-show="columnHeaderIconPath(col)" x-html="columnIconHtml(col)" class="report-header-icon shrink-0"></span>

            <span class="nttu-th-label-text" x-text="columnLabel(col)">{{ $headerDisplay }}</span>

        </div>

    </template>

    <div x-cloak x-float.start="isSortable(col) && headerPopover === col.key" data-float-close="headerPopover = null"

         class="nttu-report-header-popover nttu-floating-panel w-60 rounded-md border bg-white text-gray-900 shadow-2xl"

         :class="['good-deeds', 'incident-reports'].includes(config.variant) ? 'border-blue-100' : 'border-gray-200'">

        <div class="space-y-1 p-1.5">

            <button type="button" class="flex h-9 w-full items-center justify-between rounded px-2 text-left text-xs font-medium text-gray-900 hover:bg-gray-100"
                @click="requestSort(col.key, 'asc')">
                <span class="flex items-center gap-2">
                    <svg class="h-4 w-4 shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                    <span x-text="config.variant === 'incident-reports' ? labelText('Tăng dần') : labelText('Sắp xếp tăng dần')"></span>
                </span>
                <span x-show="isSortActive(col.key, 'asc')" x-text="sortPriorityBadge(col.key)" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200"></span>
            </button>

            <button type="button" class="flex h-9 w-full items-center justify-between rounded px-2 text-left text-xs font-medium text-gray-900 hover:bg-gray-100"
                @click="requestSort(col.key, 'desc')">
                <span class="flex items-center gap-2">
                    <svg class="h-4 w-4 shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    <span x-text="config.variant === 'incident-reports' ? labelText('Giảm dần') : labelText('Sắp xếp giảm dần')"></span>
                </span>
                <span x-show="isSortActive(col.key, 'desc')" x-text="sortPriorityBadge(col.key)" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200"></span>
            </button>

            <button type="button" x-show="sortDirection(col.key)" class="nttu-popover-clear-action flex w-full items-center gap-2 rounded px-2 py-1.5 text-xs text-rose-600 hover:bg-rose-50 border-t mt-1 pt-2" @click="removeSort(col.key)">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <span>Xóa sắp xếp cột này</span>
            </button>
            <button type="button" x-show="activeSortRules.length > 1" @click="clearSort()" class="nttu-popover-clear-action flex w-full items-center gap-2 px-2 py-1 rounded text-left text-[11px] text-gray-500 hover:bg-gray-100">
                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                <span>Xóa tất cả sắp xếp</span>
            </button>

        </div>

        <div class="space-y-2 border-t bg-gray-50/50 p-3">

            <div class="relative">

                <svg class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>

                <input type="text" class="h-9 w-full rounded border-gray-200 bg-white pl-9 text-xs text-gray-900"

                    :placeholder="labelText('Lọc') + ' ' + columnLabel(col).toLowerCase() + '...'"

                    :value="columnFilters[col.key] || ''"

                    @input="setColumnFilter(col.key, $event.target.value)"

                    @keydown.enter="headerPopover = null">

            </div>

            <button type="button" x-show="columnFilters[col.key]" class="nttu-popover-clear-action flex h-8 w-full items-center gap-2 rounded px-2 text-left text-[11px] font-bold"

                @click="clearColumnFilter(col.key)">

                <svg class="h-3.5 w-3.5 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>

                <span x-text="labelText('Xóa bộ lọc')">Xóa bộ lọc</span>

            </button>

        </div>

    </div>

</div>

