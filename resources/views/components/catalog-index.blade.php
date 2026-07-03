@props(['routePrefix', 'title', 'nameLabel' => 'Tên', 'items'])



<div class="nttu-card overflow-x-auto">

    <div class="flex flex-wrap justify-between items-center gap-3 px-4 py-3 border-b border-gray-100">

        <h2 class="text-lg font-semibold text-gray-900 i18n-auto">{{ $title }}</h2>

        <a href="{{ route($routePrefix.'.create') }}"

           class="inline-flex items-center gap-1.5 rounded-md bg-[var(--nttu-table-head)] px-3 py-1.5 text-sm font-medium text-white hover:opacity-90 transition-opacity">

            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>

            <span class="i18n-auto">Thêm mới</span>

        </a>

    </div>

    <table class="nttu-table min-w-full">

        <thead><tr>

            <th class="i18n-auto">{{ $nameLabel }}</th>

            <th class="i18n-auto">Ghi chú</th>

            <th class="w-28 text-center i18n-auto">Thao tác</th>

        </tr></thead>

        <tbody>

            @forelse ($items as $item)

                <tr>

                    <td class="font-medium">{{ $item->name }}</td>

                    <td class="text-gray-500">{{ $item->note ?: '---' }}</td>

                    <td class="text-center space-x-3">

                        <a href="{{ route($routePrefix.'.edit', $item) }}" class="inline-flex items-center gap-1 text-blue-600 hover:underline text-xs font-medium">

                            <x-form-field-icon name="edit" tone="blue" class="h-3.5 w-3.5" /> <span class="i18n-auto">Sửa</span>

                        </a>

                        <form action="{{ route($routePrefix.'.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm(window.t ? window.t('Xóa?') : 'Xóa?')">

                            @csrf @method('DELETE')

                            <button class="inline-flex items-center gap-1 text-red-600 hover:underline text-xs font-medium">

                                <x-form-field-icon name="trash" tone="destructive" class="h-3.5 w-3.5" /> <span class="i18n-auto">Xóa</span>

                            </button>

                        </form>

                    </td>

                </tr>

            @empty

                <tr><td colspan="3" class="px-4 py-12 text-center text-gray-500"><x-table-empty-state /></td></tr>

            @endforelse

        </tbody>

    </table>

</div>

<div class="mt-4">{{ $items->links() }}</div>

