@extends('layouts.nttu')
@section('title', 'Check-in online')
@section('page-section', 'Công cụ hỗ trợ')
@section('page-title', 'Kho minh chứng')
@section('content')
<p class="text-sm text-gray-600 mb-4">API: <code class="bg-gray-100 px-2 py-1 rounded">POST /api/v1/online-checkins</code> với header <code>x-api-key</code></p>
<div class="nttu-card overflow-x-auto">
    <table class="nttu-table min-w-full">
        <thead><tr>
            <th class="nttu-th-index w-12">#</th>
            <th>Thời gian</th><th>Giảng viên</th><th>Lớp</th>
            <th>Môn học</th><th>SV thực tế</th><th>Link Zoom</th>
        </tr></thead>
        <tbody>
            @forelse ($items as $item)
                @php $p = is_array($item->payload) ? $item->payload : []; @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="whitespace-nowrap">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                    <td class="font-medium">{{ $p['lecturer'] ?? $p['giangVien'] ?? '—' }}</td>
                    <td>{{ $p['class'] ?? $p['lop'] ?? '—' }}</td>
                    <td>{{ $p['subject'] ?? $p['monHoc'] ?? Str::limit($p['content'] ?? '', 30) }}</td>
                    <td class="text-center">{{ $p['actualStudentCount'] ?? $p['actual_student_count'] ?? '—' }}</td>
                    <td>
                        @php $link = $p['meetingLink'] ?? $p['meeting_link'] ?? null; @endphp
                        @if($link)
                            <a href="{{ $link }}" target="_blank" class="text-blue-600 hover:underline text-xs">Zoom ↗</a>
                        @else
                            <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-12 text-center text-gray-500"><x-table-empty-state /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
