@extends('layouts.nttu')
@section('title', 'Quản lý hồ sơ văn bản')
@section('page-section', 'Quản lý hồ sơ văn bản')
@section('content')
@php
    $docTypeFilterOptions = collect($docTypeOptions)->prepend(['value' => '', 'label' => 'Tất cả'])->values()->all();
    $statusFilterOptions = collect($statusOptions)->map(fn ($s) => ['value' => $s, 'label' => $s])->prepend(['value' => '', 'label' => 'Tất cả'])->values()->all();
    $documentImportColumns = [
        ['key' => 'doc_code', 'label' => 'Mã hồ sơ'],
        ['key' => 'doc_number', 'label' => 'Số / Ký hiệu'],
        ['key' => 'title', 'label' => 'Tiêu đề'],
        ['key' => 'abstract', 'label' => 'Trích yếu'],
        ['key' => 'doc_type', 'label' => 'Loại văn bản'],
        ['key' => 'issue_date', 'label' => 'Ngày ban hành'],
        ['key' => 'received_date', 'label' => 'Ngày đến'],
        ['key' => 'issuing_body', 'label' => 'Cơ quan ban hành'],
        ['key' => 'signer', 'label' => 'Người ký'],
        ['key' => 'department', 'label' => 'Đơn vị'],
        ['key' => 'assignee', 'label' => 'Phụ trách'],
        ['key' => 'urgency', 'label' => 'Độ khẩn'],
        ['key' => 'confidentiality', 'label' => 'Độ mật'],
        ['key' => 'status', 'label' => 'Trạng thái'],
        ['key' => 'original_file', 'label' => 'Đường dẫn file'],
        ['key' => 'note', 'label' => 'Ghi chú'],
    ];
@endphp
<x-catalog-data-table
    title="Quản lý hồ sơ"
    entity-label="hồ sơ"
    storage-key="document-records"
    modal-wide
    form-mode="document-record"
    server-paginated
    form-layout="document-record"
    advanced-filter-mode="documents"
    :advanced-filter-options="[
        'docTypes' => $docTypeOptions,
        'departments' => $departmentOptions,
        'employees' => $employeeOptions,
    ]"
    :form-defaults="['status' => 'Mới', 'urgency' => 'Thường', 'confidentiality' => 'Thường']"
    :doc-type-options="$docTypeOptions"
    :department-options="$departmentOptions"
    :employee-options="$employeeOptions"
    :urgency-options="$urgencyOptions"
    :confidentiality-options="$confidentialityOptions"
    :document-status-options="$statusOptions"
    :items="$items"
    :columns="[
        'doc_number' => ['label' => 'Số / Ký hiệu', 'width' => 'w-44 whitespace-nowrap'],
        'doc_code' => ['label' => 'Mã hồ sơ', 'visible' => false],
        'title' => ['label' => 'Tiêu đề', 'primary' => true, 'width' => 'w-[38%] min-w-[18rem]'],
        'abstract' => ['label' => 'Trích yếu', 'visible' => false],
        'doc_type' => ['label' => 'Loại'],
        'issue_date' => ['label' => 'Ngày BH'],
        'urgency' => ['label' => 'Độ khẩn', 'type' => 'badge'],
        'status' => ['label' => 'Trạng thái', 'type' => 'badge'],
        'department' => ['label' => 'Đơn vị', 'visible' => false],
        'assignee' => ['label' => 'Phụ trách', 'visible' => false],
    ]"
    :form-fields="[
        ['key' => 'doc_code', 'type' => 'hidden'],
        ['key' => 'doc_number', 'type' => 'hidden'],
        ['key' => 'title', 'type' => 'hidden'],
        ['key' => 'abstract', 'type' => 'hidden'],
        ['key' => 'doc_type', 'type' => 'hidden'],
        ['key' => 'status', 'type' => 'hidden'],
        ['key' => 'issue_date', 'type' => 'hidden'],
        ['key' => 'received_date', 'type' => 'hidden'],
        ['key' => 'issuing_body', 'type' => 'hidden'],
        ['key' => 'signer', 'type' => 'hidden'],
        ['key' => 'department', 'type' => 'hidden'],
        ['key' => 'assignee', 'type' => 'hidden'],
        ['key' => 'urgency', 'type' => 'hidden'],
        ['key' => 'confidentiality', 'type' => 'hidden'],
        ['key' => 'file_password', 'type' => 'hidden'],
        ['key' => 'original_file', 'type' => 'hidden'],
        ['key' => 'ai_summary', 'type' => 'hidden'],
        ['key' => 'extracted_text', 'type' => 'hidden'],
    ]"
    :filter-fields="[
        ['key' => 'title', 'label' => 'Tiêu đề'],
        ['key' => 'doc_number', 'label' => 'Số / Ký hiệu'],
        ['key' => 'doc_type', 'label' => 'Loại', 'type' => 'select', 'options' => $docTypeFilterOptions],
        ['key' => 'status', 'label' => 'Trạng thái', 'type' => 'select', 'options' => $statusFilterOptions],
    ]"
    :routes="[
        'store' => route('document-records.store'),
        'update' => route('document-records.update', ['document_record' => '__ID__']),
        'destroy' => route('document-records.destroy', ['document_record' => '__ID__']),
        'export' => route('document-records.export'),
        'importPreview' => route('document-records.import-preview'),
        'import' => route('document-records.import'),
        'extract' => route('document-records.extract'),
        'list' => route('document-records.index'),
    ]"
    :import-columns="$documentImportColumns"
    :import-template-options="[
        'startColumn' => 1,
        'indexColumnLabel' => 'STT',
        'sheetName' => 'Ho_so_van_ban',
        'sampleRow' => [
            'doc_code' => 'HS-2026-001',
            'doc_number' => '123/QĐ-NTTU',
            'title' => 'Quyết định ban hành quy chế đào tạo',
            'abstract' => 'Ban hành quy chế đào tạo năm học mới',
            'doc_type' => 'Quyết định',
            'issue_date' => '01/09/2026',
            'received_date' => '02/09/2026',
            'issuing_body' => 'Ban Giám hiệu',
            'signer' => 'GS.TS Nguyễn Văn A',
            'department' => 'Phòng Đào tạo',
            'assignee' => 'Nguyễn Văn B',
            'urgency' => 'Thường',
            'confidentiality' => 'Thường',
            'status' => 'Mới',
            'original_file' => '',
            'note' => '',
        ],
    ]"
/>
@endsection
