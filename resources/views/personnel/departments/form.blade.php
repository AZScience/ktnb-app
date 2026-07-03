@extends('layouts.nttu')

@section('title', $item ? 'Sửa đơn vị' : 'Thêm đơn vị')
@section('page-title', $item ? 'Sửa đơn vị' : 'Thêm đơn vị')

@section('content')
<div class="max-w-2xl bg-white rounded-lg border shadow-sm p-6">
    <form method="POST" action="{{ $item ? route('departments.update', $item) : route('departments.store') }}" class="space-y-4">
        @csrf @if($item) @method('PUT') @endif
        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-form-label>Mã đơn vị</x-form-label>
                <input name="department_id" value="{{ old('department_id', $item?->department_id) }}" class="w-full rounded-md border-gray-300 shadow-sm" required>
            </div>
            <div>
                <x-form-label>Tên đơn vị</x-form-label>
                <input name="name" value="{{ old('name', $item?->name) }}" class="w-full rounded-md border-gray-300 shadow-sm" required>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-form-label>Trưởng đơn vị</x-form-label>
                <input name="head" value="{{ old('head', $item?->head) }}" class="w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <x-form-label>Phó đơn vị</x-form-label>
                <input name="deputy_head" value="{{ old('deputy_head', $item?->deputy_head) }}" class="w-full rounded-md border-gray-300 shadow-sm">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-form-label>Điện thoại</x-form-label>
                <input name="phone" value="{{ old('phone', $item?->phone) }}" class="w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <x-form-label>Email</x-form-label>
                <input name="email" type="email" value="{{ old('email', $item?->email) }}" class="w-full rounded-md border-gray-300 shadow-sm">
            </div>
        </div>
        <div>
            <x-form-label>Ghi chú</x-form-label>
            <textarea name="note" rows="2" class="w-full rounded-md border-gray-300 shadow-sm">{{ old('note', $item?->note) }}</textarea>
        </div>
        <div class="flex gap-3">
            <x-form-actions cancel-href="{{ route('departments.index') }}" />
        </div>
    </form>
</div>
@endsection
