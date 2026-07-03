@extends('layouts.nttu')
@section('title', 'Phòng học')
@section('page-section', 'Phòng học')
@section('content')
@php
    $blockOptions = $blocks->map(fn ($b) => ['value' => $b->id, 'label' => $b->name])->values()->all();
    $roomTypeOptions = [
        ['value' => 'Lý thuyết', 'label' => 'Lý thuyết'],
        ['value' => 'Thực hành', 'label' => 'Thực hành'],
    ];
@endphp
<x-catalog-data-table
    title="Danh sách Phòng học"
    entity-label="phòng học"
    storage-key="classrooms"
    modal-wide
    :items="$items"
    :form-defaults="[
        'has_projector' => true,
        'is_inactive' => false,
        'room_type' => 'Lý thuyết',
    ]"
    :columns="[
        'name' => ['label' => 'Tên phòng', 'primary' => true],
        'building_block_name' => ['label' => 'Dãy nhà'],
        'room_type' => ['label' => 'Loại phòng'],
        'seating_capacity' => ['label' => 'Số chỗ ngồi'],
        'table_count' => ['label' => 'Số bàn', 'visible' => false],
        'exam_capacity' => ['label' => 'Số chỗ thi', 'visible' => false],
        'subject_nature' => ['label' => 'Tính chất môn', 'visible' => false],
        'has_projector' => ['label' => 'Máy chiếu', 'type' => 'boolean_yesno', 'visible' => false],
        'is_inactive' => ['label' => 'Trạng thái', 'type' => 'boolean_status'],
        'note' => ['label' => 'Ghi chú'],
    ]"
    :form-fields="[
        ['key' => 'name', 'label' => 'Tên phòng', 'required' => true, 'icon' => 'door'],
        ['key' => 'building_block_id', 'label' => 'Dãy nhà', 'required' => true, 'type' => 'select', 'options' => $blockOptions, 'icon' => 'building'],
        ['key' => 'room_type', 'label' => 'Loại phòng', 'type' => 'select', 'options' => $roomTypeOptions, 'icon' => 'layout'],
        ['key' => 'seating_capacity', 'label' => 'Số chỗ ngồi', 'type' => 'number', 'icon' => 'users'],
        ['key' => 'table_count', 'label' => 'Số bàn', 'type' => 'number', 'icon' => 'layout'],
        ['key' => 'exam_capacity', 'label' => 'Số chỗ thi', 'type' => 'number', 'icon' => 'folder'],
        ['key' => 'subject_nature', 'label' => 'Tính chất môn học', 'icon' => 'sparkles'],
        ['key' => 'has_projector', 'label' => 'Máy chiếu', 'type' => 'checkbox', 'checkboxStyle' => 'switch', 'icon' => 'monitor'],
        ['key' => 'is_inactive', 'label' => 'Ngưng sử dụng', 'type' => 'checkbox', 'checkboxStyle' => 'switch', 'icon' => 'ban', 'iconTone' => 'destructive'],
        ['key' => 'note', 'label' => 'Ghi chú', 'colSpan' => 3, 'icon' => 'note'],
    ]"
    :import-columns="[
        ['key' => 'name', 'label' => 'Tên phòng'],
        ['key' => 'building_block', 'label' => 'Dãy nhà'],
        ['key' => 'room_type', 'label' => 'Loại phòng'],
        ['key' => 'seating_capacity', 'label' => 'Số chỗ ngồi'],
        ['key' => 'table_count', 'label' => 'Số bàn'],
        ['key' => 'exam_capacity', 'label' => 'Số chỗ thi'],
        ['key' => 'note', 'label' => 'Ghi chú'],
    ]"
    :filter-fields="[
        ['key' => 'name', 'label' => 'Tên phòng'],
        ['key' => 'building_block_id', 'label' => 'Dãy nhà', 'type' => 'select', 'options' => array_merge([['value' => '', 'label' => 'Tất cả']], $blockOptions)],
    ]"
    :routes="[
        'store' => route('classrooms.store'),
        'update' => route('classrooms.update', ['classroom' => '__ID__']),
        'destroy' => route('classrooms.destroy', ['classroom' => '__ID__']),
        'export' => route('classrooms.export'),
        'importPreview' => route('classrooms.import-preview'),
        'import' => route('classrooms.import'),
    ]"
/>
@endsection
