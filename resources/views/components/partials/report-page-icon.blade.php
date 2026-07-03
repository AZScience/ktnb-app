@props(['name', 'class' => 'h-8 w-8'])

@php
    $icons = [
        'file-search' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 10l2 2"/>',
        'shield-alert' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M4.93 4.93l14.14 14.14M10.29 3.86L3.82 16.18A2 2 0 005.17 19h13.66a2 2 0 001.35-2.82L13.71 3.86a2 2 0 00-3.42 0z"/>',
        'heart-handshake' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8h2a2 2 0 012 2v1"/>',
        'mail-question' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13v3"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 17h.01"/>',
    ];
    $path = $icons[$name] ?? $icons['file-search'];
@endphp

<svg {{ $attributes->merge(['class' => $class]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $path !!}</svg>
