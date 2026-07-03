@extends('layouts.nttu')
@section('title', $item ? 'Sửa yêu cầu' : 'Tiếp nhận yêu cầu')
@section('page-title', $item ? 'Sửa yêu cầu' : 'Tiếp nhận yêu cầu')
@section('content')
<div class="max-w-3xl bg-white rounded-lg border p-6">
    <form method="POST" action="{{ $item ? route('requests.update', $item) : route('requests.store') }}" class="space-y-4">
        @csrf @if($item) @method('PUT') @endif
        <div class="grid grid-cols-3 gap-4">
            <div><x-form-label>Số phiếu</x-form-label><input name="ticket_number" value="{{ old('ticket_number', $item?->ticket_number) }}" class="w-full rounded-md border-gray-300 shadow-sm"></div>
            <div><x-form-label>Dãy nhà</x-form-label><input name="building_block" value="{{ old('building_block', $item?->building_block) }}" class="w-full rounded-md border-gray-300 shadow-sm"></div>
            <div><x-form-label>Trạng thái</x-form-label><input name="status" value="{{ old('status', $item?->status ?? 'Mới') }}" class="w-full rounded-md border-gray-300 shadow-sm"></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div><x-form-label>Họ tên SV *</x-form-label><input name="student_name" value="{{ old('student_name', $item?->student_name) }}" class="w-full rounded-md border-gray-300 shadow-sm" required></div>
            <div><x-form-label>MSSV</x-form-label><input name="student_id" value="{{ old('student_id', $item?->student_id) }}" class="w-full rounded-md border-gray-300 shadow-sm"></div>
        </div>
        <div class="grid grid-cols-3 gap-4">
            <div><x-form-label>Lớp</x-form-label><input name="class" value="{{ old('class', $item?->class) }}" class="w-full rounded-md border-gray-300 shadow-sm"></div>
            <div><x-form-label>Khoa/Đơn vị</x-form-label><input name="department" value="{{ old('department', $item?->department) }}" class="w-full rounded-md border-gray-300 shadow-sm"></div>
            <div><x-form-label>Điện thoại</x-form-label><input name="phone" value="{{ old('phone', $item?->phone) }}" class="w-full rounded-md border-gray-300 shadow-sm"></div>
        </div>
        <div><x-form-label>Nội dung yêu cầu *</x-form-label><textarea name="content" rows="3" class="w-full rounded-md border-gray-300 shadow-sm" required>{{ old('content', $item?->content) }}</textarea></div>
        <div class="grid grid-cols-2 gap-4">
            <div><x-form-label>Ngày tiếp nhận</x-form-label><input name="reception_date" value="{{ old('reception_date', $item?->reception_date ?? date('d/m/Y')) }}" class="w-full rounded-md border-gray-300 shadow-sm"></div>
            <div><x-form-label>Người tiếp nhận</x-form-label><input name="recipient" value="{{ old('recipient', $item?->recipient ?? auth()->user()->name) }}" class="w-full rounded-md border-gray-300 shadow-sm"></div>
        </div>
        <x-form-actions cancel-href="{{ route('requests.index') }}" />
    </form>
</div>
@endsection
