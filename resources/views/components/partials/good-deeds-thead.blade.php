@php

    $propertyCols = collect($config['tabs'] ?? [])->firstWhere('key', 'property')['columns'] ?? [];

    $deedCols = collect($config['tabs'] ?? [])->firstWhere('key', 'deed')['columns'] ?? [];

    $groupOrder = ['Tiếp nhận', 'Giao trả', 'Tri ân'];

    $groupShadeClass = [

        'Tiếp nhận' => '',

        'Giao trả' => 'report-th-group-return',

        'Tri ân' => '',

    ];

@endphp



{{-- Tab Tri ân: header 1 dòng --}}

<tr x-show="headerMode === 'flat'" x-cloak class="report-thead-flat">

    <th class="catalog-th-index w-[60px]">#</th>

    @foreach ($deedCols as $column)

        <th x-show="isColVisible(@js($column['key']))"

            class="catalog-th-col {{ $column['width'] ?? '' }}"
            data-col="{{ $column['key'] ?? '' }}">

            @include('components.partials.report-column-header-cell', ['column' => $column])

        </th>

    @endforeach

    <th class="catalog-th-settings relative">

        @include('components.partials.report-settings-column-inner', ['floatWhen' => "settingsOpen && headerMode === 'flat'"])

    </th>

</tr>



{{-- Tab Tiếp nhận tài sản: header dòng 1 --}}

<tr x-show="headerMode === 'grouped'" x-cloak class="report-thead-grouped">

    <th rowspan="2" class="catalog-th-index w-[50px] border-b">#</th>

    @foreach ($propertyCols as $column)

        @if (($column['group'] ?? '') === 'Chung')

            <th rowspan="2"

                x-show="isColVisible(@js($column['key']))"

                class="catalog-th-col border-b {{ $column['width'] ?? '' }}"
                data-col="{{ $column['key'] ?? '' }}">

                @include('components.partials.report-column-header-cell', ['column' => $column])

            </th>

        @endif

    @endforeach

    <th x-show="groupColSpan('Tiếp nhận') > 0"

        :colspan="groupColSpan('Tiếp nhận')"

        class="report-th-group-label report-th-group-reception text-center text-sm font-bold uppercase border-b p-2"

        x-text="groupLabelText('Tiếp nhận')"></th>

    <th x-show="groupColSpan('Giao trả') > 0"

        :colspan="groupColSpan('Giao trả')"

        class="report-th-group-label report-th-group-return text-center text-sm font-bold uppercase border-r border-b p-2"

        x-text="groupLabelText('Giao trả')"></th>

    <th x-show="groupColSpan('Tri ân') > 0"

        :colspan="groupColSpan('Tri ân')"

        class="report-th-group-label report-th-group-gratitude text-center text-sm font-bold uppercase border-b p-2"

        x-text="groupLabelText('Tri ân')"></th>

    <th rowspan="2" class="catalog-th-settings relative border-b">

        @include('components.partials.report-settings-column-inner', ['floatWhen' => "settingsOpen && headerMode === 'grouped'"])

    </th>

</tr>



{{-- Tab Tiếp nhận tài sản: header dòng 2 --}}

<tr x-show="headerMode === 'grouped'" x-cloak class="report-thead-grouped-sub">

    @foreach ($groupOrder as $group)

        @foreach (collect($propertyCols)->where('group', $group)->values() as $column)

            <th x-show="isColVisible(@js($column['key']))"

                class="catalog-th-col {{ $groupShadeClass[$group] ?? '' }} {{ $column['width'] ?? '' }} {{ ($column['key'] ?? '') !== 'note' ? 'border-r' : '' }}"
                data-col="{{ $column['key'] ?? '' }}">

                @include('components.partials.report-column-header-cell', ['column' => $column])

            </th>

        @endforeach

    @endforeach

</tr>

