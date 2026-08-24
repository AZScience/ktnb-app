@extends('layouts.nttu')

@section('title', 'Tiếp nhận yêu cầu')

@section('page-section', 'Tiếp nhận yêu cầu')

@section('content')

@php
    $filterStatusOptions = [
        ['value' => 'immediate', 'label' => 'Hỗ trợ ngay'],
        ['value' => 'appointment', 'label' => 'Hẹn ngày trả lời'],
        ['value' => 'other', 'label' => 'Lý do khác'],
    ];
    $requestImportColumns = collect([
        'ticket_number' => 'Số phiếu',
        'request_type' => 'Loại yêu cầu',
        'reception_date' => 'Ngày tiếp',
        'request_date' => 'Ngày yêu cầu',
        'student_name' => 'Họ tên SV',
        'student_id' => 'MSSV',
        'class' => 'Lớp',
        'department' => 'Đơn vị',
        'phone' => 'Điện thoại',
        'content' => 'Nội dung',
        'recipient' => 'Người tiếp',
        'status' => 'Trạng thái',
        'building_block' => 'Dãy nhà',
        'is_processed_immediately' => 'Xử lý ngay',
        'appointment_date' => 'Ngày hẹn',
        'resolution_date' => 'Ngày giải quyết',
        'resolver_name' => 'Người giải quyết',
        'feedback' => 'Phản hồi',
        'note' => 'Ghi chú',
    ])->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values()->all();
@endphp

<x-catalog-data-table
    title="Sổ Tiếp nhận Yêu cầu"
    entity-label="yêu cầu"
    storage-key="service-requests"
    modal-wide
    form-mode="service-request"
    server-paginated
    form-layout="service-request"
    advanced-filter-mode="assets"
    :advanced-filter-options="$advancedFilterOptions"
    :staff-default="$staffDefault"
    :request-type-options="$requestTypeOptions"
    :building-options="$buildingOptions"
    :department-options="$departmentOptions"
    :form-defaults="[
        'reception_date' => date('d/m/Y'),
        'request_date' => date('d/m/Y'),
        'recipient' => $staffDefault,
        'status' => 'pending',
        'is_processed_immediately' => false,
        'request_type' => 'Phiếu yêu cầu hỗ trợ',
    ]"
    :items="$items"
    :columns="[
        'ticket_number' => ['label' => 'Số phiếu'],
        'reception_date' => ['label' => 'Ngày tiếp'],
        'request_type' => ['label' => 'Loại yêu cầu'],
        'building_block_label' => ['label' => 'Dãy nhà'],
        'student_name' => ['label' => 'Họ tên SV', 'primary' => true],
        'class' => ['label' => 'Lớp'],
        'department' => ['label' => 'Đơn vị'],
        'resolution_date' => ['label' => 'Ngày giải quyết', 'visible' => false],
        'resolution_outcome' => ['label' => 'Tình trạng giải quyết'],
        'status' => ['label' => 'Hướng xử lý', 'type' => 'service_resolution', 'visible' => false],
        'phone' => ['label' => 'Điện thoại', 'visible' => false],
    ]"
    :form-fields="[
        ['key' => 'building_block', 'type' => 'hidden'],
        ['key' => 'request_date', 'type' => 'hidden'],
        ['key' => 'status', 'type' => 'hidden'],
        ['key' => 'reception_date', 'type' => 'hidden'],
        ['key' => 'ticket_number', 'type' => 'hidden'],
        ['key' => 'request_type', 'type' => 'hidden'],
        ['key' => 'student_name', 'type' => 'hidden'],
        ['key' => 'student_id', 'type' => 'hidden'],
        ['key' => 'class', 'type' => 'hidden'],
        ['key' => 'department', 'type' => 'hidden'],
        ['key' => 'phone', 'type' => 'hidden'],
        ['key' => 'content', 'type' => 'hidden'],
        ['key' => 'recipient', 'type' => 'hidden'],
        ['key' => 'is_processed_immediately', 'type' => 'hidden'],
        ['key' => 'appointment_date', 'type' => 'hidden'],
        ['key' => 'note', 'type' => 'hidden'],
        ['key' => 'resolution_date', 'type' => 'hidden'],
        ['key' => 'resolver_name', 'type' => 'hidden'],
        ['key' => 'feedback', 'type' => 'hidden'],
        ['key' => 'evidence', 'label' => 'Hồ sơ kèm theo', 'type' => 'evidence'],
    ]"
    :filter-fields="[
        ['key' => 'student_name', 'label' => 'Họ tên SV'],
        ['key' => 'student_id', 'label' => 'MSSV'],
        ['key' => 'ticket_number', 'label' => 'Số phiếu'],
        ['key' => 'class', 'label' => 'Lớp'],
        ['key' => 'status', 'label' => 'Trạng thái', 'type' => 'select', 'options' => array_merge([['value' => '', 'label' => 'Tất cả']], $filterStatusOptions)],
    ]"
    :routes="[
        'store' => route('requests.store'),
        'update' => route('requests.update', ['service_request' => '__ID__']),
        'destroy' => route('requests.destroy', ['service_request' => '__ID__']),
        'export' => route('requests.export'),
        'importPreview' => route('requests.import-preview'),
        'import' => route('requests.import'),
            'list' => route('service-requests.index'),
    ]"
    :import-columns="$requestImportColumns"
/>

@endsection
