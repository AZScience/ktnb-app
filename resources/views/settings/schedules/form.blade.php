@extends('layouts.nttu')
@section('title', $item ? 'Sửa tiết học' : 'Thêm tiết học')
@section('page-title', $item ? 'Sửa tiết học' : 'Thêm tiết học')
@section('content')
<div class="max-w-3xl bg-white rounded-lg border p-6">
    <form method="POST" action="{{ $item ? route('schedules.update', $item) : route('schedules.store') }}" class="space-y-4">
        @csrf @if($item) @method('PUT') @endif
        <div class="grid grid-cols-2 gap-4">
            <div><x-form-label>Ngày (dd/mm/yyyy)</x-form-label><input name="date" value="{{ old('date', $item?->date ?? date('d/m/Y')) }}" class="nttu-form-control" required></div>
            <div><x-form-label>Tiết</x-form-label><input name="period" value="{{ old('period', $item?->period) }}" class="nttu-form-control"></div>
            <div><x-form-label>Tòa nhà</x-form-label><input name="building" value="{{ old('building', $item?->building) }}" class="nttu-form-control" placeholder="VD: A, Trực tuyến, Ngoài..."></div>
            <div><x-form-label>Phòng</x-form-label><input name="room" value="{{ old('room', $item?->room) }}" class="nttu-form-control"></div>
            <div><x-form-label>Loại</x-form-label><input name="type" value="{{ old('type', $item?->type) }}" class="nttu-form-control" placeholder="LT / TH"></div>
            <div><x-form-label>Giờ</x-form-label><input name="time" value="{{ old('time', $item?->time) }}" class="nttu-form-control"></div>
            <div><x-form-label>Khoa / Đơn vị</x-form-label><input name="department" value="{{ old('department', $item?->department) }}" class="nttu-form-control"></div>
            <div><x-form-label>Lớp</x-form-label><input name="class" value="{{ old('class', $item?->class) }}" class="nttu-form-control"></div>
            <div><x-form-label>Sĩ số</x-form-label><input type="number" name="student_count" value="{{ old('student_count', $item?->student_count) }}" class="nttu-form-control" min="0"></div>
            <div><x-form-label>Giảng viên</x-form-label><input name="lecturer" value="{{ old('lecturer', $item?->lecturer) }}" class="nttu-form-control"></div>
            <div class="col-span-2"><x-form-label>Trạng thái</x-form-label>
                <select name="status" class="nttu-form-control">
                    @foreach (['', 'Học bình thường', 'Phòng thi', 'Thi cuối kỳ', 'SHCN', 'Sinh hoạt chủ nhiệm'] as $st)
                        <option value="{{ $st }}" @selected(old('status', $item?->status) === $st)>{{ $st ?: '—' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-2"><x-form-label>Nội dung / Môn học</x-form-label><textarea name="content" rows="2" class="nttu-form-control">{{ old('content', $item?->content) }}</textarea></div>
            <div class="col-span-2"><x-form-label>Ghi chú</x-form-label><textarea name="note" rows="2" class="nttu-form-control">{{ old('note', $item?->note) }}</textarea></div>
        </div>
        <div class="flex gap-3 pt-2">
            <x-form-actions cancel-href="{{ route('schedules.index', ['date' => old('date', $item?->date ?? date('d/m/Y'))]) }}" />
        </div>
    </form>
</div>
@endsection
