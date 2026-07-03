@php

    $columns = $config['columns'] ?? [];

    $beforeHandling = collect($columns)->filter(fn ($col) => ($col['group'] ?? '') === 'Tiếp nhận')->values();

    $handlingCols = collect($columns)->filter(fn ($col) => ($col['group'] ?? '') === 'Hướng xử lý')->values();

    $afterHandling = collect($columns)->filter(fn ($col) => ! in_array($col['group'] ?? '', ['Tiếp nhận', 'Hướng xử lý'], true))->values();

@endphp



{{-- Dòng 1: cột rowspan 2 + nhóm Hướng xử lý --}}

<tr x-show="headerMode === 'handling'" x-cloak class="report-thead-handling">

    <th rowspan="2" class="catalog-th-index w-[60px] border-b">#</th>

    @foreach ($beforeHandling as $column)

        <th rowspan="2"

            x-show="isColVisible(@js($column['key']))"

            class="catalog-th-col border-b {{ $column['width'] ?? '' }}"
            data-col="{{ $column['key'] ?? '' }}">

            @include('components.partials.report-column-header-cell', ['column' => $column])

        </th>

    @endforeach

    <th x-show="handlingColSpan > 0"

        :colspan="handlingColSpan"

        class="report-th-group-label text-center text-[11px] font-bold uppercase tracking-wider border-r border-b p-2 h-auto align-middle"

        x-text="groupLabelText('Hướng xử lý')"></th>

    @foreach ($afterHandling as $column)

        <th rowspan="2"

            x-show="isColVisible(@js($column['key']))"

            class="catalog-th-col border-b {{ $column['width'] ?? '' }}"
            data-col="{{ $column['key'] ?? '' }}">

            @include('components.partials.report-column-header-cell', ['column' => $column])

        </th>

    @endforeach

    <th rowspan="2" class="catalog-th-settings relative border-b">

        @include('components.partials.report-settings-column-inner', ['floatWhen' => "settingsOpen && headerMode === 'handling'"])

    </th>

</tr>



{{-- Dòng 2: cột con Hướng xử lý --}}

<tr x-show="headerMode === 'handling'" x-cloak class="report-thead-handling-sub">

    @foreach ($handlingCols as $column)

        <th x-show="isColVisible(@js($column['key']))"

            class="catalog-th-col border-r h-[40px] {{ $column['width'] ?? '' }}"
            data-col="{{ $column['key'] ?? '' }}">

            @include('components.partials.report-column-header-cell', ['column' => $column])

        </th>

    @endforeach

</tr>

