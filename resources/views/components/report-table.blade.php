@props(['headers', 'rows', 'exportUrl' => null])



<div class="nttu-card">

    <div class="py-3 px-4 border-b flex justify-between items-center">

        <p class="text-sm text-gray-600"><span class="i18n-auto">Tổng:</span> <strong>{{ count($rows) }}</strong> <span class="i18n-auto">bản ghi</span></p>

        @if ($exportUrl)

            <a href="{{ $exportUrl }}" class="inline-flex items-center gap-1.5 rounded-md border border-green-200 bg-green-50 px-3 py-1.5 text-sm text-green-700">

                <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>

                <span class="i18n-auto">Xuất Excel</span>

            </a>

        @endif

    </div>

    <div class="overflow-x-auto">

        <table class="nttu-table min-w-full">

            <thead>

                <tr>

                    <th class="nttu-th-index w-12">#</th>

                    @foreach ($headers as $label)

                        <th class="i18n-auto">{{ $label }}</th>

                    @endforeach

                </tr>

            </thead>

            <tbody>

                @forelse ($rows as $idx => $row)

                    <tr>

                        <td class="text-center">{{ $idx + 1 }}</td>

                        @foreach (array_keys($headers) as $key)

                            <td>

                                @php $val = data_get($row, $key); @endphp

                                @if (is_bool($val))

                                    <span class="i18n-auto">{{ $val ? 'Có' : 'Không' }}</span>

                                @else

                                    {{ $val ?? '—' }}

                                @endif

                            </td>

                        @endforeach

                    </tr>

                @empty

                    <tr>

                        <td colspan="{{ count($headers) + 1 }}" class="py-12 text-center text-gray-500">

                            <x-table-empty-state />

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

