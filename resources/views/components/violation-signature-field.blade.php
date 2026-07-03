@props(['field'])

<div class="space-y-1" x-show="fieldVisible('{{ $field['key'] }}')" x-cloak>
    <label class="flex items-center gap-2 text-sm text-gray-500">
        <x-form-field-icon name="briefcase" tone="blue" />
        <span>{{ $field['label'] }}</span>
    </label>

    <div class="relative h-[100px] w-full overflow-hidden rounded-lg border-2 border-dashed transition-all"
         :class="form.signature_base64 ? 'border-[var(--nttu-primary)]/50 bg-cyan-50/30' : 'border-gray-300 hover:border-[var(--nttu-primary)]/30'"
         @click="!isViewMode && violationOpenSignature()">
        <template x-if="form.signature_base64">
            <img :src="form.signature_base64" alt="Chữ ký" class="h-full w-full object-contain p-2">
        </template>
        <template x-if="!form.signature_base64">
            <div class="flex h-full flex-col items-center justify-center gap-1 p-2 text-center text-gray-400">
                <svg class="h-5 w-5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                <span class="text-xs" x-text="isViewMode ? 'Chưa có chữ ký' : 'Nhấp để ký tên'"></span>
            </div>
        </template>
        <button type="button" x-show="form.signature_base64 && !isViewMode" @click.stop="violationClearSignature()"
                class="absolute right-1 top-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-white shadow">
            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div x-show="dialogMode !== 'add'" class="mt-2 flex items-center gap-2 rounded-md border border-slate-100 bg-white/50 px-2 py-1">
        <span class="text-[10px] font-bold uppercase tracking-tight text-slate-600">Trạng thái: <span x-text="form.signed || 'Chưa ký'"></span></span>
    </div>
</div>
