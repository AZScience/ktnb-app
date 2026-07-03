@extends('layouts.nttu')
@section('title', 'Tài liệu API')
@section('page-section', 'Công cụ hỗ trợ')
@section('page-title', 'Tài liệu API ứng dụng')
@section('page-description', 'Hướng dẫn cấu hình, lấy dữ liệu, gửi dữ liệu và truy vấn qua REST API (extension ex-online và tích hợp bên ngoài).')
@section('content')
@php
    $tabs = [
        ['id' => 'overview', 'label' => 'Tổng quan'],
        ['id' => 'config', 'label' => 'Cấu hình'],
        ['id' => 'read', 'label' => 'Lấy dữ liệu (GET)'],
        ['id' => 'write', 'label' => 'Gửi dữ liệu (POST/PATCH)'],
        ['id' => 'query', 'label' => 'Truy vấn & lọc'],
        ['id' => 'errors', 'label' => 'Mã lỗi'],
    ];
@endphp

<div x-data="{ tab: 'overview' }" class="mx-auto max-w-5xl space-y-4">
    <div class="nttu-card overflow-hidden shadow-sm">
        <div class="flex flex-col gap-3 border-b px-4 py-4 md:flex-row md:items-center md:justify-between md:px-6">
            <div>
                <h2 class="text-lg font-bold text-gray-900">REST API v1</h2>
                <p class="text-sm text-gray-500">Base URL: <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">{{ $apiBase }}</code></p>
            </div>
            <div class="flex flex-wrap gap-1 rounded-lg bg-slate-200/60 p-1">
                @foreach ($tabs as $t)
                    <button type="button" @click="tab = '{{ $t['id'] }}'"
                        class="rounded-md px-3 py-1.5 text-xs font-semibold transition"
                        :class="tab === '{{ $t['id'] }}' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:bg-white/70'">
                        {{ $t['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Tổng quan --}}
        <div x-show="tab === 'overview'" x-cloak class="space-y-5 p-6 text-sm leading-relaxed text-gray-700">
            <p>API phục vụ <strong>extension ex-online</strong> (Giám sát Online) và các client bên ngoài gửi/nhận dữ liệu giám sát lớp học, bình chọn, bài kiểm tra, thảo luận.</p>

            <div class="grid gap-3 md:grid-cols-2">
                <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-4">
                    <p class="font-bold text-blue-900">Đọc công khai (không API key)</p>
                    <ul class="mt-2 list-disc space-y-1 pl-4 text-blue-800">
                        <li>Xem poll, exam, discussion, online-checkin, schedule theo ID</li>
                        <li>Sinh viên bình chọn poll (PATCH)</li>
                    </ul>
                </div>
                <div class="rounded-xl border border-amber-100 bg-amber-50/60 p-4">
                    <p class="font-bold text-amber-900">Ghi dữ liệu (bắt buộc API key)</p>
                    <ul class="mt-2 list-disc space-y-1 pl-4 text-amber-800">
                        <li>Tra cứu lịch theo lớp/ngày</li>
                        <li>Gửi check-in online, cập nhật lịch</li>
                        <li>Tạo poll, exam, discussion, sinh đề AI</li>
                    </ul>
                </div>
            </div>

            <div class="rounded-xl border bg-slate-50 p-4">
                <p class="mb-2 font-semibold">Luồng dữ liệu về Giám sát nội bộ</p>
                <table class="w-full text-left text-xs">
                    <thead><tr class="border-b"><th class="py-2 pr-3">API</th><th class="py-2 pr-3">Bảng DB</th><th class="py-2">Trang quản trị</th></tr></thead>
                    <tbody class="divide-y">
                        <tr><td class="py-2 pr-3"><code>online-checkins</code></td><td class="py-2 pr-3"><code>online_checkins</code></td><td class="py-2">Giám sát Online</td></tr>
                        <tr><td class="py-2 pr-3"><code>schedules</code></td><td class="py-2 pr-3"><code>daily_schedules</code></td><td class="py-2">Giám sát Online / Lịch học</td></tr>
                        <tr><td class="py-2 pr-3"><code>polls</code>, <code>exams</code></td><td class="py-2 pr-3"><code>polls</code>, <code>exams</code></td><td class="py-2">Trang công khai /poll, /exam</td></tr>
                        <tr><td class="py-2 pr-3"><code>discussions</code></td><td class="py-2 pr-3"><code>discussion_sections</code></td><td class="py-2">Bảng thảo luận</td></tr>
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-gray-500">Định dạng phản hồi chuẩn: <code>{ "success": true|false, "data"?: ..., "message"?: "..." }</code>. CORS cho phép <code>*</code> trên các endpoint API.</p>
        </div>

        {{-- Cấu hình --}}
        <div x-show="tab === 'config'" x-cloak class="space-y-5 p-6 text-sm">
            <section class="space-y-3">
                <h3 class="text-base font-bold text-gray-900">1. Cấu hình trên máy chủ Laravel</h3>
                <p class="text-gray-600">Trong file <code>laravel-app/.env</code>:</p>
                <pre class="overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100"><code>APP_URL={{ $webBase }}
EXTERNAL_API_KEY=your_secret_key_here</code></pre>
                <p class="text-gray-600">API key hiện tại (ẩn một phần): <code class="rounded bg-slate-100 px-2 py-1">{{ $apiKeyMasked }}</code>
                    @unless($apiKeyConfigured)<span class="text-amber-700"> — chưa có giá trị hợp lệ</span>@endunless
                </p>
            </section>

            <section class="space-y-3">
                <h3 class="text-base font-bold text-gray-900">2. Cấu hình extension ex-online</h3>
                <p class="text-gray-600">Sửa file <code>ex-online/config.js</code>:</p>
                <pre class="overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100"><code>const NTTU_API_CONFIG = {
  API_BASE: "{{ $apiBase }}",
  WEB_APP_BASE: "{{ $webBase }}",
  API_KEY: "your_secret_key_here",  // trùng EXTERNAL_API_KEY
};</code></pre>
            </section>

            <section class="space-y-3">
                <h3 class="text-base font-bold text-gray-900">3. Xác thực mỗi request ghi dữ liệu</h3>
                <p class="text-gray-600">Gửi API key bằng <strong>một trong hai</strong> cách:</p>
                <pre class="overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100"><code>// Cách 1 — header khuyến nghị (extension dùng cách này)
x-api-key: your_secret_key_here

// Cách 2 — Bearer token
Authorization: Bearer your_secret_key_here</code></pre>
                <p class="text-gray-600">Header bắt buộc khi gửi JSON: <code>Content-Type: application/json</code></p>
            </section>

            <section class="rounded-xl border border-emerald-100 bg-emerald-50/70 p-4 text-sm text-emerald-900">
                <p class="font-bold">Kiểm tra nhanh (PowerShell / curl)</p>
                <pre class="mt-2 overflow-x-auto text-xs"><code>curl -H "x-api-key: YOUR_KEY" "{{ $apiBase }}/schedules?class=CNTT01&date={{ now()->format('d/m/Y') }}"</code></pre>
            </section>
        </div>

        {{-- GET --}}
        <div x-show="tab === 'read'" x-cloak class="space-y-6 p-6 text-sm">
            @foreach ([
                [
                    'title' => 'Tra cứu lịch học theo lớp + ngày',
                    'auth' => 'API key',
                    'method' => 'GET',
                    'path' => '/schedules?class={maLop}&date={dd/mm/yyyy}',
                    'example' => $apiBase.'/schedules?class=CNTT01&date='.now()->format('d/m/Y'),
                    'response' => '{
  "success": true,
  "data": [
    {
      "id": "sched-uuid",
      "class": "CNTT01",
      "lecturer": "TS. Nguyễn A",
      "period": "1",
      "date": "17/06/2026",
      "content": "Lập trình Web",
      "room": "A.201",
      "studentCount": 45,
      "actualStudentCount": null,
      "meetingLink": "https://meet.google.com/...",
      "status": "Học bình thường"
    }
  ]
}',
                ],
                [
                    'title' => 'Xem một tiết lịch theo ID',
                    'auth' => 'Không',
                    'method' => 'GET',
                    'path' => '/schedules/{scheduleId}',
                    'example' => $apiBase.'/schedules/{scheduleId}',
                    'response' => '{ "success": true, "data": { "id": "...", "class": "...", ... } }',
                ],
                [
                    'title' => 'Xem báo cáo check-in online',
                    'auth' => 'Không',
                    'method' => 'GET',
                    'path' => '/online-checkins/{id}',
                    'example' => $apiBase.'/online-checkins/{uuid}',
                    'response' => '{
  "success": true,
  "data": {
    "id": "uuid",
    "class": "CNTT01",
    "type": "online_manual",
    "status": "completed",
    "studentCount": 40,
    "evidence": "data:image/jpeg;base64,...",
    "attendanceList": [],
    "attendanceDetails": []
  }
}',
                ],
                [
                    'title' => 'Xem bình chọn (poll)',
                    'auth' => 'Không',
                    'method' => 'GET',
                    'path' => '/polls/{pollId}',
                    'example' => $apiBase.'/polls/{pollId}',
                    'response' => '{ "success": true, "data": { "id": "...", "question": "...", "options": [], "voters": {} } }',
                ],
                [
                    'title' => 'Xem bài kiểm tra (exam)',
                    'auth' => 'Không',
                    'method' => 'GET',
                    'path' => '/exams/{examId}',
                    'example' => $apiBase.'/exams/{examId}',
                    'response' => '{ "success": true, "data": { "id": "...", "title": "...", "questions": [] } }',
                ],
                [
                    'title' => 'Danh sách bảng thảo luận',
                    'auth' => 'Không',
                    'method' => 'GET',
                    'path' => '/discussions',
                    'example' => $apiBase.'/discussions',
                    'response' => '{ "success": true, "data": [ { "id": "...", "title": "...", "comments": [] } ] }',
                ],
            ] as $ep)
                <article class="rounded-xl border border-slate-200 overflow-hidden">
                    <div class="flex flex-wrap items-center gap-2 border-b bg-slate-50 px-4 py-3">
                        <span class="rounded bg-green-600 px-2 py-0.5 text-xs font-bold text-white">{{ $ep['method'] }}</span>
                        <code class="text-xs font-semibold">{{ $ep['path'] }}</code>
                        <span class="ml-auto rounded-full px-2 py-0.5 text-xs {{ $ep['auth'] === 'API key' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">{{ $ep['auth'] }}</span>
                    </div>
                    <div class="space-y-2 p-4">
                        <h4 class="font-bold text-gray-900">{{ $ep['title'] }}</h4>
                        <p class="text-xs text-gray-500">Ví dụ URL:</p>
                        <pre class="overflow-x-auto rounded bg-slate-900 p-3 text-xs text-slate-100"><code>{{ $ep['example'] }}</code></pre>
                        <p class="text-xs text-gray-500">Phản hồi mẫu:</p>
                        <pre class="overflow-x-auto rounded bg-slate-800 p-3 text-xs text-slate-200"><code>{{ $ep['response'] }}</code></pre>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- POST/PATCH --}}
        <div x-show="tab === 'write'" x-cloak class="space-y-6 p-6 text-sm">
            <p class="text-gray-600">Tất cả endpoint dưới đây yêu cầu header <code>x-api-key</code>. Body JSON trừ khi ghi chú khác.</p>

            @foreach ([
                [
                    'title' => 'Gửi báo cáo check-in online (kết thúc tiết / publish)',
                    'method' => 'POST',
                    'path' => '/online-checkins',
                    'body' => '{
  "class": "CNTT01",
  "lecturer": "TS. Nguyễn A",
  "date": "17/06/2026",
  "period": "1",
  "studentCount": 40,
  "totalStudents": 45,
  "meetingLink": "https://meet.google.com/abc-defg-hij",
  "hostName": "Giảng viên A",
  "evidence": "data:image/jpeg;base64,...",
  "status": "completed",
  "type": "online_manual",
  "startTime": "2026-06-17T07:00:00.000Z",
  "endTime": "2026-06-17T09:30:00.000Z",
  "attendanceList": ["sv001@student.ntt.edu.vn"],
  "attendanceDetails": [{ "email": "sv001@student.ntt.edu.vn", "minutes": 120 }]
}',
                    'response' => '{ "success": true, "id": "uuid-moi", "data": { "id": "uuid-moi" } }',
                    'note' => 'type: online_manual (kết thúc tiết) hoặc online_publish. Dữ liệu hiển thị tại Giám sát Online.',
                ],
                [
                    'title' => 'Cập nhật báo cáo check-in online',
                    'method' => 'PATCH',
                    'path' => '/online-checkins/{id}',
                    'body' => '{ "status": "published", "attendanceDetails": [] }',
                    'response' => '{ "success": true, "id": "uuid" }',
                    'note' => 'Merge payload cũ với field mới gửi lên.',
                ],
                [
                    'title' => 'Cập nhật tiết lịch (sĩ số, link meet, điểm danh)',
                    'method' => 'PATCH',
                    'path' => '/schedules/{scheduleId}',
                    'body' => '{
  "actualStudentCount": 42,
  "studentCount": 45,
  "attendingStudents": 42,
  "meetingLink": "https://meet.google.com/...",
  "status": "completed",
  "endTime": "2026-06-17T09:30:00.000Z",
  "attendanceList": [],
  "attendanceDetails": [],
  "hostName": "Giảng viên A"
}',
                    'response' => '{ "success": true, "data": { ...schedule } }',
                    'note' => 'Extension gọi song song với POST online-checkins khi kết thúc tiết.',
                ],
                [
                    'title' => 'Tạo bình chọn (poll)',
                    'method' => 'POST',
                    'path' => '/polls',
                    'body' => '{
  "question": "Bạn hiểu bài hôm nay không?",
  "options": ["Rất rõ", "Tạm được", "Chưa rõ"],
  "duration": 10,
  "classId": "CNTT01",
  "lecturer": "TS. A",
  "attendanceList": []
}',
                    'response' => '{ "success": true, "id": "poll-uuid" }',
                    'note' => 'Sinh viên truy cập: '.$webBase.'/poll/{id}',
                ],
                [
                    'title' => 'Tạo bài kiểm tra (exam)',
                    'method' => 'POST',
                    'path' => '/exams',
                    'body' => '{
  "title": "Kiểm tra giữa kỳ",
  "classId": "CNTT01",
  "courseName": "Lập trình Web",
  "duration": 15,
  "questions": [
    { "question": "Câu 1?", "options": ["A","B","C","D"], "correct": 0 }
  ]
}',
                    'response' => '{ "success": true, "id": "exam-uuid" }',
                    'note' => 'Sinh viên truy cập: '.$webBase.'/exam/{id}',
                ],
                [
                    'title' => 'Tạo bảng thảo luận mới',
                    'method' => 'POST',
                    'path' => '/discussions',
                    'body' => '{
  "title": "Thảo luận tuần 3",
  "content": "Nội dung hướng dẫn cho sinh viên",
  "authorName": "Giảng viên A",
  "classId": "CNTT01"
}',
                    'response' => '{
  "success": true,
  "id": "section-uuid",
  "moderatorToken": "token-bi-mat-cho-gv"
}',
                    'note' => 'Link SV: /discussion?id={id} — Link GV: /discussion?id={id}&key={moderatorToken}',
                ],
                [
                    'title' => 'Thêm bình luận vào bảng thảo luận',
                    'method' => 'POST',
                    'path' => '/discussions',
                    'body' => '{
  "sectionId": "section-uuid",
  "content": "Em xin hỏi bài tập số 2",
  "authorName": "Nguyễn B",
  "authorEmail": "sv@ntt.edu.vn",
  "moderatorToken": "optional-neu-la-gv"
}',
                    'response' => '{ "success": true, "data": { "id": "cmt123", "text": "...", "authorRole": "student" } }',
                    'note' => 'Có moderatorToken → authorRole = lecturer.',
                ],
                [
                    'title' => 'Sinh đề kiểm tra AI (stub)',
                    'method' => 'POST',
                    'path' => '/ai/generate-test',
                    'body' => '{ "content": "Nội dung bài giảng...", "count": 5, "type": "mc" }',
                    'response' => '{ "success": true, "data": [ { "question": "...", "options": [], "correct": 0 } ] }',
                    'note' => 'Cần nội dung hoặc file. Hiện trả đề mẫu; tích hợp AI đầy đủ qua Tham số hệ thống.',
                ],
            ] as $ep)
                <article class="rounded-xl border border-slate-200 overflow-hidden">
                    <div class="flex flex-wrap items-center gap-2 border-b bg-slate-50 px-4 py-3">
                        <span class="rounded px-2 py-0.5 text-xs font-bold text-white {{ $ep['method'] === 'POST' ? 'bg-blue-600' : 'bg-orange-600' }}">{{ $ep['method'] }}</span>
                        <code class="text-xs font-semibold">{{ $ep['path'] }}</code>
                        <span class="ml-auto rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">API key</span>
                    </div>
                    <div class="space-y-2 p-4">
                        <h4 class="font-bold text-gray-900">{{ $ep['title'] }}</h4>
                        @if(!empty($ep['note']))
                            <p class="text-xs text-indigo-700">{{ $ep['note'] }}</p>
                        @endif
                        <p class="text-xs text-gray-500">Body JSON:</p>
                        <pre class="overflow-x-auto rounded bg-slate-900 p-3 text-xs text-slate-100"><code>{{ $ep['body'] }}</code></pre>
                        <p class="text-xs text-gray-500">Phản hồi:</p>
                        <pre class="overflow-x-auto rounded bg-slate-800 p-3 text-xs text-slate-200"><code>{{ $ep['response'] }}</code></pre>
                        <pre class="overflow-x-auto rounded border bg-white p-3 text-xs text-gray-700"><code>curl -X {{ $ep['method'] }} "{{ $apiBase }}{{ str_contains($ep['path'], '{') ? str_replace('{id}', '{uuid}', str_replace('{scheduleId}', '{scheduleId}', $ep['path'])) : $ep['path'] }}"
  -H "Content-Type: application/json"
  -H "x-api-key: YOUR_KEY"
  -d '{{ str_replace("\n", " ", $ep['body']) }}'</code></pre>
                    </div>
                </article>
            @endforeach

            <article class="rounded-xl border border-slate-200 p-4">
                <h4 class="font-bold">Bình chọn — sinh viên gửi vote (không cần API key)</h4>
                <p class="mt-1 text-xs text-gray-600">PATCH <code>/polls/{pollId}</code></p>
                <pre class="mt-2 overflow-x-auto rounded bg-slate-900 p-3 text-xs text-slate-100"><code>{
  "optionIndex": 1,
  "voterEmail": "sv001@student.ntt.edu.vn",
  "voterName": "Nguyễn Văn B"
}</code></pre>
            </article>
        </div>

        {{-- Truy vấn --}}
        <div x-show="tab === 'query'" x-cloak class="space-y-5 p-6 text-sm text-gray-700">
            <section>
                <h3 class="text-base font-bold text-gray-900">Tra cứu lịch học (GET /schedules)</h3>
                <table class="mt-3 w-full text-left text-xs">
                    <thead><tr class="border-b bg-slate-50"><th class="p-2">Tham số</th><th class="p-2">Bắt buộc</th><th class="p-2">Mô tả</th></tr></thead>
                    <tbody class="divide-y">
                        <tr><td class="p-2"><code>class</code></td><td class="p-2">Có</td><td class="p-2">Mã lớp — tìm LIKE hoặc khớp chính xác (vd. CNTT01, Y01)</td></tr>
                        <tr><td class="p-2"><code>date</code></td><td class="p-2">Không</td><td class="p-2">Định dạng <strong>dd/mm/yyyy</strong>. Mặc định: hôm nay theo server</td></tr>
                    </tbody>
                </table>
                <pre class="mt-3 overflow-x-auto rounded bg-slate-900 p-3 text-xs text-slate-100"><code>GET {{ $apiBase }}/schedules?class=CNTT01&date=17/06/2026
