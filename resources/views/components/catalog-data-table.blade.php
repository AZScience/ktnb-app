@props([
    'title',
    'entityLabel' => 'mục',
    'storageKey',
    'columns' => [],
    'items' => [],
    'serverPaginated' => false,
    'routes' => [],
    'icon' => 'briefcase',
    'cardIcon' => null,
    'formFields' => [
        ['key' => 'name', 'label' => 'Tên chức vụ', 'required' => true, 'icon' => 'briefcase'],
        ['key' => 'note', 'label' => 'Ghi chú', 'icon' => 'note'],
    ],
    'filterFields' => null,
    'importColumns' => null,
    'importTemplateOptions' => [],
    'modalWide' => false,
    'formDefaults' => [],
    'formSections' => [],
    'formLayout' => null,
    'evidenceUploadUrl' => null,
    'advancedFilterMode' => null,
    'advancedFilterOptions' => [],
    'permissionModule' => null,
    'allowAdd' => true,
    'canDelete' => true,
    'allowedDialogModes' => null,
    'editActionLabel' => 'Chỉnh sửa',
    'modalTitle' => null,
    'staffDefault' => null,
    'buildingOptions' => [],
    'docTypeOptions' => [],
    'departmentOptions' => [],
    'employeeOptions' => [],
    'urgencyOptions' => [],
    'confidentialityOptions' => [],
    'documentStatusOptions' => [],
    'requestTypeOptions' => [],
    'formMode' => null,
    'violationStudents' => [],
    'violationRoutes' => [],
])

@php
    $columnConfig = collect($columns)->mapWithKeys(fn ($col, $key) => [
        $key => [
            'label' => $col['label'],
            'primary' => $col['primary'] ?? false,
            'visible' => $col['visible'] ?? true,
            'type' => $col['type'] ?? 'text',
            'width' => $col['width'] ?? '',
        ],
    ]);
    $fieldMap = collect($formFields)->keyBy('key');
    $resolvedFormLayout = $formLayout ?? (
        $formMode === 'service-request' ? 'service-request' :
        ($formMode === 'asset-reception' ? 'asset-reception' :
        ($formMode === 'petition' ? 'petition' :
        ($formMode === 'document-record' ? 'document-record' :
        (count($formSections) > 0 ? 'sections' : ($modalWide ? 'grid' : 'simple')))))
    );
    $customFormModes = ['service-request', 'asset-reception', 'petition', 'document-record'];
    $isDocumentRecordModal = $resolvedFormLayout === 'document-record';
    $avatarFields = collect($formFields)->where('type', 'avatar')->values()->all();
    $nonAvatarFields = collect($formFields)->reject(fn ($f) => ($f['type'] ?? 'text') === 'avatar')->values()->all();
    $filterFieldList = $filterFields ?? collect($formFields)
        ->reject(fn ($f) => in_array($f['type'] ?? 'text', ['checkbox', 'avatar', 'hidden'], true))
        ->values()
        ->all();
    $importColumnList = $importColumns ?? collect($formFields)
        ->reject(fn ($f) => in_array($f['type'] ?? 'text', ['checkbox', 'avatar', 'hidden', 'number'], true))
        ->map(fn ($f) => ['key' => $f['key'], 'label' => $f['label'] ?? $f['key']])
        ->values()
        ->all();
    $catalogIcon = \App\Support\PageHeading::catalogIcon($storageKey, $icon);
    $tableIcon = $cardIcon ?? $catalogIcon['icon'];
    $tableIconTone = $catalogIcon['tone'] ?? 'primary';
    $resolvedPermissionModule = $permissionModule ?? \App\Support\RoutePermissionMap::moduleForCatalog($storageKey);
    $perm = fn (string $action) => $resolvedPermissionModule
        ? ($nttuAllows ?? $nttuCan)($resolvedPermissionModule, $action)
        : true;
    $canViewCatalog = $perm('view');
    $canAddCatalog = $perm('add');
    $canEditCatalog = $perm('edit');
    $canDeleteCatalog = $canDelete && $perm('delete');
    $canImportCatalog = $perm('import');
    $canExportCatalog = $perm('export');
    $allowAddCatalog = $allowAdd && $canAddCatalog;
    $allowedDialogModesResolved = $allowedDialogModes ?? ['add', 'edit', 'view', 'copy'];
    $allowedDialogModesResolved = array_values(array_filter(
        $allowedDialogModesResolved,
        fn (string $mode) => match ($mode) {
            'add', 'copy' => $canAddCatalog,
            'edit' => $canEditCatalog,
            'view' => $canViewCatalog,
            default => true,
        }
    ));
@endphp

