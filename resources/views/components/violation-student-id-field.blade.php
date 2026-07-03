@props(['field'])

<div class="space-y-1" x-show="fieldVisible('{{ $field['key'] }}')" x-cloak>
    <label class="flex items-center gap-2 text-sm text-gray-500">
        <x-form-field-icon name="id-card" tone="blue" />
        <span>{{ $field['label'] }}{{ !empty($field['required']) ? ' *' : '' }}</span>
    </label>

    <template x-if="fieldReadOnly('{{ $field['key'] }}')">
        <p class="font-mono text-sm font-bold text-slate-800" x-text="form.student_id || form.identifier || '---'"></p>
    </template>

    <template x-if="!fieldReadOnly('{{ $field['key'] }}')">
        <div class="relative flex items-center">
            <input type="text"
                   class="h-9 w-full rounded-md border-gray-300 pr-[4.5rem] font-mono text-sm shadow-sm transition-colors"
                   :class="{
                       'border-red-500 bg-red-50/50 focus:border-red-500 focus:ring-red-500': violationSearchStatus === 'error',
                       'border-green-500 bg-green-50/50 focus:border-green-500 focus:ring-green-500': violationSearchStatus === 'success'
                   }"
                   :value="form.student_id || form.identifier || ''"
                   @input="violationOnStudentIdInput($event.target.value)"
                   placeholder="MSSV hoặc CCCD..."
                   @keydown.enter.prevent="violationSearchStudent()">
            <div class="absolute right-0 flex items-center">
                <button type="button"
                        class="inline-flex h-9 w-9 items-center justify-center text-slate-400 hover:text-[var(--nttu-primary)]"
                        @click="violationStartScanner()"
                        title="Quét thẻ (TSV/CCCD)">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </button>
                <button type="button"
                        class="inline-flex h-9 w-9 items-center justify-center text-slate-400 hover:text-rose-500"
                        @click="violationSearchStudent()"
                        title="Tìm sinh viên">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>
            </div>
        </div>
    </template>
</div>
