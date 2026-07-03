@extends('layouts.nttu')
@section('title', $item ? 'Sửa dãy nhà' : 'Thêm dãy nhà')
@section('page-title', $item ? 'Sửa dãy nhà' : 'Thêm dãy nhà')
@section('content')
<div class="max-w-lg bg-white rounded-lg border p-6">
    <form method="POST" action="{{ $item ? route('building-blocks.update', $item) : route('building-blocks.store') }}" class="space-y-4">
        @csrf @if($item) @method('PUT') @endif
        <div><x-form-label>Mã dãy</x-form-label>
            <input name="code" value="{{ old('code', $item?->code) }}" class="w-full rounded-md border-gray-300 shadow-sm" required></div>
        <div><x-form-label>Tên dãy nhà</x-form-label>
            <input name="name" value="{{ old('name', $item?->name) }}" class="w-full rounded-md border-gray-300 shadow-sm" required></div>
        <div><label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_inactive" value="1" @checked(old('is_inactive', $item?->is_inactive))>
            <x-form-field-icon name="ban" tone="destructive" class="h-4 w-4" />
            Ngưng sử dụng
        </label></div>
        <div class="flex gap-3">
            <x-form-actions cancel-href="{{ route('building-blocks.index') }}" />
        </div>
    </form>
</div>
@endsection
