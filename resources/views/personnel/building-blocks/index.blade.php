@extends('layouts.nttu')
@section('title', 'Dãy nhà')
@section('page-section', 'Dãy nhà')
@section('content')
<x-catalog-data-table
    title="Danh sách Dãy nhà"
    entity-label="dãy nhà"
    storage-key="building_blocks"
    :items="$items"
    :columns="[
        'code' => ['label' => 'Mã phòng', 'primary' => true],
        'name' => ['label' => 'Dãy nhà', 'primary' => true],
        'is_inactive' => ['label' => 'Trạng thái', 'type' => 'boolean_status'],
        'note' => ['label' => 'Ghi chú'],
    ]"
    :form-fields="[
        ['key' => 'code', 'label' => 'Mã phòng', 'required' => true, 'icon' => 'id-card'],
        ['key' => 'name', 'label' => 'Dãy nhà', 'required' => true, 'icon' => 'building'],
        ['key' => 'is_inactive', 'label' => 'Ngưng sử dụng', 'type' => 'checkbox', 'icon' => 'ban', 'iconTone' => 'destructive'],
        ['key' => 'note', 'label' => 'Ghi chú', 'icon' => 'note'],
    ]"
    :import-columns="[
        ['key' => 'code', 'label' => 'Mã phòng'],
        ['key' => 'name', 'label' => 'Dãy nhà'],
        ['key' => 'note', 'label' => 'Ghi chú'],
    ]"
    :filter-fields="[
        ['key' => 'code', 'label' => 'Mã phòng'],
        ['key' => 'name', 'label' => 'Dãy nhà'],
    ]"
    :routes="[
        'store' => route('building-blocks.store'),
        'update' => route('building-blocks.update', ['building_block' => '__ID__']),
        'destroy' => route('building-blocks.destroy', ['building_block' => '__ID__']),
        'export' => route('building-blocks.export'),
        'importPreview' => route('building-blocks.import-preview'),
        'import' => route('building-blocks.import'),
    ]"
/>
@endsection
