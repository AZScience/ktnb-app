@extends('layouts.nttu')

@section('title', 'Biên bản sự việc')
@section('page-section', 'Biên bản sự việc')

@section('content')
<x-catalog-data-table
    title="Danh sách Biên bản sự việc"
    entity-label="biên bản"
    storage-key="incident-records"
    advanced-filter-mode="incident-records"
    :advanced-filter-options="['officers' => $employeeOptions]"
    modal-wide
    form-mode="incident-record"
    form-layout="incident-record"
    :items="$records"
    :columns="[
        'incident_time' => ['label' => 'Ngày ghi nhận', 'type' => 'date'],
        'location' => ['label' => 'Tại địa điểm', 'primary' => true],
        'content' => ['label' => 'Nội dung ghi nhận'],
        'creator_name' => ['label' => 'Người lập'],
        'creator_signature' => ['label' => 'Chữ ký người lập', 'type' => 'image', 'width' => 'w-32'],
        'witness_name' => ['label' => 'Người chứng kiến', 'visible' => false],
        'witness_signature' => ['label' => 'Chữ ký chứng kiến', 'type' => 'image', 'width' => 'w-32', 'visible' => false],
    ]"
    :form-fields="[
        ['key' => 'incident_time', 'label' => 'Vào lúc (giờ, ngày tháng)*', 'type' => 'datetime-local', 'required' => true],
        ['key' => 'location', 'label' => 'Tại địa điểm*', 'required' => true],
        ['key' => 'content', 'label' => 'Nội dung ghi nhận*', 'type' => 'textarea', 'required' => true, 'rows' => 4],
        ['key' => 'conclusion_time', 'label' => 'Lập xong lúc*', 'type' => 'datetime-local', 'required' => true],
        ['key' => 'witness_name', 'label' => 'Người chứng kiến'],
        ['key' => 'creator_name', 'label' => 'Người lập biên bản*', 'required' => true],
        ['key' => 'participants', 'type' => 'hidden'],
        ['key' => 'witness_signature', 'type' => 'hidden'],
        ['key' => 'creator_signature', 'type' => 'hidden'],
        ['key' => 'evidence', 'type' => 'evidence'],
    ]"
    :form-defaults="[
        'incident_time' => date('Y-m-d\TH:i'),
        'conclusion_time' => date('Y-m-d\TH:i'),
        'creator_name' => auth()->user()->name,
    ]"
    :routes="[
        'index' => route('incident-records.index'),
        'store' => route('incident-records.store'),
        'update' => route('incident-records.update', '__ID__'),
        'destroy' => route('incident-records.destroy', '__ID__'),
    ]"
    :custom-row-actions="[
        ['label' => 'Xem / In Biên bản', 'url' => route('incident-records.show', '_id_'), 'colorClass' => 'text-blue-600'],
        ['label' => 'Xuất Word', 'url' => route('incident-records.export', '_id_'), 'colorClass' => 'text-green-600'],
    ]"
/>
@endsection