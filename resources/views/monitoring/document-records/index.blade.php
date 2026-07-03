@extends('layouts.nttu')
@section('title', 'Quản lý hồ sơ văn bản')
@section('page-section', 'Quản lý hồ sơ văn bản')
@section('content')
@php
    $docTypeFilterOptions = collect($docTypeOptions)->prepend(['value' => '', 'label' => 'Tất cả'])->values()->all();
    $statusFilterOptions = collect($statusOptions)->map(fn ($s) => ['value' => $s, 'label' => $s])->prepend(['value' => '', 'label' => 'Tất cả'])->values()->all();
@endphp
<x-catalog-data-table
    title="Quản lý hồ sơ"
    entity-label="hồ sơ"
    storage-key="document-records"
    modal-wide
    form-mode="document-record"
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
    ]"
/>
@endsection
