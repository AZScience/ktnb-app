@props([
    'selectAll' => 'selectAllColumns()',
    'deselectAll' => 'deselectAllColumns()',
])

<div class="nttu-column-settings-bulk-actions flex gap-1 border-b border-gray-100 px-2 py-1.5">
    <button type="button" @click.stop="{{ $selectAll }}"
        class="nttu-column-settings-bulk-select flex-1 rounded px-2 py-1 text-[11px] font-semibold hover:bg-blue-50">
        <span data-i18n="Chọn tất cả">Chọn tất cả</span>
    </button>
    <button type="button" @click.stop="{{ $deselectAll }}"
        class="nttu-column-settings-bulk-deselect flex-1 rounded px-2 py-1 text-[11px] font-medium hover:bg-slate-50">
        <span data-i18n="Bỏ chọn">Bỏ chọn</span>
    </button>
</div>
