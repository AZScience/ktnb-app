@extends('layouts.nttu')
@section('title', 'Phân quyền — '.$role->name)
@section('page-section', 'Thiết lập hệ thống')
@section('page-title', 'Phân quyền: '.$role->name)
@section('content')

<form method="POST" action="{{ route('permissions.update', $role) }}" class="nttu-card overflow-hidden">
    @csrf @method('PUT')
    <div class="overflow-x-auto max-h-[70vh]">
        <table class="min-w-full text-xs">
            <thead class="bg-[#1877F2] text-white sticky top-0">
                <tr>
                    <th class="px-3 py-2 text-left min-w-[200px]">Module</th>
                    @foreach ($actions as $action => $label)
                        <th class="px-2 py-2 text-center whitespace-nowrap">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($modules as $moduleId => $moduleLabel)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2 font-medium">{{ $moduleLabel }}</td>
                        @foreach ($actions as $action => $actionLabel)
                            <td class="px-2 py-2 text-center">
                                <input type="checkbox"
                                    name="permissions[{{ $moduleId }}][{{ $action }}]"
                                    value="1"
                                    @checked($permissions[$moduleId][$action] ?? false)
                                    class="rounded border-gray-300 text-cyan-600">
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t flex gap-3">
        <button type="submit" class="rounded-md bg-[var(--nttu-table-head)] px-4 py-2 text-sm text-white inline-flex items-center gap-2">
            <x-form-field-icon name="save" tone="green" class="h-4 w-4 text-white" />
            Lưu phân quyền
        </button>
        <a href="{{ route('permissions.index') }}" class="rounded-md border px-4 py-2 text-sm">Quay lại</a>
    </div>
</form>
@endsection
