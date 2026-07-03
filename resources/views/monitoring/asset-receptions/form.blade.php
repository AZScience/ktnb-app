@extends('layouts.nttu')
@section('title', $item ? 'Sửa tiếp nhận TS' : 'Tiếp nhận tài sản')
@section('page-title', $item ? 'Sửa tiếp nhận tài sản' : 'Tiếp nhận tài sản')
@section('content')
<div class="max-w-3xl bg-white rounded-lg border p-6">
    <form method="POST" action="{{ $item ? route('asset-receptions.update', $item) : route('asset-receptions.store') }}" class="space-y-4">
        @csrf @if($item) @method('PUT') @endif
        <div class="grid grid-cols-3 gap-4">
            <div><x-form-label>Số tiếp nhận</x-form-label><input name="entry_number" value="{{ old('entry_number', $item?->entry_number) }}" class="nttu-form-control"></div>
            <div><x-form-label>Ngày TN</x-form-label><input name="reception_date" value="{{ old('reception_date', $item?->reception_date ?? date('d/m/Y')) }}" class="nttu-form-control"></div>
            <div><x-form-label>Dãy nhà</x-form-label><input name="building_block" value="{{ old('building_block', $item?->building_block) }}" class="nttu-form-control"></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div><x-form-label>Người giao *</x-form-label><input name="giver_name" value="{{ old('giver_name', $item?->giver_name) }}" class="nttu-form-control" required></div>
            <div><x-form-label>MSSV/CCCD</x-form-label><input name="giver_id" value="{{ old('giver_id', $item?->giver_id) }}" class="nttu-form-control"></div>
        </div>
        <div><x-form-label>Nội dung tiếp nhận *</x-form-label><textarea name="content" rows="3" class="nttu-form-control" required>{{ old('content', $item?->content) }}</textarea></div>
        <div class="grid grid-cols-2 gap-4">
            <div><x-form-label>Tình trạng TS</x-form-label><input name="asset_state" value="{{ old('asset_state', $item?->asset_state) }}" class="nttu-form-control"></div>
            <div><x-form-label>Trạng thái trả</x-form-label>
                <select name="return_status" class="nttu-form-control">
                    @foreach (['Chưa trả','Đã trả','Đã xử lý'] as $s)
                        <option value="{{ $s }}" @selected(old('return_status', $item?->return_status ?? 'Chưa trả') === $s)>{{ $s }}</option>
                    @endforeach
                </select></div>
        </div>
        <div class="flex gap-3">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_gratitude" value="1" @checked(old('is_gratitude', $item?->is_gratitude ?? request('gratitude')))>
                <x-form-field-icon name="gift" tone="amber" class="h-4 w-4" />
                Ghi vào sổ Người tốt việc tốt
            </label>
        </div>
        <x-form-actions cancel-href="{{ route('asset-check.index') }}" />
    </form>
</div>
@endsection
