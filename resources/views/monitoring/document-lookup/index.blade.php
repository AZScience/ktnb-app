@extends('layouts.nttu')
@section('title', 'Tra cứu văn bản')
@section('page-section', 'Công cụ hỗ trợ')
@section('page-title', 'Tra cứu văn bản')
@section('page-description', 'Hệ thống tra cứu và tìm kiếm hồ sơ văn bản tập trung')
@section('content')

<div
    x-data="documentLookupPage({
        docTypes: @js($docTypes),
        searchUrl: @js(route('document-lookup.search')),
        verifyUrlTemplate: @js(route('document-lookup.verify', ['document_record' => '__ID__'])),
    })"
    class="flex flex-col -mx-1"
    @keydown.escape.window="fullPreview = false"
>
    <div x-show="toast" x-cloak
        class="fixed bottom-4 right-4 z-[70] rounded-lg px-4 py-3 text-sm shadow-lg"
        :class="toast?.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
        x-text="toast?.message"></div>

    <div class="space-y-6 pb-8">
        {{-- Search card --}}
        <div class="nttu-card p-6 space-y-4 shadow-sm">
            <div class="flex flex-col md:flex-row gap-4 items-stretch md:items-center">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="searchTerm" :placeholder="dynamicPlaceholder"
                        @keydown.enter="handleSearch({ requireFilters: true })"
                        @input="persist()"
                        class="w-full h-11 pl-10 rounded-md border border-slate-200 text-base focus:ring-[var(--nttu-primary)]/20 focus:border-[var(--nttu-primary)]">
                </div>

                <div class="w-full md:w-64 relative">
                    <button type="button" @click="typeMenuOpen = !typeMenuOpen" @click.outside="typeMenuOpen = false"
                        class="w-full h-11 flex items-center justify-between rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-600 font-medium">
                        <span class="flex items-center gap-2 truncate">
                            <svg class="h-4 w-4 text-[var(--nttu-primary)] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                            <span x-text="selectedDocTypes.length === 0 ? 'Tất cả loại văn bản' : `Đã chọn ${selectedDocTypes.length} loại`"></span>
                        </span>
                        <svg class="h-4 w-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/></svg>
                    </button>
                    <div x-show="typeMenuOpen" x-cloak class="absolute z-30 mt-1 w-full rounded-md border border-slate-200 bg-white shadow-lg">
                        <div class="p-2 border-b bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-wider px-3">Chọn loại văn bản</div>
                        <div class="max-h-64 overflow-y-auto p-2 space-y-1">
                            <template x-for="type in docTypes" :key="type.id">
                                <label class="flex items-center gap-2 p-2 hover:bg-slate-50 rounded-md cursor-pointer text-xs font-medium">
                                    <input type="checkbox" class="rounded border-gray-300" :checked="selectedDocTypes.includes(type.name)" @change="toggleDocType(type.name)">
                                    <span x-text="type.name"></span>
                                </label>
                            </template>
                        </div>
                        <div x-show="selectedDocTypes.length > 0" class="p-2 border-t bg-slate-50 text-center">
                            <button type="button" @click="clearDocTypes()" class="text-[10px] font-bold text-red-500 hover:text-red-600">Xóa chọn</button>
                        </div>
                    </div>
                </div>

                <div class="w-full md:w-48">
                    <input type="date" x-model="issueDateIso" @change="persist()"
                        class="w-full h-11 rounded-md border border-slate-200 text-sm px-3"
                        title="Ngày ban hành">
                </div>

                <button type="button" @click="handleSearch({ requireFilters: true })" :disabled="loading"
                    class="h-11 px-4 rounded-md bg-[var(--nttu-primary)] text-white shadow-sm hover:opacity-90 disabled:opacity-50 flex items-center justify-center shrink-0">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-x-6 gap-y-3 pt-1 border-t border-slate-100">
                <div class="flex flex-wrap items-center gap-3 pr-0 sm:pr-6 sm:border-r border-slate-200">
                    <select x-model="searchMode" @change="persist()" class="h-8 rounded-md border border-slate-200 text-xs font-bold text-slate-600 px-2 bg-white">
                        <option value="partial">Tìm tương tự</option>
                        <option value="exact">Tìm chính xác</option>
                    </select>
                    <select x-model="searchLogic" @change="persist()" class="h-8 rounded-md border border-slate-200 text-xs font-bold text-slate-600 px-2 bg-white">
                        <option value="and">Điều kiện và (and)</option>
                        <option value="or">Điều kiện hoặc (or)</option>
                    </select>
                </div>
                <template x-for="scope in scopeOptions" :key="scope.key">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="rounded border-gray-300" :checked="searchScopes.includes(scope.key)" @change="toggleScope(scope.key)">
                        <span class="text-sm font-medium text-slate-600" x-text="scope.label"></span>
                    </label>
                </template>
            </div>
        </div>

        {{-- Split view --}}
        <template x-if="searched && hasActiveFilters">
            <div class="flex flex-col md:flex-row h-[85vh] min-h-[600px] overflow-hidden relative">
                {{-- Left: results --}}
                <div class="flex flex-col bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm h-full shrink-0 w-full max-md:!w-full md:w-auto"
                    :class="selectedDoc ? 'hidden md:flex' : 'flex'"
                    :style="`width: ${leftPanelWidth}px`">
                    <div class="px-4 py-3 bg-slate-50/50 border-b flex items-center justify-between shrink-0">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider" x-text="`Danh sách kết quả (${sortedItems.length})`"></span>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-500 font-medium">Số dòng</span>
                            <select class="nttu-rows-per-page-select" :value="normalizedRowsPerPage" @change="setRowsPerPage($event.target.value)">
                                <template x-for="n in rowsPerPageOptions" :key="n">
                                    <option :value="n" x-text="n" :selected="normalizedRowsPerPage === n"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto overflow-x-auto">
                        <template x-if="loading">
                            <div class="p-4 space-y-3">
                                <template x-for="i in 5" :key="i">
                                    <div class="animate-pulse h-16 bg-slate-100 rounded"></div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!loading && paginatedItems.length > 0">
                            <div class="divide-y divide-slate-100 min-w-[420px]">
                                <template x-for="item in paginatedItems" :key="item.id">
                                    <div @click="selectDoc(item)"
                                        class="p-4 cursor-pointer transition-all border-l-4"
                                        :class="selectedDoc?.id === item.id ? 'bg-blue-50 border-[var(--nttu-primary)]' : 'hover:bg-slate-50 border-transparent'">
                                        <div class="flex items-start gap-3">
                                            <div class="h-10 w-10 rounded-lg flex items-center justify-center shrink-0 shadow-sm border border-slate-100 bg-white"
                                                :class="selectedDoc?.id === item.id ? 'ring-2 ring-[var(--nttu-primary)] ring-offset-1' : ''">
                                                <div x-html="fileIconHtml(item)"></div>
                                            </div>
                                            <div class="flex-1 min-w-0 pr-2">
                                                <h4 class="text-sm font-bold truncate" :class="selectedDoc?.id === item.id ? 'text-[var(--nttu-primary)]' : 'text-slate-800'" x-text="fileDisplayName(item)" title="Tên file"></h4>
                                                <p class="text-[11px] text-slate-500 truncate mt-0.5" x-text="item.title" :title="item.title"></p>
                                                <p class="mt-1.5 text-[10px] text-slate-400 truncate" x-text="metaSummary(item)" :title="metaSummary(item)"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!loading && paginatedItems.length === 0">
                            <div class="p-12 text-center text-gray-500">
                                <x-table-empty-state
                                    icon="inbox"
                                    filters-active="hasActiveFilters"
                                    clear-action="clearDocTypes(); searchTerm = ''; issueDateIso = ''; persist(); handleSearch({ requireFilters: false })"
                                />
                            </div>
                        </template>
                    </div>

                    <div class="p-3 border-t bg-slate-50/50 flex items-center justify-between gap-2 shrink-0">
                        <div class="flex items-center gap-1">
                            <button type="button" @click="setPage(1)" :disabled="safeCurrentPage === 1" class="h-8 w-8 rounded border border-slate-200 bg-white disabled:opacity-40 flex items-center justify-center">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                            </button>
                            <button type="button" @click="setPage(safeCurrentPage - 1)" :disabled="safeCurrentPage === 1" class="h-8 w-8 rounded border border-slate-200 bg-white disabled:opacity-40 flex items-center justify-center">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                        </div>
                        <div class="text-[11px] font-bold text-slate-500" x-text="`Trang ${safeCurrentPage}/${totalPages}`"></div>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="setPage(safeCurrentPage + 1)" :disabled="safeCurrentPage === totalPages" class="h-8 w-8 rounded border border-slate-200 bg-white disabled:opacity-40 flex items-center justify-center">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <button type="button" @click="setPage(totalPages)" :disabled="safeCurrentPage === totalPages" class="h-8 w-8 rounded border border-slate-200 bg-white disabled:opacity-40 flex items-center justify-center">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Resizer --}}
                <div class="hidden md:flex w-6 shrink-0 cursor-col-resize items-center justify-center group" @mousedown="startResize($event)">
                    <div class="w-1 h-12 bg-slate-200 rounded-full group-hover:bg-[var(--nttu-primary)]/50 transition-colors"></div>
                </div>

                {{-- Right: preview --}}
                <div class="flex-1 flex flex-col bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm h-full min-w-0"
                    :class="selectedDoc ? 'flex' : 'hidden md:flex'">
                    <template x-if="selectedDoc">
                        <div class="flex-1 flex flex-col h-full overflow-hidden">
                            <div class="px-4 md:px-6 py-4 border-b flex items-center justify-between bg-slate-50/30 shrink-0 gap-2">
                                <div class="flex items-center gap-3 min-w-0">
                                    <button type="button" class="md:hidden shrink-0" @click="closePreview()">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <div class="h-10 w-10 rounded-lg flex items-center justify-center shadow-inner shrink-0 border border-slate-100 bg-white">
                                        <div x-html="fileIconHtml(selectedDoc)"></div>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-base font-bold text-slate-800 truncate" x-text="selectedDoc.title"></h3>
                                        <p class="text-xs text-slate-500 truncate mt-0.5" x-text="fileDisplayName(selectedDoc)"></p>
                                        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 mt-1">
                                            <span class="truncate" x-text="selectedDoc.issuing_body"></span>
                                            <span class="text-slate-300 hidden md:inline">|</span>
                                            <span class="truncate" x-text="`Ký bởi: ${selectedDoc.signer || '—'}`"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0" x-show="!requiresPassword(selectedDoc) && selectedDoc.original_file">
                                    <button type="button" @click="fullPreview = true" class="h-9 w-9 rounded-md border border-slate-200 bg-white text-slate-500 hover:text-[var(--nttu-primary)] flex items-center justify-center" title="Xem toàn màn hình">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                    </button>
                                    <button type="button" @click="openExternal(previewFileUrl(selectedDoc))" class="h-9 w-9 rounded-md border border-slate-200 bg-white text-slate-500 hover:text-[var(--nttu-primary)] flex items-center justify-center" title="Mở tab mới">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </button>
                                    <button type="button" @click="openExternal(previewFileUrl(selectedDoc))" class="h-9 px-3 rounded-md border border-slate-200 bg-white text-xs font-bold gap-2 hidden md:inline-flex items-center">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        Tải về
                                    </button>
                                    <button type="button" @click="closePreview()" class="h-9 w-9 text-slate-400 hover:text-slate-600 flex items-center justify-center">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="flex-1 bg-slate-100/50 p-4 md:p-6 flex flex-col overflow-hidden min-h-0">
                                <template x-if="requiresPassword(selectedDoc)">
                                    <div class="flex-1 bg-white rounded-xl shadow-lg border border-red-200 flex flex-col items-center justify-center p-8 text-center">
                                        <div class="h-24 w-24 bg-red-50 rounded-full flex items-center justify-center text-red-500 mb-6 border border-red-100">
                                            <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-slate-800 mb-2">Tài liệu bảo mật</h3>
                                        <p class="text-sm text-slate-500 max-w-md mb-8">
                                            Đây là tài liệu thuộc mức độ <b x-text="selectedDoc.confidentiality"></b>.
                                            Vui lòng nhập mật khẩu được cung cấp để xem nội dung và tải về.
                                        </p>
                                        <div class="flex w-full max-w-sm gap-2">
                                            <input type="password" x-model="passwordInput" @keydown.enter="unlockPassword()"
                                                placeholder="Nhập mật khẩu truy cập..."
                                                class="flex-1 h-11 rounded-md border border-red-200 text-center tracking-widest">
                                            <button type="button" @click="unlockPassword()" class="h-11 px-6 rounded-md bg-red-600 hover:bg-red-700 text-white font-bold">Mở khóa</button>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!requiresPassword(selectedDoc) && selectedDoc.original_file">
                                    <div class="flex-1 bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden flex flex-col min-h-0">
                                        <template x-if="fileInfo(selectedDoc).isImage">
                                            <div class="flex-1 overflow-auto p-8 flex justify-center">
                                                <img :src="previewFileUrl(selectedDoc)" alt="Preview" class="max-w-full shadow-2xl rounded">
                                            </div>
                                        </template>
                                        <template x-if="!fileInfo(selectedDoc).isImage">
                                            <iframe :src="previewUrl(selectedDoc)" class="flex-1 w-full min-h-[400px] border-none" title="Xem trước văn bản"></iframe>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!requiresPassword(selectedDoc) && !selectedDoc.original_file">
                                    <div class="flex-1 flex flex-col items-center justify-center text-slate-400 gap-4">
                                        <p class="text-sm italic">Văn bản này không có file đính kèm</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                    <template x-if="!selectedDoc">
                        <div class="flex-1 flex flex-col items-center justify-center p-12 text-center bg-slate-50/30">
                            <h3 class="text-xl font-bold text-slate-800 mb-2">Chọn văn bản để xem nội dung</h3>
                            <p class="text-slate-500 max-w-sm">Chọn một mục từ danh sách bên trái để xem trước nội dung văn bản trực tiếp tại đây.</p>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    {{-- Fullscreen preview --}}
    <div x-show="fullPreview && selectedDoc" x-cloak class="fixed inset-0 z-[80] bg-black/50 flex items-center justify-center p-2 md:p-4" @click.self="fullPreview = false">
        <div class="bg-white w-[95vw] h-[92vh] rounded-lg shadow-2xl flex flex-col overflow-hidden">
            <div class="px-6 py-3 border-b flex items-center justify-between bg-slate-50 shrink-0">
                <h3 class="text-sm font-bold text-slate-800 truncate" x-text="selectedDoc?.title"></h3>
                <div class="flex items-center gap-2">
                    <button type="button" @click="openExternal(previewFileUrl(selectedDoc))" class="h-8 px-3 text-[11px] font-bold rounded border border-slate-200">Mở tab mới</button>
                    <button type="button" @click="fullPreview = false" class="h-8 w-8 flex items-center justify-center text-slate-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="flex-1 bg-slate-100 p-4 overflow-hidden">
                <iframe x-show="selectedDoc" :src="previewUrl(selectedDoc)" class="w-full h-full bg-white rounded-lg border border-slate-200" title="Xem toàn màn hình"></iframe>
            </div>
        </div>
    </div>
</div>
@endsection
