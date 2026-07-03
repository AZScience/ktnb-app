@props([
    'action' => null,
    'icon' => null,
    'tone' => null,
    'variant' => 'outline',
    'size' => 'md',
])

@php
    $actionMap = [
        'save' => ['icon' => 'save', 'tone' => 'green', 'variant' => 'primary'],
        'confirm' => ['icon' => 'check', 'tone' => 'green', 'variant' => 'primary'],
        'cancel' => ['icon' => 'x', 'tone' => 'destructive', 'variant' => 'outline'],
        'undo' => ['icon' => 'undo', 'tone' => 'blue', 'variant' => 'outline'],
        'close' => ['icon' => 'x', 'tone' => 'destructive', 'variant' => 'ghost'],
        'delete' => ['icon' => 'trash', 'tone' => 'destructive', 'variant' => 'danger'],
        'add' => ['icon' => 'plus', 'tone' => 'primary', 'variant' => 'primary'],
        'filter' => ['icon' => 'filter', 'tone' => 'orange', 'variant' => 'outline'],
        'import' => ['icon' => 'upload', 'tone' => 'blue', 'variant' => 'outline'],
        'export' => ['icon' => 'download', 'tone' => 'green', 'variant' => 'outline'],
        'search' => ['icon' => 'search', 'tone' => 'blue', 'variant' => 'primary'],
        'send' => ['icon' => 'send', 'tone' => 'primary', 'variant' => 'primary'],
        'view' => ['icon' => 'eye', 'tone' => 'blue', 'variant' => 'ghost'],
        'edit' => ['icon' => 'edit', 'tone' => 'amber', 'variant' => 'ghost'],
        'copy' => ['icon' => 'copy', 'tone' => 'cyan', 'variant' => 'ghost'],
        'refresh' => ['icon' => 'refresh', 'tone' => 'blue', 'variant' => 'outline'],
        'login' => ['icon' => 'log-in', 'tone' => 'primary', 'variant' => 'primary'],
        'logout' => ['icon' => 'log-out', 'tone' => 'destructive', 'variant' => 'outline'],
        'apply' => ['icon' => 'check', 'tone' => 'green', 'variant' => 'primary'],
        'clear' => ['icon' => 'x', 'tone' => 'destructive', 'variant' => 'outline'],
        'create' => ['icon' => 'plus', 'tone' => 'primary', 'variant' => 'primary'],
        'restore' => ['icon' => 'refresh', 'tone' => 'green', 'variant' => 'primary'],
        'gps' => ['icon' => 'map-pin', 'tone' => 'blue', 'variant' => 'outline'],
        'find' => ['icon' => 'search', 'tone' => 'blue', 'variant' => 'primary'],
    ];

    if ($action && isset($actionMap[$action])) {
        $icon = $icon ?? $actionMap[$action]['icon'];
        $tone = $tone ?? $actionMap[$action]['tone'];
        if (! $attributes->has('variant')) {
            $variant = $actionMap[$action]['variant'];
        }
    }

    $icon = $icon ?? 'tag';
    $tone = $tone ?? 'primary';

    $sizeClass = match ($size) {
        'sm' => 'px-3 py-1.5 text-xs gap-1.5',
        'lg' => 'px-5 py-2.5 text-sm gap-2',
        'icon' => 'p-2',
        default => 'px-4 py-2 text-sm gap-2',
    };

    $variantClass = match ($variant) {
        'primary' => 'bg-[var(--nttu-table-head)] text-white border border-transparent hover:opacity-90 disabled:opacity-50',
        'danger' => 'bg-red-600 text-white border border-transparent hover:bg-red-700 disabled:opacity-50',
        'secondary' => 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50',
        'ghost' => 'bg-transparent text-gray-600 border border-transparent hover:bg-slate-100',
        default => 'bg-white text-gray-700 border border-slate-200 hover:bg-slate-50 disabled:opacity-50',
    };

    $iconOnPrimary = in_array($variant, ['primary', 'danger'], true);
    $iconClass = $iconOnPrimary ? 'text-white' : null;
    $tag = $attributes->has('href') ? 'a' : 'button';
    $baseClass = "inline-flex items-center justify-center rounded-md font-medium transition focus:outline-none focus:ring-2 focus:ring-[var(--nttu-primary)]/30 {$sizeClass} {$variantClass}";
@endphp

<{{ $tag }} {{ $attributes->merge([
    'type' => $tag === 'button' ? ($attributes->get('type', 'button')) : null,
    'class' => $baseClass,
]) }}>
    @if ($size !== 'icon' || $slot->isEmpty())
        <x-form-field-icon :name="$icon" :tone="$tone" class="h-4 w-4 {{ $iconClass }}" />
    @endif
    @if ($slot->isNotEmpty())
        <span class="i18n-auto">{{ $slot }}</span>
    @endif
</{{ $tag }}>
