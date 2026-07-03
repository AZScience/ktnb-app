@extends('layouts.nttu')
@section('title', $item ? 'Sửa' : 'Thêm')
@section('page-title', $item ? 'Sửa việc ghi nhận' : 'Thêm việc ghi nhận')
@section('content')
<div class="max-w-lg bg-white rounded-lg border p-6">
    <form method="POST" action="{{ $item ? route('recognitions.update', $item) : route('recognitions.store') }}" class="space-y-4">
        @csrf @if($item) @method('PUT') @endif
        <div><x-form-label>Tên</x-form-label><input name="name" value="{{ old('name', $item?->name) }}" class="w-full rounded-md border-gray-300 shadow-sm" required></div>
        <div><x-form-label>Ghi chú</x-form-label><textarea name="note" rows="2" class="w-full rounded-md border-gray-300 shadow-sm">{{ old('note', $item?->note) }}</textarea></div>
        <x-form-actions cancel-href="{{ route('recognitions.index') }}" />
    </form>
</div>
@endsection
