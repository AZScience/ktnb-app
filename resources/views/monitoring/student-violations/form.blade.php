@extends('layouts.nttu')
@section('title', $item ? 'Sửa vi phạm' : 'Ghi nhận vi phạm')
@section('page-title', $item ? 'Sửa vi phạm' : 'Ghi nhận vi phạm')
@section('content')
<div class="max-w-2xl bg-white rounded-lg border p-6">
    <form method="POST" action="{{ $item ? route('student-violations.update', $item) : route('student-violations.store') }}" class="space-y-4">
        @csrf @if($item) @method('PUT') @endif
        <div class="grid grid-cols-2 gap-4">
            <div><x-form-label>Họ và tên SV</x-form-label>
                <input name="full_name" value="{{ old('full_name', $item?->full_name) }}" class="w-full rounded-md border-gray-300 shadow-sm" required></div>
            <div><x-form-label>MSSV</x-form-label>
                <input name="student_id" value="{{ old('student_id', $item?->student_id) }}" class="w-full rounded-md border-gray-300 shadow-sm"></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div><x-form-label>Lớp</x-form-label>
                <input name="class" value="{{ old('class', $item?->class) }}" class="w-full rounded-md border-gray-300 shadow-sm"></div>
            <div><x-form-label>Ngày vi phạm</x-form-label>
                <input name="violation_date" value="{{ old('violation_date', $item?->violation_date ?? date('d/m/Y')) }}" class="w-full rounded-md border-gray-300 shadow-sm"></div>
        </div>
        <div><x-form-label>Loại vi phạm</x-form-label>
            <input name="violation_type" value="{{ old('violation_type', $item?->violation_type) }}" class="w-full rounded-md border-gray-300 shadow-sm"></div>
        <div class="grid grid-cols-2 gap-4">
            <div><x-form-label>Dãy nhà</x-form-label>
                <input name="building" value="{{ old('building', $item?->building) }}" class="w-full rounded-md border-gray-300 shadow-sm"></div>
            <div><x-form-label>Khoa/Đơn vị</x-form-label>
                <input name="department" value="{{ old('department', $item?->department) }}" class="w-full rounded-md border-gray-300 shadow-sm"></div>
        </div>
        <div><x-form-label>Cán bộ ghi nhận</x-form-label>
            <input name="officer" value="{{ old('officer', $item?->officer ?? auth()->user()->name) }}" class="w-full rounded-md border-gray-300 shadow-sm"></div>
        <div><x-form-label>Ghi chú</x-form-label>
            <textarea name="note" rows="2" class="w-full rounded-md border-gray-300 shadow-sm">{{ old('note', $item?->note) }}</textarea></div>
        <div class="flex gap-3">
            <x-form-actions cancel-href="{{ route('student-violations.index') }}" />
        </div>
    </form>
</div>
@endsection
