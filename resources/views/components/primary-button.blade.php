@props(['action' => 'save', 'icon' => null])

<x-nttu-button {{ $attributes->merge(['type' => 'submit', 'action' => $action, 'variant' => 'primary']) }}>
    {{ $slot }}
</x-nttu-button>
