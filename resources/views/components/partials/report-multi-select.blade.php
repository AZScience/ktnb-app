@props([
    'field',
    'placeholder' => 'Chọn...',
    'emptyText' => 'Không tìm thấy',
])

<x-nttu-multi-select
    :field="$field"
    :placeholder="$placeholder"
    :empty-text="$emptyText"
    size="sm"
/>
