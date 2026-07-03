@props(['field', 'listId', 'placeholder' => 'Chọn hoặc nhập...', 'readonlyClass' => 'font-bold', 'onBuildingChange' => false])

<div @class(['space-y-1', $attributes->get('class')])>
    {{ $label ?? '' }}
    <template x-if="isClassFieldEditable">
        <input
            type="text"
            x-model="form.{{ $field }}"
            class="nttu-form-control"
            list="{{ $listId }}"
            placeholder="{{ $placeholder }}"
            @if($onBuildingChange) @change="form.room = ''" @endif
        >
    </template>
    <template x-if="!isClassFieldEditable">
        <p @class([$readonlyClass]) x-text="form.{{ $field }} || '—'"></p>
    </template>
</div>
