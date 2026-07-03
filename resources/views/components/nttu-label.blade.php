@props([
    'icon' => 'tag',
    'tone' => 'primary',
    'required' => false,
])

<label {{ $attributes->merge(['class' => 'flex items-center gap-2 text-sm font-medium text-gray-700']) }}>
    <x-form-field-icon :name="$icon" :tone="$tone" class="h-4 w-4" />
    <span class="i18n-auto">{{ $slot }}</span>
    @if ($required)
        <span class="text-red-500">*</span>
    @endif
</label>
