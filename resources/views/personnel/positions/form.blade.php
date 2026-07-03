@extends('layouts.nttu')
@section('title', $item ? 'Sửa chức vụ' : 'Thêm chức vụ')
@section('page-title', $item ? 'Sửa chức vụ' : 'Thêm chức vụ')
@section('content')
<div class="max-w-lg bg-white rounded-lg border p-6">
    <form method="POST" action="{{ $item ? route('positions.update', $item) : route('positions.store') }}" class="space-y-4">
        @csrf @if($item) @method('PUT') @endif
        <div><x-form-label>Tên chức vụ</x-form-label>
            <input name="name" value="{{ old('name', $item?->name) }}" class="w-full rounded-md border-gray-300 shadow-sm" required></div>
        <div><x-form-label>Ghi chú</x-form-label>
            <textarea name="note" rows="2" class="w-full rounded-md border-gray-300 shadow-sm">{{ old('note', $item?->note) }}</textarea></div>
        <div class="flex gap-3">
            <x-form-actions cancel-href="{{ route('positions.index') }}" />
        </div>
    </form>
</div>
@endsection
