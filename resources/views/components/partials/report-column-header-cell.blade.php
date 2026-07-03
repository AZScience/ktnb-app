@props(['column'])

@php
    $headerDisplay = $column['headerLabel'] ?? $column['label'] ?? '';
@endphp

<div x-data="{ col: @js($column) }">
    @include('components.partials.report-column-header', ['headerDisplay' => $headerDisplay])
</div>
