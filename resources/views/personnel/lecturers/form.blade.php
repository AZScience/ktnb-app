@extends('layouts.nttu')
@section('title', $item ? 'Sửa giảng viên' : 'Thêm giảng viên')
@section('page-title', $item ? 'Sửa giảng viên' : 'Thêm giảng viên')
@section('content')
@php
    $resolvedPosition = $item
        ? (\App\Models\Position::find($item->position) ?? \App\Models\Position::where('name', $item->position)->first())?->id ?? $item->position
        : '';
@endphp
<div class="max-w-2xl bg-white rounded-lg border p-6">
    <form method="POST" action="{{ $item ? route('lecturers.update', $item) : route('lecturers.store') }}" class="space-y-4">
        @csrf @if($item) @method('PUT') @endif
        <div class="grid grid-cols-2 gap-4">
            <div><x-form-label>Mã giảng viên</x-form-label>
                <input name="id" value="{{ old('id', $item?->id) }}" class="nttu-form-control" required {{ $item ? 'readonly' : '' }}></div>
            <div><x-form-label>Họ và tên</x-form-label>
                <input name="name" value="{{ old('name', $item?->name) }}" class="nttu-form-control" required></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div><x-form-label>Đơn vị</x-form-label>
                <select name="department" class="nttu-form-control">
                    <option value="">Không có</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(old('department', $item?->department) == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div><x-form-label>Chức vụ</x-form-label>
                <select name="position" class="nttu-form-control">
                    <option value="">Không có</option>
                    @foreach ($positions as $position)
                        <option value="{{ $position->id }}" @selected(old('position', $resolvedPosition) == $position->id)>{{ $position->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div><x-form-label>Điện thoại</x-form-label>
                <input name="phone" value="{{ old('phone', $item?->phone) }}" class="nttu-form-control"></div>
            <div><x-form-label>Email</x-form-label>
                <input name="email" type="email" value="{{ old('email', $item?->email) }}" class="nttu-form-control"></div>
        </div>
        <div class="flex gap-3">
            <x-form-actions cancel-href="{{ route('lecturers.index') }}" />
        </div>
    </form>
</div>
@endsection
