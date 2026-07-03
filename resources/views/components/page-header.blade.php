@props(['title', 'description' => null, 'iconName' => null, 'iconTone' => null])

@php
    use App\Support\PageHeading;

    $meta = PageHeading::icon($title);
    $resolvedIcon = $iconName ?? $meta['icon'];
    $resolvedTone = $iconTone ?? $meta['tone'];
@endphp

<div {{ $attributes->merge(['class' => 'mb-4']) }}>
    <div class="flex items-center gap-3">
        @isset($icon)
            <div class="shrink-0">{{ $icon }}</div>
        @else
            <x-nttu-page-heading-icon :label="$title" />
        @endisset
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 i18n-auto">{{ $title }}</h1>
            @if($description)
                <p class="mt-1 text-sm text-gray-500 i18n-auto">{{ $description }}</p>
            @endif
        </div>
    </div>
</div>
