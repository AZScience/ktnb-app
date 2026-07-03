@props(['rows', 'masterData', 'initialDate'])

@php $pagePerms = $nttuPage('/dashboard'); @endphp

<div
    class="nttu-card overflow-hidden shadow-md"
    x-data="dashboardScheduleLookup({
        rows: @js($rows),
        masterData: @js($masterData),
        initialDate: @js($initialDate),
        lookupUrl: @js(route('dashboard.schedule-lookup')),
        exportUrl: @js(route('dashboard.schedule-export')),
        presetsUrl: @js(route('dashboard.filter-presets')),
        canExport: @js($pagePerms['export']),
    })"
    x-init="init()"
>
    <div class="px-4 py-3 border-b border-gray-100">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold flex items-center gap-2 text-gray-800">
                <svg class="h-6 w-6 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Tra cứu Lịch học
            </h2>
            <div class="flex items-center gap-2">
                <button type="button" @click="filterOpen = true" class="rounded-md p-2 text-orange-500 hover:bg-orange-50" title="Bộ lọc nâng cao">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 8h12M9 12h6M11 16h2"/></svg>
                </button>
                <a x-show="canExport" x-cloak :href="exportHref" class="rounded-md p-2 text-green-600 hover:bg-green-50" title="Xuất file Excel (mẫu in kiểm tra phòng)">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                </a>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto overflow-y-visible">
        <table class="nttu-table nttu-catalog-table min-w-full w-full table-fixed"
            x-bind:data-col-resize="'dashboard_schedule_lookup'">
            <thead>
                <tr>
                    <th class="catalog-th-index">#</th>
                    <template x-for="key in visibleColumnKeys" :key="key">
                        <th class="catalog-th-col relative" :data-col="key" :data-col-label="columns[key]">
                            <button type="button" @click="headerPopover = headerPopover === key ? null : key"
                                class="group nttu-th-sort-btn">
                                <svg x-show="columnIcon(key)" x-cloak class="mr-0.5 h-3.5 w-3.5 shrink-0" :class="columnIconColor(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="columnIcon(key)"></path></svg>
                                <span class="nttu-th-label-text" :class="key === 'period' ? 'whitespace-nowrap' : ''" x-text="columns[key]"></span>
                                <template x-if="sortState(key) === 'asc'">
                                    <svg class="ml-1 h-3 w-3 shrink-0" :class="sortIconClass(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                </template>
                                <template x-if="sortState(key) === 'desc'">
                                    <svg class="ml-1 h-3 w-3 shrink-0" :class="sortIconClass(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </template>
                                <template x-if="sortState(key) === 'none'">
                                    <svg class="ml-1 h-3 w-3 shrink-0 opacity-30 group-hover:opacity-100" :class="sortIconClass(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                </template>
                            </button>
                            <div x-cloak x-float.start="headerPopover === key" data-float-close="headerPopover = null"
                                class="nttu-report-header-popover nttu-floating-panel w-60 rounded-md border bg-white text-gray-800 shadow-lg text-sm overflow-hidden">
                                <div class="p-1 space-y-0.5">
                                    <button type="button" @click="requestSort(key, 'ascending')" class="w-full flex items-center gap-2 rounded px-2 py-1.5 hover:bg-slate-50 text-left">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                        Tăng dần
                                    </button>
                                    <button type="button" @click="requestSort(key, 'descending')" class="w-full flex items-center gap-2 rounded px-2 py-1.5 hover:bg-slate-50 text-left">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        Giảm dần
                                    </button>
                                    <template x-if="sortState(key) !== 'none'">
                                        <button type="button" @click="clearSort()" class="nttu-popover-clear-action w-full flex items-center gap-2 rounded px-2 py-1.5 text-left border-t mt-1 pt-2">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Xoá sắp xếp
                                        </button>
                                    </template>
                                </div>
                                <div class="border-t p-2">
                                    <div class="relative">
                                        <svg class="absolute left-2 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 8h12M9 12h6M11 16h2"/></svg>
                                        <input type="search" :value="filters[key] || ''" @input="setFilter(key, $event.target.value)"
                                            @keydown.enter="headerPopover = null"
                                            :placeholder="'Lọc ' + columns[key] + '...'"
                                            class="h-9 w-full rounded-md border-gray-300 pl-8 text-sm">
                                    </div>
                                    <button type="button" x-show="isColumnFiltered(key)" @click="clearFilter(key)"
                                        class="nttu-popover-clear-action mt-1 flex w-full items-center gap-2 rounded px-2 py-1.5 text-left text-xs">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Xóa bộ lọc
                                    </button>
                                </div>
                            </div>
                        </th>
                    </template>
                    <th class="catalog-th-settings relative">
                        <button type="button" data-float-trigger @click.stop="columnsOpen = !columnsOpen" class="nttu-catalog-cog-btn" title="Cài đặt hiển thị">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </button>
                        <div x-cloak x-float="columnsOpen" data-float-close="columnsOpen = false"
                            class="nttu-report-settings-panel nttu-floating-panel w-56 max-h-[min(20rem,calc(100vh-1rem))] overflow-y-auto rounded-md border border-gray-200 bg-white py-2 shadow-xl">
                            <p class="report-settings-title border-b border-gray-100 px-3 py-2">Hiển thị cột</p>
                            @include('components.partials.column-settings-bulk-actions')
                            <template x-for="key in columnOrder" :key="key">
                                <label class="flex items-center gap-2 px-3 py-1.5 hover:bg-slate-50 cursor-pointer">
                                    <input type="checkbox" class="rounded border-gray-300" :checked="columnVisibility[key]" @change="toggleColumn(key)">
                                    <span x-text="columns[key]"></span>
                                </label>
                            </template>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <template x-if="loading">
                    <tr><td :colspan="visibleColumnKeys.length + 2" class="py-12 text-center text-gray-500">
                        <x-table-empty-state loading="true" />
                    </td></tr>
                </template>
                <template x-if="!loading && currentItems.length === 0">
                    <tr><td :colspan="visibleColumnKeys.length + 2" class="py-12 text-center text-gray-500">
                        <x-table-empty-state
                            filters-active="hasActiveFilters"
                            clear-action="clearAllFilters()"
                        />
                    </td></tr>
                </template>
                <template x-for="(row, index) in currentItems" :key="rowKey(row, startIndex + index)">
                    <tr
                        @click="toggleRow(rowKey(row, startIndex + index))"
                        class="group cursor-pointer"
                        :class="isRowSelected(rowKey(row, startIndex + index)) ? 'row-selected' : ''"
                    >
                        <td class="text-center font-medium catalog-td-index" x-text="startIndex + index + 1"></td>
                        <template x-for="key in visibleColumnKeys" :key="key">
                            <td class="align-middle py-3 min-w-0 break-words" :data-col="key">
                                <template x-if="key === 'period'">
                                    <span class="inline-flex rounded border px-2 py-0.5 font-mono text-xs" x-text="cellValue(row, key)"></span>
                                </template>
                                <template x-if="key === 'room'">
                                    <span
                                        class="font-bold"
                                        :class="isRowSelected(rowKey(row, startIndex + index)) ? '' : 'text-blue-600'"
                                        x-text="cellValue(row, key)"
                                    ></span>
                                </template>
                                <template x-if="key === 'status'">
                                    <div class="flex flex-col gap-1">
                                        <span x-text="cellValue(row, key)"></span>
                                        <span x-show="row.incident" class="inline-flex w-fit rounded bg-red-600 px-1.5 py-0.5 text-[10px] font-medium text-white" x-text="row.incident"></span>
                                    </div>
                                </template>
                                <template x-if="key !== 'period' && key !== 'room' && key !== 'status'">
                                    <span x-text="cellValue(row, key)"></span>
                                </template>
                            </td>
                        </template>
                        <td class="catalog-td-settings sticky-action"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t px-4 py-4">
        <div class="text-sm text-gray-500 text-center sm:text-left">
            Tổng cộng <span x-text="filteredRows.length"></span> bản ghi.
            <span x-show="selectedRowIds.length > 0">Đã chọn <span x-text="selectedRowIds.length"></span> dòng.</span>
        </div>
        <div class="flex flex-wrap items-center justify-center gap-4">
            <div class="flex items-center gap-2">
                <p class="text-sm text-gray-500 shrink-0">Số dòng</p>
                <select class="nttu-rows-per-page-select" x-model.number="rowsPerPage" @change="setRowsPerPage($event.target.value)">
                    <template x-for="n in rowsPerPageOptions" :key="n">
                        <option :value="n" x-text="n" :selected="normalizedRowsPerPage === n"></option>
                    </template>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="goToPage(1)" :disabled="safeCurrentPage === 1" class="h-8 w-8 rounded border disabled:opacity-40">«</button>
                <button type="button" @click="goToPage(safeCurrentPage - 1)" :disabled="safeCurrentPage === 1" class="h-8 w-8 rounded border disabled:opacity-40">‹</button>
                <div class="flex items-center gap-1 text-sm font-medium">
                    <input type="number" class="h-8 w-12 rounded border text-center" :value="safeCurrentPage"
                        @change="goToPage($event.target.value)">
                    <span>/ <span x-text="totalPages"></span></span>
                </div>
                <button type="button" @click="goToPage(safeCurrentPage + 1)" :disabled="safeCurrentPage === totalPages" class="h-8 w-8 rounded border disabled:opacity-40">›</button>
                <button type="button" @click="goToPage(totalPages)" :disabled="safeCurrentPage === totalPages" class="h-8 w-8 rounded border disabled:opacity-40">»</button>
            </div>
        </div>
    </div>

    {{-- Bộ lọc nâng cao --}}
    <div x-show="filterOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="filterOpen = false">
        <div class="absolute inset-0 bg-black/50" @click="filterOpen = false"></div>
        <div class="relative w-full max-w-3xl max-h-[90vh] overflow-hidden rounded-xl bg-white shadow-2xl flex flex-col" @click.stop>
            <div class="flex items-center justify-between border-b bg-slate-50/80 px-4 py-3 pr-12">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 8h12M9 12h6M11 16h2"/></svg>
                    <div class="relative">
                        <button type="button" @click="presetMenuOpen = !presetMenuOpen" class="flex items-center gap-2 text-lg font-bold text-gray-900 hover:opacity-80">
                            Bộ lọc nâng cao
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
                </div>
                <div class="flex items-center gap-1">
                    <template x-if="presetNaming">
                        <div class="flex items-center gap-1">
                            <input type="text" x-model="presetName" placeholder="Tên bộ lọc..." class="h-8 w-32 rounded border-gray-300 text-xs px-2"
                                @keydown.enter.prevent="saveFilterPreset(presetName)">
                            <button type="button" class="h-8 px-2 rounded bg-[var(--nttu-table-head)] text-white text-xs disabled:opacity-50" :disabled="savingPresets" @click="saveFilterPreset(presetName)">✓</button>
                            <button type="button" class="h-8 px-2 text-gray-500" :disabled="savingPresets" @click="presetNaming = false; presetName = ''">×</button>
                        </div>
                    </template>
                    <template x-if="!presetNaming">
                        <button type="button" class="inline-flex items-center gap-1.5 text-[10px] font-bold text-[var(--nttu-primary)] hover:bg-cyan-50 px-2 py-1.5 rounded disabled:opacity-50" :disabled="savingPresets" @click="presetNaming = true; presetName = ''">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            <span x-text="savingPresets ? 'Đang lưu...' : 'Lưu hiện tại'"></span>
                        </button>
                    </template>
                </div>
            </div>
            <button type="button" class="absolute right-4 top-3 rounded-sm text-gray-400 hover:text-gray-600" @click="filterOpen = false" aria-label="Đóng">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-6 text-sm overflow-y-auto">
                <div class="space-y-2">
                    <label class="font-medium flex items-center gap-2 text-gray-700">
                        <svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Ngày lọc
                    </label>
                    <input type="date" :value="advancedFilters.date" @change="onAdvancedDateChange($event.target.value)" class="nttu-form-control">
                </div>
                <div class="space-y-2">
                    <label class="font-medium flex items-center gap-2 text-gray-700">
                        <svg class="h-4 w-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Ca học
                    </label>
                    <select x-model="advancedFilters.periodSession" class="nttu-form-control">
                        <option value="all">Tất cả</option>
                        <option value="morning">Ca sáng (1-6)</option>
                        <option value="afternoon">Ca chiều (7-12)</option>
                        <option value="evening">Ca tối (13-17)</option>
                        <option value="custom">Tùy chọn...</option>
                    </select>
                </div>
                <div x-show="advancedFilters.periodSession === 'custom'" x-cloak class="md:col-span-2 grid grid-cols-2 gap-4 border rounded-lg bg-slate-50/80 p-3">
                    <div class="space-y-2">
                        <label class="text-xs font-medium">Từ tiết</label>
                        <input type="number" min="1" max="17" x-model="advancedFilters.periodStart" class="nttu-form-control" placeholder="1">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-medium">Đến tiết</label>
                        <input type="number" min="1" max="17" x-model="advancedFilters.periodEnd" class="nttu-form-control" placeholder="17">
                    </div>
                </div>
                @foreach ([
                    ['buildings', 'Dãy nhà (Chọn nhiều)'],
                    ['statuses', 'Trạng thái (Chọn nhiều)'],
                ] as [$field, $label])
                <div class="space-y-2">
                    <label class="font-medium text-gray-700">{{ $label }}</label>
                    <x-nttu-multi-select :field="$field" placeholder="Chọn..." search-placeholder="Tìm kiếm..." />
                </div>
                @endforeach
            </div>
            <div class="flex justify-end gap-2 border-t bg-slate-50/80 px-6 py-4">
                <button type="button" @click="resetAdvanced()" class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Xóa tất cả
                </button>
                <button type="button" @click="applyAdvanced()" class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Áp dụng bộ lọc
                </button>
            </div>
        </div>
    </div>
</div>
