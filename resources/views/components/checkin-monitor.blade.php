@props(['mode', 'items', 'routes', 'title' => 'Giám sát'])

@php $pagePerms = $nttuPage(\App\Support\PagePermissionFlags::checkinModule($mode)); @endphp

<div
    x-data="checkinMonitorPage({
        mode: @js($mode),
        items: @js($items),
        routes: @js($routes),
        canEdit: @js($pagePerms['edit']),
        canDelete: @js($pagePerms['delete']),
    })"
    class="space-y-4"
    @click.self="closeRowMenu()"
>
    <div x-show="toast" x-cloak
        class="fixed bottom-4 right-4 z-[70] rounded-lg px-4 py-3 text-sm shadow-lg"
        :class="toast?.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
        x-text="toast?.message"></div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-lg border-none shadow-sm bg-[#F9F5FF] overflow-hidden group hover:shadow-md transition-all duration-300 p-6">
            <span class="text-[11px] font-bold text-[#7F56D9] uppercase tracking-wider">Đang dạy</span>
            <p class="text-4xl font-black text-[#53389E] mt-1 group-hover:scale-110 transition-transform duration-500" x-text="stats.ongoing"></p>
        </div>
        <div class="rounded-lg border-none shadow-sm bg-[#F0F9FF] overflow-hidden group hover:shadow-md transition-all duration-300 p-6">
            <span class="text-[11px] font-bold text-[#026AA2] uppercase tracking-wider">Tổng lượt dạy hôm nay</span>
            <p class="text-4xl font-black text-[#026AA2] mt-1 group-hover:scale-110 transition-transform duration-500" x-text="stats.totalToday"></p>
        </div>
        <div class="rounded-lg border-none shadow-sm bg-[#F0FDF4] overflow-hidden group hover:shadow-md transition-all duration-300 p-6">
            <span class="text-[11px] font-bold text-[#067647] uppercase tracking-wider">Đã duyệt</span>
            <p class="text-4xl font-black text-[#067647] mt-1 group-hover:scale-110 transition-transform duration-500" x-text="stats.approvedToday"></p>
        </div>
    </div>

    {{-- Content card --}}
    <div class="bg-white rounded-lg border shadow-md border-t-4 border-t-blue-600 overflow-hidden">
        <div class="px-4 py-3 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h3 class="text-lg font-semibold text-slate-800">Danh sách Minh chứng Check-in</h3>
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-lg border">
                    <button type="button" @click="setViewMode('grid')"
                        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="viewMode === 'grid' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-600 hover:text-slate-800'">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        Lưới
                    </button>
                    <button type="button" @click="setViewMode('table')"
                        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="viewMode === 'table' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-600 hover:text-slate-800'">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        Bảng
                    </button>
                </div>
                <button type="button" x-show="viewMode === 'table' && selectedIds.length && canDelete" x-cloak @click="deleteSelected()"
                    class="inline-flex items-center gap-1.5 rounded-md bg-red-600 text-white px-3 py-1.5 text-sm font-medium hover:bg-red-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span x-text="`Xóa (${selectedIds.length})`"></span>
                </button>
                <button type="button" @click="refreshItems()" class="rounded-md border px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">Làm mới</button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="px-4 py-3 border-b bg-slate-50/80 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[220px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" x-model="searchTerm" @input="currentPage = 1"
                    placeholder="Tìm lớp, môn, giảng viên, phòng..."
                    class="w-full h-10 rounded-md border bg-white pl-9 pr-3 text-sm">
            </div>
            <select x-model="statusFilter" @change="currentPage = 1" class="h-10 rounded-md border bg-white px-3 text-sm min-w-[160px]">
                <option value="">Tất cả trạng thái</option>
                <option value="pending_review">Chờ duyệt</option>
                <option value="approved">Đã duyệt</option>
                <option value="rejected">Từ chối</option>
                <template x-if="mode === 'online'"><option value="completed">Hoàn thành</option></template>
            </select>
            <button type="button" x-show="hasActiveFilters()" @click="clearFilters()"
                class="inline-flex items-center gap-1.5 h-10 px-3 rounded-md text-sm text-red-600 hover:bg-red-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                Xóa lọc
            </button>
            <span class="text-sm text-slate-500 ml-auto bg-white border rounded-full px-3 py-1.5">
                Tổng: <strong class="text-blue-600" x-text="filteredItems.length"></strong> minh chứng
            </span>
        </div>

        <div class="p-4 pt-4">
            {{-- Padlet-style grid wall --}}
            <div x-show="viewMode === 'grid'" x-cloak>
                <div x-show="paginatedItems.length === 0" class="py-16 text-center text-gray-500 border border-dashed rounded-xl bg-slate-50">
                    <x-table-empty-state
                        filters-active="hasActiveFilters()"
                        clear-action="clearFilters()"
                    />
                </div>
                <div x-show="paginatedItems.length > 0" class="columns-1 sm:columns-2 lg:columns-3 xl:columns-4 gap-4 space-y-0">
                    <template x-for="item in paginatedItems" :key="item.id">
                        <article
                            class="break-inside-avoid mb-4 rounded-2xl overflow-hidden border shadow-sm hover:shadow-xl transition-all duration-300 cursor-pointer group bg-gradient-to-br"
                            :class="cardAccentClass(item.status)"
                            @click="openReview(item, 'view')"
                        >
                            <div class="relative bg-slate-200 overflow-hidden" :class="cardPhoto(item) ? 'aspect-[4/3]' : 'aspect-[3/2]'">
                                <template x-if="cardPhoto(item)">
                                    <img :src="cardPhoto(item)" :alt="item.className || item.classId"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                </template>
                                <template x-if="!cardPhoto(item)">
                                    <div class="w-full h-full flex flex-col items-center justify-center text-slate-400 bg-slate-100">
                                        <svg class="h-12 w-12 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <p class="text-xs mt-2 font-medium">Chưa có ảnh minh chứng</p>
                                    </div>
                                </template>
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/25 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all">
                                    <div class="bg-white/95 backdrop-blur p-2.5 rounded-full shadow-lg">
                                        <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </div>
                                </div>
                                <div class="absolute top-2 left-2">
                                    <span class="text-[10px] px-2 py-0.5 rounded-full border font-semibold shadow-sm" :class="statusClass(item.status)" x-text="statusLabel(item.status)"></span>
                                </div>
                                <div x-show="photoCount(item) > 1" class="absolute bottom-2 right-2 bg-black/65 text-white text-[10px] px-2 py-0.5 rounded-full backdrop-blur"
                                    x-text="`+${photoCount(item) - 1} ảnh`"></div>
                                <template x-if="mode === 'external' && item.latitude">
                                    <div class="absolute bottom-2 left-2 bg-black/55 text-white text-[10px] px-2 py-0.5 rounded-full backdrop-blur flex items-center gap-1">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                        GPS
                                    </div>
                                </template>
                            </div>
                            <div class="p-4 space-y-2 bg-white/80 backdrop-blur-sm">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <h4 class="font-bold text-sm text-slate-800 line-clamp-1 group-hover:text-blue-700" x-text="item.classId || '—'"></h4>
                                        <p class="text-xs text-slate-600 line-clamp-2 mt-0.5" x-text="item.className || '—'"></p>
                                    </div>
                                    <span class="shrink-0 text-[10px] font-bold text-blue-700 bg-blue-50 border border-blue-100 rounded px-1.5 py-0.5" x-text="item.period ? `Tiết ${item.period}` : ''"></span>
                                </div>
                                <div class="flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-slate-500">
                                    <span class="inline-flex items-center gap-1" x-show="item.room">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        <span x-text="item.room"></span>
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span x-text="item.submittedBy || item.lecturer || '—'"></span>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between pt-2 border-t border-slate-100/80">
                                    <span class="text-[10px] text-slate-400" x-text="item.timestamp"></span>
                                    <div class="flex items-center gap-1" @click.stop>
                                        <button type="button" x-show="canEdit" x-cloak @click="openReview(item, 'edit')"
                                            class="text-[10px] font-bold uppercase tracking-wide text-orange-600 hover:bg-orange-50 px-2 py-1 rounded-md">
                                            Ghi nhận
                                        </button>
                                        <button type="button" x-show="canDelete" x-cloak @click="deleteSingle(item.id)"
                                            class="p-1 rounded-md text-slate-400 hover:text-red-600 hover:bg-red-50" title="Xóa">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>
            </div>

            {{-- Table view (app cũ) --}}
            <div x-show="viewMode === 'table'" x-cloak>
            <div class="rounded-md border border-blue-200 overflow-x-auto overflow-y-visible shadow-sm">
                <table class="nttu-table nttu-catalog-table min-w-[1400px] w-full table-fixed text-sm"
                    x-bind:data-col-resize="'checkin_monitor_' + mode">
                    <thead>
                        <tr>
                            <th class="catalog-th-index w-[50px] px-2">#</th>
                            <th class="w-[40px] px-2 py-2 text-center border-r border-blue-300">
                                <input type="checkbox" @change="toggleSelectAll()" class="w-4 h-4 rounded border-white">
                            </th>
                            <template x-for="col in columns" :key="col.key">
                                <th class="catalog-th-col border-l border-blue-400 relative" :data-col="col.key" :data-col-label="col.label">
                                    <button type="button" @click.stop="toggleFilterCol(col.key)"
                                        class="group nttu-th-sort-btn">
                                        <svg class="mr-2 h-3.5 w-3.5 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="columnIcon(col.key)"></path>
                                        </svg>
                                        <span class="nttu-th-label-text" x-text="col.label"></span>
                                        <template x-if="sortKey === col.key">
                                            <svg x-show="sortDir === 'asc'" class="ml-2 h-4 w-4 shrink-0" :class="columnFilters[col.key] && 'nttu-sort-filtered'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                            <svg x-show="sortDir === 'desc'" class="ml-2 h-4 w-4 shrink-0" :class="columnFilters[col.key] && 'nttu-sort-filtered'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </template>
                                        <template x-if="sortKey !== col.key">
                                            <svg class="ml-2 h-4 w-4 opacity-50 group-hover:opacity-100 shrink-0" :class="columnFilters[col.key] && 'nttu-sort-filtered'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                        </template>
                                    </button>
                                    <div x-show="openFilterCol === col.key" @click.outside="openFilterCol = null" x-cloak
                                        class="nttu-report-header-popover absolute left-0 top-full z-30 mt-0 w-60 rounded-md border bg-white text-slate-700 shadow-lg overflow-hidden">
                                        <div class="p-1 space-y-0.5">
                                            <button type="button" @click="requestSortAsc(col.key)" class="flex w-full items-center px-3 py-2 text-xs hover:bg-slate-50 rounded">
                                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                                Tăng dần
                                            </button>
                                            <button type="button" @click="requestSortDesc(col.key)" class="flex w-full items-center px-3 py-2 text-xs hover:bg-slate-50 rounded">
                                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                Giảm dần
                                            </button>
                                            <template x-if="sortKey === col.key">
                                                <button type="button" @click="clearSort()" class="nttu-popover-clear-action flex w-full items-center px-3 py-2 text-xs rounded border-t mt-1 pt-2">
                                                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    Xoá sắp xếp
                                                </button>
                                            </template>
                                        </div>
                                        <div class="border-t p-2">
                                            <div class="relative">
                                                <svg class="absolute left-2 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                                                <input type="text" :placeholder="`Lọc ${col.label}...`"
                                                    :value="columnFilters[col.key] || ''"
                                                    @input="setColumnFilter(col.key, $event.target.value)"
                                                    @keydown.enter="openFilterCol = null"
                                                    class="w-full h-8 rounded border pl-8 pr-2 text-xs">
                                            </div>
                                            <button type="button" x-show="columnFilters[col.key]"
                                                @click="setColumnFilter(col.key, '')"
                                                class="nttu-popover-clear-action flex w-full items-center mt-1 px-2 py-1.5 text-xs rounded">
                                                <svg class="mr-2 h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                Xóa bộ lọc
                                            </button>
                                        </div>
                                    </div>
                                </th>
                            </template>
                            {{-- Cột cố định cuối: Cài đặt hiển thị (header) / Thao tác (body) --}}
                            <th class="catalog-th-settings relative">
                                <button type="button" data-float-trigger @click.stop="colMenuOpen = !colMenuOpen"
                                    class="nttu-catalog-cog-btn"
                                    title="Cài đặt hiển thị">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </button>
                                <div x-cloak x-float="colMenuOpen" data-float-close="colMenuOpen = false"
                                    class="nttu-report-settings-panel nttu-floating-panel w-52 max-h-[min(20rem,calc(100vh-1rem))] overflow-y-auto rounded-md border bg-white py-2 shadow-xl">
                                    <p class="report-settings-title border-b border-gray-100 px-3 py-2 font-bold text-xs uppercase text-slate-500">Hiển thị cột</p>
                                    @include('components.partials.column-settings-bulk-actions')
                                    <template x-for="col in allColumns" :key="col.key">
                                        <label class="flex items-center gap-2 px-3 py-1.5 hover:bg-slate-50 cursor-pointer text-sm">
                                            <input type="checkbox" class="rounded" :checked="visibleColumns[col.key] !== false" @change="toggleColumn(col.key)">
                                            <span x-text="col.label"></span>
                                        </label>
                                    </template>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(item, idx) in paginatedItems" :key="item.id">
                            <tr :class="selectedIds.includes(item.id) ? 'row-selected' : ''">
                                <td class="px-2 py-2 text-center font-medium text-slate-500 border-r border-slate-200" x-text="rowNumber(idx)"></td>
                                <td class="px-2 py-2 text-center border-r border-slate-200">
                                    <input type="checkbox" :checked="selectedIds.includes(item.id)" @change="toggleSelect(item.id)" class="w-4 h-4 rounded border-gray-300">
                                </td>
                                <template x-for="col in columns" :key="col.key">
                                    <td class="px-3 py-2 border-r border-slate-200 min-w-0 break-words"
                                        :data-col="col.key"
                                        :class="{
                                            'text-center font-bold': col.key === 'room',
                                            'text-center': ['period','studentCount','timestamp','status'].includes(col.key),
                                            'text-center font-bold text-blue-700': col.key === 'classId',
                                            'text-center font-medium': col.key === 'studentCount',
                                            'text-center font-bold text-green-700': col.key === 'actualStudentCount',
                                            'font-medium': ['submittedBy','lecturer','className'].includes(col.key),
                                            'text-xs text-slate-600': col.key === 'incidentDetail',
                                        }">
                                        <template x-if="col.key === 'status'">
                                            <span class="text-[10px] px-2 py-0.5 rounded-full border font-medium inline-block" :class="statusClass(item.status)" x-text="statusLabel(item.status)"></span>
                                        </template>
                                        <template x-if="col.key === 'submittedBy' && mode === 'external'">
                                            <div>
                                                <span x-text="cellValue(item, col.key)"></span>
                                                <p class="text-[10px] text-slate-500 mt-0.5" x-show="item.submittedByEmail" x-text="item.submittedByEmail"></p>
                                                <p class="text-[10px] text-slate-500 mt-0.5 flex items-center gap-1" x-show="item.latitude">
                                                    <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                                    <span x-text="`Lat: ${item.latitude?.toFixed?.(4) ?? item.latitude}, Lng: ${item.longitude?.toFixed?.(4) ?? item.longitude}`"></span>
                                                </p>
                                            </div>
                                        </template>
                                        <template x-if="col.key === 'incident'">
                                            <span x-show="item.incident && item.incident !== 'none'"
                                                class="text-[10px] px-2 py-0.5 rounded bg-red-600 text-white font-medium"
                                                x-text="item.incident"></span>
                                        </template>
                                        <template x-if="col.key === 'isNotification'">
                                            <span class="text-[10px] px-2 py-0.5 rounded font-medium inline-block h-5 leading-5"
                                                :class="item.isNotification ? 'bg-blue-600 text-white' : 'border text-slate-500'"
                                                x-text="item.isNotification ? 'Có' : 'Không'"></span>
                                        </template>
                                        <template x-if="col.key === 'evidence' && item.evidence">
                                            <button type="button" @click.stop="fullImage = item.evidence" class="block">
                                                <img :src="item.evidence" class="h-10 w-14 object-cover rounded border hover:ring-2 ring-blue-400">
                                            </button>
                                        </template>
                                        <template x-if="col.key !== 'status' && col.key !== 'incident' && col.key !== 'isNotification' && col.key !== 'evidence' && !(col.key === 'submittedBy' && mode === 'external')">
                                            <span x-text="cellValue(item, col.key)" :title="col.key === 'incidentDetail' ? item.incidentDetail : ''"></span>
                                        </template>
                                        <template x-if="col.key === 'evidence' && !item.evidence">
                                            <span class="text-slate-400">—</span>
                                        </template>
                                    </td>
                                </template>
                                <td class="catalog-td-settings sticky-action text-center border-l border-slate-200 p-0 align-middle"
                                    @click.stop>
                                    <button type="button" data-float-trigger title="Thao tác"
                                        @click.stop="rowMenuOpen = rowMenuOpen === item.id ? null : item.id"
                                        class="nttu-row-action-btn">
                                        @include('components.partials.row-action-menu-icon')
                                    </button>
                                    <div x-cloak x-float="rowMenuOpen === item.id"
                                        class="nttu-row-action-menu nttu-floating-panel w-40 rounded-md border bg-white py-1 text-left text-sm shadow-lg">
                                            <button type="button" @click="openReview(item, 'view')"
                                                class="flex w-full items-center px-3 py-2 hover:bg-slate-50 text-slate-700">
                                                <svg class="mr-2 h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                Xem chi tiết
                                            </button>
                                            <button type="button" x-show="canEdit" x-cloak @click="openReview(item, 'edit')"
                                                class="flex w-full items-center px-3 py-2 hover:bg-slate-50 text-orange-600">
                                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                Ghi nhận
                                            </button>
                                            <div class="border-t my-1"></div>
                                            <button type="button" x-show="canDelete" x-cloak @click="deleteSingle(item.id)"
                                                class="flex w-full items-center px-3 py-2 hover:bg-red-50 text-red-600">
                                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                Xóa
                                            </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="paginatedItems.length === 0">
                            <td :colspan="columns.length + 3" class="py-12 text-center text-gray-500">
                                <x-table-empty-state
                                    filters-active="hasActiveFilters()"
                                    clear-action="clearFilters()"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </div>

            {{-- Pagination --}}
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-4 text-sm text-slate-500">
                <div class="flex items-center gap-2">
                    <span x-show="viewMode === 'table'">Hiển thị</span>
                    <select x-show="viewMode === 'table'" :value="normalizedRowsPerPage" @change="setRowsPerPage($event.target.value)" class="h-8 w-[70px] rounded border bg-white text-sm px-2">
                        <template x-for="n in [10,20,50,100]" :key="n"><option :value="n" x-text="n"></option></template>
                    </select>
                    <span x-show="viewMode === 'table'">dòng / trang</span>
                    <span x-show="viewMode === 'grid'" class="text-xs text-slate-500">12 thẻ / trang (lưới)</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="hidden sm:inline" x-text="`Trang ${safePage} / ${totalPages} (${filteredItems.length} bản ghi)`"></span>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="currentPage = 1" :disabled="safePage === 1" class="h-8 w-8 inline-flex items-center justify-center border rounded disabled:opacity-40 hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" @click="currentPage = safePage - 1" :disabled="safePage === 1" class="h-8 w-8 inline-flex items-center justify-center border rounded disabled:opacity-40 hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" @click="currentPage = safePage + 1" :disabled="safePage === totalPages" class="h-8 w-8 inline-flex items-center justify-center border rounded disabled:opacity-40 hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <button type="button" @click="currentPage = totalPages" :disabled="safePage === totalPages" class="h-8 w-8 inline-flex items-center justify-center border rounded disabled:opacity-40 hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Review modal --}}
    <div x-show="reviewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @keydown.escape.window="closeReview()">
        <div class="bg-slate-50 rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" @click.outside="closeReview()">
            <div class="px-6 py-4 border-b bg-white rounded-t-xl">
                <h3 class="text-xl text-blue-800 font-bold flex items-center gap-2">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                    <span x-text="mode === 'external' ? 'Minh chứng Check-in Thực hành' : 'Minh chứng Check-in Online'"></span>
                </h3>
            </div>
            <div class="p-6 space-y-4" x-show="selected">
                {{-- Thông tin chính --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                    <div class="md:col-span-2">
                        <p class="text-slate-500 text-[10px] uppercase font-bold flex items-center gap-1 mb-1">
                            <svg class="h-3 w-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            Lớp học
                        </p>
                        <p class="font-semibold text-sm text-blue-900" x-text="selected?.className"></p>
                        <p class="text-xs text-blue-700 font-bold mt-0.5" x-text="selected?.classId"></p>
                    </div>
                    <div>
                        <p class="text-slate-500 text-[10px] uppercase font-bold flex items-center gap-1 mb-1">
                            <svg class="h-3 w-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Giảng viên / Người gửi
                        </p>
                        <p class="font-semibold text-sm" x-text="selected?.submittedBy || selected?.lecturer"></p>
                    </div>
                    <div class="pt-2 border-t md:border-t-0 md:border-l md:pl-4">
                        <p class="text-slate-500 text-[10px] uppercase font-bold flex items-center gap-1 mb-1">
                            <svg class="h-3 w-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Thời gian gửi
                        </p>
                        <p class="font-semibold text-sm" x-text="selected?.timestamp"></p>
                    </div>
                    <div class="pt-2 border-t md:border-t-0">
                        <p class="text-slate-500 text-[10px] uppercase font-bold flex items-center gap-1 mb-1">
                            <svg class="h-3 w-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Việc phát sinh (Gốc)
                        </p>
                        <span class="text-[10px] px-2 py-0.5 rounded border inline-block"
                            :class="selected?.incident && selected.incident !== 'none' ? 'bg-red-600 text-white border-red-600' : 'text-slate-500'"
                            x-text="selected?.incident && selected.incident !== 'none' ? selected.incident : 'Không có'"></span>
                    </div>
                    <template x-if="mode === 'online' && selected?.meetingLink">
                        <div class="md:col-span-3 pt-2 border-t">
                            <p class="text-[10px] uppercase font-bold text-slate-500 mb-1">Link họp</p>
                            <a :href="selected.meetingLink" target="_blank" class="text-blue-600 text-sm break-all" x-text="selected.meetingLink"></a>
                        </div>
                    </template>
                </div>

                {{-- Ghi nhận mới --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                    <div>
                        <label class="text-xs font-bold text-slate-600 uppercase tracking-tight">SV tham gia thực tế</label>
                        <input type="text" x-model="reviewForm.actual_student_count" :disabled="reviewMode === 'view'"
                            class="w-full mt-1 rounded-md border border-blue-200 px-3 py-2 text-sm font-bold bg-white disabled:bg-slate-50 disabled:text-slate-600">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-600 uppercase tracking-tight">Việc phát sinh</label>
                        <input type="text" x-model="reviewForm.incident" placeholder="Nhập sự cố nếu có..." :disabled="reviewMode === 'view'"
                            class="w-full mt-1 rounded-md border border-blue-200 px-3 py-2 text-sm bg-white disabled:bg-slate-50">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-bold text-slate-600 uppercase tracking-tight">Chi tiết sự việc</label>
                        <input type="text" x-model="reviewForm.incident_detail" placeholder="Mô tả chi tiết sự việc..." :disabled="reviewMode === 'view'"
                            class="w-full mt-1 rounded-md border border-blue-200 px-3 py-2 text-sm bg-white disabled:bg-slate-50">
                    </div>
                </div>

                {{-- Vị trí & Hình ảnh --}}
                <div class="border rounded-lg overflow-hidden transition-all duration-300"
                    :class="detailCollapsed ? 'bg-slate-100/50 border-slate-200' : 'bg-white border-blue-200 shadow-md'">
                    <button type="button" @click="detailCollapsed = !detailCollapsed"
                        class="w-full flex items-center justify-between p-3 hover:bg-slate-100 transition-colors">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 rounded-full" :class="detailCollapsed ? 'bg-slate-200' : 'bg-blue-100 text-blue-600'">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <span class="font-bold text-sm" :class="detailCollapsed ? 'text-slate-600' : 'text-blue-700'">Vị trí & Hình ảnh minh chứng</span>
                        </div>
                        <span class="text-xs text-slate-500 flex items-center gap-1">
                            <span x-text="detailCollapsed ? 'Xem chi tiết' : 'Thu gọn'"></span>
                            <svg x-show="detailCollapsed" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            <svg x-show="!detailCollapsed" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                        </span>
                    </button>
                    <div x-show="!detailCollapsed" class="p-4 pt-0 space-y-4 border-t border-blue-100">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <template x-if="mode === 'external' && (selected?.latitude || mapUrl(selected))">
                                <div>
                                    <p class="text-slate-500 text-[10px] uppercase font-bold flex items-center gap-1 mb-2">
                                        <svg class="h-3 w-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                        Tọa độ xác thực
                                    </p>
                                    <div class="bg-slate-50 border rounded p-3">
                                        <p class="font-mono text-sm font-bold text-slate-700" x-text="`${selected?.latitude?.toFixed?.(6) ?? selected?.latitude}, ${selected?.longitude?.toFixed?.(6) ?? selected?.longitude}`"></p>
                                        <a :href="mapUrl(selected)" target="_blank" class="text-xs text-blue-600 hover:underline flex items-center mt-2 font-semibold">
                                            <svg class="h-3.5 w-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                            Mở trong Google Maps
                                        </a>
                                    </div>
                                </div>
                            </template>
                            <div>
                                <p class="text-slate-500 text-[10px] uppercase font-bold flex items-center gap-1 mb-2">
                                    <svg class="h-3 w-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span x-text="mode === 'online' ? 'Ảnh chụp màn hình' : 'Ảnh chụp hiện trường'"></span>
                                </p>
                                <div class="border border-slate-200 rounded-lg p-2 bg-slate-50">
                                    <template x-if="mode === 'external' && selected?.photoUrls?.length">
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                            <template x-for="(url, i) in selected.photoUrls" :key="i">
                                                <button type="button" @click="fullImage = url" class="relative group border border-blue-100 rounded-lg overflow-hidden shadow-sm hover:shadow-md">
                                                    <img :src="url" class="w-full h-40 object-cover">
                                                    <div class="absolute bottom-1 right-1 bg-black/50 text-[10px] text-white px-1.5 py-0.5 rounded-sm" x-text="`#${i + 1}`"></div>
                                                </button>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="mode === 'online' && selected?.evidence">
                                        <button type="button" @click="fullImage = selected.evidence" class="block w-full">
                                            <img :src="selected.evidence" class="w-full max-h-40 object-cover rounded border">
                                        </button>
                                    </template>
                                    <template x-if="(mode === 'external' && !selected?.photoUrls?.length) || (mode === 'online' && !selected?.evidence)">
                                        <div class="py-8 text-center text-slate-400 border border-dashed border-slate-200 rounded-lg">
                                            <svg class="h-8 w-8 mx-auto mb-2 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <p class="text-xs italic">Không có hình ảnh minh chứng</p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quyết định phê duyệt --}}
                <div class="space-y-2 border-t pt-4">
                    <label class="text-base font-semibold text-slate-800">Quyết định phê duyệt</label>
                    <select x-model="reviewForm.status" :disabled="reviewMode === 'view'"
                        class="w-full h-12 rounded-md border px-3 text-base font-medium"
                        :class="reviewStatusClass()">
                        <option value="pending_review">Chờ duyệt (Chưa có kết quả)</option>
                        <option value="approved">Chấp nhận (Đã kiểm tra đúng)</option>
                        <option value="rejected">Từ chối (Sai vị trí / Hình mờ)</option>
                        <template x-if="mode === 'online'"><option value="completed">Hoàn thành</option></template>
                    </select>
                </div>
            </div>

            <div class="px-6 py-4 border-t bg-white rounded-b-xl flex justify-end gap-2">
                <button type="button" @click="closeReview()" class="rounded-md border px-4 py-2 text-sm hover:bg-slate-50">Đóng</button>
                <button type="button" x-show="reviewMode === 'edit' && canEdit" x-cloak @click="saveReview()" :disabled="saving"
                    class="rounded-md bg-blue-600 text-white px-4 py-2 text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
                    x-text="saving ? 'Đang lưu...' : 'Lưu kết quả duyệt'"></button>
            </div>
        </div>
    </div>

    {{-- Lightbox --}}
    <div x-show="fullImage" x-cloak class="fixed inset-0 z-[60] bg-black/90 flex items-center justify-center p-4" @keydown.escape.window="fullImage = null">
        <button type="button" @click="fullImage = null" class="absolute top-4 right-4 text-white hover:bg-white/20 rounded p-2">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <img :src="fullImage" class="max-w-full max-h-[90vh] object-contain rounded-sm shadow-2xl" @click.stop>
    </div>
</div>
