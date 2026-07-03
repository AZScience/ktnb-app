@extends('layouts.nttu')
@section('title', 'Giảng viên')
@section('page-section', 'Giảng viên')
@section('content')
@php
    $departmentOptions = $departments->map(fn ($d) => ['value' => $d->id, 'label' => $d->name])->values()->all();
    $positionOptions = $positions->map(fn ($p) => ['value' => $p->id, 'label' => $p->name])->values()->all();
@endphp
<x-catalog-data-table
    title="Danh sách Giảng viên"
    entity-label="giảng viên"
    storage-key="lecturers"
    :items="$items"
    :columns="[
        'avatar_url' => ['label' => 'Hình', 'type' => 'avatar'],
        'id' => ['label' => 'Mã GV'],
        'name' => ['label' => 'Họ và tên', 'primary' => true],
        'department_name' => ['label' => 'Đơn vị'],
        'position_name' => ['label' => 'Chức vụ'],
        'birth_date' => ['label' => 'Ngày sinh', 'type' => 'date', 'visible' => false],
        'address' => ['label' => 'Địa chỉ', 'visible' => false],
        'phone' => ['label' => 'Điện thoại'],
        'email' => ['label' => 'Email'],
        'note' => ['label' => 'Ghi chú'],
    ]"
    :form-fields="[
        ['key' => 'avatar_url', 'label' => 'Hình đại diện', 'type' => 'avatar'],
        ['key' => 'id', 'label' => 'Mã GV', 'required' => true, 'readonlyOnEdit' => true, 'icon' => 'id-card', 'iconTone' => 'blue'],
        ['key' => 'name', 'label' => 'Họ và tên', 'required' => true, 'icon' => 'user', 'iconTone' => 'blue'],
        ['key' => 'department', 'label' => 'Đơn vị', 'type' => 'select', 'options' => array_merge([['value' => '', 'label' => 'Không có']], $departmentOptions), 'icon' => 'landmark', 'iconTone' => 'blue'],
        ['key' => 'position', 'label' => 'Chức vụ', 'type' => 'select', 'options' => array_merge([['value' => '', 'label' => 'Không có']], $positionOptions), 'icon' => 'briefcase', 'iconTone' => 'blue'],
        ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'icon' => 'mail', 'iconTone' => 'blue'],
        ['key' => 'birth_date', 'label' => 'Ngày sinh', 'type' => 'date', 'icon' => 'calendar', 'iconTone' => 'orange'],
        ['key' => 'phone', 'label' => 'Số điện thoại', 'icon' => 'phone', 'iconTone' => 'orange'],
        ['key' => 'address', 'label' => 'Địa chỉ', 'icon' => 'map-pin', 'iconTone' => 'orange'],
        ['key' => 'note', 'label' => 'Ghi chú', 'colSpan' => 3, 'icon' => 'note', 'iconTone' => 'orange'],
    ]"
    :form-sections="[
        ['id' => 'work-info', 'title' => 'THÔNG TIN CÔNG TÁC', 'icon' => 'book-user', 'tone' => 'primary', 'fields' => ['id', 'name', 'department', 'position', 'email']],
        ['id' => 'personal-info', 'title' => 'THÔNG TIN CÁ NHÂN', 'icon' => 'user-circle', 'tone' => 'orange', 'fields' => ['birth_date', 'phone', 'address', 'note']],
    ]"
    :import-columns="[
        ['key' => 'id', 'label' => 'Mã GV'],
        ['key' => 'name', 'label' => 'Họ và tên'],
        ['key' => 'department', 'label' => 'Đơn vị'],
        ['key' => 'position', 'label' => 'Chức vụ'],
        ['key' => 'birth_date', 'label' => 'Ngày sinh'],
        ['key' => 'address', 'label' => 'Địa chỉ'],
        ['key' => 'phone', 'label' => 'Điện thoại'],
        ['key' => 'email', 'label' => 'Email'],
        ['key' => 'note', 'label' => 'Ghi chú'],
    ]"
    :filter-fields="[
        ['key' => 'name', 'label' => 'Họ và tên'],
        ['key' => 'id', 'label' => 'Mã GV'],
        ['key' => 'department', 'label' => 'Đơn vị', 'type' => 'select', 'options' => array_merge([['value' => '', 'label' => 'Tất cả']], $departmentOptions)],
        ['key' => 'position', 'label' => 'Chức vụ', 'type' => 'select', 'options' => array_merge([['value' => '', 'label' => 'Tất cả']], $positionOptions)],
    ]"
    :routes="[
        'store' => route('lecturers.store'),
        'update' => route('lecturers.update', ['lecturer' => '__ID__']),
        'destroy' => route('lecturers.destroy', ['lecturer' => '__ID__']),
        'export' => route('lecturers.export'),
        'importPreview' => route('lecturers.import-preview'),
        'import' => route('lecturers.import'),
    ]"
/>
@endsection
