@props(['action' => 'cancel'])

<x-nttu-button {{ $attributes->merge(['action' => $action, 'variant' => 'secondary']) }}>
    {{ $slot }}
</x-nttu-button>
