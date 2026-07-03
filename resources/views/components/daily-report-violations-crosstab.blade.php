{{-- Crosstab table for tab SINH VIÊN VI PHẠM --}}
<div class="overflow-x-auto overflow-y-visible">
    <table class="daily-report-table daily-report-crosstab nttu-table nttu-catalog-table w-max min-w-full"
        data-col-resize="daily_report_violations">
        <thead>
            <tr>
                <th class="daily-report-th nttu-th-index text-sm font-bold" rowspan="2">#</th>
                <template x-for="col in visibleCrosstabFixedColumns(tabKey)" :key="'fix-h1-' + col.key">
                    <th class="daily-report-th relative h-auto p-0" rowspan="2" :data-col="col.key" :data-col-label="col.label">
                        <div class="relative h-full">
                            <button type="button"
                                class="group flex h-full min-h-11 w-full items-center justify-center gap-1 px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-black hover:bg-yellow-400/60"
                                @click="stateFor(tabKey).openPopover = stateFor(tabKey).openPopover === col.key ? null : col.key">
                                <svg x-show="columnHeaderIconPath(col)" x-cloak class="nttu-th-col-icon mr-0.5 h-3.5 w-3.5 shrink-0 opacity-90" :class="crosstabColumnHeaderIconClass(col)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="columnHeaderIconPath(col)"></path></svg>
                                <span class="nttu-th-label-text" x-text="col.label"></span>
                                <span class="nttu-th-sort-icons shrink-0 whitespace-nowrap">
                                    <svg x-show="sortStateFor(tabKey, col.key)?.direction === 'ascending'" x-cloak class="h-3 w-3 shrink-0" :class="sortIconClass(tabKey, col.key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                    <svg x-show="sortStateFor(tabKey, col.key)?.direction === 'descending'" x-cloak class="h-3 w-3 shrink-0" :class="sortIconClass(tabKey, col.key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    <svg x-show="!sortStateFor(tabKey, col.key)" x-cloak class="h-3 w-3 shrink-0 opacity-40 group-hover:opacity-100" :class="sortIconClass(tabKey, col.key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                </span>
                            </button>
                            <div x-cloak x-float.start="stateFor(tabKey).openPopover === col.key" data-float-close="stateFor(tabKey).openPopover = null"
                                class="nttu-report-header-popover nttu-floating-panel w-64 rounded-md border border-gray-100 bg-white py-1 shadow-2xl">
                                @include('components.partials.daily-report-header-popover')
                            </div>
                        </div>
                    </th>
                </template>
                <th class="daily-report-th report-th-group-label px-3 py-2 text-center align-middle text-[11px] font-bold uppercase tracking-wider text-black"
                    x-show="visibleCrosstabViolationColumns(tabKey).length > 0"
                    :colspan="visibleCrosstabViolationColumns(tabKey).length"
                    x-text="crosstabConfig(tabKey).violationGroupLabel"></th>
                <th class="daily-report-th catalog-th-settings relative sticky right-0 z-20 shadow-[-2px_0_5px_rgba(0,0,0,0.1)]" rowspan="2">
                    <button type="button" data-float-trigger title="Cài đặt hiển thị" class="nttu-catalog-cog-btn"
                        @click="stateFor(tabKey).colMenuOpen = !stateFor(tabKey).colMenuOpen">
                        <svg class="h-5 w-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>
                    <div x-cloak x-float="stateFor(tabKey).colMenuOpen" data-float-close="stateFor(tabKey).colMenuOpen = false"
                        class="nttu-report-settings-panel nttu-floating-panel w-56 max-h-[min(20rem,calc(100vh-1rem))] overflow-y-auto rounded-md border border-gray-200 bg-white py-2 shadow-xl">
                        <p class="report-settings-title border-b border-gray-100 px-3 py-2">Hiển thị cột</p>
                        @include('components.partials.column-settings-bulk-actions', [
                            'selectAll' => 'selectAllColumns(tabKey)',
                            'deselectAll' => 'deselectAllColumns(tabKey)',
                        ])
                        <template x-for="col in tabDefinitions[tabKey].columns" :key="'vis-' + col.key">
                            <label class="flex cursor-pointer items-center gap-2 px-3 py-1.5 hover:bg-slate-50">
                                <input type="checkbox" class="rounded border-gray-300" :checked="stateFor(tabKey).columnVisibility[col.key] !== false"
                                    @change="toggleColumn(tabKey, col.key)">
                                <span x-text="col.label"></span>
                            </label>
                        </template>
                    </div>
                </th>
            </tr>
            <tr>
                <template x-for="col in visibleCrosstabViolationColumns(tabKey)" :key="'vio-h2-' + col.key">
                    <th class="daily-report-th relative h-auto p-0" :data-col="col.key" :data-col-label="col.label">
                        <div class="relative h-full">
                            <button type="button"
                                class="group flex h-full min-h-11 w-full items-center justify-center gap-1 px-1.5 py-2 text-center text-[10px] font-bold uppercase tracking-wider text-black hover:bg-yellow-400/60"
                                @click="stateFor(tabKey).openPopover = stateFor(tabKey).openPopover === col.key ? null : col.key">
                                <svg x-show="columnHeaderIconPath(col)" x-cloak class="nttu-th-col-icon mr-0.5 h-3.5 w-3.5 shrink-0 opacity-90" :class="crosstabColumnHeaderIconClass(col)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="columnHeaderIconPath(col)"></path></svg>
                                <span class="nttu-th-label-text" x-text="col.label"></span>
                                <span class="nttu-th-sort-icons shrink-0 whitespace-nowrap">
                                    <svg x-show="sortStateFor(tabKey, col.key)?.direction === 'ascending'" x-cloak class="h-3 w-3 shrink-0" :class="sortIconClass(tabKey, col.key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                    <svg x-show="sortStateFor(tabKey, col.key)?.direction === 'descending'" x-cloak class="h-3 w-3 shrink-0" :class="sortIconClass(tabKey, col.key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    <svg x-show="!sortStateFor(tabKey, col.key)" x-cloak class="h-3 w-3 shrink-0 opacity-40 group-hover:opacity-100" :class="sortIconClass(tabKey, col.key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                </span>
                            </button>
                            <div x-cloak x-float.start="stateFor(tabKey).openPopover === col.key" data-float-close="stateFor(tabKey).openPopover = null"
                                class="nttu-report-header-popover nttu-floating-panel w-64 rounded-md border border-gray-100 bg-white py-1 shadow-2xl">
                                @include('components.partials.daily-report-header-popover')
                            </div>
                        </div>
                    </th>
                </template>
            </tr>
        </thead>
        <tbody>
            <template x-if="currentItems(tabKey).length === 0">
                <tr>
                    <td :colspan="crosstabColumnCount(tabKey)" class="py-12 text-center text-gray-500">
                        <x-table-empty-state
                            filters-active="hasActiveFilters(tabKey)"
                            clear-action="clearAllFilters(tabKey)"
                        />
                    </td>
                </tr>
            </template>
            <template x-for="(item, idx) in currentItems(tabKey)" :key="item.id">
                <tr class="group cursor-pointer"
                    :class="isSelected(tabKey, item.id) ? 'row-selected' : ''"
                    @click="toggleRow(tabKey, item.id)">
                    <td class="catalog-td-index px-2 py-3 text-center text-sm font-medium">
                        <span class="row-handled border-red-500 text-red-600"
                            x-text="(stateFor(tabKey).currentPage - 1) * rowsPerPageFor(tabKey) + idx + 1"></span>
                    </td>
                    <template x-for="col in visibleCrosstabFixedColumns(tabKey)" :key="item.id + '-fix-' + col.key">
                        <td class="px-3 py-3 text-sm text-left align-middle break-words"
                            :data-col="col.key"
                            x-text="item[col.key] || '---'"></td>
                    </template>
                    <template x-for="col in visibleCrosstabViolationColumns(tabKey)" :key="item.id + '-vio-' + col.key">
                        <td class="px-2 py-3 text-sm text-center align-middle whitespace-nowrap"
                            :data-col="col.key"
                            x-text="crosstabCount(item, col.key)"></td>
                    </template>
                    <td class="catalog-td-settings sticky-action sticky right-0 z-10 p-0 text-center align-middle shadow-[-2px_0_5px_rgba(0,0,0,0.05)]"></td>
                </tr>
            </template>
        </tbody>
    </table>
</div>
