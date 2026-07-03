@props(['value', 'icon' => 'tag', 'tone' => 'primary', 'required' => false])

<x-nttu-label :icon="$icon" :tone="$tone" :required="$required" {{ $attributes }}>
    {{ $value ?? $slot }}
</x-nttu-label>
