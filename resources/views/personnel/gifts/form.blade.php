@extends('layouts.nttu')
@section('title', $item ? 'Sửa quà tặng' : 'Thêm quà tặng')
@section('page-title', $item ? 'Sửa quà tặng' : 'Thêm quà tặng')
@section('content')
<div class="max-w-lg bg-white rounded-lg border p-6">
    <form method="POST" action="{{ $item ? route('gifts.update', $item) : route('gifts.store') }}" class="space-y-4">
        @csrf @if($item) @method('PUT') @endif
        <div><x-form-label>Tên quà</x-form-label><input name="name" value="{{ old('name', $item?->name) }}" class="w-full rounded-md border-gray-300 shadow-sm" required></div>
        <div><x-form-label>Ghi chú</x-form-label><textarea name="note" rows="2" class="w-full rounded-md border-gray-300 shadow-sm">{{ old('note', $item?->note) }}</textarea></div>
        <x-form-actions cancel-href="{{ route('gifts.index') }}" />
    </form>
</div>
@endsection