Header: x-api-key: YOUR_KEY</code></pre>
                <p class="mt-2 text-xs text-gray-500">Trả về mảng tiết học sắp theo <code>period</code>. Thiếu <code>class</code> → <code>data: []</code>.</p>
            </section>

            <section>
                <h3 class="text-base font-bold text-gray-900">Tra cứu theo ID (GET một bản ghi)</h3>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                    <li><code>GET /schedules/{id}</code> — một tiết lịch</li>
                    <li><code>GET /online-checkins/{id}</code> — một báo cáo online (payload gốc + id)</li>
                    <li><code>GET /polls/{id}</code> — chi tiết poll + voters</li>
                    <li><code>GET /exams/{id}</code> — đề + câu hỏi</li>
                </ul>
                <p class="mt-2 text-xs text-gray-500">Không cần API key. 404 nếu ID không tồn tại: <code>{ "success": false, "message": "..." }</code></p>
            </section>

            <section>
                <h3 class="text-base font-bold text-gray-900">Truy vấn danh sách thảo luận</h3>
                <pre class="mt-2 overflow-x-auto rounded bg-slate-900 p-3 text-xs text-slate-100"><code>GET {{ $apiBase }}/discussions</code></pre>
                <p class="mt-2 text-xs">Sắp xếp <code>created_at</code> giảm dần. Trang web công khai chỉ hiển thị một board khi có <code>?id=</code>.</p>
            </section>

            <section class="rounded-xl border border-violet-100 bg-violet-50/60 p-4">
                <h3 class="font-bold text-violet-900">Gợi ý tích hợp client JavaScript</h3>
                <pre class="mt-2 overflow-x-auto text-xs"><code>async function callApi(url, method = 'GET', body = null) {
  const headers = { 'Content-Type': 'application/json', 'x-api-key': API_KEY };
  const res = await fetch(url, {
    method,
    headers,
    body: body ? JSON.stringify(body) : undefined,
  });
  return res.json();
}

