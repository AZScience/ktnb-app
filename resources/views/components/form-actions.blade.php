@props([
    'saveLabel' => 'Lưu',
    'cancelLabel' => 'Hủy',
    'cancelHref' => null,
    'showCancel' => true,
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap gap-3']) }}>
    <x-nttu-button type="submit" action="save">{{ $saveLabel }}</x-nttu-button>
    @if ($showCancel)
        @if ($cancelHref)
            <x-nttu-button href="{{ $cancelHref }}" action="cancel">{{ $cancelLabel }}</x-nttu-button>
        @else
            <x-nttu-button type="button" action="cancel">{{ $cancelLabel }}</x-nttu-button>
        @endif
    @endif
    {{ $slot }}
</div>
