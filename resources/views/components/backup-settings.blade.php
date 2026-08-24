@props([
    'files' => [],
    'scope' => 'database',
    'projectRootLabel' => null,
    'purgeDataTypes' => [],
])

@php
    $pagePerms = $nttuPage('/settings/backup');
    $isProject = $scope === 'project';
    $backupPath = static fn (string $routeName) => parse_url(route($routeName, ['filename' => '__FILE__']), PHP_URL_PATH);

    $copy = $isProject ? [
        'info_title' => 'Phạm vi: sao lưu / phục hồi mã nguồn project',
        'info_body' => 'Nén file PHP, JS, Blade, cấu hình… từ thư mục ' . ($projectRootLabel ?: 'project') . '. Không gồm vendor, node_modules, cache và file .env.',
        'export_title' => 'Xuất mã nguồn (Backup project)',
        'export_desc' => 'Gom file mã nguồn thành file .zip, lưu trên server và tự động tải về máy.',
        'export_btn' => 'Tạo bản sao lưu project',
        'import_title' => 'Phục hồi project từ máy tính',
        'import_desc' => 'Chọn file .zip đã sao lưu để ghi đè file mã nguồn. Không ảnh hưởng dữ liệu CSDL.',
        'import_btn' => 'Chọn file backup project (.zip)',
        'server_title' => 'Các bản sao lưu project trên server',
        'empty_hint' => 'Nhấn Tạo bản sao lưu project để tạo file đầu tiên.',
        'restore_btn' => 'Phục hồi project',
        'rename_title' => 'Đổi tên file backup project',
        'rename_hint' => 'Dạng project_dd-mm-yyyy_hh-mm-ss (có thể thêm hậu tố, ví dụ -import).',
        'restore_title' => 'Xác nhận phục hồi project',
        'restore_desc' => 'Phục hồi mã nguồn từ file',
        'restore_busy' => 'Đang phục hồi project, vui lòng đợi...',
        'restore_confirm' => 'Phục hồi project',
        'delete_desc' => 'trên server? Chỉ xóa file sao lưu, không xóa file đang chạy trong project.',
        'info_tone' => 'violet',
    ] : [
        'info_title' => 'Phạm vi: sao lưu / phục hồi dữ liệu CSDL',
        'info_body' => 'Xuất các bảng MySQL (nhân sự, lịch học, yêu cầu, đơn thư, tài sản…). Không gồm mã nguồn hay file upload.',
        'export_title' => 'Xuất dữ liệu (Backup CSDL)',
        'export_desc' => 'Gom dữ liệu từ các bảng hệ thống thành file .zip, lưu trên server và tự động tải về máy.',
        'export_btn' => 'Tạo bản sao lưu CSDL',
        'import_title' => 'Phục hồi CSDL từ máy tính',
        'import_desc' => 'Chọn file .zip đã sao lưu để nạp lại dữ liệu vào CSDL. Dữ liệu hiện tại có thể bị ghi đè.',
        'import_btn' => 'Chọn file backup CSDL (.zip)',
        'server_title' => 'Các bản sao lưu CSDL trên server',
        'empty_hint' => 'Nhấn Tạo bản sao lưu CSDL để tạo file đầu tiên.',
        'restore_btn' => 'Phục hồi CSDL',
        'rename_title' => 'Đổi tên file backup CSDL',
        'rename_hint' => 'Dạng dd-mm-yyyy_hh-mm-ss hoặc backup_dd-mm-yyyy_hh-mm-ss (có thể thêm hậu tố, ví dụ -import).',
        'restore_title' => 'Xác nhận phục hồi CSDL',
        'restore_desc' => 'Phục hồi dữ liệu từ file',
        'restore_busy' => 'Đang phục hồi CSDL, vui lòng đợi...',
        'restore_confirm' => 'Phục hồi CSDL',
        'delete_desc' => 'trên server? Chỉ xóa file sao lưu, không xóa dữ liệu đang chạy trong CSDL.',
        'info_tone' => 'amber',
    ];
@endphp

