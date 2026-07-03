@extends('layouts.nttu')
@section('title', $item ? 'Sửa đơn thư' : 'Tiếp nhận đơn thư')
@section('page-title', $item ? 'Sửa đơn thư' : 'Tiếp nhận đơn thư')
@section('content')
<div class="max-w-3xl bg-white rounded-lg border p-6">
    <form method="POST" action="{{ $item ? route('petitions.update', $item) : route('petitions.store') }}" class="space-y-4">
        @csrf @if($item) @method('PUT') @endif
        <div class="grid grid-cols-2 gap-4">
            <div><x-form-label>Họ tên công dân *</x-form-label><input name="citizen_name" value="{{ old('citizen_name', $item?->citizen_name) }}" class="nttu-form-control" required></div>
            <div><x-form-label>CCCD</x-form-label><input name="citizen_id" value="{{ old('citizen_id', $item?->citizen_id) }}" class="nttu-form-control"></div>
        </div>
        <div class="grid grid-cols-3 gap-4">
            <div><x-form-label>Loại đơn</x-form-label>
                <select name="petition_type" class="nttu-form-control">
                    @foreach (['Khiếu nại','Tố cáo','Kiến nghị','Phản ánh'] as $t)
                        <option value="{{ $t }}" @selected(old('petition_type', $item?->petition_type) === $t)>{{ $t }}</option>
                    @endforeach
                </select></div>
            <div><x-form-label>Dãy nhà</x-form-label><input name="building_block" value="{{ old('building_block', $item?->building_block) }}" class="nttu-form-control"></div>
            <div><x-form-label>Ngày tiếp nhận</x-form-label><input name="reception_date" value="{{ old('reception_date', $item?->reception_date ?? date('d/m/Y')) }}" class="nttu-form-control"></div>
        </div>
        <div><x-form-label>Tóm tắt nội dung *</x-form-label><textarea name="summary" rows="3" class="nttu-form-control" required>{{ old('summary', $item?->summary) }}</textarea></div>
        <x-form-actions cancel-href="{{ route('petitions.index') }}" />
    </form>
</div>
@endsection
