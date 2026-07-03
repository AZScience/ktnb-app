@extends('layouts.nttu')
@section('title', $item ? 'Sửa vai trò' : 'Thêm vai trò')
@section('page-title', $item ? 'Sửa vai trò' : 'Thêm vai trò')
@section('content')
<div class="max-w-lg bg-white rounded-lg border p-6">
    <form method="POST" action="{{ $item ? route('roles.update', $item) : route('roles.store') }}" class="space-y-4">
        @csrf @if($item) @method('PUT') @endif
        <div><x-form-label>Tên vai trò</x-form-label>
            <input name="name" value="{{ old('name', $item?->name) }}" class="w-full rounded-md border-gray-300 shadow-sm" required></div>
        <div><x-form-label>Mô tả</x-form-label>
            <textarea name="note" rows="3" class="w-full rounded-md border-gray-300 shadow-sm">{{ old('note', $item?->note) }}</textarea></div>
        <div class="flex gap-3">
            <x-form-actions cancel-href="{{ route('roles.index') }}" />
        </div>
    </form>
</div>
@endsection
