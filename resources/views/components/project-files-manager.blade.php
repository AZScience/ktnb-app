@props([])

@php
    $pagePerms = $nttuPage('/settings/project-files');
    $projectRootPath = realpath(config('nttu.project_files.root', base_path())) ?: base_path();
    $projectRootLabel = basename($projectRootPath);
@endphp

<div
    x-data="projectFilesPage({
        routes: {
            list: @js(route('project-files.list')),
            show: @js(route('project-files.show')),
            store: @js(route('project-files.store')),
            batchUpload: @js(route('project-files.batch-upload')),
            update: @js(route('project-files.update')),
            rename: @js(route('project-files.rename')),
            move: @js(route('project-files.move')),
            extract: @js(route('project-files.extract')),
            compress: @js(route('project-files.compress')),
            batchDownload: @js(route('project-files.batch-download')),
            destroy: @js(route('project-files.destroy')),
            download: @js(route('project-files.download')),
        },
        canAdd: @js($pagePerms['add']),
        canEdit: @js($pagePerms['edit']),
        canDelete: @js($pagePerms['delete']),
        canExport: @js($pagePerms['export']),
    })"
    class="mx-auto max-w-6xl space-y-6"
>
    <div x-show="toast" x-cloak
        class="fixed top-4 right-4 z-[70] max-w-md rounded-lg px-4 py-3 text-sm text-white shadow-lg flex items-center justify-between gap-3"
        :class="toast?.type === 'success' ? 'bg-green-600' : 'bg-red-600'">
        <span x-text="toast?.message"></span>
        <button type="button" @click="toast = null" class="shrink-0 rounded-full p-1 text-white/70 hover:bg-black/10 hover:text-white transition-colors" title="Đóng">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <div class="space-y-3">
        <div class="flex items-start gap-3">
            <div class="rounded-lg bg-violet-50 p-2.5">
                <svg class="h-7 w-7 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h5l2 2h11v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 md:text-3xl">Quản lý file mã nguồn (project)</h1>
                <p class="mt-2 text-sm text-gray-600 md:text-base">
                    Duyệt, tải lên, sửa và tải xuống file trong thư mục dự án
                    <code class="rounded bg-slate-100 px-1 text-xs">{{ $projectRootLabel }}</code>.
                </p>
            </div>
        </div>

        <div class="rounded-lg border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-950">
            <p class="font-medium">Phạm vi: quản lý <strong>file mã nguồn / tài nguyên project</strong> (PHP, JS, Blade, cấu hình…).</p>
            <p class="mt-1 text-violet-900/90">Không phải sao lưu CSDL — dùng tab <a href="{{ route('backup.index', ['tab' => 'database']) }}" class="font-semibold underline hover:text-violet-700">Cơ sở dữ liệu</a> hoặc <a href="{{ route('backup.index', ['tab' => 'project']) }}" class="font-semibold underline hover:text-violet-700">Mã nguồn project</a> trên trang <a href="{{ route('backup.index') }}" class="font-semibold underline hover:text-violet-700">Sao lưu và Phục hồi</a>. Một số thư mục hệ thống (<code class="text-xs">vendor</code>, <code class="text-xs">node_modules</code>, cache…) bị ẩn.</p>
        </div>
    </div>

    <div class="nttu-card overflow-hidden">
        <div class="flex flex-col gap-3 border-b bg-slate-50/80 px-4 py-4 lg:flex-row lg:items-center lg:justify-between">
            <nav class="flex min-w-0 flex-wrap items-center gap-1 text-sm text-gray-600">
                <template x-for="(crumb, idx) in breadcrumbs" :key="crumb.path + '-' + idx">
                    <span class="inline-flex min-w-0 items-center gap-1">
                        <button type="button"
                            class="inline-flex max-w-full items-center gap-1.5 truncate rounded px-1 hover:text-[var(--nttu-primary)] hover:underline"
                            :class="idx === breadcrumbs.length - 1 ? 'font-semibold text-gray-900' : ''"
                            :title="crumb.path === '' ? 'Thư mục gốc project' : crumb.label"
                            @click="goToPath(crumb.path)">
                            <svg x-show="crumb.path === ''" class="h-4 w-4 shrink-0 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span x-show="crumb.path === ''">Gốc project</span>
                            <span x-show="crumb.path !== ''" class="truncate" x-text="crumb.label"></span>
                        </button>
                        <span x-show="idx < breadcrumbs.length - 1" class="text-gray-400">/</span>
                    </span>
                </template>
            </nav>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="openParent()"
                    class="inline-flex h-9 items-center gap-1.5 rounded-md border bg-white px-3 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="!currentPath" title="Lên thư mục">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v8"/></svg>
                    <span class="hidden sm:inline">Lên</span>
                </button>
                <button type="button" x-show="canAdd" x-cloak @click="openCreate('folder')"
                    class="inline-flex h-9 items-center gap-1.5 rounded-md border border-indigo-200 bg-indigo-50 px-3 text-sm font-medium text-indigo-700 hover:bg-indigo-100">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-7 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                    <span class="hidden sm:inline">Thư mục</span>
                </button>
                <button type="button" x-show="canAdd" x-cloak @click="openCreate('file')"
                    class="inline-flex h-9 items-center gap-1.5 rounded-md border border-cyan-200 bg-cyan-50 px-3 text-sm font-medium text-cyan-700 hover:bg-cyan-100">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-5 2H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="hidden sm:inline">File mới</span>
                </button>
                <label x-show="canAdd" x-cloak class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-md border border-blue-200 bg-blue-50 px-3 text-sm font-medium text-blue-700 hover:bg-blue-100">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <span class="hidden sm:inline">Tải file</span>
                    <input type="file" class="hidden" multiple accept="*/*" @change="uploadSelectedFile($event)">
                </label>
                <label x-show="canAdd" x-cloak class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-md border border-violet-200 bg-violet-50 px-3 text-sm font-medium text-violet-700 hover:bg-violet-100">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v6m0 0l-2-2m2 2l2-2"/></svg>
                    <span class="hidden sm:inline">Tải thư mục</span>
                    <input type="file" class="hidden" webkitdirectory directory multiple @change="uploadSelectedFolder($event)">
                </label>
            </div>
        </div>

        <div x-show="selectedCount > 0" x-cloak class="flex flex-wrap items-center gap-2 border-b bg-amber-50 px-4 py-2.5 text-sm">
            <span class="font-medium text-amber-900">Đã chọn <span x-text="selectedCount"></span> mục</span>
            <button type="button" x-show="canExport" x-cloak @click="downloadSelected()" class="rounded-md border border-green-200 bg-green-50 px-3 py-1.5 text-green-800 hover:bg-green-100" :disabled="saving">Tải xuống</button>
            <button type="button" x-show="canEdit" x-cloak @click="openBulkCompress()" class="rounded-md border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-indigo-800 hover:bg-indigo-100" :disabled="saving">Nén (.zip)</button>
            <button type="button" x-show="canEdit && selectedArchivePaths.length > 0" x-cloak @click="openBulkExtract()" class="rounded-md border border-teal-200 bg-teal-50 px-3 py-1.5 text-teal-800 hover:bg-teal-100" :disabled="saving">
                Giải nén (<span x-text="selectedArchivePaths.length"></span>)
            </button>
            <button type="button" x-show="canDelete" x-cloak @click="openBulkDelete()" class="rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-red-700 hover:bg-red-100" :disabled="saving">Xóa</button>
            <button type="button" @click="clearSelection()" class="rounded-md px-2 py-1.5 text-gray-600 hover:bg-white/80">Bỏ chọn</button>
        </div>

        <div class="border-b px-4 py-3"
             @drop.prevent="onDrop($event)"
             @dragover.prevent="onDragOver($event)"
             @dragleave.prevent="onDragLeave($event)"
             :class="dragOver ? 'bg-blue-50' : ''">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative min-w-0 flex-1 sm:max-w-xs">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="search" class="nttu-form-control w-full pl-9 text-sm" placeholder="Lọc theo tên..."
                           :value="filters.name || ''" @input="setFilter('name', $event.target.value)">
                </div>
                <p class="text-xs text-gray-500 sm:text-right">
                    Kéo thả file/thư mục vào vùng này để upload
                    <span x-show="saving" x-cloak class="ml-1 font-medium text-blue-600">· Đang upload...</span>
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr class="border-b bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                        <th class="w-10 px-3 py-3">
                            <input type="checkbox" class="rounded border-gray-300"
                                :checked="allVisibleSelected"
                                :indeterminate="someVisibleSelected && !allVisibleSelected"
                                @change="toggleSelectAllVisible()"
                                title="Chọn tất cả">
                        </th>
                        <th class="w-10 px-2 py-3 text-center">#</th>
                        <th class="cursor-pointer px-3 py-3 hover:bg-slate-100" @click="requestSort('name')">
                            <span class="inline-flex items-center gap-1">Tên
                                <span x-show="sortDirection('name') === 'asc'" class="text-blue-600">↑</span>
                                <span x-show="sortDirection('name') === 'desc'" class="text-blue-600">↓</span>
                            </span>
                        </th>
                        <th class="hidden cursor-pointer px-3 py-3 hover:bg-slate-100 md:table-cell" @click="requestSort('type')">Loại</th>
                        <th class="hidden cursor-pointer px-3 py-3 hover:bg-slate-100 sm:table-cell" @click="requestSort('size')">Dung lượng</th>
                        <th class="hidden cursor-pointer px-3 py-3 hover:bg-slate-100 lg:table-cell" @click="requestSort('modified_at')">Sửa lần cuối</th>
                        <th class="w-36 px-3 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr x-show="loading" x-cloak>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-500">Đang tải danh sách file...</td>
                    </tr>
                    <tr x-show="!loading && sortedItems.length === 0" x-cloak>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                            <svg class="mx-auto mb-2 h-8 w-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h5l2 2h11v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                            Thư mục trống hoặc không có kết quả lọc.
                        </td>
                    </tr>
                    <template x-for="(item, idx) in sortedItems" :key="item.path">
                        <tr class="group hover:bg-slate-50/80"
                            @dblclick="item.type === 'directory' ? openDirectory(item) : (item.editable ? openEditor(item) : downloadItem(item))">
                            <td class="px-3 py-2.5 align-middle" @click.stop>
                                <input type="checkbox" class="rounded border-gray-300" :checked="isRowSelected(item.path)" @change="toggleRowSelect(item.path)">
                            </td>
                            <td class="px-2 py-2.5 text-center align-middle text-gray-500" x-text="idx + 1"></td>
                            <td class="px-3 py-2.5 align-middle">
                                <button type="button" class="flex max-w-full items-center gap-2 text-left font-medium text-[var(--nttu-primary)] hover:underline"
                                    @click="item.type === 'directory' ? openDirectory(item) : (item.editable && canEdit ? openEditor(item) : downloadItem(item))">
                                    <svg x-show="item.type === 'directory'" class="h-4 w-4 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h5l2 2h11v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                                    <svg x-show="item.type !== 'directory'" class="h-4 w-4 shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span class="truncate font-mono text-sm" :title="item.name" x-text="item.name"></span>
                                </button>
                            </td>
                            <td class="hidden px-3 py-2.5 align-middle text-gray-600 md:table-cell" x-text="typeLabel(item)"></td>
                            <td class="hidden px-3 py-2.5 align-middle text-gray-600 sm:table-cell" x-text="item.size_label"></td>
                            <td class="hidden px-3 py-2.5 align-middle text-gray-600 lg:table-cell" x-text="item.modified_at"></td>
                            <td class="px-3 py-2.5 align-middle text-right" @click.stop>
                                <div class="inline-flex items-center justify-end gap-1">
                                    <button type="button" x-show="item.type === 'directory'" @click="openDirectory(item)"
                                        class="rounded px-2 py-1 text-xs font-medium text-amber-700 hover:bg-amber-50" title="Mở thư mục">Mở</button>
                                    <button type="button" x-show="canExport && item.type !== 'directory'" x-cloak @click="downloadItem(item)"
                                        class="rounded px-2 py-1 text-xs font-medium text-green-700 hover:bg-green-50" title="Tải xuống">Tải</button>
                                    <button type="button" x-show="item.editable && canEdit" x-cloak @click="openEditor(item)"
                                        class="rounded px-2 py-1 text-xs font-medium text-blue-700 hover:bg-blue-50" title="Sửa file">Sửa</button>
                                    <button type="button" data-float-trigger title="Thêm thao tác" @click.stop="rowMenuOpen = rowMenuOpen === item.path ? null : item.path"
                                        class="nttu-row-action-btn !h-8 !w-8">
                                        @include('components.partials.row-action-menu-icon')
                                    </button>
                                    <div x-cloak x-float="rowMenuOpen === item.path"
                                        class="nttu-row-action-menu nttu-floating-panel w-44 rounded-md border bg-white py-1 text-left text-sm text-gray-800 shadow-lg">
                                        <button type="button" x-show="canEdit" x-cloak @click="openRename(item)" class="flex w-full items-center gap-2 px-3 py-2 hover:bg-slate-50">Đổi tên</button>
                                        <button type="button" x-show="canEdit" x-cloak @click="openMove(item)" class="flex w-full items-center gap-2 px-3 py-2 hover:bg-slate-50">Di chuyển</button>
                                        <button type="button" x-show="item.is_archive && canEdit" x-cloak @click="openExtract(item)" class="flex w-full items-center gap-2 px-3 py-2 hover:bg-slate-50">Giải nén</button>
                                        <button type="button" x-show="canEdit" x-cloak @click="openCompressItem(item)" class="flex w-full items-center gap-2 px-3 py-2 hover:bg-slate-50">Nén (.zip)</button>
                                        <div class="my-1 border-t" x-show="canDelete"></div>
                                        <button type="button" x-show="canDelete" x-cloak @click="openDelete(item)" class="flex w-full items-center gap-2 px-3 py-2 text-red-600 hover:bg-red-50">Xóa</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="border-t bg-slate-50/80 px-4 py-3 text-sm text-gray-500">
            Tổng cộng <span x-text="sortedItems.length"></span> mục
            <span x-show="selectedCount > 0" x-cloak> · <span x-text="selectedCount"></span> đã chọn</span>
        </div>
    </div>

    <div x-show="editorOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" @keydown.escape.window="closeEditor()">
        <div class="absolute inset-0 bg-black/50" @click="closeEditor()"></div>
        <div class="relative flex h-[90vh] w-full max-w-6xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl" @click.stop>
            <div class="flex items-center justify-between border-b px-4 py-3">
                <div>
                    <h3 class="font-semibold text-gray-900" x-text="editorFile?.name || 'Sửa file'"></h3>
                    <p class="font-mono text-xs text-gray-500" x-text="editorFile?.path"></p>
                </div>
                <button type="button" @click="closeEditor()" class="rounded p-2 text-gray-500 hover:bg-gray-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <textarea x-model="editorContent" class="min-h-0 flex-1 resize-none border-0 p-4 font-mono text-sm focus:ring-0"></textarea>
            <div class="flex items-center justify-between border-t bg-slate-50 px-4 py-3">
                <span class="text-xs text-gray-500" x-text="editorChanged ? 'Có thay đổi chưa lưu' : 'Đã đồng bộ'"></span>
                <div class="flex gap-2">
                    <x-nttu-button type="button" action="undo" @click="revertEditor()">Hoàn tác</x-nttu-button>
                    <button type="button" @click="saveEditor()" :disabled="saving || !editorChanged"
                        class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white disabled:opacity-50">
                        <x-form-field-icon name="save" tone="green" class="h-4 w-4 text-white" />
                        <span x-text="saving ? 'Đang lưu...' : 'Lưu file'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="createOpen" x-cloak class="fixed inset-0 z-[55] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="createOpen = false"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-5 shadow-2xl" @click.stop>
            <h3 class="text-lg font-semibold" x-text="createType === 'folder' ? 'Tạo thư mục mới' : 'Tạo file mới'"></h3>
            <input type="text" x-model="createName" class="mt-4 w-full rounded-md border-gray-300 text-sm" :placeholder="createType === 'folder' ? 'ten-thu-muc' : 'ten-file.php'">
            <div class="mt-4 flex justify-end gap-2">
                <x-nttu-button type="button" action="cancel" @click="createOpen = false">Hủy</x-nttu-button>
                <x-nttu-button type="button" action="create" x-bind:disabled="saving" @click="submitCreate()">Tạo</x-nttu-button>
            </div>
        </div>
    </div>

    <div x-show="renameOpen" x-cloak class="fixed inset-0 z-[55] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="renameOpen = false"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-5 shadow-2xl" @click.stop>
            <h3 class="text-lg font-semibold">Đổi tên</h3>
            <input type="text" x-model="renameValue" class="mt-4 w-full rounded-md border-gray-300 text-sm">
            <div class="mt-4 flex justify-end gap-2">
                <x-nttu-button type="button" action="cancel" @click="renameOpen = false">Hủy</x-nttu-button>
                <x-nttu-button type="button" action="save" x-bind:disabled="saving" @click="submitRename()">Lưu</x-nttu-button>
            </div>
        </div>
    </div>

    <div x-show="moveOpen" x-cloak class="fixed inset-0 z-[55] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="moveOpen = false"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-5 shadow-2xl" @click.stop>
            <h3 class="text-lg font-semibold">Di chuyển</h3>
            <p class="mt-1 text-sm text-gray-500">Di chuyển <strong x-text="selected?.name"></strong> tới thư mục đích.</p>
            <label class="mt-4 block text-sm font-medium text-gray-700">Thư mục đích (đường dẫn tương đối)</label>
            <input type="text" x-model="moveDestination" class="mt-1 w-full rounded-md border-gray-300 font-mono text-sm" placeholder="vd: resources/views">
            <p class="mt-2 text-xs text-gray-500">Để trống hoặc nhập <code class="rounded bg-gray-100 px-1">.</code> để chuyển về thư mục gốc project.</p>
            <div class="mt-4 flex justify-end gap-2">
                <x-nttu-button type="button" action="cancel" @click="moveOpen = false">Hủy</x-nttu-button>
                <x-nttu-button type="button" action="save" x-bind:disabled="saving" @click="submitMove()">Di chuyển</x-nttu-button>
            </div>
        </div>
    </div>

    <div x-show="compressOpen" x-cloak class="fixed inset-0 z-[55] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="compressOpen = false"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-5 shadow-2xl" @click.stop>
            <h3 class="text-lg font-semibold">Nén thành file .zip</h3>
            <p class="mt-1 text-sm text-gray-500">Nén <strong x-text="selectedCount"></strong> mục đã chọn vào thư mục hiện tại.</p>
            <label class="mt-4 block text-sm font-medium text-gray-700">Tên file nén</label>
            <input type="text" x-model="compressName" class="mt-1 w-full rounded-md border-gray-300 font-mono text-sm" placeholder="archive.zip">
            <div class="mt-4 flex justify-end gap-2">
                <x-nttu-button type="button" action="cancel" @click="compressOpen = false">Hủy</x-nttu-button>
                <x-nttu-button type="button" action="confirm" x-bind:disabled="saving" @click="submitCompress()">Nén</x-nttu-button>
            </div>
        </div>
    </div>

    <div x-show="extractOpen" x-cloak class="fixed inset-0 z-[55] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="extractOpen = false"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-5 shadow-2xl" @click.stop>
            <h3 class="text-lg font-semibold">Giải nén</h3>
            <p class="mt-1 text-sm text-gray-500">Giải nén <strong x-text="selected?.name"></strong> (zip, tar, tar.gz, tgz).</p>
            <label class="mt-4 block text-sm font-medium text-gray-700">Thư mục đích</label>
            <input type="text" x-model="extractDestination" class="mt-1 w-full rounded-md border-gray-300 font-mono text-sm" placeholder="Thư mục sẽ được tạo nếu chưa có">
            <div class="mt-4 flex justify-end gap-2">
                <x-nttu-button type="button" action="cancel" @click="extractOpen = false">Hủy</x-nttu-button>
                <x-nttu-button type="button" action="confirm" x-bind:disabled="saving" @click="submitExtract()">Giải nén</x-nttu-button>
            </div>
        </div>
    </div>

    <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-[55] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="deleteOpen = false"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-5 shadow-2xl" @click.stop>
            <h3 class="text-lg font-semibold text-red-700">Xác nhận xóa</h3>
            <template x-if="deleteBulkMode">
                <p class="mt-2 text-sm text-gray-600">Xóa <strong x-text="selectedCount"></strong> mục đã chọn khỏi project? Thao tác không thể hoàn tác.</p>
            </template>
            <template x-if="!deleteBulkMode">
                <p class="mt-2 text-sm text-gray-600">Xóa <strong x-text="selected?.name"></strong> khỏi project? Thao tác không thể hoàn tác.</p>
            </template>
            <div class="mt-4 flex justify-end gap-2">
                <x-nttu-button type="button" action="cancel" @click="deleteOpen = false">Hủy</x-nttu-button>
                <x-nttu-button type="button" action="delete" x-bind:disabled="saving" @click="submitDelete()">Xóa</x-nttu-button>
            </div>
        </div>
    </div>
</div>
