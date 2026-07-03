@extends('layouts.nttu')
@section('title', 'Nhận - Trả tài sản')
@section('page-title', 'Nhận - Trả tài sản')
@section('content')
<x-crud-header title="Sổ tiếp nhận tài sản" icon-name="folder" icon-tone="pink" :create-route="route('asset-receptions.create')" />
<div class="nttu-card overflow-x-auto">
    <table class="nttu-table min-w-full">
        <thead><tr>
            <th class="nttu-th-index w-16">#</th>
            <th>Số TN</th><th>Ngày</th><th>Người giao</th>
            <th>MSSV/ID</th><th>Lớp</th><th>Nội dung</th><th>Loại</th>
            <th>Trạng thái trả</th>
            <th class="w-24 text-center">Thao tác</th>
        </tr></thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="font-mono text-xs">{{ $item->entry_number ?: '—' }}</td>
                    <td>{{ $item->reception_date }}</td>
                    <td class="font-medium">{{ $item->giver_name }}</td>
                    <td class="text-xs">{{ $item->giver_id }}</td>
                    <td class="text-xs">{{ $item->giver_class }}</td>
                    <td>{{ Str::limit($item->content, 40) }}</td>
                    <td>{{ $item->asset_state }}</td>
                    <td>
                        @php $rs = $item->return_status; @endphp
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                            {{ $rs === 'Đã trả' ? 'bg-green-100 text-green-800' : ($rs === 'Chưa trả' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-700') }}">
                            {{ $rs ?: '—' }}
                        </span>
                    </td>
                    <td class="text-center space-x-2">
                        <a href="{{ route('asset-receptions.edit', $item) }}" class="inline-flex items-center gap-1 text-blue-600 text-xs hover:underline"><x-form-field-icon name="edit" tone="blue" class="h-3.5 w-3.5" /> Sửa</a>
                        <form action="{{ route('asset-receptions.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Xóa?')">@csrf @method('DELETE')<button class="inline-flex items-center gap-1 text-red-600 text-xs hover:underline"><x-form-field-icon name="trash" tone="destructive" class="h-3.5 w-3.5" /> Xóa</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="px-4 py-12 text-center text-gray-500"><x-table-empty-state /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
