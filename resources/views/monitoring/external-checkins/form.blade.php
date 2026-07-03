@extends('layouts.nttu')
@section('title', 'Duyệt check-in')
@section('page-title', 'Duyệt check-in TH ngoài')
@section('content')
<div class="grid lg:grid-cols-2 gap-4">
    <form method="POST" action="{{ route('external-checkins.update', $item) }}" class="nttu-card p-5 space-y-4">
        @csrf @method('PUT')
        <div>
            <x-form-label>Trạng thái</x-form-label>
            <select name="status" class="w-full rounded-md border text-sm mt-1 px-3 py-2">
                @foreach (['pending_review' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối'] as $k => $v)
                    <option value="{{ $k }}" @selected($item->status === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-form-label>SV tham gia</x-form-label>
            <input name="actual_student_count" value="{{ $item->actual_student_count }}" class="w-full rounded-md border text-sm mt-1 px-3 py-2">
        </div>
        <div>
            <x-form-label>Việc phát sinh</x-form-label>
            <input name="incident" value="{{ $item->incident }}" class="w-full rounded-md border text-sm mt-1 px-3 py-2">
        </div>
        <div>
            <x-form-label>Chi tiết</x-form-label>
            <textarea name="incident_detail" rows="3" class="w-full rounded-md border text-sm mt-1 px-3 py-2">{{ $item->incident_detail }}</textarea>
        </div>
        <x-form-actions cancel-href="{{ route('external-checkins.index') }}" />
        <a href="{{ route('external-checkins.index') }}" class="rounded-md border px-4 py-2 text-sm ml-2">Quay lại</a>
    </form>
    <div class="nttu-card p-5 space-y-3 text-sm">
        <p><strong>Lớp:</strong> {{ $item->class_id }} — {{ $item->class_name }}</p>
        <p><strong>GV:</strong> {{ $item->lecturer }}</p>
        <p><strong>Ngày học:</strong> {{ $item->schedule_date }}</p>
        <p><strong>GPS:</strong> {{ $item->location['latitude'] ?? '' }}, {{ $item->location['longitude'] ?? '' }}</p>
        @if ($item->photo_urls)
            <div class="grid grid-cols-2 gap-2 pt-2">
                @foreach ($item->photo_urls as $url)
                    <a href="{{ $url }}" target="_blank"><img src="{{ $url }}" class="rounded border w-full h-32 object-cover"></a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
