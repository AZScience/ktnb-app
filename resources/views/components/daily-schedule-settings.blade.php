@props(['masterData', 'todayDate'])

@php $pagePerms = $nttuPage('/settings/schedule'); @endphp

<div
    x-data="dailyScheduleSettingsPage({
        masterData: @js($masterData),
        todayDate: @js($todayDate),
        initialItems: [],
        dataUrl: @js(route('schedules.data')),
        presetsUrl: @js(route('schedules.filter-presets')),
        storeUrl: @js(url('settings/schedules')),
        exportUrl: @js(route('schedules.export')),
        importUrl: @js(route('schedules.import')),
        importPreviewUrl: @js(route('schedules.import-preview')),
        importBatchUrl: @js(route('schedules.import-batch')),
        importTemplateUrl: @js(route('schedules.import-template')),
        bulkDeleteUrl: @js(route('schedules.bulk-destroy')),
        deleteByDateUrl: @js(route('schedules.destroy-by-date')),
        canAdd: @js($pagePerms['add']),
        canEdit: @js($pagePerms['edit']),
        canDelete: @js($pagePerms['delete']),
        canImport: @js($pagePerms['import']),
        canExport: @js($pagePerms['export']),
    })"
    class="space-y-3"
>
    <div x-show="toast" x-cloak
        class="fixed bottom-4 right-4 z-[70] rounded-lg px-4 py-3 text-sm shadow-lg"
        :class="toast?.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
        x-text="toast?.message"></div>

    <div class="nttu-card">
        <div class="py-3 px-4 border-b flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-xl font-semibold flex items-center gap-2 text-gray-800">
                    <svg class="h-6 w-6 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>Lịch học chi tiết</span>
                </h2>
                <button type="button" x-show="canDelete" x-cloak @click="showDeleteAction = !showDeleteAction"
                    class="rounded-full p-1.5 transition-colors"
                    :class="showDeleteAction ? 'text-red-500 bg-red-50' : 'text-gray-400 hover:bg-gray-100'"
                    title="Bật/tắt thao tác xóa nhanh">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
                <template x-if="showDeleteAction && advancedFilters.date && canDelete">
                    <button type="button" @click="confirmDeleteByDate()"
                        class="inline-flex items-center gap-2 rounded-md border border-red-100 bg-red-50 px-2 py-1 text-sm text-red-600 hover:bg-red-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span x-text="'Xóa toàn bộ lịch ngày ' + (advancedFilters.date.includes('/') ? advancedFilters.date : advancedFilters.date)"></span>
                    </button>
                </template>
                <template x-if="visibleSelectedCount > 0 && canDelete">
                    <button type="button" @click="confirmBulkDelete()"
                        class="inline-flex items-center gap-2 rounded-md border border-red-200 bg-red-50 px-2 py-1 text-sm text-red-600 hover:bg-red-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span x-text="'Xóa ' + visibleSelectedCount + ' dòng đã chọn'"></span>
                    </button>
                </template>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="openAdvancedFilter()" class="rounded-md p-2 text-orange-500 hover:bg-orange-50" title="Bộ lọc nâng cao">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 8h12M9 12h6M11 16h2"/></svg>
                </button>
                <a x-show="canImport" x-cloak :href="importTemplateUrl" class="rounded-md p-2 text-indigo-600 hover:bg-indigo-50" title="Tải mẫu file import">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M4 12V7a3 3 0 013-3h10a3 3 0 013 3v1"/></svg>
                </a>
                <label x-show="canImport" x-cloak class="rounded-md p-2 text-blue-600 hover:bg-blue-50 cursor-pointer" title="Nhập file Excel">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    <input type="file" accept=".xlsx,.xls" class="hidden" @change="handleImportFile($event)">
                </label>
                <a x-show="canExport" x-cloak :href="exportHref" class="rounded-md p-2 text-green-600 hover:bg-green-50" title="Xuất file Excel (mẫu in kiểm tra phòng)">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                </a>
                <button type="button" x-show="canAdd" x-cloak @click="openAddModal()" class="rounded-md p-2 text-[var(--nttu-primary)] hover:bg-cyan-50" title="Thêm mới">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto overflow-y-visible">
            <table class="nttu-table nttu-catalog-table min-w-full w-full table-fixed daily-schedule-table"
                x-bind:data-col-resize="'daily_schedule_settings'">
                <thead>
                    <tr class="bg-[#1877F2]">
                        <th class="nttu-th-index text-white font-bold text-xs">#</th>
                        <template x-for="key in visibleColumnKeys" :key="key">
                            <th class="p-0 h-auto border-r border-blue-300 relative" :data-col="key" :data-col-label="columnLabel(key)">
                                <button type="button" @click="headerPopover = headerPopover === key ? null : key"
                                    class="w-full h-10 px-3 text-left text-white font-bold text-[11px] uppercase tracking-wider flex items-center gap-1 hover:bg-blue-700 group">
                                    <svg x-show="columnIcon(key)" class="mr-0.5 h-3.5 w-3.5 shrink-0" :class="columnIconColor(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="columnIcon(key)"></path>
                                    </svg>
                                    <span class="nttu-th-label-text" x-text="columnLabel(key)"></span>
                                    <template x-if="sortDirection(key) === 'asc'">
                                        <svg class="ml-1 h-3 w-3 shrink-0" :class="sortIconClass(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                    </template>
                                    <template x-if="sortDirection(key) === 'desc'">
                                        <svg class="ml-1 h-3 w-3 shrink-0" :class="sortIconClass(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </template>
                                    <template x-if="!sortDirection(key)">
                                        <svg class="ml-1 h-3 w-3 shrink-0 opacity-30 group-hover:opacity-100" :class="sortIconClass(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    </template>
                                </button>
                                <div x-cloak x-float.start="headerPopover === key" data-float-close="headerPopover = null"
                                    class="nttu-report-header-popover nttu-floating-panel w-60 rounded-md border bg-white text-gray-800 text-sm shadow-lg overflow-hidden">
                                    <div class="p-1 space-y-0.5">
                                        <button type="button" @click="requestSort(key, 'asc')" class="flex w-full items-center gap-2 px-2 py-1.5 hover:bg-gray-50 rounded text-left">
                                            <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                            Sắp xếp tăng dần
                                        </button>
                                        <button type="button" @click="requestSort(key, 'desc')" class="flex w-full items-center gap-2 px-2 py-1.5 hover:bg-gray-50 rounded text-left">
                                            <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            Sắp xếp giảm dần
                                        </button>
                                        <button type="button" x-show="sortDirection(key)" @click="clearSort()" class="nttu-popover-clear-action flex w-full items-center gap-2 px-2 py-1.5 rounded text-left border-t mt-1 pt-2">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Xóa sắp xếp
                                        </button>
                                    </div>
                                    <div class="border-t p-2">
                                        <div class="relative">
                                            <svg class="absolute left-2 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 8h12M9 12h6M11 16h2"/></svg>
                                            <input type="search" :placeholder="'Lọc ' + columnLabel(key) + '...'"
                                                :value="columnFilters[key] || ''"
                                                @input="setColumnFilter(key, $event.target.value)"
                                                @keydown.enter="headerPopover = null"
                                                class="w-full rounded border-gray-300 text-sm py-1.5 pl-8 h-9">
                                        </div>
                                        <button type="button" x-show="isColumnFiltered(key)" @click="clearColumnFilter(key)"
                                            class="nttu-popover-clear-action flex w-full items-center gap-2 px-2 py-1.5 mt-1 rounded text-left text-xs">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Xóa bộ lọc
                                        </button>
                                    </div>
                                </div>
                            </th>
                        </template>
                        <th class="catalog-th-settings relative w-16 text-center sticky right-0 bg-[#1877F2] z-30 border-l border-blue-400 p-0 shadow-[-2px_0_5px_rgba(0,0,0,0.1)]">
                            <button type="button" data-float-trigger @click="settingsOpen = !settingsOpen" class="w-full h-10 flex items-center justify-center text-white hover:bg-blue-700" title="Cài đặt hiển thị">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </button>
                            <div x-cloak x-float="settingsOpen" data-float-close="settingsOpen = false" class="nttu-report-settings-panel nttu-floating-panel w-56 max-h-[min(20rem,calc(100vh-1rem))] overflow-y-auto rounded-md border border-gray-200 bg-white py-2 shadow-xl">
                                <p class="report-settings-title border-b border-gray-100 px-3 py-2">Hiển thị cột</p>
                                @include('components.partials.column-settings-bulk-actions')
                                <template x-for="key in columnOrder" :key="key">
                                    <label class="flex items-center gap-2 px-3 py-1.5 hover:bg-slate-50 cursor-pointer">
                                        <input type="checkbox" :checked="columnVisibility[key]" @change="toggleColumn(key)" class="rounded border-gray-300">
                                        <span x-text="columnLabel(key)"></span>
                                    </label>
                                </template>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="loading && items.length === 0">
                        <tr><td :colspan="visibleColumnKeys.length + 2" class="py-12 text-center text-gray-500">
                            <x-table-empty-state loading="true" />
                        </td></tr>
                    </template>
                    <template x-if="!loading && filteredItems.length === 0">
                        <tr><td :colspan="visibleColumnKeys.length + 2" class="py-12 text-center text-gray-500">
                            <x-table-empty-state
                                filters-active="hasActiveAdvancedFilters || Object.keys(columnFilters).some(k => columnFilters[k])"
                            />
                        </td></tr>
                    </template>
                    <template x-for="(item, idx) in paginatedItems" :key="item.id">
                        <tr @click="handleRowClick(item)" class="group cursor-pointer" :class="isRowSelected(item.id) && 'row-selected'">
                            <td class="text-center align-middle w-20 border-r" x-text="startIndex + idx + 1"></td>
                            <template x-for="key in visibleColumnKeys" :key="key">
                                <td class="align-middle border-r min-w-0 break-words" :data-col="key">
                                    <template x-if="key === 'period'">
                                        <span class="font-mono font-bold text-xs" x-text="cellValue(item, key) || '—'"></span>
                                    </template>
                                    <template x-if="key === 'type'">
                                        <span x-show="item.type" class="inline-flex items-center rounded border border-gray-300 px-1.5 py-0.5 text-xs font-medium text-gray-700" x-text="item.type"></span>
                                        <span x-show="!item.type" class="text-gray-400">---</span>
                                    </template>
                                    <template x-if="key !== 'period' && key !== 'type'">
                                        <span class="break-words block" x-text="cellValue(item, key)"></span>
                                    </template>
                                </td>
                            </template>
                            <td class="catalog-td-settings sticky-action text-center border-l border-slate-100 p-0 align-middle shadow-[-2px_0_5px_rgba(0,0,0,0.05)]" @click.stop>
                                <button type="button" data-float-trigger title="Thao tác" class="nttu-row-action-btn" @click.stop="rowMenuOpen = rowMenuOpen === item.id ? null : item.id">
                                    @include('components.partials.row-action-menu-icon')
                                </button>
                                <div x-cloak x-float="rowMenuOpen === item.id"
                                    class="nttu-row-action-menu nttu-floating-panel w-44 rounded-md border bg-white py-1 text-sm shadow-lg text-left font-normal">
                                    <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-slate-50" @click="openModal('view', item)">
                                        <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Chi tiết
                                    </button>
                                    <button type="button" x-show="canEdit" x-cloak class="flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-slate-50" @click="openModal('edit', item)">
                                        <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Sửa
                                    </button>
                                    <button type="button" x-show="canAdd" x-cloak class="flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-slate-50" @click="openModal('copy', item)">
                                        <svg class="h-4 w-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        Sao chép
                                    </button>
                                    <button type="button" x-show="canDelete" x-cloak class="flex w-full items-center gap-2 px-3 py-2 text-left text-red-600 hover:bg-red-50" @click="confirmDelete(item)">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Xóa
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t text-sm text-gray-500 bg-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3">
            <span>
                <span x-text="'Tổng cộng ' + filteredItems.length + ' bản ghi.'"></span>
                <span x-show="visibleSelectedCount > 0" x-text="' Đã chọn ' + visibleSelectedCount + ' dòng.'"></span>
            </span>
            <div class="flex items-center gap-3">
                <label class="inline-flex items-center gap-2">
                    <span>Số dòng</span>
                    <select class="nttu-rows-per-page-select" :value="normalizedRowsPerPage" @change="setRowsPerPage($event.target.value)">
                        <template x-for="n in [5,10,15,20,25,30,35,40,45,50]" :key="n">
                            <option :value="n" x-text="n" :selected="normalizedRowsPerPage == n"></option>
                        </template>
                    </select>
                </label>
                <div class="flex items-center gap-1">
                    <button type="button" class="rounded border h-8 w-8 flex items-center justify-center disabled:opacity-40" :disabled="safeCurrentPage <= 1" @click="goToPage(1)" title="Trang đầu">«</button>
                    <button type="button" class="rounded border h-8 w-8 flex items-center justify-center disabled:opacity-40" :disabled="safeCurrentPage <= 1" @click="goToPage(safeCurrentPage - 1)" title="Trang trước">‹</button>
                    <span class="flex items-center gap-1 text-sm font-medium">
                        <input type="number" class="h-8 w-12 rounded border-gray-300 text-center text-sm" :value="safeCurrentPage" min="1" :max="totalPages" @change="goToPage(parseInt($event.target.value) || 1)">
                        <span x-text="'/ ' + totalPages"></span>
                    </span>
                    <button type="button" class="rounded border h-8 w-8 flex items-center justify-center disabled:opacity-40" :disabled="safeCurrentPage >= totalPages" @click="goToPage(safeCurrentPage + 1)" title="Trang sau">›</button>
                    <button type="button" class="rounded border h-8 w-8 flex items-center justify-center disabled:opacity-40" :disabled="safeCurrentPage >= totalPages" @click="goToPage(totalPages)" title="Trang cuối">»</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bộ lọc nâng cao --}}
    <div x-show="advancedOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="closeAdvancedFilter()">
        <div class="absolute inset-0 bg-black/50" @click="closeAdvancedFilter()"></div>
        <div class="relative w-full max-w-2xl max-h-[90vh] overflow-hidden rounded-xl bg-white shadow-2xl flex flex-col" @click.stop>
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
                            <p class="px-3 py-1 text-xs font-bold uppercase tracking-wider text-gray-500">Bộ lọc đã lưu</p>
                            @include('components.partials.filter-preset-menu-items')
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <template x-if="presetNaming">
                        <div class="flex items-center gap-1">
                            <input type="text" x-model="presetName" placeholder="Tên bộ lọc..." class="h-8 w-32 rounded border-gray-300 text-xs px-2" @keydown.enter.prevent="saveFilterPreset(presetName)">
                            <button type="button" class="h-8 px-2 rounded bg-[var(--nttu-table-head)] text-white text-xs" :disabled="savingPresets" @click="saveFilterPreset(presetName)">✓</button>
                            <button type="button" class="h-8 px-2 text-gray-500" @click="presetNaming = false; presetName = ''">×</button>
                        </div>
                    </template>
                    <template x-if="!presetNaming">
                        <button type="button" class="inline-flex items-center gap-1.5 text-[10px] font-bold text-[var(--nttu-primary)] hover:bg-cyan-50 px-2 py-1.5 rounded" :disabled="savingPresets" @click="presetNaming = true; presetName = ''">
                            Lưu hiện tại
                        </button>
                    </template>
                </div>
            </div>
            <button type="button" class="absolute right-4 top-3 rounded-sm text-gray-400 hover:text-gray-600" @click="closeAdvancedFilter()">×</button>
            <div class="overflow-y-auto p-6 max-h-[70vh]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="space-y-2">
                        <label class="font-medium flex items-center gap-2 text-gray-700">Ngày lọc</label>
                        <input type="date"
                            :value="advancedFilters.date?.includes('/') ? advancedFilters.date.split('/').reverse().join('-') : (advancedFilters.date || '')"
                            @change="onAdvancedDateChange($event.target.value)"
                            class="nttu-form-control">
                    </div>
                    <div class="space-y-2">
                        <label class="font-medium flex items-center gap-2 text-gray-700">Ca học</label>
                        <select :value="advancedFilters.periodSession" @change="onPeriodSessionChange($event.target.value)" class="nttu-form-control">
                            <option value="all">Tất cả</option>
                            <option value="morning">Ca sáng (1-6)</option>
                            <option value="afternoon">Ca chiều (7-12)</option>
                            <option value="evening">Ca tối (13-17)</option>
                            <option value="custom">Tùy chỉnh...</option>
                        </select>
                    </div>
                    <div x-show="advancedFilters.periodSession === 'custom'" x-cloak class="md:col-span-2 grid grid-cols-2 gap-4 border rounded-lg bg-slate-50/80 p-3">
                        <div class="space-y-2">
                            <label class="text-xs font-medium">Từ tiết</label>
                            <input type="number" :value="advancedFilters.periodStart" @input="onPeriodRangeChange('periodStart', $event.target.value)" class="nttu-form-control" min="1" max="17">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-medium">Đến tiết</label>
                            <input type="number" :value="advancedFilters.periodEnd" @input="onPeriodRangeChange('periodEnd', $event.target.value)" class="nttu-form-control" min="1" max="17">
                        </div>
                    </div>
                    @foreach ([
                        ['buildings', 'Dãy nhà (Chọn nhiều)'],
                        ['departments', 'Khoa / Đơn vị (Chọn nhiều)'],
                        ['rooms', 'Phòng (Chọn nhiều)'],
                        ['lecturers', 'Giảng viên / CBCT (Chọn nhiều)'],
                    ] as [$field, $label])
                    <div class="space-y-2">
                        <label class="font-medium text-gray-700">{{ $label }}</label>
                        <x-nttu-multi-select :field="$field" placeholder="Chọn..." search-placeholder="Tìm kiếm..." />
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="border-t px-4 py-3 flex justify-end gap-2 bg-slate-50">
                <button type="button" @click="resetAdvanced()" class="rounded-md border px-4 py-2 text-sm text-gray-600 hover:bg-white">Xóa bộ lọc</button>
                <button type="button" @click="applyAdvanced()" class="rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white">Áp dụng</button>
            </div>
        </div>
    </div>

    {{-- Dialog thêm/sửa --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="closeModal()">
        <div class="absolute inset-0 bg-black/50" @click="closeModal()"></div>
        <div class="relative w-full max-w-4xl max-h-[92vh] overflow-hidden rounded-xl bg-white shadow-2xl flex flex-col" @click.stop>
            <div class="border-b px-6 py-4 pr-14">
                <h3 class="text-xl font-semibold text-gray-900" x-text="modalTitle"></h3>
                <p class="mt-1 text-sm text-gray-500" x-show="!isViewMode">Nhập thông tin lịch học chi tiết.</p>
            </div>
            <button type="button" class="absolute right-4 top-4 rounded-sm text-gray-400 hover:text-gray-600" @click="closeModal()" aria-label="Đóng">×</button>
            <div class="overflow-y-auto p-6 max-h-[70vh]">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="space-y-1">
                        <label class="font-semibold text-gray-700 flex items-center gap-2">
                            <x-form-field-icon name="calendar" tone="orange" /> Ngày *
                        </label>
                        <template x-if="isClassFieldEditable">
                            <input type="date"
                                :value="form.date?.includes('/') ? form.date.split('/').reverse().join('-') : (form.date || '')"
                                @change="const v = $event.target.value; if (v) { const p = v.split('-'); form.date = p[2]+'/'+p[1]+'/'+p[0]; } else { form.date = ''; }"
                                class="nttu-form-control">
                        </template>
                        <template x-if="!isClassFieldEditable"><p class="font-bold py-2" x-text="form.date || '—'"></p></template>
                    </div>
                    <x-monitoring-creatable-input field="building" list-id="nttu-schedule-building" :on-building-change="true">
                        <x-slot:label>
                            <label class="font-semibold text-gray-700 flex items-center gap-2">
                                <x-form-field-icon name="landmark" /> Dãy nhà *
                            </label>
                        </x-slot:label>
                    </x-monitoring-creatable-input>
                    <x-monitoring-creatable-input field="room" list-id="nttu-schedule-room">
                        <x-slot:label>
                            <label class="font-semibold text-gray-700 flex items-center gap-2">
                                <x-form-field-icon name="door" /> Phòng *
                            </label>
                        </x-slot:label>
                    </x-monitoring-creatable-input>
                    <div class="space-y-1">
                        <label class="font-semibold text-gray-700 flex items-center gap-2">
                            <x-form-field-icon name="clock" tone="orange" /> Tiết *
                        </label>
                        <template x-if="isClassFieldEditable">
                            <input type="text" x-model="form.period" class="nttu-form-control" placeholder="Ví dụ: 1->3">
                        </template>
                        <template x-if="!isClassFieldEditable"><p class="font-bold font-mono py-2" x-text="form.period || '—'"></p></template>
                    </div>
                    <x-monitoring-creatable-input field="department" list-id="nttu-schedule-department">
                        <x-slot:label>
                            <label class="font-semibold text-gray-700 flex items-center gap-2">
                                <x-form-field-icon name="landmark" /> Khoa sử dụng *
                            </label>
                        </x-slot:label>
                    </x-monitoring-creatable-input>
                    <div class="space-y-1">
                        <label class="font-semibold text-gray-700 flex items-center gap-2">
                            <x-form-field-icon name="graduation" /> Lớp *
                        </label>
                        <template x-if="isClassFieldEditable">
                            <input type="text" x-model="form.class" class="nttu-form-control" placeholder="Tên lớp...">
                        </template>
                        <template x-if="!isClassFieldEditable"><p class="font-bold py-2" x-text="form.class || '—'"></p></template>
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-gray-700 flex items-center gap-2">
                            <x-form-field-icon name="activity" tone="blue" /> Loại (LT/TH)
                        </label>
                        <template x-if="isClassFieldEditable">
                            <select x-model="form.type" class="nttu-form-control">
                                <option value="">Chọn loại...</option>
                                <option value="LT">LT</option>
                                <option value="TH">TH</option>
                            </select>
                        </template>
                        <template x-if="!isClassFieldEditable"><p class="py-2" x-text="form.type || '—'"></p></template>
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-gray-700 flex items-center gap-2">
                            <x-form-field-icon name="hash" tone="blue" /> Sĩ số
                        </label>
                        <template x-if="isClassFieldEditable">
                            <input type="number" x-model="form.student_count" min="0" class="nttu-form-control">
                        </template>
                        <template x-if="!isClassFieldEditable"><p class="py-2" x-text="form.student_count ?? '—'"></p></template>
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-gray-700 flex items-center gap-2">
                            <x-form-field-icon name="file-text" tone="destructive" /> Trạng thái
                        </label>
                        <template x-if="isClassFieldEditable">
                            <select x-model="form.status" class="nttu-form-control">
                                <option value="Phòng học">Phòng học</option>
                                <option value="Phòng thi">Phòng thi</option>
                                <option value="Phòng tự do">Phòng tự do</option>
                                <option value="Học bình thường">Học bình thường</option>
                            </select>
                        </template>
                        <template x-if="!isClassFieldEditable"><p class="py-2" x-text="form.status || 'Phòng học'"></p></template>
                    </div>
                    <x-monitoring-creatable-input field="lecturer" list-id="nttu-schedule-lecturer" readonly-class="font-bold text-[var(--nttu-primary)]">
                        <x-slot:label>
                            <label class="font-semibold text-gray-700 flex items-center gap-2">
                                <x-form-field-icon name="graduation" /> Giảng viên giảng dạy
                            </label>
                        </x-slot:label>
                    </x-monitoring-creatable-input>
                    @foreach (['proctor1' => 'CBCT 01', 'proctor2' => 'CBCT 02', 'proctor3' => 'CBCT 03'] as $proctorField => $proctorLabel)
                    <x-monitoring-creatable-input :field="$proctorField" list-id="nttu-schedule-lecturer" readonly-class="font-bold text-green-700">
                        <x-slot:label>
                            <label class="font-semibold text-green-700 flex items-center gap-2">
                                <x-form-field-icon name="users" tone="green" /> {{ $proctorLabel }}
                            </label>
                        </x-slot:label>
                    </x-monitoring-creatable-input>
                    @endforeach
                    <div class="space-y-1">
                        <label class="font-semibold text-gray-700 flex items-center gap-2">
                            <x-form-field-icon name="file-text" /> Nội dung
                        </label>
                        <template x-if="isClassFieldEditable">
                            <input type="text" x-model="form.content" class="nttu-form-control" placeholder="Nội dung chi tiết...">
                        </template>
                        <template x-if="!isClassFieldEditable"><p class="py-2" x-text="form.content || '—'"></p></template>
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-gray-700 flex items-center gap-2">
                            <x-form-field-icon name="note" /> Ghi chú
                        </label>
                        <template x-if="isNoteFieldEditable">
                            <input type="text" x-model="form.note" class="nttu-form-control" placeholder="Ghi chú thêm...">
                        </template>
                        <template x-if="!isNoteFieldEditable"><p class="py-2" x-text="form.note || '—'"></p></template>
                    </div>
                </div>
                <datalist id="nttu-schedule-building">
                    @foreach (($masterData['buildings'] ?? []) as $name)
                        <option value="{{ $name }}"></option>
                    @endforeach
                </datalist>
                <datalist id="nttu-schedule-room">
                    <template x-for="name in filteredRoomNames" :key="name">
                        <option :value="name"></option>
                    </template>
                </datalist>
                <datalist id="nttu-schedule-department">
                    @foreach (($masterData['departments'] ?? []) as $name)
                        <option value="{{ $name }}"></option>
                    @endforeach
                </datalist>
                <datalist id="nttu-schedule-lecturer">
                    @foreach (($masterData['lecturers'] ?? []) as $name)
                        <option value="{{ $name }}"></option>
                    @endforeach
                </datalist>
            </div>
            <div class="border-t px-6 py-4 flex justify-end gap-2 bg-slate-50">
                <template x-if="!isViewMode">
                    <button type="button" @click="undoForm()" :disabled="!isChanged || saving"
                        class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm text-gray-600 hover:bg-white disabled:opacity-40">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        Hoàn tác
                    </button>
                </template>
                <button type="button" @click="closeModal()" class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm text-gray-600 hover:bg-white">
                    <x-form-field-icon name="x" tone="destructive" class="h-4 w-4" />
                    <span x-text="isViewMode ? 'Đóng' : 'Hủy'"></span>
                </button>
                <button type="button" x-show="!isViewMode" @click="saveForm()" :disabled="!isChanged || saving"
                    class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white disabled:opacity-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    <span x-text="saving ? 'Đang lưu...' : 'Lưu lại'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Xem trước import Excel --}}
    <div x-show="importPreviewOpen" x-cloak class="fixed inset-0 z-[55] flex items-center justify-center p-4" @keydown.escape.window="closeImportPreview()">
        <div class="absolute inset-0 bg-black/50" @click="closeImportPreview()"></div>
        <div class="relative w-full max-w-5xl max-h-[90vh] overflow-hidden rounded-xl bg-white shadow-2xl flex flex-col" @click.stop>
            <div class="border-b px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900">Xem trước dữ liệu trước khi thêm.</h3>
            </div>
            <div class="overflow-auto max-h-[60vh] border-y">
                <table class="nttu-table min-w-full text-sm">
                    <thead>
                        <tr class="bg-slate-100">
                            <th class="px-3 py-2 text-left">Ngày</th>
                            <th class="px-3 py-2 text-left">Dãy nhà</th>
                            <th class="px-3 py-2 text-left">Phòng</th>
                            <th class="px-3 py-2 text-center">Tiết</th>
                            <th class="px-3 py-2 text-left">Khoa sử dụng</th>
                            <th class="px-3 py-2 text-left">Giảng viên / CBCT</th>
                            <th class="px-3 py-2 text-left">Nội dung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, idx) in importPreviewRows" :key="idx">
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2 text-xs whitespace-nowrap" x-text="row.date"></td>
                                <td class="px-3 py-2 text-xs font-medium" x-text="row.building"></td>
                                <td class="px-3 py-2 font-bold text-[var(--nttu-primary)]" x-text="row.room"></td>
                                <td class="px-3 py-2 text-center font-semibold text-orange-600" x-text="row.period"></td>
                                <td class="px-3 py-2 text-xs" x-text="row.department"></td>
                                <td class="px-3 py-2 text-xs">
                                    <template x-if="row.status === 'Phòng thi'">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-blue-600 font-medium" x-show="row.proctor1" x-text="row.proctor1"></span>
                                            <span class="text-blue-600 font-medium" x-show="row.proctor2" x-text="row.proctor2"></span>
                                            <span class="text-blue-600 font-medium" x-show="row.proctor3" x-text="row.proctor3"></span>
                                        </div>
                                    </template>
                                    <template x-if="row.status !== 'Phòng thi'">
                                        <span x-text="row.lecturer"></span>
                                    </template>
                                </td>
                                <td class="px-3 py-2 text-xs max-w-[250px] truncate" x-text="row.content"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 flex justify-end gap-2 bg-slate-50">
                <x-nttu-button type="button" action="cancel" @click="closeImportPreview()">Hủy</x-nttu-button>
                <button type="button" @click="processImport()" :disabled="processingImport || loading"
                    class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white disabled:opacity-50">
                    <x-form-field-icon name="save" tone="green" class="h-4 w-4 text-white" />
                    <span x-text="processingImport ? 'Đang xử lý...' : (loading ? 'Đang tải dữ liệu...' : 'Xác nhận Lưu')"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Xác nhận xóa --}}
    <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="deleteOpen = false"></div>
        <div class="relative rounded-lg bg-white p-6 shadow-xl max-w-md w-full">
            <h3 class="text-lg font-semibold text-gray-900">Xác nhận xóa</h3>
            <p class="mt-2 text-sm text-gray-600">Bạn có chắc muốn xóa tiết học này?</p>
            <div class="mt-4 flex justify-end gap-2">
                <x-nttu-button type="button" action="cancel" @click="deleteOpen = false">Hủy</x-nttu-button>
                <x-nttu-button type="button" action="delete" x-bind:disabled="saving" @click="deleteItem()">Xóa</x-nttu-button>
            </div>
        </div>
    </div>

    <div x-show="bulkDeleteOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="bulkDeleteOpen = false"></div>
        <div class="relative rounded-lg bg-white p-6 shadow-xl max-w-md w-full">
            <h3 class="text-lg font-semibold text-gray-900">Xóa nhiều dòng</h3>
            <p class="mt-2 text-sm text-gray-600" x-text="'Xóa ' + selectedRowIds.length + ' dòng đã chọn?'"></p>
            <div class="mt-4 flex justify-end gap-2">
                <x-nttu-button type="button" action="cancel" @click="bulkDeleteOpen = false">Hủy</x-nttu-button>
                <x-nttu-button type="button" action="delete" x-bind:disabled="saving" @click="bulkDelete()">Xóa</x-nttu-button>
            </div>
        </div>
    </div>

    <div x-show="deleteByDateOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="deleteByDateOpen = false"></div>
        <div class="relative rounded-lg bg-white p-6 shadow-xl max-w-md w-full">
            <h3 class="text-lg font-semibold text-gray-900">Xóa theo ngày</h3>
            <p class="mt-2 text-sm text-gray-600" x-text="'Xóa toàn bộ lịch ngày ' + (deleteDate || '') + '?'"></p>
            <div class="mt-4 flex justify-end gap-2">
                <x-nttu-button type="button" action="cancel" @click="deleteByDateOpen = false">Hủy</x-nttu-button>
                <x-nttu-button type="button" action="delete" x-bind:disabled="saving" @click="deleteByDate()">Xóa</x-nttu-button>
            </div>
        </div>
    </div>
</div>