<div
    class="w-full space-y-3"
    x-data="catalogTablePage({
        title: @js($title),
        entityLabel: @js($entityLabel),
        storageKey: @js($storageKey),
        columns: @js($columnConfig),
        formFields: @js($formFields),
        formSections: @js($formSections),
        formLayout: @js($resolvedFormLayout),
        formDefaults: @js($formDefaults),
        items: @js($serverPaginated ? [] : $items->values()),
        serverPaginated: @js($serverPaginated),
        routes: @js($routes),
        importColumns: @js($importColumnList),
        importTemplateFilename: @js('Mau_Import_' . \Illuminate\Support\Str::slug($storageKey, '_') . '.xlsx'),
        importTemplateOptions: @js($importTemplateOptions),
        evidenceUploadUrl: @js($evidenceUploadUrl ?? $routes['evidenceUpload'] ?? route('monitoring.evidence.upload')),
        advancedFilterMode: @js($advancedFilterMode),
        advancedFilterOptions: @js($advancedFilterOptions),
        allowAdd: @js($allowAddCatalog),
        allowedDialogModes: @js($allowedDialogModesResolved),
        canDelete: @js($canDeleteCatalog),
        editActionLabel: @js($editActionLabel),
        modalTitle: @js($modalTitle),
        staffDefault: @js($staffDefault),
        buildingOptions: @js($buildingOptions),
        docTypeOptions: @js($docTypeOptions),
        departmentOptions: @js($departmentOptions),
        employeeOptions: @js($employeeOptions),
        urgencyOptions: @js($urgencyOptions),
        confidentialityOptions: @js($confidentialityOptions),
        documentStatusOptions: @js($documentStatusOptions),
        requestTypeOptions: @js($requestTypeOptions),
        formMode: @js($formMode),
        violationStudents: @js($violationStudents),
        violationRoutes: @js($violationRoutes),
    })"
