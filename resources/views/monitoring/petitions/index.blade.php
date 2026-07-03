@extends('layouts.nttu')
@section('title', 'Tiếp nhận đơn thư')
@section('page-section', 'Tiếp nhận đơn thư')
@section('content')
@php
    $petitionTypes = [
        ['value' => 'Khiếu nại', 'label' => 'Khiếu nại'],
        ['value' => 'Tố cáo', 'label' => 'Tố cáo'],
        ['value' => 'Kiến nghị', 'label' => 'Kiến nghị'],
        ['value' => 'Phản ánh', 'label' => 'Phản ánh'],
    ];
    $petitionImportColumns = collect([
        'reception_date' => 'Ngày tiếp',
        'building_block' => 'Dãy nhà',
        'recipient' => 'Người tiếp',
        'citizen_name' => 'Tên công dân',
        'citizen_id' => 'CCCD',
        'citizen_address' => 'Địa chỉ',
        'citizen_phone' => 'Điện thoại',
        'summary' => 'Nội dung tóm tắt',
        'petition_type' => 'Loại đơn',
        'number_of_people' => 'Số người',
        'previous_authority' => 'Cơ quan đã giải quyết',
        'is_accepted' => 'Tiếp nhận',
        'is_returned' => 'Trả lại',
        'is_forwarded' => 'Chuyển đơn',
        'resolution_follow_up' => 'Theo dõi giải quyết',
        'note' => 'Ghi chú',
    ])->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values()->all();
@endphp
<x-catalog-data-table
    title="Danh sách đơn thư"
    entity-label="đơn thư"
    storage-key="petitions"
    modal-wide
    form-mode="petition"
    form-layout="petition"
    advanced-filter-mode="assets"
    :advanced-filter-options="$assetAdvancedFilterOptions"
    :building-options="$buildingOptions"
    :staff-default="$staffDefault"
    :form-defaults="[
        'reception_date' => date('d/m/Y'),
        'recipient' => $staffDefault,
        'petition_type' => 'Kiến nghị',
        'number_of_people' => 1,
        'is_accepted' => false,
        'is_returned' => false,
        'is_forwarded' => false,
    ]"
    :items="$items"
    :columns="[
        'reception_date' => ['label' => 'Ngày tiếp'],
        'citizen_name' => ['label' => 'Tên công dân', 'primary' => true],
        'petition_type' => ['label' => 'Loại đơn'],
        'summary' => ['label' => 'Nội dung tóm tắt'],
        'building_block' => ['label' => 'Dãy nhà', 'visible' => false],
        'recipient' => ['label' => 'Người tiếp', 'visible' => false],
    ]"
    :form-fields="[
        ['key' => 'reception_date', 'type' => 'hidden'],
        ['key' => 'citizen_name', 'type' => 'hidden'],
        ['key' => 'citizen_id', 'type' => 'hidden'],
        ['key' => 'citizen_phone', 'type' => 'hidden'],
        ['key' => 'citizen_address', 'type' => 'hidden'],
        ['key' => 'building_block', 'type' => 'hidden'],
        ['key' => 'recipient', 'type' => 'hidden'],
        ['key' => 'summary', 'type' => 'hidden'],
        ['key' => 'petition_type', 'type' => 'hidden'],
        ['key' => 'number_of_people', 'type' => 'hidden'],
        ['key' => 'previous_authority', 'type' => 'hidden'],
        ['key' => 'is_accepted', 'type' => 'hidden'],
        ['key' => 'is_returned', 'type' => 'hidden'],
        ['key' => 'is_forwarded', 'type' => 'hidden'],
        ['key' => 'resolution_follow_up', 'type' => 'hidden'],
        ['key' => 'note', 'type' => 'hidden'],
    ]"
    :filter-fields="[
        ['key' => 'citizen_name', 'label' => 'Tên công dân'],
        ['key' => 'petition_type', 'label' => 'Loại đơn', 'type' => 'select', 'options' => array_merge([['value' => '', 'label' => 'Tất cả']], $petitionTypes)],
        ['key' => 'summary', 'label' => 'Nội dung'],
    ]"
    :routes="[
        'store' => route('petitions.store'),
        'update' => route('petitions.update', ['petition' => '__ID__']),
        'destroy' => route('petitions.destroy', ['petition' => '__ID__']),
        'export' => route('petitions.export'),
        'importPreview' => route('petitions.import-preview'),
        'import' => route('petitions.import'),
    ]"
    :import-columns="$petitionImportColumns"
/>
@endsection
