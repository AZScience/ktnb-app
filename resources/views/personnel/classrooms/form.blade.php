@extends('layouts.nttu')
@section('title', $item ? 'Sửa phòng học' : 'Thêm phòng học')
@section('page-title', $item ? 'Sửa phòng học' : 'Thêm phòng học')
@section('content')
<div class="max-w-2xl bg-white rounded-lg border p-6">
    <form method="POST" action="{{ $item ? route('classrooms.update', $item) : route('classrooms.store') }}" class="space-y-4">
        @csrf @if($item) @method('PUT') @endif
        @if (!$item)
        <div><x-form-label>Mã phòng (VD: A.801)</x-form-label>
            <input name="id" value="{{ old('id') }}" class="nttu-form-control" required></div>
        @endif
        <div class="grid grid-cols-2 gap-4">
            <div><x-form-label>Tên phòng</x-form-label>
                <input name="name" value="{{ old('name', $item?->name) }}" class="nttu-form-control" required></div>
            <div><x-form-label>Dãy nhà</x-form-label>
                <select name="building_block_id" class="nttu-form-control" required>
                    @foreach ($blocks as $block)
                        <option value="{{ $block->id }}" @selected(old('building_block_id', $item?->building_block_id) === $block->id)>{{ $block->name }}</option>
                    @endforeach
                </select></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div><x-form-label>Sức chứa</x-form-label>
                <input name="seating_capacity" type="number" value="{{ old('seating_capacity', $item?->seating_capacity) }}" class="nttu-form-control"></div>
            <div><x-form-label>Loại phòng</x-form-label>
                <input name="room_type" value="{{ old('room_type', $item?->room_type) }}" class="nttu-form-control"></div>
        </div>
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="has_projector" value="1" @checked(old('has_projector', $item?->has_projector))>
            <x-form-field-icon name="monitor" tone="blue" class="h-4 w-4" />
            Có máy chiếu
        </label>
        <div class="flex gap-3">
            <x-form-actions cancel-href="{{ route('classrooms.index') }}" />
        </div>
    </form>
</div>
@endsection
