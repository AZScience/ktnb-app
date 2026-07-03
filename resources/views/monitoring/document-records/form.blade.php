@extends('layouts.nttu')
@section('title', $item ? 'Sửa hồ sơ' : 'Thêm hồ sơ')
@section('page-title', $item ? 'Sửa hồ sơ văn bản' : 'Thêm hồ sơ văn bản')
@section('content')
<form method="POST" action="{{ $item ? route('document-records.update', $item) : route('document-records.store') }}" enctype="multipart/form-data" class="nttu-card p-6 max-w-4xl space-y-4">
    @csrf
    @if ($item) @method('PUT') @endif

    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <x-form-label>Mã hồ sơ</x-form-label>
            <input name="doc_code" value="{{ old('doc_code', $item->doc_code ?? '') }}" class="nttu-form-control">
        </div>
        <div>
            <x-form-label>Số văn bản</x-form-label>
            <input name="doc_number" value="{{ old('doc_number', $item->doc_number ?? '') }}" class="nttu-form-control">
        </div>
        <div class="md:col-span-2">
            <x-form-label>Trích yếu *</x-form-label>
            <input name="title" required value="{{ old('title', $item->title ?? '') }}" class="nttu-form-control">
        </div>
        <div class="md:col-span-2">
            <x-form-label>Tóm tắt</x-form-label>
            <textarea name="abstract" rows="3" class="nttu-form-control">{{ old('abstract', $item->abstract ?? '') }}</textarea>
        </div>
        <div>
            <x-form-label>Loại văn bản</x-form-label>
            <select name="doc_type" class="nttu-form-control">
                <option value="">— Chọn —</option>
                @foreach ($docTypes as $type)
                    <option value="{{ $type->name }}" @selected(old('doc_type', $item->doc_type ?? '') === $type->name)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-form-label>Trạng thái</x-form-label>
            <select name="status" class="nttu-form-control">
                @foreach ($statusOptions as $st)
                    <option value="{{ $st }}" @selected(old('status', $item->status ?? 'Mới') === $st)>{{ $st }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-form-label>Ngày ban hành</x-form-label>
            <input name="issue_date" value="{{ old('issue_date', $item->issue_date ?? '') }}" placeholder="dd/mm/yyyy" class="nttu-form-control">
        </div>
        <div>
            <x-form-label>Ngày đến</x-form-label>
            <input name="received_date" value="{{ old('received_date', $item->received_date ?? '') }}" placeholder="dd/mm/yyyy" class="nttu-form-control">
        </div>
        <div>
            <x-form-label>Cơ quan ban hành</x-form-label>
            <input name="issuing_body" value="{{ old('issuing_body', $item->issuing_body ?? '') }}" class="nttu-form-control">
        </div>
        <div>
            <x-form-label>Người ký</x-form-label>
            <input name="signer" value="{{ old('signer', $item->signer ?? '') }}" class="nttu-form-control">
        </div>
        <div>
            <x-form-label>Đơn vị</x-form-label>
            <input name="department" list="departments" value="{{ old('department', $item->department ?? '') }}" class="nttu-form-control">
            <datalist id="departments">@foreach ($departments as $d)<option value="{{ $d->name }}">@endforeach</datalist>
        </div>
        <div>
            <x-form-label>Người phụ trách</x-form-label>
            <input name="assignee" list="employees" value="{{ old('assignee', $item->assignee ?? '') }}" class="nttu-form-control">
            <datalist id="employees">@foreach ($employees as $e)<option value="{{ $e->name }}">@endforeach</datalist>
        </div>
        <div>
            <x-form-label>Độ khẩn</x-form-label>
            <select name="urgency" class="nttu-form-control">
                @foreach ($urgencyOptions as $u)
                    <option value="{{ $u }}" @selected(old('urgency', $item->urgency ?? 'Thường') === $u)>{{ $u }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-form-label>Độ mật</x-form-label>
            <select name="confidentiality" class="nttu-form-control">
                @foreach ($confidentialityOptions as $c)
                    <option value="{{ $c }}" @selected(old('confidentiality', $item->confidentiality ?? 'Thường') === $c)>{{ $c }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-form-label>Từ khóa (phẩy)</x-form-label>
            <input name="keywords" value="{{ old('keywords', $item ? implode(', ', $item->keywords ?? []) : '') }}" class="nttu-form-control">
        </div>
        <div>
            <x-form-label>Mật khẩu tệp (nếu có)</x-form-label>
            <input name="file_password" type="password" value="{{ old('file_password', $item->file_password ?? '') }}" class="nttu-form-control" autocomplete="new-password">
        </div>
        <div class="md:col-span-2">
            <x-form-label>Tệp gốc (PDF/Word/Ảnh)</x-form-label>
            <input type="file" name="original_file_upload" class="w-full text-sm">
            @if ($item?->original_file)
                @php $files = app(\App\Services\EvidenceStorageService::class)->parse($item->original_file); @endphp
                @foreach ($files as $f)
                    <p class="text-xs mt-1"><a href="{{ $f['url'] }}" target="_blank" class="text-blue-600">{{ $f['name'] }}</a></p>
                @endforeach
            @endif
        </div>
        <div class="md:col-span-2">
            <x-form-label>Nội dung trích xuất</x-form-label>
            <textarea name="extracted_text" rows="4" class="nttu-form-control">{{ old('extracted_text', $item->extracted_text ?? '') }}</textarea>
        </div>
        <div class="md:col-span-2">
            <x-form-label>Tóm tắt AI</x-form-label>
            <textarea name="ai_summary" rows="3" class="nttu-form-control">{{ old('ai_summary', $item->ai_summary ?? '') }}</textarea>
        </div>
    </div>

    <div class="flex gap-3 pt-2">
        <x-form-actions cancel-href="{{ route('document-records.index') }}" />
    </div>
</form>
@endsection
