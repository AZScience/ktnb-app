@props(['items', 'module', 'date', 'title', 'cardTitle' => null, 'modalEntity' => null, 'uiConfig' => [], 'incidentCategories', 'masterData', 'employeeDefault' => null, 'dateNotice' => null])

@php
    $cardTitle = $cardTitle ?? $title;
    $modalEntity = $modalEntity ?? 'lớp học';
    $employeeDefault = $employeeDefault ?? auth()->user()->name;
    $showsAttendingStudents = \App\Models\DailySchedule::showsAttendingStudents($module);
    $uiConfig = array_merge([
        'recordingOnlyOnEdit' => false,
        'noteInClassSection' => false,
        'attendingStudentsLabel' => 'SV tham gia',
        'rowClickSelects' => false,
        'hideEmployeeInEdit' => false,
        'hideRecognitionDateInEdit' => false,
        'advancedDateReloads' => false,
        'iconToolbar' => false,
        'cardIcon' => null,
    ], $uiConfig);
    $pagePerms = $nttuPage(\App\Support\PagePermissionFlags::monitoringModule($module));
@endphp

<div
    x-data="monitoringSchedulesPage({
        module: @js($module),
        date: @js($date),
        items: @js($items->values()),
        masterData: @js($masterData),
        incidentCategories: @js($incidentCategories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()),
        employeeDefault: @js($employeeDefault),
        todayDate: @js(date('d/m/Y')),
        modalEntity: @js($modalEntity),
        showsAttendingStudents: @js($showsAttendingStudents),
        uiConfig: @js($uiConfig),
        dateNotice: @js($dateNotice ?? ''),
        dataUrl: @js(route('monitoring.schedules.data', $module)),
        exportUrl: @js(route('monitoring.schedules.export', $module)),
        detailUrl: @js(route('monitoring.schedules.show', ['module' => $module, 'schedule' => '__ID__'])),
        presetsUrl: @js(route('monitoring.schedules.filter-presets', $module)),
        evidenceUploadUrl: @js(route('monitoring.evidence.upload')),
        canAdd: @js($pagePerms['add']),
        canEdit: @js($pagePerms['edit']),
        canDelete: @js($pagePerms['delete']),
        canImport: @js($pagePerms['import']),
        canExport: @js($pagePerms['export']),
    })"
    class="space-y-3"
