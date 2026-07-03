@props([
    'moduleCategories' => [],
    'actions' => [],
    'roles' => [],
])

@php $pagePerms = $nttuPage('/settings/permissions'); @endphp

<div
    x-data="permissionSettingsPage({
        moduleCategories: @js($moduleCategories),
        permissionAliases: @js(config('nttu.permission_aliases', [])),
        actionLabels: @js($actions),
        roles: @js($roles),
        routes: {
            storeUrl: @js(route('permissions.store')),
            updateUrl: @js(route('permissions.update', ['role' => '__ID__'])),
            destroyUrl: @js(route('permissions.destroy', ['role' => '__ID__'])),
            defaultsUrl: @js(route('permissions.defaults', ['role' => '__ID__'])),
        },
        canAdd: @js($pagePerms['add']),
        canEdit: @js($pagePerms['edit']),
        canDelete: @js($pagePerms['delete']),
    })"
    class="space-y-4"
>
    <div x-show="toast" x-cloak
        class="fixed top-4 right-4 z-[70] max-w-md rounded-lg px-4 py-3 text-sm text-white shadow-lg"
        :class="toast?.type === 'error' ? 'bg-red-600' : 'bg-green-600'"
        x-text="toast?.message"></div>

    <div class="nttu-card overflow-hidden">
        {{-- Card header --}}
        <div class="flex items-center justify-between border-b px-4 py-3">
            <h2 class="flex items-center gap-2 text-xl font-semibold text-gray-900">
                <x-form-field-icon name="shield" tone="orange" class="h-6 w-6" />
                <span>Danh sách Vai trò</span>
            </h2>
            <button type="button" x-show="canAdd" x-cloak @click="openDialog('add')" class="inline-flex h-9 w-9 items-center justify-center rounded-md text-[var(--nttu-primary)] hover:bg-blue-50" title="Thêm mới">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </button>
        </div>

        <div class="overflow-x-auto overflow-y-visible">
            <table class="nttu-table nttu-catalog-table nttu-permissions-table w-full min-w-full">
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
                                                    Xoá sắp xếp
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
                            <button type="button" data-float-trigger @click="settingsOpen = !settingsOpen" class="nttu-catalog-cog-btn" title="Cài đặt hiển thị">
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
                                    filters-active="hasActiveFilters"
                                    clear-action="clearFilters()"
                                />
                            </td>
                        </tr>
                    </template>
                    <template x-for="(item, idx) in currentItems" :key="item.id">
                        <tr class="group" :class="canEdit ? 'cursor-pointer' : ''" @click="canEdit && openDialog('edit', item)">
                            <td class="catalog-td-index align-middle font-medium" x-text="startIndex + idx + 1"></td>
                            <template x-for="key in visibleColumnKeys" :key="key">
                                <td class="border-r align-middle py-3" :data-col="key">
                                    <template x-if="key === 'name'">
                                        <div class="flex items-center gap-2 font-bold text-[var(--nttu-primary)]">
                                            <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                            <span x-text="item.name ?? ''"></span>
                                        </div>
                                    </template>
                                    <template x-if="key === 'note'">
                                        <span class="text-gray-600" x-text="item.note ?? ''"></span>
                                    </template>
                                </td>
                            </template>
                            <td class="sticky-action catalog-td-settings text-center align-middle p-0" @click.stop>
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button" x-show="canEdit" x-cloak @click.stop="openDialog('edit', item)" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-blue-600 hover:bg-blue-100" title="Chỉnh sửa / Phân quyền">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button type="button" x-show="canDelete" x-cloak @click.stop="confirmDelete(item)" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-red-600 hover:bg-red-100" title="Xóa">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Footer pagination --}}
        <div class="flex flex-col gap-4 border-t px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-500">Tổng cộng <span x-text="sortedItems.length"></span> vai trò</p>
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

    {{-- Dialog phân quyền --}}
    <div x-show="dialogOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" @keydown.escape.window="closeDialog()">
        <div class="absolute inset-0 bg-black/50" @click="closeDialog()"></div>
        <div class="relative flex max-h-[90vh] w-full max-w-6xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl" @click.stop>
            <button type="button"
                class="absolute right-4 top-4 z-20 rounded-sm text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300"
                @click="closeDialog()"
                aria-label="Đóng">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="border-b px-6 pb-2 pt-6 pr-14">
                <h3 class="flex items-center gap-2 text-xl font-semibold text-gray-900">
                    <svg class="h-6 w-6 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span x-text="dialogMode === 'edit' ? ('Cập nhật phân quyền: ' + (form.name || '')) : 'Thêm mới Vai trò'"></span>
                </h3>
            </div>

            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <div x-show="dialogMode === 'add'" x-cloak class="grid grid-cols-1 gap-6 rounded-lg border bg-slate-50/60 p-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <x-form-label class="font-bold text-[var(--nttu-primary)]">Tên vai trò *</x-form-label>
                        <input type="text" x-model="form.name" class="nttu-form-control font-semibold" placeholder="Nhập tên vai trò (VD: Giảng viên, Quản trị viên...)">
                    </div>
                    <div class="space-y-2">
                        <x-form-label class="font-bold text-gray-500">Ghi chú</x-form-label>
                        <input type="text" x-model="form.note" class="nttu-form-control" placeholder="Nhập mô tả cho vai trò này">
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="border-l-4 border-[var(--nttu-primary)] pl-3 text-lg font-bold">Ma trận phân quyền</h4>
                        <button type="button" @click="toggleAllPermissions()" class="inline-flex items-center gap-2 rounded-md border bg-white px-3 py-1.5 text-sm hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Chọn / Bỏ chọn tất cả
                        </button>
                    </div>

                    <div class="overflow-x-auto rounded-md border">
                        <table class="nttu-perm-matrix min-w-full text-sm">
                            <thead class="sticky top-0 z-10 bg-slate-100">
                                <tr>
                                    <th class="border-r border-slate-300 bg-slate-200 px-3 py-2 text-left font-bold text-black">
                                        <div class="flex items-center gap-2">
                                            <svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="permissionIconPaths.package"/></svg>
                                            <span>Chức năng (Module)</span>
                                        </div>
                                    </th>
                                    @foreach (['access', 'view', 'add', 'edit', 'delete', 'import', 'export'] as $permKey)
                                    <th class="w-24 border-r border-slate-300 px-2 py-2 text-center font-bold text-black">
                                        <div class="flex flex-col items-center gap-2">
                                            <div class="flex items-center gap-1 text-[10px] uppercase tracking-tighter text-slate-500">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="permissionIcon('{{ $permKey }}')"/></svg>
                                                <span>{{ $actions[$permKey] ?? ucfirst($permKey) }}</span>
                                            </div>
                                            <input type="checkbox" class="nttu-perm-checkbox nttu-perm-checkbox--{{ $permKey === 'access' ? 'blue' : ($permKey === 'delete' ? 'red' : ($permKey === 'view' ? 'slate' : 'green')) }}" @change="toggleColumnPermissions('{{ $permKey }}', $event.target.checked)" :checked="isColumnChecked('{{ $permKey }}')">
                                        </div>
                                    </th>
                                    @endforeach
                                    <th class="w-24 px-2 py-2 text-center font-bold text-black">
                                        <div class="flex flex-col items-center gap-2">
                                            <div class="flex items-center gap-1 text-[10px] uppercase tracking-tighter text-slate-500">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="permissionIconPaths['check-square']"/></svg>
                                                <span>Tất cả</span>
                                            </div>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($moduleCategories as $category)
                                <tr class="group cursor-pointer bg-slate-100/50 transition-colors hover:bg-slate-200/50" @click="toggleGroup(@js($category['title']))">
                                    <td colspan="9" class="bg-blue-50/50 px-4 py-2.5">
                                        <div class="flex items-center gap-2">
                                            <svg class="h-4 w-4 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="isGroupExpanded(@js($category['title'])) ? 'M19 9l-7 7-7-7' : 'M9 5l7 7-7 7'"/></svg>
                                            <span class="text-sm font-bold uppercase tracking-wider text-[var(--nttu-primary)]">{{ $category['title'] }}</span>
                                            <span class="ml-2 rounded bg-slate-200 px-1.5 py-0.5 text-[10px] text-gray-600 opacity-70 group-hover:opacity-100">{{ count($category['modules'] ?? []) }} chức năng</span>
                                        </div>
                                    </td>
                                </tr>
                                @foreach ($category['modules'] ?? [] as $module)
                                <tr class="transition-colors hover:bg-blue-50/30" x-show="isGroupExpanded(@js($category['title']))" x-cloak>
                                    <td class="border-r border-slate-200 py-2 pl-8 pr-3 text-sm font-medium">
                                        <div class="flex items-center gap-3">
                                            <span class="rounded-md bg-slate-100 p-1.5 text-slate-600">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="moduleIcon(@js($module['icon']))"/></svg>
                                            </span>
                                            <span>{{ $module['label'] }}</span>
                                        </div>
                                    </td>
                                    @foreach (['access', 'view', 'add', 'edit', 'delete', 'import', 'export'] as $permKey)
                                    <td class="border-r border-slate-200 text-center">
                                        <input type="checkbox" class="nttu-perm-checkbox nttu-perm-checkbox--{{ $permKey === 'access' ? 'blue' : ($permKey === 'delete' ? 'red' : 'green') }}"
                                            :checked="!!form.permissions?.[@js($module['id'])]?.[@js($permKey)]"
                                            @click.stop
                                            @change="togglePermission(@js($module['id']), @js($permKey), $event.target.checked)">
                                    </td>
                                    @endforeach
                                    <td class="border-l border-slate-200 bg-slate-50/30 text-center">
                                        <input type="checkbox" class="nttu-perm-checkbox nttu-perm-checkbox--slate"
                                            :checked="isRowChecked(@js($module['id']))"
                                            @click.stop
                                            @change="toggleRowPermissions(@js($module['id']), $event.target.checked)">
                                    </td>
                                </tr>
                                @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t bg-slate-50 p-4">
                <button type="button" @click="undoForm()" :disabled="!isChanged" class="inline-flex items-center gap-2 rounded-md border bg-white px-4 py-2 text-sm text-gray-600 hover:bg-white disabled:cursor-not-allowed disabled:opacity-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    Hoàn tác
                </button>
                <button type="button" x-show="(dialogMode === 'add' && canAdd) || (dialogMode === 'edit' && canEdit)" x-cloak @click="saveRole()" :disabled="saving || (dialogMode === 'add' && !form.name?.trim()) || !isChanged" class="inline-flex min-w-32 items-center justify-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white disabled:opacity-50">
                    <svg x-show="saving" class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <svg x-show="!saving" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 002.83-2M15 11h3m-3 4h2"/></svg>
                    Lưu lại
                </button>
            </div>
        </div>
    </div>

    {{-- Xóa vai trò --}}
    <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="deleteOpen = false"></div>
        <div class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-gray-900">Xác nhận xóa vai trò?</h3>
            <p class="mt-2 text-sm text-gray-600">
                Hành động này không thể hoàn tác. Bạn có chắc chắn muốn xóa vai trò
                <span class="font-bold text-red-600">"<span x-text="selectedRole?.name"></span>"</span>?
            </p>
            <div class="mt-6 flex justify-end gap-2">
                <x-nttu-button type="button" action="cancel" @click="deleteOpen = false">Hủy</x-nttu-button>
                <x-nttu-button type="button" action="delete" x-bind:disabled="deleting" @click="deleteRole()">Xóa</x-nttu-button>
            </div>
        </div>
    </div>
</div>
