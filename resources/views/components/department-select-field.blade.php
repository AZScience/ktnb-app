@props(['field'])

@php
    $key = $field['key'];
    $icon = $field['icon'] ?? 'landmark';
    $iconTone = $field['iconTone'] ?? 'primary';
    $placeholder = $field['placeholder'] ?? 'Chọn khoa/đơn vị...';
@endphp

<div class="space-y-1" x-show="fieldVisible('{{ $key }}')" x-cloak>
    <label class="flex items-center gap-2 text-sm text-gray-500">
        <x-form-field-icon :name="$icon" :tone="$iconTone" />
        <span>
            <span x-text="text(@js($field['label']))"></span>@if (!empty($field['required'])) *@endif
        </span>
    </label>

    <template x-if="fieldReadOnly('{{ $key }}')">
        <p class="text-sm font-bold text-slate-800" x-text="fieldDisplayValue('{{ $key }}')"></p>
    </template>

    <template x-if="!fieldReadOnly('{{ $key }}')">
        <select class="nttu-form-control"
                x-model="form['{{ $key }}']"
                @change="ensureDepartmentField('{{ $key }}')"
                @if (!empty($field['required'])) required @endif>
            <option value="">{{ $placeholder }}</option>
            <template x-for="opt in departmentOptions" :key="'dept-{{ $key }}-' + opt.value">
                <option :value="opt.value" x-text="opt.label"></option>
            </template>
        </select>
    </template>
</div>
