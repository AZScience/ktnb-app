@props(['from' => null, 'to' => null, 'date' => null, 'single' => false])

<form method="GET" class="flex flex-wrap items-end gap-3 mb-4 nttu-card p-4">
    @if ($single)
        <div>
            <x-filter-label class="mb-1">Ngày</x-filter-label>
            <input type="date" name="date" value="{{ request('date', date('Y-m-d')) }}"
                class="rounded-md border-gray-300 text-sm shadow-sm">
        </div>
    @else
        <div>
            <x-filter-label class="mb-1">Từ ngày</x-filter-label>
            <input type="date" name="from" value="{{ request('from', date('Y-m-d')) }}"
                class="rounded-md border-gray-300 text-sm shadow-sm">
        </div>
        <div>
            <x-filter-label class="mb-1">Đến ngày</x-filter-label>
            <input type="date" name="to" value="{{ request('to', date('Y-m-d')) }}"
                class="rounded-md border-gray-300 text-sm shadow-sm">
        </div>
    @endif
    <x-nttu-button type="submit" action="search">Xem báo cáo</x-nttu-button>
</form>