>
    <div x-show="toast" x-cloak
        class="fixed bottom-4 right-4 z-[70] rounded-lg px-4 py-3 text-sm shadow-lg"
        :class="toast?.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
        x-text="toast?.message"></div>

    <div class="nttu-card">
    {{-- Card header --}}
    <div class="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3">
        <h2 class="flex items-center gap-2 text-xl font-semibold text-gray-800">
            <x-form-field-icon :name="$tableIcon" :tone="$tableIconTone" class="h-6 w-6" />
            <span x-text="pageTitle()"></span>
        </h2>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="advancedOpen = true" class="relative rounded-md p-2 text-orange-500 hover:bg-orange-50" :title="text('Bộ lọc nâng cao')">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 8h12M9 12h6M11 16h2"/></svg>
            </button>
            @if (!empty($routes['importPreview']) && $canImportCatalog)
            <button type="button" @click="downloadImportTemplateFile()" class="rounded-md p-2 text-indigo-600 hover:bg-indigo-50" :title="text('Tải mẫu file import')">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M4 12V7a3 3 0 013-3h10a3 3 0 013 3v1"/></svg>
            </button>
            <label class="cursor-pointer rounded-md p-2 text-blue-600 hover:bg-blue-50" :title="text('Nhập file Excel')">
                <input type="file" class="hidden" accept=".xlsx,.xls" @change="onImportFile($event)">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            </label>
            @endif
            @if (!empty($routes['export']) && $canExportCatalog)
            <button type="button" @click="exportExcel()" class="rounded-md p-2 text-green-600 hover:bg-green-50" :title="text('Xuất file Excel')">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            </button>
            @endif
            @if ($allowAddCatalog)
            <button type="button" @click="openDialog('add')" class="rounded-md p-2 text-[var(--nttu-primary)] hover:bg-cyan-50" :title="text('Thêm mới')">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </button>
            @endif
        </div>
    </div>

    <div class="w-full overflow-x-auto overflow-y-visible relative">
        <div x-show="listLoading && currentItems.length > 0" x-cloak class="absolute inset-0 z-20 flex items-center justify-center bg-white/70 text-sm text-slate-600" x-text="text('Đang tải dữ liệu...')">
        </div>
        <table class="nttu-table nttu-catalog-table w-full min-w-full table-fixed"
            data-col-resize="catalog_{{ $storageKey }}">
            <thead>
                <tr>
                    <th class="catalog-th-index">#</th>
                    <template x-for="key in visibleColumnKeys" :key="key">
                        <th class="catalog-th-col relative" :data-col="key" :data-col-label="columnLabel(key)" :class="[columns[key].width || '', columns[key].type === 'date' ? 'nttu-col-date' : ''].filter(Boolean).join(' ')">
                            <div class="relative">
                                <button type="button" class="group nttu-th-sort-btn"
                                    @click.stop="headerPopover = headerPopover === key ? null : key">
                                    <svg x-show="columnHeaderIconPath(key)" x-cloak class="mr-0.5 h-3.5 w-3.5 shrink-0" :class="columnHeaderIconClass(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="columnHeaderIconPath(key)"></path></svg>
                                    <span class="nttu-th-label-text" :class="key === 'period' ? 'whitespace-nowrap' : ''" x-text="columnLabel(key)"></span>
                                    <svg x-show="sortStateFor(key)?.direction === 'ascending'" x-cloak class="ml-2 h-4 w-4 shrink-0" :class="sortIconClass(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                    <svg x-show="sortStateFor(key)?.direction === 'descending'" x-cloak class="ml-2 h-4 w-4 shrink-0" :class="sortIconClass(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    <svg x-show="!sortStateFor(key)" x-cloak class="ml-2 h-4 w-4 shrink-0 opacity-50 group-hover:opacity-100" :class="sortIconClass(key)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                </button>
                                <div x-cloak x-float.start="headerPopover === key" data-float-close="headerPopover = null"
                                     class="nttu-report-header-popover nttu-floating-panel w-60 rounded-md border bg-white text-gray-800 shadow-lg">
                                    <div class="space-y-1 p-1">
                                        <button type="button" class="flex w-full items-center gap-2 rounded px-2 py-1.5 text-xs hover:bg-gray-100" @click="requestSort(key, 'ascending')">
                                            <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                            <span x-text="text('Tăng dần')"></span>
                                        </button>
                                        <button type="button" class="flex w-full items-center gap-2 rounded px-2 py-1.5 text-xs hover:bg-gray-100" @click="requestSort(key, 'descending')">
                                            <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            <span x-text="text('Giảm dần')"></span>
                                        </button>
                                        <template x-if="sortStateFor(key)">
                                            <button type="button" class="nttu-popover-clear-action flex w-full items-center gap-2 rounded px-2 py-1.5 text-xs" @click="clearSort()">
                                                <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                <span x-text="text('Xoá sắp xếp')"></span>
                                            </button>
                                        </template>
                                    </div>
                                    <div class="border-t p-2">
                                        <div class="relative">
                                            <svg class="pointer-events-none absolute left-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                                            <input type="text" class="h-8 w-full rounded border-gray-300 pl-8 text-xs"
                                                   :placeholder="filterPlaceholder(key)"
                                                   :value="filters[key] || ''"
                                                   @input="setFilter(key, $event.target.value)"
                                                   @keydown.enter="headerPopover = null">
                                        </div>
                                        <button type="button" x-show="isFiltered(key)" class="nttu-popover-clear-action mt-1 flex h-8 w-full items-center gap-2 rounded px-2 text-xs" @click="clearColumnFilter(key)">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span x-text="text('Xóa bộ lọc')"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </th>
                    </template>
                    <th class="catalog-th-settings relative">
                        <button type="button" data-float-trigger @click.stop="settingsOpen = !settingsOpen" class="nttu-catalog-cog-btn" :title="text('Cài đặt hiển thị')">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </button>
                        <div x-cloak x-float="settingsOpen" data-float-close="settingsOpen = false" class="nttu-report-settings-panel nttu-floating-panel w-56 max-h-[min(20rem,calc(100vh-1rem))] overflow-y-auto rounded-md border border-gray-200 bg-white py-2 shadow-xl">
                            <p class="report-settings-title border-b border-gray-100 px-3 py-2" x-text="text('Hiển thị cột')"></p>
                            @include('components.partials.column-settings-bulk-actions')
                            <template x-for="(col, key) in columns" :key="key">
                                <label class="flex cursor-pointer items-center gap-2 px-3 py-1.5 hover:bg-slate-50">
                                    <input type="checkbox" :checked="columnVisibility[key]" @change="toggleColumn(key)" class="rounded border-gray-300">
                                    <span x-text="columnLabel(key)"></span>
                                </label>
                            </template>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <template x-if="listLoading && currentItems.length === 0">
                    <tr>
                        <td :colspan="visibleColumnKeys.length + 2" class="py-12 text-center text-gray-500">
                            <x-table-empty-state
                                icon="table"
                                loading="listLoading"
                            />
                        </td>
                    </tr>
                </template>
                <template x-if="!listLoading && currentItems.length === 0">
                    <tr>
                        <td :colspan="visibleColumnKeys.length + 2" class="py-12 text-center text-gray-500">
                            <x-table-empty-state
                                icon="table"
                                filters-active="hasActiveFilters"
                                clear-action="clearFilters()"
                                message-expr="tableEmptyMessage()"
                                subtitle-expr="tableEmptySubtitle()"
                            />
                        </td>
                    </tr>
                </template>
                <template x-for="(item, idx) in currentItems" :key="item.id">
                    <tr
                        class="group"
                        :class="isSelected(item.id) ? 'row-selected' : ''"
                        @click="toggleRow(item.id)"
                    >
                        <td class="catalog-td-index align-middle" x-text="(safePage - 1) * normalizedRowsPerPage + idx + 1"></td>
                        <template x-for="key in visibleColumnKeys" :key="key">
                            <td class="align-middle min-w-0 break-words" :data-col="key" :class="[columns[key].width || '', columns[key].type === 'date' ? 'nttu-col-date' : ''].filter(Boolean).join(' ')">
                                <template x-if="columns[key].type === 'avatar'">
                                    <div class="flex justify-center">
                                        <img x-show="item.avatar_url" :src="item.avatar_url" :alt="item.name" class="h-9 w-9 rounded-full object-cover">
                                        <div x-show="!item.avatar_url" class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600" x-text="(item.name || '?').charAt(0)"></div>
                                    </div>
                                </template>
                                <template x-if="columns[key].type === 'boolean_status'">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                          :class="item[key] ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
                                          x-text="booleanStatusText(item[key])"></span>
                                </template>
                                <template x-if="columns[key].type === 'boolean_yesno'">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                          :class="item[key] ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'"
                                          x-text="booleanYesNoText(item[key])"></span>
                                </template>
                                <template x-if="columns[key].type === 'badge'">
                                    <span class="inline-flex rounded-full bg-cyan-50 px-2 py-0.5 text-xs font-medium text-cyan-700 ring-1 ring-cyan-600/20"
                                          x-text="cellValue(item, key)"></span>
                                </template>
                                <template x-if="isPrimaryTextColumn(key)">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </div>
                                        <span class="font-bold text-slate-800 break-words min-w-0" x-text="cellValue(item, key)"></span>
                                    </div>
                                </template>
                                <template x-if="columns[key].type === 'date'">
                                    <span class="text-slate-600" x-text="cellValue(item, key)"></span>
                                </template>
                                <template x-if="isStandardTextColumn(key)">
                                    <span class="text-slate-600 break-words" x-text="cellValue(item, key)"></span>
                                </template>
                            </td>
                        </template>
                        <td class="sticky-action catalog-td-settings relative border-l border-slate-100 p-0 text-center align-middle shadow-[-2px_0_5px_rgba(0,0,0,0.05)]" @click.stop>
                            <button type="button" data-float-trigger :title="text('Thao tác')" class="nttu-row-action-btn" @click.stop="rowMenuOpen = rowMenuOpen === item.id ? null : item.id">
                                @include('components.partials.row-action-menu-icon')
                            </button>
                            <div x-cloak x-float="rowMenuOpen === item.id"
                                class="nttu-row-action-menu nttu-floating-panel w-44 rounded-md border bg-white py-1 text-left text-sm font-normal shadow-lg">
                                <button type="button" class="flex w-full items-center gap-2 px-3 py-2 hover:bg-slate-50" @click="openDialog('view', item)">
                                    <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span x-text="text('Chi tiết')"></span>
                                </button>
                                <button type="button" x-show="dialogModeAllowed('edit')" class="flex w-full items-center gap-2 px-3 py-2 hover:bg-slate-50" @click="openDialog('edit', item)">
                                    <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    <span x-text="editActionLabelText()"></span>
                                </button>
                                <button type="button" x-show="dialogModeAllowed('copy')" class="flex w-full items-center gap-2 px-3 py-2 hover:bg-slate-50" @click="openDialog('copy', item)">
                                    <svg class="h-4 w-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <span x-text="text('Sao chép')"></span>
                                </button>
                                <button type="button" x-show="canDelete" class="flex w-full items-center gap-2 px-3 py-2 text-left text-red-600 hover:bg-red-50" @click="confirmDelete(item)">
                                    <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span x-text="text('Xóa')"></span>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    {{-- Footer pagination --}}
    <div class="flex flex-col gap-3 border-t bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-gray-500" x-text="paginationSummary()"></p>
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <span x-text="text('Số dòng')"></span>
                <select class="nttu-rows-per-page-select" :value="normalizedRowsPerPage" @change="setRowsPerPage($event.target.value)">
                    <template x-for="n in [5,10,15,20,25,30,35,40,45,50]" :key="n">
                        <option :value="n" x-text="n" :selected="normalizedRowsPerPage === n"></option>
                    </template>
                </select>
            </div>
            <div class="flex items-center gap-1">
                <button type="button" class="h-8 w-8 rounded border text-sm disabled:opacity-40" :disabled="safePage === 1" @click="goPage(1)">«</button>
                <button type="button" class="h-8 w-8 rounded border text-sm disabled:opacity-40" :disabled="safePage === 1" @click="goPage(safePage - 1)">‹</button>
                <span class="flex items-center gap-1 text-sm font-medium">
                    <input type="number" class="h-8 w-12 rounded border text-center text-sm" :value="safePage" @change="goPage($event.target.value)"> / <span x-text="totalPages"></span>
                </span>
                <button type="button" class="h-8 w-8 rounded border text-sm disabled:opacity-40" :disabled="safePage === totalPages" @click="goPage(safePage + 1)">›</button>
                <button type="button" class="h-8 w-8 rounded border text-sm disabled:opacity-40" :disabled="safePage === totalPages" @click="goPage(totalPages)">»</button>
            </div>
        </div>
    </div>
    </div>

    {{-- Edit/View modal --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="closeModal()" @click.self="closeModal()">
        <div class="relative flex w-full overflow-hidden rounded-xl bg-white shadow-2xl {{ $isDocumentRecordModal ? 'h-[90vh] max-w-6xl flex-col md:flex-row' : 'max-h-[92vh] flex-col ' . ($modalWide || in_array($resolvedFormLayout, ['sections', ...$customFormModes], true) ? 'max-w-4xl' : ($resolvedFormLayout === 'grid' ? 'max-w-4xl' : 'max-w-lg')) }}" @click.stop>
            @if (! in_array($formMode, $customFormModes, true))
            <div class="border-b px-6 py-4 pr-14">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-xl font-semibold text-gray-900" x-text="modalHeading()"></h3>
                    @if ($formMode === 'violations')
                    <button type="button" x-show="dialogMode !== 'view'" x-cloak
                            @click.stop="violationStartScanner()"
                            class="inline-flex items-center gap-2 rounded-md border border-[var(--nttu-primary)] px-3 py-1.5 text-sm font-medium text-[var(--nttu-primary)] hover:bg-cyan-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Quét thẻ (TSV/CCCD)
                    </button>
                    @endif
                </div>
            </div>
            @endif
            <button type="button"
                    class="absolute right-4 top-4 z-20 rounded-sm text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300"
                    @click="closeModal()"
                    aria-label="Đóng">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            @if ($isDocumentRecordModal)
                <x-document-record-form />
            @else
            <div class="max-h-[75vh] overflow-y-auto {{ in_array($resolvedFormLayout, $customFormModes, true) ? 'p-0' : 'p-6' }}" x-ref="catalogModalForm">
                @if (count($avatarFields) > 0)
                    <div class="space-y-4">
                        @foreach ($avatarFields as $field)
                            <x-catalog-form-field :field="$field" />
                        @endforeach
                    </div>
                    @if ($resolvedFormLayout === 'sections' || count($nonAvatarFields) > 0)
                        <hr class="my-6 border-gray-200">
                    @endif
                @endif

                @if ($resolvedFormLayout === 'service-request')
                    <x-service-request-form
                        :request-type-options="$requestTypeOptions"
                        :building-options="$buildingOptions"
                    />
                @elseif ($resolvedFormLayout === 'asset-reception')
                    <x-asset-reception-form :building-options="$buildingOptions" />
                @elseif ($resolvedFormLayout === 'petition')
                    <x-petition-form />
                @elseif ($resolvedFormLayout === 'sections' && count($formSections) > 0)
                    <div class="space-y-4">
                        @foreach ($formSections as $section)
                            <div class="overflow-hidden rounded-lg border bg-white px-4">
                                <button type="button" class="flex w-full items-center justify-between py-3 text-left" @click="toggleSection('{{ $section['id'] }}')">
                                    <span @class([
                                        'flex items-center gap-2 text-sm font-bold uppercase tracking-wider',
                                        'text-[var(--nttu-primary)]' => ($section['tone'] ?? 'primary') === 'primary',
                                        'text-orange-600' => ($section['tone'] ?? '') === 'orange',
                                        'text-blue-600' => ($section['tone'] ?? '') === 'blue',
                                    ])>
                                        <x-form-field-icon :name="$section['icon'] ?? 'user-cog'" :tone="$section['tone'] ?? 'primary'" />
                                        {{ $section['title'] }}
                                    </span>
                                    <svg class="h-4 w-4 text-gray-400 transition-transform" :class="openSections['{{ $section['id'] }}'] ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="openSections['{{ $section['id'] }}']" x-cloak class="pb-4">
                                    @php
                                        $sectionCols = (int) ($section['columns'] ?? 3);
                                        $sectionGridClass = match ($sectionCols) {
                                            2 => 'md:grid-cols-2',
                                            1 => 'grid-cols-1',
                                            default => 'md:grid-cols-3',
                                        };
                                    @endphp
                                    <div class="grid grid-cols-1 gap-4 rounded-lg bg-slate-50 p-4 text-sm {{ $sectionGridClass }}">
                                        @foreach ($section['fields'] as $fieldKey)
                                            @if ($fieldMap->has($fieldKey))
                                                <x-catalog-form-field :field="$fieldMap->get($fieldKey)" />
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif ($resolvedFormLayout === 'grid')
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($nonAvatarFields as $field)
                            <x-catalog-form-field :field="$field" />
                        @endforeach
                    </div>
                @else
                    <div class="grid gap-4">
                        @foreach ($nonAvatarFields as $field)
                            <x-catalog-form-field :field="$field" />
                        @endforeach
                    </div>
                @endif

            </div>
            <div class="flex justify-end gap-2 border-t px-6 py-4" x-show="dialogMode !== 'view'">
                <button type="button" class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm" :disabled="!isChanged" @click="undoForm()">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    <span x-text="text('Hoàn tác')"></span>
                </button>
                <button type="button" class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white disabled:opacity-50" :disabled="!isChanged || saving || (ckeditorMounting ?? false)" @click="saveItem()">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    <span x-text="savingLabel()"></span>
                </button>
            </div>
            @endif
        </div>
    </div>

    @if ($formMode === 'violations')
        <x-violation-form-modals />
    @endif

    {{-- Advanced filter --}}
    <div x-show="advancedOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="advancedOpen = false">
        <div class="absolute inset-0 bg-black/50" @click="advancedOpen = false"></div>
        @if ($advancedFilterMode === 'violations')
        <div class="relative z-10 w-full max-w-2xl max-h-[90vh] overflow-hidden rounded-xl bg-white shadow-2xl flex flex-col" @click.stop>
            <div class="flex items-center justify-between border-b bg-slate-50/80 px-4 py-3 pr-12">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
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
                            <button type="button" class="h-8 px-2 rounded bg-[var(--nttu-table-head)] text-white text-xs" @click="saveFilterPreset(presetName)">✓</button>
                            <button type="button" class="h-8 px-2 text-gray-500" @click="presetNaming = false; presetName = ''">×</button>
                        </div>
                    </template>
                    <template x-if="!presetNaming">
                        <button type="button" class="inline-flex items-center gap-1.5 text-[10px] font-bold text-[var(--nttu-primary)] hover:bg-cyan-50 px-2 py-1.5 rounded" @click="presetNaming = true; presetName = ''">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            Lưu hiện tại
                        </button>
                    </template>
                </div>
            </div>
            <button type="button" class="absolute right-4 top-3 rounded-sm text-gray-400 hover:text-gray-600" @click="advancedOpen = false" aria-label="Đóng">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 text-sm overflow-y-auto">
                <div class="space-y-2">
                    <label class="font-medium flex items-center gap-2 text-gray-700">
                        <svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Ngày lọc
                    </label>
                    <input type="date" x-model="advancedFilters.filterDate" class="nttu-form-control">
                </div>
                <div class="space-y-2">
                    <label class="font-medium flex items-center gap-2 text-gray-700">
                        <svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        Dãy nhà
                    </label>
                    <x-nttu-multi-select field="buildings" placeholder="Tất cả dãy nhà" search-placeholder="Tìm dãy nhà..." chip-mode="count" />
                </div>
                <div class="space-y-2">
                    <label class="font-medium flex items-center gap-2 text-gray-700">
                        <svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Người ghi nhận
                    </label>
                    <x-nttu-multi-select field="officers" placeholder="Tất cả cán bộ" search-placeholder="Tìm người ghi nhận..." chip-mode="count" />
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
            </div>
            <div class="flex justify-end gap-2 border-t bg-slate-50/80 px-6 py-4">
                <button type="button" @click="resetAdvancedFilters()" class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Xóa tất cả
                </button>
                <button type="button" @click="applyAdvancedFilters()" class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Áp dụng bộ lọc
                </button>
            </div>
        </div>
        @elseif ($advancedFilterMode === 'assets')
        <div class="relative z-10 w-full max-w-2xl max-h-[90vh] overflow-hidden rounded-xl bg-white shadow-2xl flex flex-col" @click.stop>
            <div class="flex items-center justify-between border-b bg-slate-50/80 px-4 py-3 pr-12">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
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
                            <button type="button" class="h-8 px-2 rounded bg-[var(--nttu-table-head)] text-white text-xs" @click="saveFilterPreset(presetName)">✓</button>
                            <button type="button" class="h-8 px-2 text-gray-500" @click="presetNaming = false; presetName = ''">×</button>
                        </div>
                    </template>
                    <template x-if="!presetNaming">
                        <button type="button" class="inline-flex items-center gap-1.5 text-[10px] font-bold text-[var(--nttu-primary)] hover:bg-cyan-50 px-2 py-1.5 rounded" @click="presetNaming = true; presetName = ''">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            Lưu hiện tại
                        </button>
                    </template>
                </div>
            </div>
            <button type="button" class="absolute right-4 top-3 rounded-sm text-gray-400 hover:text-gray-600" @click="advancedOpen = false" aria-label="Đóng">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 text-sm overflow-y-auto">
                <div class="space-y-2">
                    <label class="font-medium flex items-center gap-2 text-gray-700">
                        <svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Từ ngày
                    </label>
                    <input type="date" x-model="advancedFilters.start_date" class="nttu-form-control">
                </div>
                <div class="space-y-2">
                    <label class="font-medium flex items-center gap-2 text-gray-700">
                        <svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Đến ngày
                    </label>
                    <input type="date" x-model="advancedFilters.end_date" class="nttu-form-control">
                </div>
                <div class="space-y-2">
                    <label class="font-medium flex items-center gap-2 text-gray-700">
                        <svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        Dãy nhà
                    </label>
                    <x-nttu-multi-select field="buildings" placeholder="Tất cả dãy nhà" search-placeholder="Tìm dãy nhà..." chip-mode="count" />
                </div>
                <div class="space-y-2">
                    <label class="font-medium flex items-center gap-2 text-gray-700">
                        <svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Người ghi nhận
                    </label>
                    <x-nttu-multi-select field="officers" placeholder="Tất cả cán bộ" search-placeholder="Tìm người ghi nhận..." chip-mode="count" />
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t bg-slate-50/80 px-6 py-4">
                <button type="button" @click="resetAdvancedFilters()" class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Xóa tất cả
                </button>
                <button type="button" @click="applyAdvancedFilters()" class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Áp dụng bộ lọc
                </button>
            </div>
        </div>
        @elseif ($advancedFilterMode === 'documents')
        <div class="relative z-10 flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl" @click.stop>
            <div class="flex items-center justify-between border-b bg-slate-50/80 px-4 py-3 pr-12">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <div class="relative">
                        <button type="button" @click="presetMenuOpen = !presetMenuOpen" class="flex items-center gap-2 text-lg font-bold text-gray-900 hover:opacity-80">
                            Lọc hồ sơ nâng cao
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
                            <input type="text" x-model="presetName" placeholder="Tên bộ lọc..." class="h-8 w-32 rounded border-gray-300 text-xs px-2"
                                @keydown.enter.prevent="saveFilterPreset(presetName)">
                            <button type="button" class="h-8 px-2 rounded bg-[var(--nttu-table-head)] text-white text-xs" @click="saveFilterPreset(presetName)">✓</button>
                            <button type="button" class="h-8 px-2 text-gray-500" @click="presetNaming = false; presetName = ''">×</button>
                        </div>
                    </template>
                    <template x-if="!presetNaming">
                        <button type="button" class="inline-flex items-center gap-1.5 text-[10px] font-bold text-[var(--nttu-primary)] hover:bg-cyan-50 px-2 py-1.5 rounded" @click="presetNaming = true; presetName = ''">
                            Lưu hiện tại
                        </button>
                    </template>
                </div>
            </div>
            <button type="button" class="absolute right-4 top-3 rounded-sm text-gray-400 hover:text-gray-600" @click="advancedOpen = false" aria-label="Đóng">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="space-y-6 overflow-y-auto p-6 text-sm">
                <div class="space-y-3 rounded-lg border bg-slate-50 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Khoảng thời gian</p>
                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 text-xs">
                            <input type="radio" value="issue_date" x-model="advancedFilters.date_type" class="text-[var(--nttu-primary)]">
                            Theo ngày ban hành
                        </label>
                        <label class="flex items-center gap-2 text-xs">
                            <input type="radio" value="received_date" x-model="advancedFilters.date_type" class="text-[var(--nttu-primary)]">
                            Theo ngày nhập
                        </label>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-xs font-medium text-gray-700">Từ ngày</label>
                            <input type="date" x-model="advancedFilters.start_date" class="nttu-form-control">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-medium text-gray-700">Đến ngày</label>
                            <input type="date" x-model="advancedFilters.end_date" class="nttu-form-control">
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-gray-700">Loại văn bản</label>
                        <select x-model="advancedFilters.doc_type" class="nttu-form-control">
                            <option value="">Tất cả loại</option>
                            @foreach ($advancedFilterOptions['docTypes'] ?? [] as $opt)
                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-gray-700">Tiêu đề/Trích yếu</label>
                        <input type="text" x-model="advancedFilters.title" class="nttu-form-control" placeholder="Tìm trong tiêu đề...">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-gray-700">Đơn vị xử lý chính</label>
                        <select x-model="advancedFilters.department" class="nttu-form-control">
                            <option value="">Tất cả đơn vị</option>
                            @foreach ($advancedFilterOptions['departments'] ?? [] as $opt)
                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-gray-700">Nhân sự phụ trách</label>
                        <select x-model="advancedFilters.assignee" class="nttu-form-control">
                            <option value="">Tất cả nhân sự</option>
                            @foreach ($advancedFilterOptions['employees'] ?? [] as $opt)
                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-gray-700">Cơ quan ban hành</label>
                        <input type="text" x-model="advancedFilters.issuing_body" class="nttu-form-control" placeholder="Tên cơ quan...">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-gray-700">Người ký văn bản</label>
                        <input type="text" x-model="advancedFilters.signer" class="nttu-form-control" placeholder="Họ tên người ký...">
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t bg-slate-50/80 px-6 py-4">
                <button type="button" @click="resetAdvancedFilters()" class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                    Xóa tất cả bộ lọc
                </button>
                <button type="button" @click="applyAdvancedFilters()" class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white">
                    Áp dụng bộ lọc
                </button>
            </div>
        </div>
        @elseif ($advancedFilterMode === 'announcements')
        <div class="relative z-10 flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl" @click.stop>
            <div class="flex items-center justify-between border-b bg-slate-50/80 px-4 py-3 pr-12">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <h3 class="text-lg font-bold text-gray-900" x-text="text('Bộ lọc nâng cao')"></h3>
                </div>
            </div>
            <button type="button" class="absolute right-4 top-3 rounded-sm text-gray-400 hover:text-gray-600" @click="advancedOpen = false" aria-label="Đóng">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="grid grid-cols-1 gap-6 overflow-y-auto p-6 text-sm md:grid-cols-2">
                <div class="space-y-2">
                    <label class="flex items-center gap-2 font-medium text-gray-700">
                        <svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span x-text="text('Từ ngày')"></span>
                    </label>
                    <input type="date" x-model="advancedFilters.start_date" class="nttu-form-control">
                </div>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 font-medium text-gray-700">
                        <svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span x-text="text('Đến ngày')"></span>
                    </label>
                    <input type="date" x-model="advancedFilters.end_date" class="nttu-form-control">
                </div>
                <div class="space-y-2 md:col-span-2">
                    <label class="flex items-center gap-2 font-medium text-gray-700">
                        <svg class="h-4 w-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        <span x-text="text('Tiêu đề')"></span>
                    </label>
                    <input type="text" x-model="advancedFilters.title" class="nttu-form-control" placeholder="Tìm trong tiêu đề...">
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t bg-slate-50/80 px-6 py-4">
                <button type="button" @click="resetAdvancedFilters()" class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span x-text="text('Xóa tất cả')"></span>
                </button>
                <button type="button" @click="applyAdvancedFilters()" class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-text="text('Áp dụng bộ lọc')"></span>
                </button>
            </div>
        </div>
        @else
        <div class="relative z-10 flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl" @click.stop>
            <div class="flex items-center justify-between border-b bg-slate-50/80 px-4 py-3 pr-12">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 8h12M9 12h6M11 16h2"/></svg>
                    <h3 class="text-lg font-bold text-gray-900" x-text="text('Bộ lọc nâng cao')"></h3>
                </div>
            </div>
            <button type="button"
                    class="absolute right-4 top-3 rounded-sm text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300"
                    @click="advancedOpen = false"
                    aria-label="Đóng">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="grid grid-cols-1 gap-4 overflow-y-auto p-6 text-sm md:grid-cols-2">
                @foreach ($filterFieldList as $field)
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 font-medium text-gray-700">
                            <x-form-field-icon :name="$field['icon'] ?? 'tag'" :tone="$field['tone'] ?? 'blue'" class="h-4 w-4" />
                            <span>{{ $field['label'] }}</span>
                        </label>
                        @if (($field['type'] ?? 'text') === 'select')
                            <select class="nttu-form-control"
                                    :value="filters['{{ $field['key'] }}'] || ''"
                                    @change="setFilter('{{ $field['key'] }}', $event.target.value)">
                                @foreach ($field['options'] ?? [] as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" class="nttu-form-control"
                                   :value="filters['{{ $field['key'] }}'] || ''"
                                   placeholder="Lọc {{ $field['label'] }}..."
                                   @input="setFilter('{{ $field['key'] }}', $event.target.value)">
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="flex justify-end gap-2 border-t bg-slate-50/80 px-6 py-4">
                <button type="button" class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm text-red-600 hover:bg-red-50" @click="clearFilters()">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Xóa tất cả
                </button>
                <button type="button" class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white" @click="advancedOpen = false">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Áp dụng bộ lọc
                </button>
            </div>
        </div>
        @endif
    </div>

    {{-- Delete confirm --}}
    <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="deleteOpen = false">
        <div class="relative w-full max-w-sm rounded-xl bg-white p-5 shadow-2xl">
            <button type="button"
                    class="absolute right-4 top-4 rounded-sm text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300"
                    @click="deleteOpen = false"
                    aria-label="Đóng">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <h3 class="pr-8 font-semibold text-gray-900" x-text="text('Xác nhận xóa?')"></h3>
            <p class="mt-2 text-sm text-gray-500" x-text="text('Hành động này không thể hoàn tác.')"></p>
            <div class="mt-4 flex justify-end gap-2">
                <x-nttu-button type="button" action="cancel" @click="deleteOpen = false">Hủy</x-nttu-button>
                <x-nttu-button type="button" action="delete" @click="deleteItem()">Xóa</x-nttu-button>
            </div>
        </div>
    </div>

    {{-- Import preview --}}
    <div x-show="importOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="importOpen = false">
        <div class="relative max-h-[90vh] w-full max-w-4xl overflow-hidden rounded-xl bg-white shadow-2xl">
            <div class="border-b px-4 py-3 pr-12 font-bold" x-text="text('Xem trước Import')"></div>
            <button type="button"
                    class="absolute right-4 top-3 rounded-sm text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300"
                    @click="importOpen = false"
                    aria-label="Đóng">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="max-h-[60vh] overflow-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-3 py-2 text-center">STT</th>
                        @foreach ($importColumnList as $col)
                            <th class="px-3 py-2">{{ $col['label'] }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                        <template x-for="(row, i) in importPreview" :key="i">
                            <tr class="border-t">
                                <td class="px-3 py-2 text-center" x-text="i + 1"></td>
                                @foreach ($importColumnList as $col)
                                    <td class="px-3 py-2 {{ in_array($col['key'], ['name', 'code'], true) ? 'font-medium' : '' }}" x-text="row['{{ $col['key'] }}']"></td>
                                @endforeach
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div x-show="importProgress" x-cloak class="border-t px-4 py-3 text-sm text-slate-600">
                <p x-text="importProgressMessage()"></p>
                <div class="mt-2 h-2 overflow-hidden rounded bg-slate-100">
                    <div class="h-full bg-[var(--nttu-primary)] transition-all"
                        :style="`width: ${importProgress?.total ? Math.round((importProgress.processed / importProgress.total) * 100) : 0}%`"></div>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t px-4 py-3">
                <x-nttu-button type="button" action="cancel" @click="importOpen = false">Hủy bỏ</x-nttu-button>
                <button type="button" class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white disabled:opacity-50" :disabled="importing" @click="processImport()">
                    <x-form-field-icon name="import" tone="blue" class="h-4 w-4 text-white" />
                    <span x-text="importingLabel()"></span>
                </button>
            </div>
        </div>
    </div>
</div>
