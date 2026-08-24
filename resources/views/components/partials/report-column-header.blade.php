@props(['headerDisplay' => null])



<div class="relative">

    <template x-if="isSortable(col)">

        <button type="button" class="group nttu-th-sort-btn"

            :class="columnHeaderAlignClass(col)"

            @click.stop="headerPopover = headerPopover === col.key ? null : col.key">

            <span x-show="columnHeaderIconPath(col)" x-html="columnIconHtml(col)" class="report-header-icon shrink-0"></span>

            <span class="nttu-th-label-text" :class="columnHeaderTextClass(col)" x-text="columnLabel(col)">{{ $headerDisplay }}</span>

            <svg x-show="sortDirection(col.key) === 'asc'" x-cloak class="ml-1 h-3 w-3 shrink-0" :class="isColumnFiltered(col.key) && 'nttu-sort-filtered'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>

            <svg x-show="sortDirection(col.key) === 'desc'" x-cloak class="ml-1 h-3 w-3 shrink-0" :class="isColumnFiltered(col.key) && 'nttu-sort-filtered'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>

            <svg x-show="!sortDirection(col.key)" class="ml-1 h-3 w-3 shrink-0 opacity-30 group-hover:opacity-100" :class="isColumnFiltered(col.key) && 'nttu-sort-filtered'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>

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

            <button type="button" class="flex h-9 w-full items-center gap-2 rounded px-2 text-left text-xs font-medium text-gray-900 hover:bg-gray-100"

                @click="requestSort(col.key, 'asc')">

                <svg class="h-4 w-4 shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>

                <span x-text="config.variant === 'incident-reports' ? labelText('Tăng dần') : labelText('Sắp xếp tăng dần')"></span>

            </button>

            <button type="button" class="flex h-9 w-full items-center gap-2 rounded px-2 text-left text-xs font-medium text-gray-900 hover:bg-gray-100"

                @click="requestSort(col.key, 'desc')">

                <svg class="h-4 w-4 shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>

                <span x-text="config.variant === 'incident-reports' ? labelText('Giảm dần') : labelText('Sắp xếp giảm dần')"></span>

            </button>

            <button type="button" x-show="sortKey === col.key" class="nttu-popover-clear-action flex w-full items-center gap-2 rounded px-2 py-1.5 text-xs" @click="clearSort()">

                <svg class="h-4 w-4 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>

                <span x-text="labelText('Xoá sắp xếp')">Xoá sắp xếp</span>

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

