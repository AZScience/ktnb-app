@props([
    'logs' => [],
    'userOptions' => [],
])

@php $pagePerms = $nttuPage('/settings/access-log'); @endphp

<div
    x-data="activityLogSettingsPage({
        logs: @js($logs),
        userOptions: @js($userOptions),
        routes: {
            destroyUrl: @js(route('activity-logs.destroy', ['activityLog' => '__ID__'])),
            bulkDestroyUrl: @js(route('activity-logs.bulk-destroy')),
            export: @js(route('activity-logs.export')),
            statistics: @js(route('activity-logs.statistics')),
        },
        canDelete: @js($pagePerms['delete']),
        canExport: @js($pagePerms['export']),
    })"
    class="space-y-4"
>
    <div x-show="toast" x-cloak
        class="fixed top-4 right-4 z-[70] max-w-md rounded-lg px-4 py-3 text-sm text-white shadow-lg"
        :class="toast?.type === 'error' ? 'bg-red-600' : 'bg-green-600'"
        x-text="toast?.message"></div>

    <div class="nttu-card overflow-hidden">
        <div class="border-b px-4 pt-3">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="flex items-center gap-2 text-xl font-semibold text-gray-900">
                    <svg class="h-6 w-6 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Nhật ký truy cập
                </h2>
                <div class="flex items-center gap-2">
                    <button type="button" x-show="activeMainTab === 'history' && selectedIds.length > 0 && canDelete" x-cloak @click="bulkDeleteOpen = true"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-md text-red-600 hover:bg-red-50" title="Xóa các mục đã chọn">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                    <button type="button" @click="advancedOpen = true"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-md text-orange-500 hover:bg-orange-50" title="Bộ lọc nâng cao">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    </button>
                    <button type="button" x-show="canExport" x-cloak @click="exportExcel()"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-md text-green-600 hover:bg-green-50" title="Xuất file Excel">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </button>
                </div>
            </div>
            <nav class="-mb-px mt-3 flex gap-1 overflow-x-auto" aria-label="Tab thống kê">
                <template x-for="tab in mainTabs" :key="tab.id">
                    <button type="button"
                        @click="switchMainTab(tab.id)"
                        class="whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium transition-colors"
                        :class="activeMainTab === tab.id
                            ? 'border-[var(--nttu-primary)] text-[var(--nttu-primary)]'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                        x-text="tab.label"></button>
                </template>
            </nav>
        </div>

        <div x-show="activeMainTab === 'history'" x-cloak>
        <div class="overflow-x-auto overflow-y-visible">
            <table class="nttu-table nttu-catalog-table w-full min-w-full">
                <thead>
                    <tr class="bg-[#1877F2]">
                        <th class="catalog-th-index">
                            <div class="flex items-center justify-center gap-1 text-base font-bold normal-case tracking-normal">
                                <svg class="h-4 w-4 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 002.83-2M15 11h3m-3 4h2"/></svg>
                                <span>#</span>
                            </div>
                        </th>
                        <template x-for="key in visibleColumnKeys" :key="key">
                            <th class="catalog-th-col" :data-col="key">
                                <div class="relative">
                                    <button type="button" class="group flex h-10 w-full items-center gap-1 px-3 text-left text-[11px] font-bold uppercase tracking-wider text-white hover:bg-blue-700"
                                        @click="headerPopover = headerPopover === key ? null : key">
                                        <svg x-show="columnHeaderIconPath(key)" x-cloak class="mr-0.5 h-3.5 w-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="columnHeaderIconPath(key)"></path></svg>
                                        <span class="nttu-th-label-text" x-text="columns[key].label"></span>
                                        <svg x-show="sortStateFor(key)?.direction === 'ascending'" x-cloak class="ml-2 h-4 w-4 shrink-0" :class="sortIconClass(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                        <svg x-show="sortStateFor(key)?.direction === 'descending'" x-cloak class="ml-2 h-4 w-4 shrink-0" :class="sortIconClass(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        <svg x-show="!sortStateFor(key)" x-cloak class="ml-2 h-4 w-4 shrink-0 opacity-50 group-hover:opacity-100" :class="sortIconClass(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    </button>
                                    <div x-cloak x-float.start="headerPopover === key" data-float-close="headerPopover = null"
                                         class="nttu-report-header-popover nttu-floating-panel w-60 rounded-md border bg-white text-gray-800 shadow-lg">
                                        <div class="space-y-1 p-1">
                                            <button type="button" class="flex w-full items-center gap-2 rounded px-2 py-1.5 text-xs hover:bg-gray-100" @click="requestSort(key, 'ascending')">
                                                <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                                Tăng dần
                                            </button>
                                            <button type="button" class="flex w-full items-center gap-2 rounded px-2 py-1.5 text-xs hover:bg-gray-100" @click="requestSort(key, 'descending')">
                                                <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                Giảm dần
                                            </button>
                                            <template x-if="sortStateFor(key)">
                                                <button type="button" class="nttu-popover-clear-action flex w-full items-center gap-2 rounded px-2 py-1.5 text-xs" @click="clearSort()">
                                                    <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    <span x-text="$t('Xóa sắp xếp')"></span>
                                                </button>
                                            </template>
                                        </div>
                                        <div class="border-t p-2">
                                            <div class="relative">
                                                <svg class="pointer-events-none absolute left-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                                                <input type="text" class="h-8 w-full rounded border-gray-300 pl-8 text-xs"
                                                       :placeholder="'Lọc ' + columns[key].label + '...'"
                                                       :value="filters[key] || ''"
                                                       @input="setFilter(key, $event.target.value)"
                                                       @keydown.enter="headerPopover = null">
                                            </div>
                                            <button type="button" x-show="isFiltered(key)" class="nttu-popover-clear-action mt-1 flex h-8 w-full items-center gap-2 rounded px-2 text-xs" @click="clearColumnFilter(key)">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                Xóa bộ lọc
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </th>
                        </template>
                        <th class="catalog-th-settings relative">
                            <button type="button" data-float-trigger @click="settingsOpen = !settingsOpen" class="inline-flex h-10 w-10 items-center justify-center text-white hover:bg-blue-700" title="Cài đặt hiển thị">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </button>
                            <div x-cloak x-float="settingsOpen" data-float-close="settingsOpen = false" class="nttu-report-settings-panel nttu-floating-panel w-56 max-h-[min(20rem,calc(100vh-1rem))] overflow-y-auto rounded-md border border-gray-200 bg-white py-2 shadow-xl">
                                <p class="report-settings-title border-b border-gray-100 px-3 py-2">Hiển thị cột</p>
                                @include('components.partials.column-settings-bulk-actions')
                                <template x-for="(col, key) in columns" :key="key">
                                    <label class="flex cursor-pointer items-center gap-2 px-3 py-1.5 hover:bg-slate-50">
                                        <input type="checkbox" class="rounded border-gray-300" :checked="columnVisibility[key]" @change="toggleColumn(key)">
                                        <span x-text="col.label"></span>
                                    </label>
                                </template>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="currentItems.length === 0">
                        <tr>
                            <td :colspan="visibleColumnKeys.length + 2" class="py-12 text-center text-gray-500">
                                <x-table-empty-state
                                    icon="table"
                                    filters-active="hasActiveFilters"
                                    clear-action="clearFilters()"
                                />
                            </td>
                        </tr>
                    </template>
                    <template x-for="(item, idx) in currentItems" :key="item.id">
                        <tr @click="toggleRow(item.id)" class="group cursor-pointer"
                            :class="isSelected(item.id) ? 'row-selected' : ''">
                            <td class="catalog-td-index align-middle font-medium" x-text="startIndex + idx + 1"></td>
                            <template x-for="key in visibleColumnKeys" :key="key">
                                <td class="border-r align-middle py-3" :data-col="key">
                                    <template x-if="key === 'action'">
                                        <span class="inline-flex rounded border px-2 py-0.5 text-xs font-medium" :class="actionBadge(item.action, item.actionLabel, item.actionRaw).color" x-text="actionBadge(item.action, item.actionLabel, item.actionRaw).label"></span>
                                    </template>
                                    <template x-if="key === 'details'">
                                        <div class="max-w-[300px] truncate" :title="item.details" x-text="item.details ?? ''"></div>
                                    </template>
                                    <template x-if="key !== 'action' && key !== 'details'">
                                        <span x-text="item[key] ?? ''"></span>
                                    </template>
                                </td>
                            </template>
                            <td class="sticky-action catalog-td-settings text-center align-middle p-0" @click.stop>
                                <div class="relative inline-flex">
                                    <button type="button" data-float-trigger title="Thao tác" @click.stop="rowMenuOpen = rowMenuOpen === item.id ? null : item.id"
                                        class="nttu-row-action-btn">
                                        @include('components.partials.row-action-menu-icon')
                                    </button>
                                    <div x-cloak x-float="rowMenuOpen === item.id"
                                        class="nttu-row-action-menu nttu-floating-panel w-44 rounded-md border bg-white py-1 text-left text-sm text-gray-800 shadow-lg">
                                        <button type="button" @click="openView(item)" class="flex w-full items-center gap-2 px-3 py-2 hover:bg-slate-50">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Chi tiết
                                        </button>
                                        <div class="my-1 border-t"></div>
                                        <button type="button" x-show="canDelete" x-cloak @click="confirmDelete(item)" class="flex w-full items-center gap-2 px-3 py-2 text-red-600 hover:bg-red-50">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Xóa nhật ký
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-4 border-t px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-500">
                Tổng cộng <span x-text="sortedItems.length"></span> bản ghi (Chỉ hiển thị 1000 bản ghi).
                <span x-show="selectedIds.length > 0" x-cloak> Đã chọn <span x-text="selectedIds.length"></span> dòng.</span>
            </p>
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span>Số dòng</span>
                    <select class="nttu-rows-per-page-select" :value="normalizedRowsPerPage" @change="setRowsPerPage($event.target.value)">
                        <template x-for="n in rowsPerPageOptions" :key="n">
                            <option :value="n" x-text="n" :selected="normalizedRowsPerPage === n"></option>
                        </template>
                    </select>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded border bg-white text-sm disabled:opacity-40" :disabled="safePage === 1" @click="goPage(1)" title="Trang đầu">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded border bg-white text-sm disabled:opacity-40" :disabled="safePage === 1" @click="goPage(safePage - 1)" title="Trang trước">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <span class="flex items-center gap-1 text-sm font-medium">
                        <input type="number" class="h-8 w-12 rounded border text-center text-sm" :value="safePage" @change="goPage($event.target.value)" title="Nhập số trang">
                        / <span x-text="totalPages"></span>
                    </span>
                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded border bg-white text-sm disabled:opacity-40" :disabled="safePage === totalPages" @click="goPage(safePage + 1)" title="Trang sau">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded border bg-white text-sm disabled:opacity-40" :disabled="safePage === totalPages" @click="goPage(totalPages)" title="Trang cuối">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>
        </div>

        <div x-show="activeMainTab !== 'history'" x-cloak x-ref="statsPanel" class="p-4 sm:p-6">
            <div x-show="statsLoading" class="flex items-center justify-center py-16 text-sm text-gray-500">
                <svg class="mr-2 h-5 w-5 animate-spin text-[var(--nttu-primary)]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                Đang tải thống kê...
            </div>
            <div x-show="statsError && !statsLoading" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-text="statsError"></div>
            <template x-if="!statsLoading && !statsError && statsData">
                <div class="space-y-6">
                    <p class="text-sm text-gray-500">
                        Phân tích <span class="font-semibold text-gray-800" x-text="(statsData?.total || 0).toLocaleString('vi-VN')"></span> bản ghi
                        <span class="text-gray-400">(tối đa 10.000 bản ghi theo bộ lọc)</span>
                    </p>

                    <template x-if="activeMainTab === 'overview'">
                        <div class="space-y-6">
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <template x-for="item in (statsData?.overview?.series || [])" :key="item.key">
                                    <div class="rounded-lg border bg-slate-50 p-4 text-center">
                                        <p class="text-2xl font-bold text-gray-900" x-text="item.count.toLocaleString('vi-VN')"></p>
                                        <p class="mt-1 text-xs font-medium uppercase tracking-wide text-gray-500" x-text="item.label"></p>
                                        <p class="text-xs text-gray-400" x-text="item.percent + '%'"></p>
                                    </div>
                                </template>
                            </div>
                            <div class="rounded-lg border p-4">
                                <h3 class="mb-3 text-sm font-semibold text-gray-800">Biểu đồ phân bổ (Xem · Thêm · Sửa · Xóa)</h3>
                                <div class="relative mx-auto h-72 max-w-md">
                                    <canvas data-chart="overview"></canvas>
                                </div>
                            </div>
                            <div class="rounded-lg border p-4">
                                <h3 class="mb-3 text-sm font-semibold text-gray-800">Hoạt động theo 10 chức năng chính (Công cụ kiểm tra)</h3>
                                <div class="relative h-80 w-full">
                                    <canvas data-chart="overview-features"></canvas>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="activeMainTab === 'module'">
                        <div class="space-y-6">
                            <div class="rounded-lg border p-4">
                                <h3 class="mb-3 text-sm font-semibold text-gray-800">Biểu đồ theo chức năng chính</h3>
                                <p class="mb-3 text-xs text-gray-500">Cố vấn học tập · Lớp học online · Lớp học trực tiếp · Thi kết thúc môn · Thực hành ngoài · Sinh viên vi phạm · Nhận - Trả tài sản · Tiếp nhận yêu cầu · Tiếp nhận đơn thư · Quản lý hồ sơ</p>
                                <div class="relative h-80 w-full">
                                    <canvas data-chart="module"></canvas>
                                </div>
                            </div>
                            <div class="overflow-x-auto rounded-lg border">
                                <table class="nttu-table w-full min-w-[640px] text-sm">
                                    <thead>
                                        <tr class="bg-slate-100 text-left text-xs uppercase tracking-wide text-gray-600">
                                            <th class="px-3 py-2">Chức năng chính</th>
                                            <th class="px-3 py-2 text-right">Tổng</th>
                                            <th class="px-3 py-2 text-right">Xem</th>
                                            <th class="px-3 py-2 text-right">Thêm</th>
                                            <th class="px-3 py-2 text-right">Sửa</th>
                                            <th class="px-3 py-2 text-right">Xóa</th>
                                            <th class="px-3 py-2 text-right">%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="row in (statsData?.byModule?.items || [])" :key="row.key || row.module">
                                            <tr class="border-t" :class="(row.total || 0) === 0 ? 'text-gray-400' : ''">
                                                <td class="px-3 py-2 font-medium" x-text="row.module"></td>
                                                <td class="px-3 py-2 text-right" x-text="statsCell(row.total)"></td>
                                                <td class="px-3 py-2 text-right" x-text="statsCell(row.VIEW)"></td>
                                                <td class="px-3 py-2 text-right" x-text="statsCell(row.CREATE)"></td>
                                                <td class="px-3 py-2 text-right" x-text="statsCell(row.UPDATE)"></td>
                                                <td class="px-3 py-2 text-right" x-text="statsCell(row.DELETE)"></td>
                                                <td class="px-3 py-2 text-right" x-text="statsPercent(row.percent)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <template x-if="activeMainTab === 'action'">
                        <div class="space-y-6">
                            <div class="rounded-lg border p-4">
                                <h3 class="mb-3 text-sm font-semibold text-gray-800">Biểu đồ theo hành động</h3>
                                <div class="relative h-72 w-full max-w-2xl">
                                    <canvas data-chart="action"></canvas>
                                </div>
                            </div>
                            <div class="overflow-x-auto rounded-lg border">
                                <table class="nttu-table w-full text-sm">
                                    <thead>
                                        <tr class="bg-slate-100 text-left text-xs uppercase tracking-wide text-gray-600">
                                            <th class="px-3 py-2">Hành động</th>
                                            <th class="px-3 py-2 text-right">Số lượt</th>
                                            <th class="px-3 py-2 text-right">Tỷ lệ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="row in (statsData?.byAction?.items || [])" :key="row.key">
                                            <tr class="border-t">
                                                <td class="px-3 py-2">
                                                    <span class="inline-flex rounded border px-2 py-0.5 text-xs font-medium" :class="actionBadge(row.key).color" x-text="row.label"></span>
                                                </td>
                                                <td class="px-3 py-2 text-right font-medium" x-text="statsCell(row.count)"></td>
                                                <td class="px-3 py-2 text-right" x-text="statsPercent(row.percent)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <template x-if="activeMainTab === 'employee'">
                        <div class="space-y-6">
                            <div class="rounded-lg border p-4">
                                <h3 class="mb-3 text-sm font-semibold text-gray-800">Biểu đồ theo nhân viên</h3>
                                <div class="relative h-80 w-full">
                                    <canvas data-chart="employee"></canvas>
                                </div>
                            </div>
                            <div class="overflow-x-auto rounded-lg border">
                                <table class="nttu-table w-full min-w-[640px] text-sm">
                                    <thead>
                                        <tr class="bg-slate-100 text-left text-xs uppercase tracking-wide text-gray-600">
                                            <th class="px-3 py-2">Nhân viên</th>
                                            <th class="px-3 py-2">Email</th>
                                            <th class="px-3 py-2">Chức năng chính</th>
                                            <th class="px-3 py-2 text-right">Tổng</th>
                                            <th class="px-3 py-2 text-right">Xem</th>
                                            <th class="px-3 py-2 text-right">Thêm</th>
                                            <th class="px-3 py-2 text-right">Sửa</th>
                                            <th class="px-3 py-2 text-right">Xóa</th>
                                            <th class="px-3 py-2 text-right">%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="row in (statsData?.byEmployee?.items || [])" :key="row.email || row.name">
                                            <tr class="border-t">
                                                <td class="px-3 py-2 font-medium" x-text="row.name"></td>
                                                <td class="px-3 py-2 text-gray-500" x-text="row.email || '—'"></td>
                                                <td class="px-3 py-2 text-sm text-gray-600" x-text="row.topFeature || '—'"></td>
                                                <td class="px-3 py-2 text-right" x-text="statsCell(row.total)"></td>
                                                <td class="px-3 py-2 text-right" x-text="statsCell(row.VIEW)"></td>
                                                <td class="px-3 py-2 text-right" x-text="statsCell(row.CREATE)"></td>
                                                <td class="px-3 py-2 text-right" x-text="statsCell(row.UPDATE)"></td>
                                                <td class="px-3 py-2 text-right" x-text="statsCell(row.DELETE)"></td>
                                                <td class="px-3 py-2 text-right" x-text="statsPercent(row.percent)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <div x-show="(statsData?.total || 0) === 0" class="rounded-lg border border-dashed py-12 text-center text-sm text-gray-500">
                        Không có dữ liệu thống kê trong phạm vi bộ lọc hiện tại.
                    </div>

                    <div x-show="(statsSection()?.commentary || []).length > 0" class="rounded-lg border-l-4 border-l-[var(--nttu-primary)] bg-blue-50 p-4">
                        <h4 class="text-sm font-semibold text-gray-900">Nhận xét / Đánh giá</h4>
                        <div class="mt-2 space-y-2">
                            <template x-for="(para, idx) in (statsSection()?.commentary || [])" :key="'commentary-' + idx">
                                <p class="text-sm leading-relaxed text-gray-700" x-text="para"></p>
                            </template>
                        </div>
                    </div>
                    <div x-show="(statsSection()?.recommendations || []).length > 0" class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <h4 class="text-sm font-semibold text-amber-900">Kiến nghị</h4>
                        <ul class="mt-2 list-outside list-decimal space-y-2 pl-5 text-sm leading-relaxed text-amber-900/90">
                            <template x-for="(rec, idx) in (statsSection()?.recommendations || [])" :key="idx">
                                <li x-text="rec"></li>
                            </template>
                        </ul>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Bộ lọc nâng cao --}}
    <div x-show="advancedOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" @keydown.escape.window="advancedOpen = false">
        <div class="absolute inset-0 bg-black/50" @click="advancedOpen = false"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-2xl" @click.stop>
            <button type="button" class="absolute right-4 top-4 rounded-sm text-gray-400 hover:bg-gray-100 hover:text-gray-600" @click="advancedOpen = false" aria-label="Đóng">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <h3 class="flex items-center gap-2 pr-8 text-lg font-semibold text-gray-900">
                <svg class="h-5 w-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Bộ lọc nâng cao
            </h3>
            <div class="mt-4 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <x-filter-label class="uppercase">Từ ngày</x-filter-label>
                        <input type="date" class="nttu-form-control" :value="advancedFilters.fromDate" @input="setAdvancedDate('fromDate', $event.target.value)">
                    </div>
                    <div class="space-y-1">
                        <x-filter-label class="uppercase">Đến ngày</x-filter-label>
                        <input type="date" class="nttu-form-control" :value="advancedFilters.toDate" @input="setAdvancedDate('toDate', $event.target.value)">
                    </div>
                </div>
                <div class="space-y-1">
                    <x-filter-label class="uppercase">Người dùng (Bí danh)</x-filter-label>
                    <div class="relative">
                        <button type="button" @click="userPickerOpen = !userPickerOpen"
                            class="flex min-h-[40px] w-full items-center justify-between rounded-md border border-slate-200 px-3 py-2 text-left text-sm">
                            <div class="flex flex-wrap gap-1">
                                <template x-if="!(advancedFilters.users?.length > 0)">
                                    <span class="text-gray-400">Tất cả người dùng</span>
                                </template>
                                <template x-for="userId in (advancedFilters.users || [])" :key="userId">
                                    <span class="rounded border border-blue-200 bg-blue-100 px-1.5 py-0.5 text-[10px] text-blue-700" x-text="userLabel(userId)"></span>
                                </template>
                            </div>
                            <svg class="h-4 w-4 shrink-0 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="userPickerOpen" x-cloak @click.outside="userPickerOpen = false"
                            class="absolute left-0 right-0 top-full z-10 mt-1 max-h-60 overflow-y-auto rounded-md border bg-white shadow-lg">
                            <div class="border-b p-2">
                                <input type="text" x-model="userSearch" placeholder="Tìm người dùng..." class="h-8 w-full rounded border-gray-300 px-2 text-xs">
                            </div>
                            <template x-if="filteredUserOptions.length === 0">
                                <p class="px-3 py-4 text-center text-xs text-gray-500" x-text="text('Không tìm thấy kết quả.')"></p>
                            </template>
                            <template x-for="user in filteredUserOptions" :key="user.id">
                                <button type="button" @click="toggleAdvancedUser(user.id)"
                                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-slate-50">
                                    <input type="checkbox" class="rounded border-gray-300" :checked="advancedFilters.users?.includes(user.id)" @click.stop>
                                    <div class="min-w-0">
                                        <div class="font-medium" x-text="user.name"></div>
                                        <div class="truncate text-[10px] text-gray-500" x-text="user.email"></div>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="space-y-1">
                    <x-filter-label class="uppercase">Thao tác</x-filter-label>
                    <select class="nttu-form-control" :value="advancedFilters.action" @change="setAdvancedAction($event.target.value)">
                        <option value="ALL">Tất cả thao tác</option>
                        <template x-for="(meta, key) in actionMap" :key="key">
                            <option :value="key" x-text="meta.label"></option>
                        </template>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex justify-between border-t pt-4">
                <button type="button" @click="clearAdvancedFilters()" class="text-sm text-red-600 hover:underline">Xóa tất cả</button>
                <button type="button" @click="applyAdvancedFilters()" class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Áp dụng bộ lọc
                </button>
            </div>
        </div>
    </div>

    {{-- Chi tiết nhật ký --}}
    <div x-show="viewOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" @keydown.escape.window="viewOpen = false">
        <div class="absolute inset-0 bg-black/50" @click="viewOpen = false"></div>
        <div class="relative flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl" @click.stop>
            <button type="button" class="absolute right-4 top-4 z-20 rounded-sm text-gray-400 hover:bg-gray-100 hover:text-gray-600" @click="viewOpen = false" aria-label="Đóng">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="border-b px-6 pb-4 pt-6 pr-14">
                <h3 class="flex items-center gap-2 text-xl font-semibold text-gray-900">
                    <svg class="h-5 w-5 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Chi tiết nhật ký hoạt động
                </h3>
            </div>
            <div class="flex-1 overflow-y-auto p-6" x-show="selectedLog">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-1">
                        <x-filter-label class="uppercase tracking-wider">Thời gian</x-filter-label>
                        <p class="font-bold" x-text="selectedLog?.formattedTime"></p>
                    </div>
                    <div class="space-y-1">
                        <x-filter-label class="uppercase tracking-wider">Hành động</x-filter-label>
                        <span class="inline-flex rounded border px-2 py-0.5 text-xs font-medium" :class="actionBadge(selectedLog?.action, selectedLog?.actionLabel, selectedLog?.actionRaw).color" x-text="actionBadge(selectedLog?.action, selectedLog?.actionLabel, selectedLog?.actionRaw).label"></span>
                    </div>
                    <div class="space-y-1">
                        <x-filter-label class="uppercase tracking-wider">Người dùng</x-filter-label>
                        <p class="font-medium"><span x-text="selectedLog?.userName"></span> <span class="font-normal text-gray-500">(<span x-text="selectedLog?.userEmail"></span>)</span></p>
                    </div>
                    <div class="space-y-1">
                        <x-filter-label class="uppercase tracking-wider">IP Address</x-filter-label>
                        <p class="font-medium" x-text="selectedLog?.ipAddress || 'Không có thông tin'"></p>
                    </div>
                    <div class="space-y-1 md:col-span-2">
                        <x-filter-label class="uppercase tracking-wider">Chức năng chính</x-filter-label>
                        <p class="rounded-md border bg-slate-50 p-2 font-medium" x-text="selectedLog?.module"></p>
                    </div>
                    <div class="space-y-1 md:col-span-2">
                        <x-filter-label class="uppercase tracking-wider">Chi tiết tổng quan</x-filter-label>
                        <p class="rounded-md border-l-4 border-l-[var(--nttu-primary)] bg-slate-50 p-3 text-sm leading-relaxed" x-text="selectedLog?.details"></p>
                    </div>
                </div>
                <div x-show="selectedLog?.previousData || selectedLog?.newData" class="mt-6 space-y-3 border-t pt-4">
                    <h4 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wider">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Biến động dữ liệu
                    </h4>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div x-show="selectedLog?.previousData" class="space-y-1">
                            <label class="inline-block rounded bg-red-100 px-2 py-1 text-xs font-bold text-red-600">Dữ liệu cũ (Trước khi sửa/xóa)</label>
                            <pre class="max-h-[300px] overflow-x-auto rounded-md border border-slate-800 bg-slate-950 p-3 text-xs text-red-300"><code x-text="formatJson(selectedLog?.previousData)"></code></pre>
                        </div>
                        <div x-show="selectedLog?.newData" class="space-y-1">
                            <label class="inline-block rounded bg-green-100 px-2 py-1 text-xs font-bold text-green-600">Dữ liệu mới (Sau khi thêm/sửa)</label>
                            <pre class="max-h-[300px] overflow-x-auto rounded-md border border-slate-800 bg-slate-950 p-3 text-xs text-green-300"><code x-text="formatJson(selectedLog?.newData)"></code></pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end border-t bg-slate-50 p-4">
                <x-nttu-button type="button" action="close" @click="viewOpen = false">Đóng</x-nttu-button>
            </div>
        </div>
    </div>

    {{-- Xóa một mục --}}
    <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="deleteOpen = false"></div>
        <div class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <h3 class="flex items-center gap-2 text-lg font-semibold text-red-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Xác nhận xóa
            </h3>
            <p class="mt-2 text-sm text-gray-600">
                Bạn có chắc chắn muốn xóa nhật ký hoạt động này không?
                <span class="mt-2 block text-xs italic text-gray-500">Lưu ý: Hành động này không thể hoàn tác.</span>
            </p>
            <div class="mt-6 flex justify-end gap-2">
                <x-nttu-button type="button" action="cancel" x-bind:disabled="deleting" @click="deleteOpen = false">Hủy</x-nttu-button>
                <button type="button" @click="deleteLog()" :disabled="deleting" class="inline-flex items-center gap-2 rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-50">
                    <x-form-field-icon name="trash" tone="destructive" class="h-4 w-4 text-white" />
                    <span x-text="deleting ? 'Đang xóa...' : 'Xác nhận xóa'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Xóa hàng loạt --}}
    <div x-show="bulkDeleteOpen" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="bulkDeleteOpen = false"></div>
        <div class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <h3 class="flex items-center gap-2 text-lg font-semibold text-red-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Xác nhận xóa hàng loạt
            </h3>
            <p class="mt-2 text-sm text-gray-600">
                Bạn đã chọn <span class="font-bold" x-text="selectedIds.length"></span> nhật ký để xóa.
                Bạn có chắc chắn muốn xóa tất cả các mục đã chọn không?
                <span class="mt-2 block text-xs italic text-gray-500">Lưu ý: Hành động này không thể hoàn tác.</span>
            </p>
            <div class="mt-6 flex justify-end gap-2">
                <x-nttu-button type="button" action="cancel" x-bind:disabled="deleting" @click="bulkDeleteOpen = false">Hủy</x-nttu-button>
                <button type="button" @click="bulkDelete()" :disabled="deleting" class="inline-flex items-center gap-2 rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-50">
                    <x-form-field-icon name="trash" tone="destructive" class="h-4 w-4 text-white" />
                    <span x-show="!deleting">Xóa <span x-text="selectedIds.length"></span> mục</span>
                    <span x-show="deleting" x-cloak>Đang xóa...</span>
                </button>
            </div>
        </div>
    </div>
</div>
