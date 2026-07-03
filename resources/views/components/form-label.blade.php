@props([

    'icon' => null,

    'tone' => null,

    'required' => false,

])



@php

    $text = trim(strip_tags((string) $slot));

    $resolved = \App\Support\LabelIconResolver::resolve($text, $icon, $tone);

@endphp



<x-nttu-label {{ $attributes->merge(['class' => 'block mb-1']) }} :icon="$resolved['icon']" :tone="$resolved['tone']" :required="$required || str_contains($text, '*')">

    {{ $slot }}

</x-nttu-label>

