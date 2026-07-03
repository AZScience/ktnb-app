@extends('layouts.public')
@section('title', 'Báo cáo giám sát trực tuyến')
@section('content')
<div class="min-h-screen bg-slate-50 p-4 md:p-8">
    <div class="max-w-4xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-black text-slate-900">BÁO CÁO GIÁM SÁT TRỰC TUYẾN</h1>
            <p class="text-sm text-slate-500 mt-1">ID: {{ $data['id'] ?? '' }}</p>
        </div>

        <div class="bg-white rounded-2xl shadow border p-6 grid md:grid-cols-2 gap-4 text-sm">
            <div><span class="text-slate-400 text-xs uppercase font-bold">Lớp</span><p class="font-bold">{{ $data['class'] ?? $data['Class'] ?? '—' }}</p></div>
            <div><span class="text-slate-400 text-xs uppercase font-bold">Giảng viên</span><p class="font-bold">{{ $data['lecturer'] ?? $data['Lecturer'] ?? '—' }}</p></div>
            <div><span class="text-slate-400 text-xs uppercase font-bold">Môn học</span><p class="font-bold">{{ $data['content'] ?? $data['Course'] ?? '—' }}</p></div>
            <div><span class="text-slate-400 text-xs uppercase font-bold">Ca / Ngày</span><p class="font-bold">{{ ($data['period'] ?? $data['Period'] ?? '') }} — {{ $data['date'] ?? $data['Date'] ?? '' }}</p></div>
            <div><span class="text-slate-400 text-xs uppercase font-bold">Sĩ số / Có mặt</span>
                <p class="font-bold">{{ $data['studentCount'] ?? $data['totalStudents'] ?? '—' }} / {{ $data['actualStudentCount'] ?? $data['attendingStudents'] ?? '—' }}</p>
            </div>
            <div><span class="text-slate-400 text-xs uppercase font-bold">Trạng thái</span>
                <p class="font-bold">{{ $data['status'] ?? '—' }}</p>
            </div>
        </div>

        @if (!empty($data['meetingLink']))
            <div class="bg-white rounded-2xl shadow border p-4">
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Link học</p>
                <a href="{{ $data['meetingLink'] }}" class="text-blue-600 break-all text-sm" target="_blank">{{ $data['meetingLink'] }}</a>
            </div>
        @endif

        @if (!empty($data['attendanceList']) && is_array($data['attendanceList']))
            <div class="bg-white rounded-2xl shadow border overflow-hidden">
                <div class="bg-[#1877F2] text-white px-4 py-3 font-bold text-sm">Danh sách điểm danh ({{ count($data['attendanceList']) }})</div>
                <ul class="divide-y max-h-96 overflow-y-auto">
                    @foreach ($data['attendanceList'] as $name)
                        <li class="px-4 py-2 text-sm">{{ is_string($name) ? $name : ($name['name'] ?? json_encode($name)) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!empty($data['evidence']))
            <div class="bg-white rounded-2xl shadow border p-4">
                <p class="text-xs font-bold text-slate-400 uppercase mb-2">Bằng chứng</p>
                <img src="{{ $data['evidence'] }}" alt="Evidence" class="max-w-full rounded-lg border">
            </div>
        @endif
    </div>
</div>
@endsection
