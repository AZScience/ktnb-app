@extends('layouts.nttu')
@section('title', $item ? 'Sửa' : 'Thêm')
@section('page-title', $item ? 'Sửa việc phát sinh' : 'Thêm việc phát sinh')
@section('content')
<div class="max-w-lg bg-white rounded-lg border p-6">
    <form method="POST" action="{{ $item ? route('incident-categories.update', $item) : route('incident-categories.store') }}" class="space-y-4">
        @csrf @if($item) @method('PUT') @endif
        <div><x-form-label>Tên việc phát sinh</x-form-label><input name="name" value="{{ old('name', $item?->name) }}" class="nttu-form-control" required></div>
        <div><x-form-label>Nhóm việc ghi nhận</x-form-label>
            <select name="recognition_id" class="nttu-form-control" required>
                @foreach ($recognitions as $r)
                    <option value="{{ $r->id }}" @selected(old('recognition_id', $item?->recognition_id) === $r->id)>{{ $r->name }}</option>
                @endforeach
            </select></div>
        <div><x-form-label>Ghi chú</x-form-label><textarea name="note" rows="2" class="nttu-form-control">{{ old('note', $item?->note) }}</textarea></div>
        <x-form-actions cancel-href="{{ route('incident-categories.index') }}" />
    </form>
</div>
@endsection
