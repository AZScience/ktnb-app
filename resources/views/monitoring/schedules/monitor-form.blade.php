@extends('layouts.nttu')
@section('title', 'Ghi nhận — ' . $title)
@section('page-title', 'Ghi nhận: ' . $title)
@section('content')

<div class="nttu-card max-w-4xl">
    <div class="p-4 border-b bg-slate-50 text-sm space-y-1">
        <p><strong>Ngày:</strong> {{ $item->date }} · <strong>Tiết {{ $item->period }}</strong> · {{ $item->building }} {{ $item->room }}</p>
        <p><strong>Lớp:</strong> {{ $item->class }} · <strong>GV:</strong> {{ $item->lecturer }} · <strong>Sĩ số:</strong> {{ $item->student_count ?? '—' }}</p>
        <p class="text-gray-600">{{ $item->content }}</p>
    </div>
    <form method="POST" action="{{ route('monitoring.schedules.update', ['module' => $module, 'schedule' => $item]) }}" class="p-6 space-y-4">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-form-label>Cán bộ giám sát</x-form-label>
                <input name="employee" value="{{ old('employee', $item->employee ?? auth()->user()->name) }}" class="nttu-form-control focus:border-cyan-500 focus:ring-cyan-500">
            </div>
            <div>
                <x-form-label>SV tham gia</x-form-label>
                <input type="number" name="attending_students" value="{{ old('attending_students', $item->attending_students) }}" class="nttu-form-control focus:border-cyan-500 focus:ring-cyan-500" min="0">
            </div>
            <div>
                <x-form-label>Việc phát sinh @if(\App\Models\DailySchedule::requiresIncidentSelection($module))<span class="text-red-600">*</span>@endif</x-form-label>
                <select name="incident" class="nttu-form-control focus:border-cyan-500 focus:ring-cyan-500" @if(\App\Models\DailySchedule::requiresIncidentSelection($module)) required @endif>
                    @unless(\App\Models\DailySchedule::requiresIncidentSelection($module))
                        <option value="">— Không có —</option>
                    @else
                        <option value="" disabled @selected(! old('incident', $item->incident)) hidden>— Chọn việc phát sinh —</option>
                    @endunless
                    @foreach ($incidentCategories as $cat)
                        <option value="{{ $cat->name }}" @selected(old('incident', $item->incident) === $cat->name)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-form-label>Ngày ghi nhận</x-form-label>
                <input name="recognition_date" value="{{ old('recognition_date', $item->recognition_date ?? date('d/m/Y')) }}" class="nttu-form-control focus:border-cyan-500 focus:ring-cyan-500">
            </div>
            <div class="md:col-span-2">
                <x-form-label>Chi tiết việc phát sinh</x-form-label>
                <textarea name="incident_detail" rows="3" class="nttu-form-control focus:border-cyan-500 focus:ring-cyan-500">{{ old('incident_detail', $item->incident_detail) }}</textarea>
            </div>
            <div class="md:col-span-2">
                <x-form-label>Bằng chứng / Ghi chú</x-form-label>
                <textarea name="evidence" rows="2" class="nttu-form-control focus:border-cyan-500 focus:ring-cyan-500">{{ old('evidence', $item->evidence) }}</textarea>
            </div>
            <div class="md:col-span-2">
                <x-form-label>Ghi chú thêm</x-form-label>
                <textarea name="note" rows="2" class="nttu-form-control focus:border-cyan-500 focus:ring-cyan-500">{{ old('note', $item->note) }}</textarea>
            </div>
            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_notification" value="1" @checked(old('is_notification', $item->is_notification)) class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500">
                    <x-form-field-icon name="bell" tone="orange" class="h-4 w-4" />
                    Gửi thông báo
                </label>
            </div>
        </div>
        <div class="flex gap-3 pt-2 border-t">
            <x-nttu-button type="submit" action="save">Lưu ghi nhận</x-nttu-button>
            <x-nttu-button href="{{ route("monitoring.{$module}.index", ['date' => $item->date]) }}" action="cancel">Quay lại</x-nttu-button>
        </div>
    </form>
</div>
@endsection