// Lấy lịch
const schedules = await callApi(
  `${API_BASE}/schedules?class=${encodeURIComponent('CNTT01')}&date=${encodeURIComponent('17/06/2026')}`
);

// Gửi check-in
const checkin = await callApi(`${API_BASE}/online-checkins`, 'POST', {
  class: 'CNTT01', type: 'online_manual', status: 'completed', ...
});</code></pre>
            </section>
        </div>

        {{-- Lỗi --}}
        <div x-show="tab === 'errors'" x-cloak class="space-y-4 p-6 text-sm">
            <table class="w-full text-left text-xs">
                <thead><tr class="border-b bg-slate-50"><th class="p-2">HTTP</th><th class="p-2">Nguyên nhân</th><th class="p-2">Cách xử lý</th></tr></thead>
                <tbody class="divide-y">
                    <tr><td class="p-2 font-mono">401</td><td class="p-2">Thiếu hoặc sai API key</td><td class="p-2">Kiểm tra <code>EXTERNAL_API_KEY</code> và header <code>x-api-key</code></td></tr>
                    <tr><td class="p-2 font-mono">404</td><td class="p-2">ID không tồn tại</td><td class="p-2">Xác nhận UUID/id từ response lúc tạo</td></tr>
                    <tr><td class="p-2 font-mono">422</td><td class="p-2">Dữ liệu không hợp lệ</td><td class="p-2">Đọc field <code>message</code> trong JSON</td></tr>
                    <tr><td class="p-2 font-mono">500</td><td class="p-2">Lỗi server</td><td class="p-2">Xem <code>storage/logs/laravel.log</code></td></tr>
                </tbody>
            </table>
            <div class="rounded-xl border border-red-100 bg-red-50/70 p-4 text-red-900">
                <p class="font-bold">Unauthorized mẫu</p>
                <pre class="mt-2 text-xs"><code>{ "success": false, "message": "Unauthorized. Invalid API Key." }</code></pre>
            </div>
            <p class="text-xs text-gray-500">Sau khi đổi API key trên server, cập nhật đồng bộ <code>ex-online/config.js</code> và reload extension trong Chrome.</p>
        </div>
    </div>
</div>
@endsection
