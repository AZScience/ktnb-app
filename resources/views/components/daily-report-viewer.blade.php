@props([
    'dateIso',
    'displayDate',
    'officer',
    'tabDefinitions',
    'datasets',
    'exportUrl',
    'googleSheetsConfigured' => false,
    'defaultSheetTab' => 'Báo cáo Tổng hợp',
    'googleSheetTabsUrl' => '',
    'googleSheetPushUrl' => '',
])

@php $pagePerms = $nttuPage('/reports/daily'); @endphp

<div
    x-data="dailyReportPage({
        dateIso: @js($dateIso),
        displayDate: @js($displayDate),
        officer: @js($officer),
        tabDefinitions: @js($tabDefinitions),
        datasets: @js($datasets),
        exportUrl: @js($exportUrl),
        googleSheetsConfigured: @js($googleSheetsConfigured),
        defaultSheetTab: @js($defaultSheetTab),
        googleSheetTabsUrl: @js($googleSheetTabsUrl),
        googleSheetPushUrl: @js($googleSheetPushUrl),
        canExport: @js($pagePerms['export']),
        canEdit: @js($pagePerms['edit']),
    })"
    class="space-y-6"
>
    <div x-show="toast" x-cloak
        class="fixed bottom-4 right-4 z-[70] rounded-lg px-4 py-3 text-sm shadow-lg"
        :class="toast?.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
        x-text="toast?.message"></div>

    {{-- Toolbar --}}
    <div class="nttu-card p-4 shadow-md">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-700">Ngày báo cáo:</label>
                    <input type="date" name="date" value="{{ $dateIso }}" class="nttu-form-control h-10">
                </div>
                <button type="submit" class="rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white">Xem báo cáo</button>
            </form>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" x-show="canExport" x-cloak @click="exportAll()" :disabled="isExportingAll"
                    class="inline-flex items-center gap-2 rounded-md border border-green-500 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-600 hover:text-white disabled:opacity-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span x-text="isExportingAll ? 'Đang xuất...' : 'Xuất Excel (Tất cả)'"></span>
                </button>
            </div>
        </div>
        @if ($officer === '')
            <p class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                Không tìm thấy biệt danh cán bộ cho tài khoản đăng nhập. Báo cáo chỉ hiển thị dữ liệu gắn với cán bộ (nickname) trong hồ sơ nhân viên.
            </p>
        @else
            <p class="mt-2 text-xs text-gray-500">Cán bộ báo cáo: <strong>{{ $officer }}</strong> · Ngày: <strong>{{ $displayDate }}</strong></p>
        @endif
        @unless ($googleSheetsConfigured)
            <p class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                Chưa cấu hình Google Sheet. Vào <a href="{{ route('parameters.index') }}" class="font-medium underline">Tham số hệ thống</a> và thêm các khóa:
                <code class="text-xs">googleSheetId</code>, <code class="text-xs">googleServiceAccountEmail</code>, <code class="text-xs">googlePrivateKey</code>, <code class="text-xs">reportSheetTabName</code>.
            </p>
        @endunless
    </div>

    {{-- Tabs --}}
    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white p-1 shadow-sm">
        <div class="flex flex-nowrap gap-1 overflow-x-auto">
            <template x-for="tabKey in tabList()" :key="tabKey">
                <button type="button" @click="tab = tabKey"
                    class="flex min-w-[140px] flex-1 items-center justify-center gap-2 rounded-lg border px-2 py-3 text-[11px] font-bold uppercase transition-all"
                    :class="tabToneClass(tabKey)">
                    <span class="truncate" x-text="tabDefinitions[tabKey]?.label"></span>
                    <span x-show="tabCount(tabKey) > 0" x-cloak
                        class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full px-1 text-[10px] font-bold"
                        :class="tabBadgeClass(tabKey)"
                        x-text="tabCount(tabKey)"></span>
                </button>
            </template>
        </div>
    </div>

    {{-- Tab panels --}}
    <template x-for="tabKey in tabList()" :key="'panel-' + tabKey">
        <div x-show="tab === tabKey" x-cloak class="space-y-0">
            <div class="nttu-card overflow-hidden">
                <div class="border-b px-4 py-3 md:px-6" :class="tabPanelHeaderClass(tabKey)">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <h3 class="flex items-center gap-2 text-sm font-black uppercase tracking-widest" :class="tabPanelTitleClass(tabKey)">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span x-text="`${tabDefinitions[tabKey]?.title} (${processedItems(tabKey).length})`"></span>
                        </h3>
                        <button type="button"
                            x-show="googleSheetsConfigured && (canEdit || canExport)"
                            x-cloak
                            @click="openPushDialog(tabKey)"
                            class="inline-flex items-center gap-2 rounded-md border border-orange-500 px-3 py-2 text-xs font-medium text-orange-700 shadow-sm hover:bg-orange-600 hover:text-white">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            Đẩy lên GoogleSheet
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto overflow-y-visible" x-show="!isCrosstabTab(tabKey)">
                    <table class="daily-report-table nttu-table nttu-catalog-table w-full min-w-full table-fixed"
                        x-bind:data-col-resize="'daily_report_' + tabKey">
                        <thead>
                            <tr class="bg-[#1877F2]">
                                <th class="daily-report-th nttu-th-index border-r text-sm font-bold">#</th>
                                <template x-for="col in visibleColumns(tabKey)" :key="col.key">
                                    <th class="daily-report-th relative h-auto border-r border-blue-400 p-0" :data-col="col.key" :data-col-label="col.label">
                                        <div class="relative">
                                            <button type="button"
                                                class="group flex h-10 w-full items-center gap-1 px-3 text-left text-[11px] font-bold uppercase tracking-wider text-white hover:bg-blue-700"
                                                @click="stateFor(tabKey).openPopover = stateFor(tabKey).openPopover === col.key ? null : col.key">
                                                <svg x-show="columnHeaderIconPath(col)" x-cloak class="mr-0.5 h-3.5 w-3.5 shrink-0 opacity-80" :class="columnHeaderIconClass(col)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="columnHeaderIconPath(col)"></path></svg>
                                                <span class="nttu-th-label-text flex-1" :class="col.key === 'period' ? 'whitespace-nowrap' : ''" x-text="col.label"></span>
                                                <svg x-show="sortStateFor(tabKey, col.key)?.direction === 'ascending'" x-cloak class="ml-1 h-3 w-3 shrink-0" :class="sortIconClass(tabKey, col.key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                                <svg x-show="sortStateFor(tabKey, col.key)?.direction === 'descending'" x-cloak class="ml-1 h-3 w-3 shrink-0" :class="sortIconClass(tabKey, col.key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                <svg x-show="!sortStateFor(tabKey, col.key)" x-cloak class="ml-1 h-3 w-3 shrink-0 opacity-30 group-hover:opacity-100" :class="sortIconClass(tabKey, col.key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                            </button>
                                            <div x-cloak x-float.start="stateFor(tabKey).openPopover === col.key" data-float-close="stateFor(tabKey).openPopover = null"
                                                class="nttu-report-header-popover nttu-floating-panel w-64 rounded-md border border-gray-100 bg-white py-1 shadow-2xl">
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
                                            </div>
                                        </div>
                                    </th>
                                </template>
                                <th class="daily-report-th catalog-th-settings relative sticky right-0 z-20 border-l shadow-[-2px_0_5px_rgba(0,0,0,0.1)]">
                                    <button type="button" data-float-trigger title="Cài đặt hiển thị" class="nttu-catalog-cog-btn"
                                            @click="stateFor(tabKey).colMenuOpen = !stateFor(tabKey).colMenuOpen">
                                        <svg class="h-5 w-5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
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
                        </thead>
                        <tbody>
                            <template x-if="currentItems(tabKey).length === 0">
                                <tr>
                                    <td :colspan="visibleColumns(tabKey).length + 2" class="py-12 text-center text-gray-500">
                                        <x-table-empty-state
                                            filters-active="hasActiveFilters(tabKey)"
                                            clear-action="clearAllFilters(tabKey)"
                                        />
                                    </td>
                                </tr>
                            </template>
                            <template x-for="(item, idx) in currentItems(tabKey)" :key="item.id">
                                <tr class="group cursor-pointer border-b"
                                    :class="isSelected(tabKey, item.id) ? 'row-selected' : ''"
                                    @click="toggleRow(tabKey, item.id)">
                                    <td class="catalog-td-index border-r px-2 py-3 text-center text-sm font-medium">
                                        <span class="row-handled border-red-500 text-red-600"
                                            x-text="(stateFor(tabKey).currentPage - 1) * rowsPerPageFor(tabKey) + idx + 1"></span>
                                    </td>
                                    <template x-for="col in visibleColumns(tabKey)" :key="item.id + '-' + col.key">
                                        <td class="border-r px-3 py-3 text-sm min-w-0 break-words"
                                            :class="cellTdClass(col)"
                                            :data-col="col.key">
                                            <template x-if="cellValue(item, col).kind === 'checkbox'">
                                                <input type="checkbox"
                                                    class="h-4 w-4 rounded border-gray-300 text-orange-600 pointer-events-none"
                                                    :checked="cellValue(item, col).checked"
                                                    disabled
                                                    aria-label="Thông báo">
                                            </template>
                                            <template x-if="cellValue(item, col).kind === 'badge'">
                                                <span class="inline-flex rounded px-2 py-0.5 text-[10px] font-bold" :class="cellValue(item, col).class" x-text="cellValue(item, col).text"></span>
                                            </template>
                                            <template x-if="cellValue(item, col).kind === 'normal'">
                                                <span class="text-[10px] font-bold text-green-600">Bình thường</span>
                                            </template>
                                            <template x-if="cellValue(item, col).kind === 'muted'">
                                                <span class="text-gray-400" x-text="cellValue(item, col).text"></span>
                                            </template>
                                            <template x-if="cellValue(item, col).kind === 'class'">
                                                <span class="text-sm font-bold text-blue-700 break-words" x-text="cellValue(item, col).text"></span>
                                            </template>
                                            <template x-if="cellValue(item, col).kind === 'text'">
                                                <span x-text="cellValue(item, col).text"></span>
                                            </template>
                                        </td>
                                    </template>
                                    <td class="catalog-td-settings sticky-action sticky right-0 z-10 border-l border-slate-100 p-0 text-center align-middle shadow-[-2px_0_5px_rgba(0,0,0,0.05)]"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div x-show="isCrosstabTab(tabKey)" x-cloak>
                    <x-daily-report-violations-crosstab />
                </div>

                <div class="flex flex-col items-center justify-between gap-4 border-t bg-slate-50/80 px-4 py-3 text-sm text-gray-600 md:flex-row md:px-6">
                    <div>
                        <template x-if="isCrosstabTab(tabKey)">
                            <span>Tổng cộng <strong x-text="processedItems(tabKey).length"></strong> dòng tổng hợp.</span>
                        </template>
                        <template x-if="!isCrosstabTab(tabKey)">
                            <span>Tổng cộng <strong x-text="processedItems(tabKey).length"></strong> bản ghi.</span>
                        </template>
                        <span x-show="stateFor(tabKey).selectedIds.length > 0" x-cloak>
                            Đã chọn <strong x-text="stateFor(tabKey).selectedIds.length"></strong> dòng.
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2">
                            <span>Số dòng</span>
                            <select class="nttu-rows-per-page-select" x-model.number="stateFor(tabKey).rowsPerPage"
                                @change="setRowsPerPage(tabKey, $event.target.value)">
                                <template x-for="n in [5,10,15,20,25,30,35,40,45,50]" :key="n">
                                    <option :value="n" x-text="n" :selected="rowsPerPageFor(tabKey) === n"></option>
                                </template>
                            </select>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" class="h-8 w-8 rounded border disabled:opacity-40" :disabled="stateFor(tabKey).currentPage === 1" @click="goPage(tabKey, 1)">«</button>
                            <button type="button" class="h-8 w-8 rounded border disabled:opacity-40" :disabled="stateFor(tabKey).currentPage === 1" @click="goPage(tabKey, stateFor(tabKey).currentPage - 1)">‹</button>
                            <span class="flex items-center gap-1 font-medium">
                                <input type="number" class="h-8 w-12 rounded border text-center text-sm"
                                    :value="stateFor(tabKey).currentPage" @change="goPage(tabKey, $event.target.value)">
                                / <span x-text="totalPages(tabKey)"></span>
                            </span>
                            <button type="button" class="h-8 w-8 rounded border disabled:opacity-40" :disabled="stateFor(tabKey).currentPage === totalPages(tabKey)" @click="goPage(tabKey, stateFor(tabKey).currentPage + 1)">›</button>
                            <button type="button" class="h-8 w-8 rounded border disabled:opacity-40" :disabled="stateFor(tabKey).currentPage === totalPages(tabKey)" @click="goPage(tabKey, totalPages(tabKey))">»</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Google Sheet push dialog --}}
    <div x-show="pushDialogOpen" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="pushDialogOpen = false">
        <div class="w-full max-w-lg rounded-xl bg-white shadow-2xl" @click.outside="pushDialogOpen = false">
            <div class="border-b px-6 py-4">
                <h4 class="text-lg font-semibold text-gray-900">Đẩy dữ liệu lên Google Sheet</h4>
                <p class="mt-1 text-sm text-gray-500" x-text="`Dữ liệu từ bảng &quot;${tabDefinitions[pushTabKey]?.title || ''}&quot; sẽ được đưa vào Google Sheet đã kết nối.`"></p>
            </div>
            <div class="space-y-4 px-6 py-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Chọn Tab / Sheet đích</label>
                    <template x-if="isLoadingTabs">
                        <p class="text-sm text-gray-500 animate-pulse">Đang tải danh sách tab...</p>
                    </template>
                    <template x-if="!isLoadingTabs">
                        <select class="nttu-form-control w-full" x-model="targetTabName">
                            <template x-for="tab in availableTabs" :key="tab">
                                <option :value="tab" x-text="tab"></option>
                            </template>
                        </select>
                    </template>
                </div>
                <div class="rounded-lg bg-slate-50 p-3 text-xs space-y-2">
                    <p class="font-bold text-gray-700">Các cột sẽ đẩy lên Google Sheet (theo file mẫu):</p>
                    <div class="flex flex-wrap gap-1">
                        <template x-for="(label, idx) in googleSheetPushColumnLabels(pushTabKey)" :key="'push-col-' + idx + '-' + label">
                            <span class="inline-flex rounded bg-white px-2 py-0.5 text-[11px] border" x-text="label"></span>
                        </template>
                    </div>
                    <p class="text-[10px] text-gray-500 italic">* Cài đặt ẩn/hiện cột trên bảng chỉ áp dụng khi xem trên web, không thay đổi cấu trúc cột trên Google Sheet.</p>
                    <p class="text-[10px] text-gray-500 italic">* Chỉ những dữ liệu mới (chưa có trên Sheet) mới được đẩy vào để tránh trùng lặp.</p>
                    <p x-show="pushTabKey === 'online'" x-cloak class="text-[10px] font-medium text-orange-700">
                        Tab Kiểm tra trực tuyến: chỉ đẩy các dòng có <strong>việc phát sinh khác bình thường</strong>
                        (<span x-text="googleSheetPushRowCount('online')"></span> dòng đủ điều kiện).
                    </p>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t px-6 py-4">
                <x-nttu-button type="button" action="cancel" @click="pushDialogOpen = false">Hủy</x-nttu-button>
                <button type="button" class="inline-flex items-center gap-2 rounded-md bg-orange-600 px-4 py-2 text-sm text-white hover:bg-orange-700 disabled:opacity-50"
                    :disabled="isPushing || isLoadingTabs || !targetTabName"
                    @click="confirmPushToGoogleSheet()">
                    <x-form-field-icon name="upload" tone="orange" class="h-4 w-4 text-white" />
                    <span x-text="isPushing ? 'Đang đẩy...' : 'Xác nhận đẩy dữ liệu'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
