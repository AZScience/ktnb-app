@props([
    'icon' => null,
    'tone' => null,
    'required' => false,
])

@php
    $text = trim(strip_tags((string) $slot));
    $resolved = \App\Support\LabelIconResolver::resolve($text, $icon, $tone);
@endphp

<label {{ $attributes->merge(['class' => 'flex items-center gap-1.5 text-xs font-semibold text-gray-600']) }}>
    <x-form-field-icon :name="$resolved['icon']" :tone="$resolved['tone']" class="h-3.5 w-3.5" />
    <span class="sidebar-label i18n-auto">{{ $slot }}</span>
    @if ($required || str_contains($text, '*'))
        <span class="text-red-500">*</span>
    @endif
</label>
