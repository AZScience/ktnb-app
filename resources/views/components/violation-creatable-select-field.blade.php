@props([
    'field',
    'listId',
    'placeholder' => 'Chọn hoặc nhập...',
])

@php
    $key = $field['key'];
    $icon = $field['icon'] ?? 'user';
    $iconTone = $field['iconTone'] ?? 'primary';
@endphp

<div class="space-y-1" x-show="fieldVisible('{{ $key }}')" x-cloak>
    <label class="flex items-center gap-2 text-sm text-gray-500">
        <x-form-field-icon :name="$icon" :tone="$iconTone" />
        <span>{{ $field['label'] }}{{ !empty($field['required']) ? ' *' : '' }}</span>
    </label>

    <template x-if="fieldReadOnly('{{ $key }}') || !violationStudentFieldEditable()">
        <p class="text-sm font-bold text-slate-800" x-text="form['{{ $key }}'] || '---'"></p>
    </template>

    <template x-if="!fieldReadOnly('{{ $key }}') && violationStudentFieldEditable()">
        <input type="text"
               class="nttu-form-control"
               x-model="form['{{ $key }}']"
               list="{{ $listId }}"
               placeholder="{{ $placeholder }}"
               @input="violationEnsureSelectOption('{{ $key }}', form['{{ $key }}'])">
    </template>

    <datalist id="{{ $listId }}">
        @foreach ($field['options'] ?? [] as $option)
            @if (($option['value'] ?? '') !== '')
                <option value="{{ $option['value'] }}"></option>
            @endif
        @endforeach
    </datalist>
</div>
