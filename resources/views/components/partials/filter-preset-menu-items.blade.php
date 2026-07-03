<div x-show="filterPresets.length === 0" class="px-3 py-4 text-center text-[10px] italic text-gray-400">Chưa có bộ lọc nào</div>
<template x-for="preset in filterPresets" :key="preset.name">
    <div class="flex items-center px-1 group hover:bg-slate-50">
        <div x-show="presetRenaming === preset.name" class="flex flex-1 items-center gap-1 px-1 py-1 min-w-0">
            <input type="text" x-model="renamingPresetValue" class="h-7 flex-1 min-w-0 rounded border-gray-300 text-xs px-2"
                @keydown.enter.prevent="renameFilterPreset()"
                @keydown.escape.prevent="cancelRenameFilterPreset()">
            <button type="button" class="shrink-0 px-1.5 text-green-600 hover:text-green-700 disabled:opacity-50" :disabled="!!savingPresets" @click="renameFilterPreset()" title="Lưu">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </button>
            <button type="button" class="shrink-0 px-1.5 text-gray-500 hover:text-gray-700 disabled:opacity-50" :disabled="!!savingPresets" @click="cancelRenameFilterPreset()" title="Hủy">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div x-show="presetRenaming !== preset.name" class="flex flex-1 items-center min-w-0">
            <button type="button" class="flex-1 min-w-0 px-3 py-1.5 text-left truncate" @click="applyFilterPreset(preset)" x-text="preset.name"></button>
            <button type="button" class="shrink-0 px-2 py-1 text-amber-600 opacity-0 group-hover:opacity-100 hover:text-amber-700 disabled:opacity-50" :disabled="!!savingPresets" @click.stop="startRenameFilterPreset(preset.name)" title="Đổi tên">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </button>
            <button type="button" class="shrink-0 px-2 py-1 text-red-500 opacity-0 group-hover:opacity-100 hover:text-red-700 disabled:opacity-50" :disabled="!!savingPresets" @click.stop="deleteFilterPreset(preset.name)" title="Xóa">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </div>
    </div>
</template>
