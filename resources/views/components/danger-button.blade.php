@props(['action' => 'delete'])

<x-nttu-button {{ $attributes->merge(['type' => 'submit', 'action' => $action, 'variant' => 'danger']) }}>
    {{ $slot }}
</x-nttu-button>
