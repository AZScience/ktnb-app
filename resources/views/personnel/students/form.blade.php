@extends('layouts.nttu')
@section('title', $item ? 'Sửa sinh viên' : 'Thêm sinh viên')
@section('page-title', $item ? 'Sửa sinh viên' : 'Thêm sinh viên')
@section('content')
<div class="max-w-2xl bg-white rounded-lg border p-6">
    <form method="POST" action="{{ $item ? route('students.update', $item) : route('students.store') }}" class="space-y-4">
        @csrf @if($item) @method('PUT') @endif
        <div class="grid grid-cols-2 gap-4">
            <div><x-form-label>MSSV</x-form-label>
                <input name="id" value="{{ old('id', $item?->id) }}" class="nttu-form-control" required {{ $item ? 'readonly' : '' }}></div>
            <div><x-form-label>Họ và tên</x-form-label>
                <input name="name" value="{{ old('name', $item?->name) }}" class="nttu-form-control" required></div>
        </div>
        <div class="grid grid-cols-3 gap-4">
            <div><x-form-label>Giới tính</x-form-label>
                <select name="gender" class="nttu-form-control">
                    <option value="Nam" @selected(old('gender', $item?->gender) === 'Nam')>Nam</option>
                    <option value="Nữ" @selected(old('gender', $item?->gender) === 'Nữ')>Nữ</option>
                </select></div>
            <div><x-form-label>Lớp</x-form-label>
                <input name="class" value="{{ old('class', $item?->class) }}" class="nttu-form-control"></div>
            <div><x-form-label>Ngành</x-form-label>
                <input name="major" value="{{ old('major', $item?->major) }}" class="nttu-form-control"></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div><x-form-label>Điện thoại</x-form-label>
                <input name="phone" value="{{ old('phone', $item?->phone) }}" class="nttu-form-control"></div>
            <div><x-form-label>Email</x-form-label>
                <input name="email" type="email" value="{{ old('email', $item?->email) }}" class="nttu-form-control"></div>
        </div>
        <div class="flex gap-3">
            <x-form-actions cancel-href="{{ route('students.index') }}" />
        </div>
    </form>
</div>
@endsection