>
    <div x-show="toast" x-cloak
        class="fixed bottom-4 right-4 z-[70] rounded-lg px-4 py-3 text-sm shadow-lg flex items-center justify-between gap-3"
        :class="toast?.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'">
        <span x-text="toast?.message"></span>
        <button type="button" @click="toast = null" class="shrink-0 rounded-full p-1 text-white/70 hover:bg-black/10 hover:text-white transition-colors" title="Đóng">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
    <div class="nttu-card">
        <div class="py-3 px-4 border-b flex flex-wrap justify-between items-center gap-3">
            <h2 class="text-xl font-semibold flex items-center gap-2 text-gray-800">
                @if($uiConfig['iconToolbar'])
                @if(($uiConfig['cardIcon'] ?? '') === 'laptop')
                <svg class="h-6 w-6 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5z"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
                @elseif(($uiConfig['cardIcon'] ?? '') === 'monitor-check')
                <svg class="h-6 w-6 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17v5"/><path d="M8.5 2.206a2 2 0 0 0-1 1.728l-1 8.5A2 2 0 0 0 8.5 14H12m0 0h3.5a2 2 0 0 0 1.974-1.566l1-8.5A2 2 0 0 0 16.5 2.206 2 2 0 0 0 15 3.172V11m-6 6h6"/><path d="m9 12 2 2 4-4"/></svg>
                @elseif(($uiConfig['cardIcon'] ?? '') === 'book-check')
                <svg class="h-6 w-6 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/><path d="m9 10 2 2 4-4"/></svg>
                @elseif(($uiConfig['cardIcon'] ?? '') === 'truck')
                <svg class="h-6 w-6 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17h4"/><path d="M5 17h2a2 2 0 0 0 2-2V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v10a2 2 0 0 0 2 2h2"/><path d="M7 11h10"/></svg>
                @else
                <svg class="h-6 w-6 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/><path d="M8 12h6"/><path d="M8 8h6"/><circle cx="16" cy="8" r="2"/></svg>
                @endif
                @endif
                <span x-text="'{{ $cardTitle }}'"></span>
            </h2>
            <div class="flex flex-wrap items-center gap-2">
                @if($uiConfig['iconToolbar'])
                <button type="button" @click="openAdvancedFilter()" class="relative rounded-md p-2 text-orange-500 hover:bg-orange-50" title="Bộ lọc nâng cao">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 8h12M9 12h6M11 16h2"/></svg>
                </button>
                @if ($pagePerms['import'])
                <a href="{{ route('schedules.import-template') }}" class="rounded-md p-2 text-indigo-600 hover:bg-indigo-50" title="Tải mẫu file import">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M4 12V7a3 3 0 013-3h10a3 3 0 013 3v1"/></svg>
                </a>
                <form method="POST" action="{{ route('monitoring.schedules.import', $module) }}" enctype="multipart/form-data" class="inline">
                    @csrf
                    <input type="hidden" name="date" :value="date">
                    <input type="file" name="file" accept=".xlsx,.xls" class="hidden" id="import-file-{{ $module }}-icon" onchange="this.form.submit()">
                    <button type="button" onclick="document.getElementById('import-file-{{ $module }}-icon').click()" class="rounded-md p-2 text-blue-600 hover:bg-blue-50" title="Nhập file Excel">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    </button>
                </form>
                @endif
                <a x-show="canExport" x-cloak :href="exportUrl" class="rounded-md p-2 text-green-600 hover:bg-green-50" title="Xuất file Excel">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                </a>
                <button type="button" x-show="canAdd" x-cloak @click="openAddModal()" class="rounded-md p-2 text-[var(--nttu-primary)] hover:bg-cyan-50" title="Thêm mới">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </button>
                @else
                <form method="GET" class="flex items-center gap-2">
                    <label class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500">
                        <x-form-field-icon name="calendar" tone="blue" class="h-3.5 w-3.5" />
                        Ngày
                    </label>
                    <input name="date" value="{{ $date }}" placeholder="dd/mm/yyyy" class="rounded-md border-gray-300 text-sm w-32 shadow-sm">
                    <x-nttu-button type="submit" action="filter" size="sm">Lọc</x-nttu-button>
                </form>
                <x-nttu-button type="button" action="filter" size="sm" class="border-orange-200 bg-orange-50 text-orange-700" @click="openAdvancedFilter()">Bộ lọc nâng cao</x-nttu-button>
                <button type="button" @click="colVisOpen = !colVisOpen" class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm text-gray-600 hover:bg-slate-50">
                    <x-form-field-icon name="eye" tone="indigo" class="h-4 w-4" />
                    Cột hiển thị
                </button>
                <a x-show="canExport" x-cloak :href="exportUrl" class="inline-flex items-center gap-1.5 rounded-md border border-green-200 bg-green-50 px-3 py-1.5 text-sm text-green-700 hover:bg-green-100">
                    <x-form-field-icon name="export" tone="green" class="h-4 w-4" />
                    Xuất
                </a>
                @if ($pagePerms['import'])
                <a href="{{ route('schedules.import-template') }}" class="inline-flex items-center gap-1.5 rounded-md border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-sm text-indigo-700 hover:bg-indigo-100">
                    <x-form-field-icon name="file-text" tone="indigo" class="h-4 w-4" />
                    Mẫu import
                </a>
                <form method="POST" action="{{ route('monitoring.schedules.import', $module) }}" enctype="multipart/form-data" class="inline">
                    @csrf
                    <input type="hidden" name="date" :value="date">
                    <input type="file" name="file" accept=".xlsx,.xls" class="hidden" id="import-file-{{ $module }}" onchange="this.form.submit()">
                    <x-nttu-button type="button" action="import" size="sm" class="border-blue-200 bg-blue-50 text-blue-700" onclick="document.getElementById('import-file-{{ $module }}').click()">Nhập</x-nttu-button>
                </form>
                @endif
                <x-nttu-button type="button" action="add" size="sm" x-show="canAdd" x-cloak class="border-cyan-200 bg-cyan-50 text-cyan-700" @click="openAddModal()" title="Thêm mới">Thêm</x-nttu-button>
                @endif
            </div>
        </div>

        <div x-show="colVisOpen && !uiConfig.iconToolbar" x-cloak class="px-4 py-2 border-b bg-slate-50 space-y-2 text-sm">
            <div class="nttu-column-settings-bulk-actions flex gap-1 rounded-md border border-gray-200 bg-white px-1 py-1">
                <button type="button" @click.stop="selectAllColumns()"
                    class="nttu-column-settings-bulk-select flex-1 rounded px-2 py-1 text-[11px] font-semibold hover:bg-blue-50">
                    <span data-i18n="Chọn tất cả">Chọn tất cả</span>
                </button>
                <button type="button" @click.stop="deselectAllColumns()"
                    class="nttu-column-settings-bulk-deselect flex-1 rounded px-2 py-1 text-[11px] font-medium hover:bg-slate-50">
                    <span data-i18n="Bỏ chọn">Bỏ chọn</span>
                </button>
            </div>
            <div class="flex flex-wrap gap-3">
            <template x-for="key in columnOrder" :key="key">
                <label class="inline-flex items-center gap-1.5">
                    <input type="checkbox" :checked="columnVisibility[key]" @change="toggleColumn(key)" class="rounded border-gray-300">
                    <span x-text="columns[key]"></span>
                </label>
            </template>
            </div>
        </div>

        <div class="overflow-x-auto overflow-y-visible">
            <table class="nttu-table nttu-catalog-table min-w-full w-full table-fixed"
                x-bind:data-col-resize="'monitoring_' + module">
                <thead>
                    <tr>
                        <th class="catalog-th-index">#</th>
                        <template x-for="key in visibleColumnKeys" :key="key">
                            <th class="catalog-th-col relative" :data-col="key" :data-col-label="columns[key]">
                                <button type="button" @click="headerPopover = headerPopover === key ? null : key"
                                    class="group nttu-th-sort-btn">
                                    <svg x-show="columnIcon(key)" class="mr-0.5 h-3.5 w-3.5 shrink-0" :class="columnIconColor(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="columnIcon(key)"></path>
                                    </svg>
                                    <span class="nttu-th-label-text" :class="key === 'period' ? 'whitespace-nowrap' : ''" x-text="columns[key]"></span>
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
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                            Tăng dần
                                        </button>
                                        <button type="button" @click="requestSort(key, 'desc')" class="flex w-full items-center gap-2 px-2 py-1.5 hover:bg-gray-50 rounded text-left">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            Giảm dần
                                        </button>
                                        <button type="button" x-show="sortDirection(key)" @click="clearSort()" class="nttu-popover-clear-action flex w-full items-center gap-2 px-2 py-1.5 rounded text-left border-t mt-1 pt-2">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Xóa sắp xếp
                                        </button>
                                    </div>
                                    <div class="border-t p-2">
                                        <div class="relative">
                                            <svg class="absolute left-2 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 8h12M9 12h6M11 16h2"/></svg>
                                            <input type="search" :placeholder="'Lọc ' + columns[key] + '...'"
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
                        <th class="catalog-th-settings relative">
                            <button type="button" data-float-trigger @click.stop="settingsOpen = !settingsOpen" class="nttu-catalog-cog-btn" title="Cài đặt hiển thị">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </button>
                            <div x-cloak x-float="settingsOpen" data-float-close="settingsOpen = false"
                                class="nttu-report-settings-panel nttu-floating-panel w-56 max-h-[min(20rem,calc(100vh-1rem))] overflow-y-auto rounded-md border border-gray-200 bg-white py-2 shadow-xl">
                                <p class="report-settings-title border-b border-gray-100 px-3 py-2">Hiển thị cột</p>
                                @include('components.partials.column-settings-bulk-actions')
                                <template x-for="key in columnOrder" :key="key">
                                    <label class="flex items-center gap-2 px-3 py-1.5 hover:bg-slate-50 cursor-pointer">
                                        <input type="checkbox" :checked="columnVisibility[key]" @change="toggleColumn(key)" class="rounded border-gray-300">
                                        <span x-text="columns[key]"></span>
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
                                icon="table"
                                filters-active="hasActiveAdvancedFilters || Object.keys(columnFilters).some(k => columnFilters[k])"
                                clear-action="clearAllFilters()"
                            />
                        </td></tr>
                    </template>
                    <template x-for="(item, idx) in paginatedItems" :key="item.id">
                        <tr @click="handleRowClick(item)" class="group" :class="isRowSelected(item.id) && 'row-selected'">
                            <td class="text-center align-middle w-20 border-r font-medium">
                                <span :class="rowIndexBadgeClass(item)" x-text="startIndex + idx + 1"></span>
                            </td>
                            <template x-for="key in visibleColumnKeys" :key="key">
                                <td class="align-middle border-r min-w-0 break-words"
                                    :class="(key === 'note' || key === 'content' || key === 'lecturer') ? 'text-left' : ''"
                                    :data-col="key">
                                    <span x-show="key === 'period'" x-cloak class="font-mono font-bold text-xs" x-text="item.period ?? '—'"></span>
                                    <span x-show="key === 'type' && item.type" x-cloak class="nttu-cell-chip inline-flex items-center rounded border border-gray-300 px-1.5 py-0.5 text-xs font-medium text-gray-700" x-text="item.type"></span>
                                    <span x-show="key === 'type' && !item.type" x-cloak class="text-gray-400">---</span>
                                    <span x-show="key === 'status'" x-cloak x-text="item.status || 'Phòng học'"></span>
                                    <span x-show="key === 'is_notification'" x-cloak class="flex items-center justify-center">
                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 pointer-events-none"
                                            :checked="notificationChecked(item.is_notification)" disabled aria-label="Thông báo">
                                    </span>
                                    <span x-show="key === 'building'" x-cloak class="break-words" x-text="item.building_label || item.building || ''"></span>
                                    <span x-show="key === 'room'" x-cloak class="break-words" x-text="item.room_label || item.room || ''"></span>
                                    <span x-show="key !== 'period' && key !== 'type' && key !== 'status' && key !== 'is_notification' && key !== 'building' && key !== 'room'" x-cloak class="break-words" x-text="item[key] ?? ''"></span>
                                </td>
                            </template>
                            <td class="catalog-td-settings sticky-action text-center border-l border-slate-100 p-0 align-middle shadow-[-2px_0_5px_rgba(0,0,0,0.05)]" @click.stop>
                                <button type="button" data-float-trigger title="Thao tác" class="nttu-row-action-btn" @click.stop="rowMenuOpen = rowMenuOpen === item.id ? null : item.id">
                                    @include('components.partials.row-action-menu-icon')
                                </button>
                                <div x-cloak x-float="rowMenuOpen === item.id"
                                    class="nttu-row-action-menu nttu-floating-panel w-44 rounded-md border bg-white py-1 text-sm shadow-lg text-left font-normal">
                                    <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-slate-50" @click="openModal('view', item)">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Chi tiết
                                    </button>
                                    <button type="button" x-show="canEdit" x-cloak class="flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-slate-50" @click="openModal('edit', item)">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Ghi nhận
                                    </button>
                                    <button type="button" x-show="canEdit && isHandled(item)" x-cloak class="flex w-full items-center gap-2 px-3 py-2 text-left text-amber-700 hover:bg-amber-50" @click="confirmClearRecording(item)">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                        Hủy ghi nhận
                                    </button>
                                    <button type="button" x-show="canAdd" x-cloak class="flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-slate-50" @click="openModal('copy', item)">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
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
                <span x-text="text('Tổng cộng') + ' ' + filteredItems.length + ' ' + text('bản ghi.')"></span>
                <span x-show="visibleSelectedCount > 0" x-text="' ' + text('Đã chọn') + ' ' + visibleSelectedCount + ' ' + text('dòng.')"></span>
            </span>
            <div class="flex items-center gap-3">
                <label class="inline-flex items-center gap-2">
                    <span x-text="'Số dòng'"></span>
                            <input type="number" class="nttu-rows-per-page-input [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" list="{{ $datalistId = uniqid('dl_') }}" :value="normalizedRowsPerPage" @change="setRowsPerPage($event.target.value)">
