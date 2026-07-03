@extends('layouts.nttu')
@section('title', 'Quản lý Minh chứng')
@section('page-section', 'Công cụ hỗ trợ')
@section('page-title', 'Quản lý Minh chứng')
@section('page-description', 'Kho lưu trữ tập trung tất cả hình ảnh và tài liệu minh chứng từ các hoạt động giám sát.')
@section('content')
<div x-data="evidenceManager(@js($pageConfig))" class="space-y-6">
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-end">
    <div class="flex items-center gap-1 self-start rounded-lg border bg-white p-1 shadow-sm">
      <button type="button" @click="viewMode = 'grid'" class="inline-flex h-8 items-center gap-1.5 rounded-md px-3 text-xs font-semibold"
        :class="viewMode === 'grid' ? 'bg-[var(--nttu-primary)] text-white' : 'text-gray-600 hover:bg-slate-50'">Lưới</button>
      <button type="button" @click="viewMode = 'list'" class="inline-flex h-8 items-center gap-1.5 rounded-md px-3 text-xs font-semibold"
        :class="viewMode === 'list' ? 'bg-[var(--nttu-primary)] text-white' : 'text-gray-600 hover:bg-slate-50'">Danh sách</button>
    </div>
  </div>

  <div class="nttu-card p-4 shadow-sm">
    <div class="flex flex-wrap items-center gap-3">
      <div class="relative min-w-[200px] flex-1">
        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" x-model="searchTerm" @input="currentPage = 1" placeholder="Tìm kiếm theo lớp, phòng, người gửi..." class="h-10 w-full rounded-md border border-slate-200 pl-9 text-sm">
      </div>
      <select x-model="filterSource" @change="currentPage = 1" class="h-10 rounded-md border border-slate-200 px-3 text-sm">
        <template x-for="opt in sourceOptions" :key="opt.value">
          <option :value="opt.value" x-text="opt.label"></option>
        </template>
      </select>
      <select x-model="filterType" @change="currentPage = 1" class="h-10 rounded-md border border-slate-200 px-3 text-sm">
        <option value="all">Tất cả loại file</option>
        <option value="image">Hình ảnh</option>
        <option value="video">Video</option>
        <option value="document">Tài liệu</option>
      </select>
      <input type="date" x-model="filterDate" @change="currentPage = 1" class="h-10 rounded-md border border-slate-200 px-3 text-sm">
      <button type="button" x-show="hasActiveFilters" @click="clearFilters()" class="text-sm font-medium text-red-600 hover:bg-red-50 rounded-md px-2 h-10">Xóa lọc</button>
      <div class="ml-auto rounded-full bg-slate-100 px-3 py-1.5 text-sm font-medium text-gray-600">
        Tổng cộng: <span class="font-bold text-blue-600" x-text="filteredItems.length"></span> minh chứng
      </div>
    </div>
  </div>

  <template x-if="loading">
    <div class="nttu-card border-dashed p-12 text-center text-gray-500">
      <x-table-empty-state loading="true" icon="inbox" />
    </div>
  </template>

  <template x-if="!loading && filteredItems.length === 0">
    <div class="nttu-card border-dashed p-12 text-center text-gray-500">
      <x-table-empty-state
        icon="inbox"
        filters-active="hasActiveFilters"
        clear-action="clearFilters()"
      />
    </div>
  </template>

  <template x-if="!loading && filteredItems.length > 0 && viewMode === 'grid'">
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <template x-for="item in paginatedItems" :key="item.id">
        <div @click="openDetail(item)" class="nttu-card group cursor-pointer overflow-hidden border border-slate-200 transition hover:border-blue-400 hover:shadow-lg">
          <div class="relative aspect-video overflow-hidden bg-slate-100">
            <template x-if="isImage(item.items[0])">
              <img :src="thumbUrl(item)" :alt="item.title" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            </template>
            <template x-if="!isImage(item.items[0])">
              <div class="flex h-full flex-col items-center justify-center p-6 text-slate-400">
                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p class="mt-2 max-w-full truncate font-mono text-[10px]" x-text="parseItem(item.items[0]).name || 'Tệp đính kèm'"></p>
              </div>
            </template>
            <span class="absolute left-2 top-2 rounded px-2 py-0.5 text-[10px] font-bold text-white shadow-sm" :class="badgeClass(item.source)" x-text="item.source_label"></span>
            <span x-show="item.items.length > 1" class="absolute bottom-2 right-2 rounded bg-black/60 px-2 py-0.5 text-[10px] text-white backdrop-blur" x-text="'+' + (item.items.length - 1) + ' ảnh'"></span>
          </div>
          <div class="space-y-2 p-4">
            <h3 class="line-clamp-1 text-sm font-bold text-slate-800 group-hover:text-blue-600" x-text="item.title"></h3>
            <p class="line-clamp-2 min-h-[2rem] text-xs leading-relaxed text-slate-500" x-text="item.description"></p>
            <div class="flex items-center justify-between border-t border-slate-50 pt-2">
              <span class="text-[10px] font-medium text-slate-400" x-text="item.submitted_by_name"></span>
              <div class="flex items-center gap-1">
                <button type="button" x-show="canDelete" @click="confirmDelete(item, $event)" class="rounded p-1 text-slate-400 hover:bg-red-50 hover:text-red-600">
                  <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
                <span class="text-[10px] text-slate-400" x-text="item.date_str"></span>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </template>

  <template x-if="!loading && filteredItems.length > 0 && viewMode === 'list'">
    <div class="nttu-card overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <table class="nttu-table w-full text-sm">
          <thead><tr>
            <th class="w-20">Xem</th><th class="w-44">Phân loại</th><th>Đối tượng / Nội dung</th><th class="w-36">Người cập nhật</th><th class="w-28">Ngày</th><th class="w-24 text-right">Tác vụ</th>
          </tr></thead>
          <tbody>
            <template x-for="item in paginatedItems" :key="item.id">
              <tr @click="openDetail(item)" class="cursor-pointer hover:bg-blue-50">
                <td><div class="h-10 w-12 overflow-hidden rounded border bg-slate-100">
                  <img x-show="isImage(item.items[0])" :src="thumbUrl(item)" class="h-full w-full object-cover" alt="">
                </div></td>
                <td><span class="rounded border px-2 py-0.5 text-[10px] font-bold" :class="badgeClass(item.source) + ' text-white'" x-text="item.source_label"></span></td>
                <td><div class="font-bold" x-text="item.title"></div><div class="truncate text-xs text-gray-500" x-text="item.description"></div></td>
                <td class="text-xs" x-text="item.submitted_by_name"></td>
                <td class="text-xs text-gray-500" x-text="item.date_str"></td>
                <td class="text-right">
                  <button type="button" x-show="canDelete" @click="confirmDelete(item, $event)" class="rounded p-1 text-slate-400 hover:text-red-600"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>
  </template>

  <div x-show="totalPages > 1" x-cloak class="flex items-center justify-between rounded-xl border bg-white p-4 shadow-sm">
    <p class="hidden text-sm text-gray-500 sm:block">Trang <span class="font-bold" x-text="currentPage"></span> / <span x-text="totalPages"></span></p>
    <div class="mx-auto flex items-center gap-2 sm:mx-0">
      <button type="button" @click="setPage(1)" :disabled="currentPage === 1" class="rounded border px-2 py-1 text-sm disabled:opacity-40">«</button>
      <button type="button" @click="setPage(currentPage - 1)" :disabled="currentPage === 1" class="rounded border px-2 py-1 text-sm disabled:opacity-40">‹</button>
      <button type="button" @click="setPage(currentPage + 1)" :disabled="currentPage === totalPages" class="rounded border px-2 py-1 text-sm disabled:opacity-40">›</button>
      <button type="button" @click="setPage(totalPages)" :disabled="currentPage === totalPages" class="rounded border px-2 py-1 text-sm disabled:opacity-40">»</button>
    </div>
  </div>

  {{-- Detail dialog --}}
  <div x-show="selectedEvidence" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" @click="closeDetail()"></div>
    <div class="relative flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-lg bg-slate-50 shadow-2xl" @click.stop>
      <div class="shrink-0 border-b bg-white p-6">
        <div class="flex flex-wrap items-center gap-2">
          <span class="rounded px-2 py-0.5 text-[10px] font-bold text-white" :class="badgeClass(selectedEvidence?.source)" x-text="selectedEvidence?.source_label"></span>
          <span class="rounded border bg-white px-2 py-0.5 text-[10px]" x-text="(selectedEvidence?.items?.length || 0) + ' minh chứng'"></span>
        </div>
        <h2 class="mt-2 text-2xl font-bold text-slate-800" x-text="selectedEvidence?.title"></h2>
        <p class="text-sm text-slate-500">Cập nhật vào <span x-text="selectedEvidence?.date_str"></span> bởi <span x-text="selectedEvidence?.submitted_by_name"></span></p>
      </div>
      <div class="flex min-h-0 flex-1 flex-col md:flex-row">
        <div class="flex-1 overflow-y-auto p-6">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <template x-for="(file, idx) in selectedEvidence?.items || []" :key="idx">
              <div @click="openPreview(file)" class="cursor-zoom-in overflow-hidden rounded-xl border bg-white p-2 shadow-sm hover:shadow-md">
                <template x-if="isImage(file)">
                  <img :src="previewUrl(file)" class="aspect-[4/3] w-full rounded-lg object-cover" alt="">
                </template>
                <template x-if="!isImage(file)">
                  <div class="flex aspect-[4/3] flex-col items-center justify-center gap-2 rounded-lg bg-slate-50 text-slate-400">
                    <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                  </div>
                </template>
                <div class="mt-2 flex items-center justify-between px-1">
                  <span class="truncate text-[10px] font-bold text-slate-500" x-text="parseItem(file).name || ('Minh chứng #' + (idx + 1))"></span>
                  <a :href="previewUrl(file)" download class="text-blue-600" @click.stop>↓</a>
                </div>
              </div>
            </template>
          </div>
        </div>
        <div class="w-full shrink-0 space-y-4 overflow-y-auto border-l bg-white p-6 md:w-80">
          <div>
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Mô tả / Sự cố</p>
            <p class="mt-2 rounded-lg border bg-slate-50 p-3 text-sm italic text-slate-700" x-text="'&quot;' + (selectedEvidence?.description || '') + '&quot;'"></p>
          </div>
          <template x-if="selectedEvidence?.location?.latitude">
            <div>
              <p class="text-[10px] font-bold uppercase text-slate-400">Tọa độ xác thực</p>
              <p class="mt-1 font-mono text-[11px]" x-text="selectedEvidence.location.latitude.toFixed(6) + ', ' + selectedEvidence.location.longitude.toFixed(6)"></p>
              <a :href="mapsUrl(selectedEvidence.location)" target="_blank" rel="noreferrer" class="text-xs text-blue-600 hover:underline">Mở bản đồ vệ tinh</a>
            </div>
          </template>
          <a :href="previewUrl(selectedEvidence?.items?.[0])" target="_blank" rel="noreferrer" class="block w-full rounded-md bg-blue-600 py-2 text-center text-sm font-medium text-white hover:bg-blue-700">Mở minh chứng gốc</a>
          <button type="button" @click="closeDetail()" class="w-full rounded-md border py-2 text-sm">Đóng chi tiết</button>
        </div>
      </div>
    </div>
  </div>

  {{-- Lightbox --}}
  <div x-show="previewItem" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/90 p-4" @keydown.escape.window="closePreview()">
    <button type="button" @click="closePreview()" class="absolute right-4 top-4 rounded-full p-2 text-white/80 hover:bg-white/10">×</button>
    <template x-if="previewItem && isImage(previewItem)">
      <img :src="previewUrl(previewItem)" class="max-h-[90vh] max-w-full object-contain" alt="">
    </template>
  </div>

  {{-- Delete confirm --}}
  <div x-show="deleteTarget" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" @click="deleteTarget = null"></div>
    <div class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
      <h3 class="text-lg font-bold text-red-600">Xác nhận xóa minh chứng</h3>
      <p class="mt-2 text-sm text-gray-600">Bạn có chắc muốn xóa tất cả minh chứng của <strong x-text="deleteTarget?.title"></strong>? Hành động này không thể hoàn tác.</p>
      <div class="mt-4 flex justify-end gap-2">
        <x-nttu-button type="button" action="cancel" @click="deleteTarget = null">Hủy</x-nttu-button>
        <x-nttu-button type="button" action="delete" x-bind:disabled="deleting" @click="handleDelete()">Xác nhận xóa</x-nttu-button>
      </div>
    </div>
  </div>
</div>
@endsection
