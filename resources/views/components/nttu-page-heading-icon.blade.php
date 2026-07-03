@props(['label', 'size' => 'lg'])

@php
    use App\Support\PageHeading;

    $meta = PageHeading::icon($label);
    $tone = $meta['tone'] ?? 'primary';
    $boxClass = match ($tone) {
        'sky' => 'bg-sky-50 border-sky-100',
        'indigo' => 'bg-indigo-50 border-indigo-100',
        'amber' => 'bg-amber-50 border-amber-100',
        'rose' => 'bg-rose-50 border-rose-100',
        'cyan' => 'bg-cyan-50 border-cyan-100',
        'fuchsia' => 'bg-fuchsia-50 border-fuchsia-100',
        'lime' => 'bg-lime-50 border-lime-100',
        'yellow' => 'bg-yellow-50 border-yellow-100',
        'pink' => 'bg-pink-50 border-pink-100',
        'purple' => 'bg-purple-50 border-purple-100',
        'teal' => 'bg-teal-50 border-teal-100',
        'green' => 'bg-green-50 border-green-100',
        'orange' => 'bg-orange-50 border-orange-100',
        'blue' => 'bg-blue-50 border-blue-100',
        'violet' => 'bg-violet-50 border-violet-100',
        'gray' => 'bg-gray-50 border-gray-200',
        'green' => 'bg-green-50 border-green-100',
        'primary' => 'bg-sky-50 border-sky-100',
        default => 'bg-sky-50 border-sky-100',
    };
    $sizeClass = match ($size) {
        'sm' => 'h-8 w-8 rounded-lg p-1.5',
        'md' => 'h-9 w-9 rounded-lg p-2',
        default => 'h-10 w-10 rounded-xl p-2',
    };
@endphp

<div {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center justify-center border shadow-sm {$boxClass} {$sizeClass}"]) }}>
    <x-form-field-icon :name="$meta['icon']" :tone="$tone" class="h-full w-full" />
</div>