<datalist id="{{ $datalistId }}">
    <template x-for="n in [5,10,15,20,25,30,35,40,45,50]" :key="n">
        <option :value="n"></option>
    </template>
</datalist>
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
            <button type="button" class="absolute right-4 top-3 rounded-sm text-gray-400 hover:text-gray-600" @click="closeAdvancedFilter()" aria-label="Đóng">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="overflow-y-auto p-6 max-h-[70vh]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    @if($uiConfig['advancedDateReloads'])
                    <div class="space-y-2">
                        <label class="font-medium flex items-center gap-2 text-gray-700">
                            <svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Ngày lọc
                        </label>
                        <input type="date"
                            :value="advancedFilters.date?.includes('/') ? advancedFilters.date.split('/').reverse().join('-') : (advancedFilters.date || '')"
                            @change="onAdvancedDateChange($event.target.value)"
                            class="nttu-form-control">
                    </div>
                    @endif
                    <div class="space-y-2">
                        <label class="font-medium flex items-center gap-2 text-gray-700">
                            <svg class="h-4 w-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Ca học
                        </label>
                        <select
                            :value="advancedFilters.periodSession"
                            @change="onPeriodSessionChange($event.target.value)"
                            class="nttu-form-control">
                            <option value="all">Tất cả</option>
                            <option value="morning">Ca sáng (1-6)</option>
                            <option value="afternoon">Ca chiều (7-12)</option>
                            <option value="evening">Ca tối (13-17)</option>
                            <option value="custom">Tùy chỉnh...</option>
                        </select>
                    </div>
                    <div x-show="advancedFilters.periodSession === 'custom'" x-cloak class="md:col-span-2 grid grid-cols-2 gap-4 border rounded-lg bg-slate-50/80 p-3">
                        <div class="space-y-2">
                            <x-filter-label>Từ tiết</x-filter-label>
                            <input type="number" :value="advancedFilters.periodStart" @input="onPeriodRangeChange('periodStart', $event.target.value)" class="nttu-form-control" min="1" max="17" placeholder="1">
                        </div>
                        <div class="space-y-2">
                            <x-filter-label>Đến tiết</x-filter-label>
                            <input type="number" :value="advancedFilters.periodEnd" @input="onPeriodRangeChange('periodEnd', $event.target.value)" class="nttu-form-control" min="1" max="17" placeholder="17">
                        </div>
                    </div>
                    @php
                    $lecturerFilterLabel = $module === 'exams' ? 'CBCT (Chọn nhiều)' : 'Giảng viên (Chọn nhiều)';
                    @endphp
                    @foreach ([
                        ['buildings', 'Dãy nhà (Chọn nhiều)', 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
                        ['departments', 'Khoa / Đơn vị (Chọn nhiều)', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                        ['rooms', 'Phòng (Chọn nhiều)', 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z'],
                        ['lecturers', $lecturerFilterLabel, 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                    ] as [$field, $label, $iconPath])
                    <div class="space-y-2">
                        <label class="font-medium flex items-center gap-2 text-gray-700">
                            <svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/></svg>
                            {{ $label }}
                        </label>
                        <x-nttu-multi-select :field="$field" placeholder="Chọn..." search-placeholder="Tìm kiếm..." />
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t bg-slate-50/80 px-4 py-4">
                <button type="button" @click="resetAdvanced()" class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Xóa tất cả
                </button>
                <button type="button" @click="applyAdvanced()" class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Áp dụng bộ lọc</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Modal ghi nhận --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="closeModal()">
        <div class="absolute inset-0 bg-black/50" @click="closeModal()"></div>
        <div class="relative flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl" @click.stop>
            <div class="border-b px-6 py-4 pr-14">
                <h3 class="text-xl font-semibold text-gray-900" x-text="modalTitle"></h3>
            </div>
            <button type="button" class="absolute right-4 top-4 rounded-sm text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300" @click="closeModal()" aria-label="Đóng">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="max-h-[75vh] overflow-y-auto p-6 space-y-4">
                <div class="space-y-4">
                    <div class="rounded-lg border bg-white px-4">
                        <button type="button" class="flex w-full items-center justify-between py-3 text-left" @click="toggleSection('class-info')">
                            <span class="flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-[var(--nttu-primary)]">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                {{ $uiConfig['classSectionTitle'] ?? 'THÔNG TIN LỚP HỌC' }}
                            </span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform" :class="openSections['class-info'] ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="openSections['class-info']" x-cloak class="pb-4">
                            <div class="grid grid-cols-1 gap-y-4 gap-x-6 rounded-lg bg-slate-50 p-4 text-sm md:grid-cols-3"
                                :class="isClassReadOnly && 'pointer-events-none opacity-60'">
                                @php
                                $periodLateBuildingModules = in_array($module, ['online', 'in-person', 'external-practice'], true);
                                $comboFieldsEarly = $periodLateBuildingModules ? [] : [
                                    ['building', 'Dãy nhà'],
                                    ['room', 'Phòng'],
                                ];
                                $comboFieldsLate = $periodLateBuildingModules ? [
                                    ['building', 'Dãy nhà'],
                                    ['room', 'Phòng'],
                                ] : [];
                                $contentFieldLabel = $module === 'online' ? 'Môn học' : 'Nội dung';
                                @endphp
                                <div class="space-y-1">
                                    <label class="text-gray-500 flex items-center gap-1.5"><svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg> Ngày</label>
                                    <template x-if="isClassFieldEditable"><input type="date" :value="form.date?.includes('/') ? form.date.split('/').reverse().join('-') : ''" @change="const v=$event.target.value; if(v){const[y,m,d]=v.split('-'); form.date=`${d}/${m}/${y}`;} else form.date='';" class="nttu-form-control"></template>
                                    <template x-if="!isClassFieldEditable"><p class="font-bold" x-text="form.date || '—'"></p></template>
                                </div>
                                @foreach ($comboFieldsEarly as [$field, $label])
                                @if ($field === 'room')
                                <div class="space-y-1">
                                    <label class="text-gray-500 flex items-center gap-1.5">{{ $label }}</label>
                                    <template x-if="isClassFieldEditable">
                                        <input type="text" x-model="form.room" class="nttu-form-control" list="nttu-room-{{ $module }}" placeholder="Chọn hoặc nhập...">
                                    </template>
                                    <template x-if="!isClassFieldEditable">
                                        <p class="font-bold" x-text="form.room || '—'"></p>
                                    </template>
                                </div>
                                @else
                                <x-monitoring-creatable-input
                                    :field="$field"
                                    :list-id="'nttu-' . $field . '-' . $module"
                                    :on-building-change="$field === 'building'"
                                >
                                    <x-slot:label>
                                        <label class="text-gray-500 flex items-center gap-1.5">{{ $label }}</label>
                                    </x-slot:label>
                                </x-monitoring-creatable-input>
                                @endif
                                @endforeach
                                <div class="space-y-1">
                                    <label class="text-gray-500 flex items-center gap-1.5">Tiết</label>
                                    <template x-if="isClassFieldEditable"><input x-model="form.period" class="nttu-form-control"></template>
                                    <template x-if="!isClassFieldEditable"><p class="font-bold font-mono" x-text="form.period || '—'"></p></template>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-gray-500 flex items-center gap-1.5">LT/TH</label>
                                    <template x-if="isClassFieldEditable">
                                        <select x-model="form.type" class="nttu-form-control">
                                            <option value="">---</option><option value="LT">Lý thuyết (LT)</option><option value="TH">Thực hành (TH)</option>
                                        </select>
                                    </template>
                                    <template x-if="!isClassFieldEditable"><p class="font-bold" x-text="form.type || '—'"></p></template>
                                </div>
                                <x-monitoring-creatable-input field="department" :list-id="'nttu-department-' . $module">
                                    <x-slot:label>
                                        <label class="text-gray-500 flex items-center gap-1.5">Khoa sử dụng</label>
                                    </x-slot:label>
                                </x-monitoring-creatable-input>
                                <div class="space-y-1">
                                    <label class="text-gray-500 flex items-center gap-1.5">Lớp</label>
                                    <template x-if="isClassFieldEditable"><input x-model="form.class" class="nttu-form-control"></template>
                                    <template x-if="!isClassFieldEditable"><p class="font-bold text-blue-600" x-text="form.class || '—'"></p></template>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-gray-500 flex items-center gap-1.5">Sĩ số</label>
                                    <template x-if="isClassFieldEditable"><input type="number" x-model="form.student_count" min="0" class="nttu-form-control"></template>
                                    <template x-if="!isClassFieldEditable"><p class="font-bold" x-text="form.student_count ?? '—'"></p></template>
                                </div>
                                @if($module === 'exams')
                                @foreach (['proctor1' => 'CBCT 01', 'proctor2' => 'CBCT 02', 'proctor3' => 'CBCT 03'] as $proctorField => $proctorLabel)
                                <x-monitoring-creatable-input
                                    :field="$proctorField"
                                    :list-id="'nttu-lecturer-' . $module"
                                    readonly-class="font-bold text-[var(--nttu-primary)]"
                                >
                                    <x-slot:label>
                                        <label class="text-gray-500 flex items-center gap-1.5">{{ $proctorLabel }}</label>
                                    </x-slot:label>
                                </x-monitoring-creatable-input>
                                @endforeach
                                @else
                                <x-monitoring-creatable-input
                                    field="lecturer"
                                    :list-id="'nttu-lecturer-' . $module"
                                    readonly-class="font-bold text-[var(--nttu-primary)]"
                                >
                                    <x-slot:label>
                                        <label class="text-gray-500 flex items-center gap-1.5">Giảng viên</label>
                                    </x-slot:label>
                                </x-monitoring-creatable-input>
                                @endif
                                @foreach ($comboFieldsLate as [$field, $label])
                                @if ($field === 'room')
                                <div class="space-y-1">
                                    <label class="text-gray-500 flex items-center gap-1.5">{{ $label }}</label>
                                    <template x-if="isClassFieldEditable">
                                        <input type="text" x-model="form.room" class="nttu-form-control" list="nttu-room-{{ $module }}" placeholder="Chọn hoặc nhập...">
                                    </template>
                                    <template x-if="!isClassFieldEditable">
                                        <p class="font-bold" x-text="form.room || '—'"></p>
                                    </template>
                                </div>
                                @else
                                <x-monitoring-creatable-input
                                    :field="$field"
                                    :list-id="'nttu-' . $field . '-' . $module"
                                    :on-building-change="$field === 'building'"
                                >
                                    <x-slot:label>
                                        <label class="text-gray-500 flex items-center gap-1.5">{{ $label }}</label>
                                    </x-slot:label>
                                </x-monitoring-creatable-input>
                                @endif
                                @endforeach
                                <div class="md:col-span-2 space-y-1">
                                    <label class="text-gray-500 flex items-center gap-1.5">{{ $contentFieldLabel }}</label>
                                    <template x-if="isClassFieldEditable"><input x-model="form.content" class="nttu-form-control"></template>
                                    <template x-if="!isClassFieldEditable"><p class="text-xs" x-text="form.content || '—'"></p></template>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-gray-500 flex items-center gap-1.5">Trạng thái</label>
                                    <template x-if="isStatusEditable">
                                        <select x-model="form.status" class="nttu-form-control">
                                            <option value="Phòng học">Phòng học</option>
                                            <option value="Phòng thi">Phòng thi</option>
                                            <option value="Phòng tự do">Phòng tự do</option>
                                        </select>
                                    </template>
                                    <template x-if="!isStatusEditable"><p class="font-bold" x-text="form.status || 'Phòng học'"></p></template>
                                </div>
                            </div>
                            <div x-show="uiConfig.noteInClassSection" class="mt-4 rounded-lg bg-slate-50 p-4 text-sm">
                                <div class="space-y-1">
                                    <label class="text-gray-500 flex items-center gap-1.5">Ghi chú</label>
                                    <template x-if="isNoteFieldEditable">
                                        <div class="relative">
                                            <input
                                                x-model="form.note"
                                                type="text"
                                                class="nttu-form-control pr-10"
                                                :placeholder="persistLocalNoteEnabled && module === 'online' ? 'Dán link eLearning (LCMS) hoặc ghi chú...' : 'Ghi chú thêm...'"
                                                @input.debounce.400ms="persistLocalNoteDraft()"
                                            >
                                            <div x-data="{ showDropdown: false }" class="absolute inset-y-0 right-0 flex items-center pr-2" x-show="module === 'online' || module === 'homeroom'">
                                                <button type="button" 
                                                    title="Quét tìm Link Google Meet"
                                                    @click="module === 'homeroom' ? fetchMeetLinkFromEmail('email') : showDropdown = !showDropdown"
                                                    @click.away="showDropdown = false"
                                                    class="p-1 rounded-md text-blue-600 hover:bg-blue-50 hover:text-blue-800 disabled:opacity-50 flex items-center justify-center"
                                                    :disabled="isFetchingMeetLink">
                                                    <svg x-show="isFetchingMeetLink" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    <svg x-show="!isFetchingMeetLink" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    </svg>
                                                </button>
                                                
                                                <div x-show="showDropdown && module !== 'homeroom'" x-transition.opacity.duration.200ms
                                                    class="absolute right-0 top-full mt-1 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-50 overflow-hidden"
                                                    style="display: none;">
                                                    <div class="py-1">
                                                        <button type="button" @click="showDropdown = false; fetchMeetLinkFromEmail('email')" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                            <svg class="h-4 w-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                                            Từ Email (ktnb@...)
                                                        </button>
                                                        <button type="button" @click="showDropdown = false; fetchMeetLinkFromEmail('lcms')" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2 border-t border-gray-100">
                                                            <svg class="h-4 w-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                                                            Từ LCMS (Moodle)
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!isNoteFieldEditable">
                                        <p class="py-2" x-text="form.note || '—'"></p>
                                    </template>
                                </div>
                            </div>
                            <datalist id="nttu-building-{{ $module }}">
                                @foreach (($masterData['buildings'] ?? []) as $name)
                                    <option value="{{ $name }}"></option>
                                @endforeach
                            </datalist>
                            <datalist id="nttu-room-{{ $module }}">
                                <template x-for="name in filteredRoomNames" :key="name">
                                    <option :value="name"></option>
                                </template>
                            </datalist>
                            <datalist id="nttu-department-{{ $module }}">
                                @foreach (($masterData['departments'] ?? []) as $name)
                                    <option value="{{ $name }}"></option>
                                @endforeach
                            </datalist>
                            <datalist id="nttu-lecturer-{{ $module }}">
                                @foreach (($masterData['lecturers'] ?? []) as $name)
                                    <option value="{{ $name }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>

                    <div x-show="showRecordingSection" class="rounded-lg border bg-white px-4">
                        <button type="button" class="flex w-full items-center justify-between py-3 text-left" @click="toggleSection('recording-info')">
                            <span class="flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-orange-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                THÔNG TIN GHI NHẬN
                            </span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform" :class="openSections['recording-info'] ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="openSections['recording-info']" x-cloak class="pb-4">
                            <div class="grid grid-cols-1 gap-4 rounded-lg bg-slate-50 p-4 text-sm md:grid-cols-3" :class="!isViewMode && !isRecordingEditable && 'pointer-events-none opacity-80'">
                                <div x-show="showsAttendingStudents" class="space-y-1">
                                    <label class="text-gray-500 flex items-center gap-1.5">
                                        <svg class="h-4 w-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                                        <span x-text="attendingStudentsLabel"></span>
                                    </label>
                                    <input type="number" x-model="form.attending_students" :readonly="isViewMode" class="nttu-form-control" min="0">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-gray-500 flex items-center gap-1.5">
                                        <svg class="h-4 w-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        Việc phát sinh<span x-show="requiresIncident" class="text-red-600"> *</span>
                                    </label>
                                    <select x-model="form.incident" :disabled="isViewMode" class="nttu-form-control" :required="requiresIncident && !isViewMode">
                                        <option value="" x-show="!requiresIncident">--- Không có ---</option>
                                        <option value="" x-show="requiresIncident" disabled hidden>— Chọn việc phát sinh —</option>
                                        @foreach ($incidentCategories as $cat)
                                            <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex items-end">
                                    <label class="flex w-full items-center gap-2 rounded-md border border-orange-200 bg-orange-50 px-3 py-2 text-sm font-bold text-orange-700">
                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300" :disabled="isViewMode"
                                            :checked="notificationChecked(form.is_notification)"
                                            @change="form.is_notification = $event.target.checked">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                        <span>Thông báo</span>
                                    </label>
                                </div>
                                <div class="md:col-span-3 space-y-1">
                                    <label class="text-gray-500 flex items-center gap-1.5">
                                        <svg class="h-4 w-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                        Chi tiết việc phát sinh @if (in_array($module, ['homeroom', 'external-practice']))<span class="text-red-600">*</span>@endif
                                    </label>
                                    <div class="relative">
                                        <input x-model="form.incident_detail" :readonly="isViewMode" class="nttu-form-control w-full"
                                            @if (in_array($module, ['homeroom', 'external-practice'])) required @endif
                                            @if (in_array($module, ['homeroom', 'exams'])) :class="!isViewMode && 'pr-20'" @endif
                                            :placeholder="incidentDetailExtracting ? 'Đang đọc nội dung từ ảnh phiếu...' : ''">
                                        <div x-show="!isViewMode" class="absolute inset-y-0 right-2 flex items-center gap-1">
                                            <svg x-show="incidentDetailExtracting" x-cloak class="h-4 w-4 animate-spin text-orange-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                                            @if (in_array($module, ['homeroom', 'exams']))
                                            <button type="button" x-show="!incidentDetailExtracting"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded text-orange-600 hover:bg-orange-100"
                                                @click="openIncidentDetailCamera()"
                                                title="Chụp ảnh phiếu để trích xuất nội dung">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            </button>
                                            <button type="button" x-show="!incidentDetailExtracting"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded text-orange-600 hover:bg-orange-100"
                                                @click="$refs.incidentDetailPhotoInput.click()"
                                                title="Tải ảnh phiếu từ máy để trích xuất nội dung">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            </button>
                                            @endif
                                        </div>
                                        <input type="file" x-ref="incidentDetailCameraInput" accept="image/*" capture="environment" class="hidden"
                                            @change="extractIncidentDetailFromPhoto($event)">
                                        <input type="file" x-ref="incidentDetailPhotoInput" accept="image/*" class="hidden"
                                            @change="extractIncidentDetailFromPhoto($event)">

                                        <div x-show="incidentCameraOpen" x-cloak
                                            class="fixed inset-0 z-[80] flex items-center justify-center bg-black/70 p-4"
                                            @keydown.escape.window="closeIncidentDetailCamera()">
                                            <div class="w-full max-w-2xl overflow-hidden rounded-lg bg-white shadow-xl" @click.stop>
                                                <div class="flex items-center justify-between border-b px-4 py-3">
                                                    <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-orange-600">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                        Chụp ảnh phiếu sinh hoạt
                                                    </h3>
                                                    <button type="button" class="rounded p-1 text-gray-500 hover:bg-gray-100" @click="closeIncidentDetailCamera()">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-2 border-b bg-slate-50 px-4 py-2">
                                                    <label class="text-xs font-bold text-gray-600">Camera:</label>
                                                    <select class="h-8 flex-1 min-w-[160px] rounded-md border-gray-300 py-0 text-xs shadow-sm"
                                                        :value="incidentCameraDeviceId"
                                                        @change="selectIncidentDetailCamera($event.target.value)">
                                                        <template x-for="cam in incidentCameraDevices" :key="cam.id">
                                                            <option :value="cam.id" :selected="cam.id === incidentCameraDeviceId" x-text="cam.label"></option>
                                                        </template>
                                                    </select>
                                                    <button type="button"
                                                        class="inline-flex h-8 shrink-0 items-center gap-1.5 rounded-md border border-gray-300 bg-white px-2.5 text-xs font-bold text-gray-700 hover:bg-gray-100"
                                                        @click="flipIncidentDetailCamera()" title="Đổi camera trước / sau">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                        Trước/Sau
                                                    </button>
                                                </div>
                                                <div class="bg-black">
                                                    <video x-ref="incidentDetailVideo" autoplay playsinline muted class="mx-auto max-h-[60vh] w-full object-contain"></video>
                                                </div>
                                                <div class="flex items-center justify-between gap-3 px-4 py-3">
                                                    <p class="text-xs text-gray-500">Đưa phiếu vào giữa khung, đủ sáng, rõ mục "Nội dung sinh hoạt (CVHT)".</p>
                                                    <div class="flex shrink-0 gap-2">
                                                        <button type="button" class="rounded-md border px-3 py-2 text-sm hover:bg-gray-50" @click="closeIncidentDetailCamera()">Đóng</button>
                                                        <button type="button" class="inline-flex items-center gap-1.5 rounded-md bg-orange-600 px-4 py-2 text-sm font-bold text-white hover:bg-orange-700" @click="captureIncidentDetailPhoto()">
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                            Chụp &amp; trích xuất
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div x-show="!uiConfig.noteInClassSection" class="md:col-span-3 space-y-1">
                                    <label class="text-gray-500">Ghi chú</label>
                                    <template x-if="isNoteFieldEditable">
                                        <textarea x-model="form.note" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm"></textarea>
                                    </template>
                                    <template x-if="!isNoteFieldEditable">
                                        <p class="py-2" x-text="form.note || '—'"></p>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="showEvidenceSection" class="rounded-lg border bg-white px-4">
                        <button type="button" class="flex w-full items-center justify-between py-3 text-left" @click="toggleSection('evidence-info')">
                            <span class="flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-blue-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                THÔNG TIN MINH CHỨNG
                            </span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform" :class="openSections['evidence-info'] ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="openSections['evidence-info']" x-cloak class="pb-4">
                            <x-evidence-input-panel />
                        </div>
                    </div>
                </div>

            </div>
            <div class="flex justify-end gap-2 border-t px-6 py-4" x-show="!isViewMode">
                <button type="button" class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm" :disabled="!isChanged" @click="undoForm()">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    Hoàn tác
                </button>
                <button type="button" @click="saveMonitoring()" :disabled="saving || !isChanged" class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white disabled:opacity-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    <span x-text="saving ? 'Đang lưu...' : 'Lưu lại'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Xác nhận xóa --}}
    <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="deleteOpen = false">
        <div class="absolute inset-0 bg-black/50" @click="deleteOpen = false"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-2xl" @click.stop>
            <h3 class="text-lg font-semibold text-gray-900 i18n-auto">Xác nhận xóa</h3>
            <p class="mt-2 text-sm text-gray-600">Bạn có chắc muốn xóa lịch này? Hành động không thể hoàn tác.</p>
            <div class="mt-4 flex justify-end gap-2">
                <x-nttu-button type="button" action="cancel" @click="deleteOpen = false">Hủy</x-nttu-button>
                <x-nttu-button type="button" action="delete" x-bind:disabled="saving" @click="deleteItem()">Xóa</x-nttu-button>
            </div>
        </div>
    </div>

    {{-- Xác nhận hủy ghi nhận --}}
    <div x-show="clearRecordingOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="clearRecordingOpen = false">
        <div class="absolute inset-0 bg-black/50" @click="clearRecordingOpen = false"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-2xl" @click.stop>
            <h3 class="text-lg font-semibold text-gray-900">Xác nhận hủy ghi nhận</h3>
            <p class="mt-2 text-sm text-gray-600">Xóa thông tin ghi nhận (cán bộ, ngày ghi nhận, việc phát sinh, sĩ số hiện diện, minh chứng). Dòng lịch vẫn được giữ; vòng đỏ ở cột # sẽ biến mất.</p>
            <div class="mt-4 flex justify-end gap-2">
                <x-nttu-button type="button" action="cancel" @click="clearRecordingOpen = false">Đóng</x-nttu-button>
                <button type="button"
                    class="inline-flex items-center gap-2 rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50"
                    x-bind:disabled="saving"
                    @click="clearRecording()">
                    <span x-text="saving ? 'Đang hủy...' : 'Hủy ghi nhận'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