<div
    x-data="backupSettingsPage({
        scope: @js($scope),
        files: @js($files),
        routes: {
            download: @js($backupPath('backup.download')),
            rename: @js($backupPath('backup.rename')),
            restore: @js($backupPath('backup.restore')),
            destroy: @js($backupPath('backup.destroy')),
            purgeData: @js(route('backup.purge-data')),
        },
        canExport: @js($pagePerms['export']),
        canImport: @js($pagePerms['import']),
        canEdit: @js($pagePerms['edit']),
        canDelete: @js($pagePerms['delete']),
        purgeDataTypes: @js($purgeDataTypes),
    })"
    class="space-y-6"
>
    <div x-show="toast" x-cloak
        class="fixed top-4 right-4 z-[70] max-w-md rounded-lg px-4 py-3 text-sm text-white shadow-lg flex items-center justify-between gap-3"
        :class="toast?.type === 'success' ? 'bg-green-600' : 'bg-red-600'">
        <span x-text="toast?.message"></span>
        <button type="button" @click="toast = null" class="shrink-0 rounded-full p-1 text-white/70 hover:bg-black/10 hover:text-white transition-colors" title="Đóng">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <div @class([
        'rounded-lg border px-4 py-3 text-sm',
        'border-violet-200 bg-violet-50 text-violet-950' => $isProject,
        'border-amber-200 bg-amber-50 text-amber-900' => ! $isProject,
    ])>
        <p class="font-medium">{{ $copy['info_title'] }}</p>
        <p @class(['mt-1', 'text-violet-900/90' => $isProject, 'text-amber-800/90' => ! $isProject])>{{ $copy['info_body'] }}</p>
    </div>

    <div class="nttu-card overflow-hidden">
        <div class="border-b bg-slate-50/80 px-6 py-4">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                {{ $copy['export_title'] }}
            </h2>
            <p class="mt-1 text-sm text-gray-600">{{ $copy['export_desc'] }}</p>
        </div>
        <div class="space-y-6 p-6">
            @if($pagePerms['export'])
            <form method="POST" action="{{ route('backup.export') }}">
                @csrf
                <input type="hidden" name="scope" value="{{ $scope }}">
                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-green-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    {{ $copy['export_btn'] }}
                </button>
            </form>
            @else
            <p class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-gray-500">Bạn không có quyền tạo bản sao lưu.</p>
            @endif

            <div class="border-t pt-6">
                <h3 class="mb-1 flex items-center gap-2 text-sm font-semibold text-gray-900">
                    <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    {{ $copy['import_title'] }}
                </h3>
                <p class="mb-3 text-sm text-gray-600">{{ $copy['import_desc'] }}</p>
                @if($pagePerms['import'])
                <form method="POST" action="{{ route('backup.import') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="scope" value="{{ $scope }}">
                    <label class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700 hover:bg-blue-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        {{ $copy['import_btn'] }}
                        <input type="file" name="backup" accept=".zip" required class="hidden" onchange="if(this.files.length) this.form.submit()">
                    </label>
                </form>
                @else
                <p class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-gray-500">Bạn không có quyền phục hồi.</p>
                @endif
            </div>

            <div class="border-t pt-6">
                <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900">
                    <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $copy['server_title'] }}
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-normal text-gray-600" x-text="files.length"></span>
                </h3>

                <template x-if="files.length === 0">
                    <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-center text-sm text-gray-500">
                        <x-table-empty-state
                            icon="inbox"
                            message-empty="Chưa có bản sao lưu nào trên server."
                        />
                        <p class="mt-1 text-xs text-gray-500">{{ $copy['empty_hint'] }}</p>
                    </div>
                </template>

                <div class="space-y-2" x-show="files.length > 0" x-cloak>
                    <template x-for="file in sortedFiles" :key="file.name">
                        <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-slate-50/70 p-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 overflow-hidden">
                                    <svg class="h-4 w-4 shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span class="truncate font-mono text-xs font-semibold text-[var(--nttu-primary)]" :title="file.name" x-text="file.name"></span>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    <span x-text="formatSize(file.size)"></span>
                                    <span class="mx-1">·</span>
                                    <span x-text="file.date"></span>
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 sm:shrink-0">
                                <a :href="downloadUrl(file)" class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-slate-50">
                                    <svg class="h-3.5 w-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Tải về
                                </a>
                                <button type="button" x-show="canImport" x-cloak @click="openRestore(file)" :disabled="actionBusy"
                                    class="inline-flex items-center gap-1 rounded-md border border-blue-200 bg-white px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50 disabled:opacity-50">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    {{ $copy['restore_btn'] }}
                                </button>
                                <button type="button" x-show="canEdit" x-cloak @click="openRename(file)"
                                    class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-orange-700 hover:bg-orange-50">
                                    Đổi tên
                                </button>
                                <button type="button" x-show="canDelete" x-cloak @click="openDelete(file)"
                                    class="inline-flex items-center justify-center rounded-md border border-red-200 bg-white px-2.5 py-1.5 text-red-600 hover:bg-red-50" title="Xóa file backup">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    @if (! $isProject)
    <div class="nttu-card overflow-hidden border-red-200" x-show="canDelete" x-cloak>
        <div class="border-b border-red-100 bg-red-50/60 px-6 py-4">
            <h2 class="flex items-center gap-2 text-base font-semibold text-red-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Xóa dữ liệu vận hành theo khoảng ngày
            </h2>
            <p class="mt-1 text-sm text-red-800/80">Xóa trực tiếp bản ghi trong CSDL theo loại dữ liệu đã chọn (nhật ký, sinh viên, giảng viên, lịch học, yêu cầu, đơn thư…). Khác với xóa file backup.</p>
        </div>
        <div class="p-6">
            <button type="button" @click="openPurgeDialog()" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50 sm:w-auto">
                Mở công cụ xóa dữ liệu theo ngày
            </button>
        </div>
    </div>
    @endif

    <div x-show="renameOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" @keydown.escape.window="renameOpen = false">
        <div class="absolute inset-0 bg-black/50" @click="renameOpen = false"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-2xl" @click.stop>
            <button type="button" class="absolute right-4 top-4 text-gray-400 hover:text-gray-600" @click="renameOpen = false" aria-label="Đóng">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <h3 class="pr-8 text-lg font-semibold text-gray-900">{{ $copy['rename_title'] }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ $copy['rename_hint'] }}</p>
            <div class="mt-4">
                <x-form-label class="text-sm">Tên mới</x-form-label>
                <input type="text" x-model="renameValue" required class="nttu-form-control mt-1 font-mono text-sm" placeholder="{{ $isProject ? 'project_19-06-2026_05-18-24' : '19-06-2026_05-18-24' }}" @keydown.enter.prevent="submitRename()">
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <x-nttu-button type="button" action="cancel" @click="renameOpen = false">Hủy</x-nttu-button>
                <x-nttu-button type="button" action="save" x-bind:disabled="actionBusy" @click="submitRename()">Lưu tên</x-nttu-button>
            </div>
        </div>
    </div>

    <div x-show="restoreOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" @keydown.escape.window="restoreOpen = false">
        <div class="absolute inset-0 bg-black/50" @click="restoreOpen = false"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-2xl" @click.stop>
            <h3 class="flex items-center gap-2 text-lg font-semibold text-green-700">{{ $copy['restore_title'] }}</h3>
            <p class="mt-2 text-sm text-gray-600">
                {{ $copy['restore_desc'] }} <span class="font-mono font-semibold" x-text="selected?.name"></span>?
                @if ($isProject)
                    File mã nguồn hiện tại có thể bị ghi đè.
                @else
                    Dữ liệu hiện tại trong CSDL có thể bị ghi đè.
                @endif
            </p>
            <p x-show="actionBusy" x-cloak class="mt-3 flex items-center gap-2 rounded-md border border-blue-100 bg-blue-50 px-3 py-2 text-sm text-blue-800">
                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                {{ $copy['restore_busy'] }}
            </p>
            <div class="mt-6 flex justify-end gap-2">
                <x-nttu-button type="button" action="cancel" x-bind:disabled="actionBusy" @click="restoreOpen = false">Hủy</x-nttu-button>
                <x-nttu-button type="button" action="restore" x-bind:disabled="actionBusy" @click="submitRestore()">
                    <span x-show="!actionBusy">{{ $copy['restore_confirm'] }}</span>
                    <span x-show="actionBusy" x-cloak>Đang phục hồi...</span>
                </x-nttu-button>
            </div>
        </div>
    </div>

    <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" @keydown.escape.window="deleteOpen = false">
        <div class="absolute inset-0 bg-black/50" @click="deleteOpen = false"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-2xl" @click.stop>
            <h3 class="flex items-center gap-2 text-lg font-semibold text-red-600">Xác nhận xóa file backup</h3>
            <p class="mt-2 text-sm text-gray-600">
                Xóa file <span class="font-mono font-semibold" x-text="selected?.name"></span> {{ $copy['delete_desc'] }}
            </p>
            <div class="mt-6 flex justify-end gap-2">
                <x-nttu-button type="button" action="cancel" @click="deleteOpen = false">Hủy</x-nttu-button>
                <x-nttu-button type="button" action="delete" x-bind:disabled="actionBusy" @click="submitDelete()">Xóa file</x-nttu-button>
            </div>
        </div>
    </div>

    @if (! $isProject)
    <div x-show="purgeOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" @keydown.escape.window="purgeOpen = false">
        <div class="absolute inset-0 bg-black/50" @click="purgeOpen = false"></div>
        <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white p-6 shadow-2xl" @click.stop>
            <button type="button" class="absolute right-4 top-4 text-gray-400 hover:text-gray-600" @click="purgeOpen = false" aria-label="Đóng">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <h3 class="flex items-center gap-2 pr-8 text-lg font-semibold text-red-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Xóa dữ liệu CSDL theo khoảng ngày
            </h3>
            <p class="mt-2 text-sm text-gray-600">Chọn loại dữ liệu vận hành cần xóa trong khoảng thời gian. Không xóa danh mục nhân sự, phòng học hay tài khoản. Với <strong>Lịch học theo ngày</strong>: chỉ xóa dòng chưa ghi nhận (# không khoanh đỏ); giữ lại dòng đã ghi nhận.</p>
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <x-filter-label class="uppercase">Từ ngày</x-filter-label>
                    <input type="date" x-model="purgeFromDate" required class="nttu-form-control">
                </div>
                <div class="space-y-1">
                    <x-filter-label class="uppercase">Đến ngày</x-filter-label>
                    <input type="date" x-model="purgeToDate" required class="nttu-form-control">
                </div>
            </div>
            <div class="mt-4 space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <x-filter-label class="uppercase mb-0">Loại dữ liệu xóa</x-filter-label>
                    <div class="flex items-center gap-2 text-xs">
                        <button type="button" class="font-medium text-[var(--nttu-primary)] hover:underline" @click="selectAllPurgeTypes()">Chọn tất cả</button>
                        <span class="text-gray-300">|</span>
                        <button type="button" class="font-medium text-gray-600 hover:underline" @click="clearPurgeTypes()">Bỏ chọn</button>
                    </div>
                </div>
                <div class="max-h-56 space-y-1 overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <template x-for="item in purgeDataTypes" :key="item.key">
                        <label class="flex cursor-pointer items-start gap-2 rounded-md px-2 py-1.5 hover:bg-white">
                            <input type="checkbox"
                                   class="mt-0.5 h-4 w-4 rounded border-gray-300 text-red-600"
                                   :value="item.key"
                                   :checked="isPurgeTypeSelected(item.key)"
                                   @change="togglePurgeType(item.key)">
                            <span class="text-sm text-gray-800" x-text="item.label"></span>
                        </label>
                    </template>
                </div>
                <p class="text-xs text-gray-500" x-show="!purgeFromDate || !purgeToDate" x-cloak>Vui lòng chọn Từ ngày và Đến ngày.</p>
                <p class="text-xs text-gray-500" x-show="purgeSelectedTypes.length === 0" x-cloak>Vui lòng chọn ít nhất một loại dữ liệu.</p>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <x-nttu-button type="button" action="cancel" x-bind:disabled="purging" @click="purgeOpen = false">Hủy</x-nttu-button>
                <x-nttu-button type="button" action="delete" x-bind:disabled="!canPurge" @click="purgeData()">
                    <span x-show="!purging">Xóa dữ liệu CSDL</span>
                    <span x-show="purging" x-cloak>Đang xóa...</span>
                </x-nttu-button>
            </div>
        </div>
    </div>
    @endif
</div>
