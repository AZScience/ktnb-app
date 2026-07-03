@extends('layouts.nttu')
@section('title', 'Nhân viên')
@section('page-section', 'Nhân viên')
@section('content')
@php
    $roleOptions = $roles->map(fn ($r) => ['value' => $r->id, 'label' => $r->name])->values()->all();
    $positionOptions = $positions->map(fn ($p) => ['value' => $p->id, 'label' => $p->name])->values()->all();
@endphp
<x-catalog-data-table
    title="Danh sách Nhân viên"
    entity-label="nhân viên"
    storage-key="employees"
    :form-defaults="['role_id' => 'staff']"
    :items="$items"
    :server-paginated="$serverPaginated ?? false"
    :columns="[
        'avatar_url' => ['label' => 'Hình', 'type' => 'avatar'],
        'employee_id' => ['label' => 'Mã số'],
        'name' => ['label' => 'Họ và tên', 'primary' => true],
        'nickname' => ['label' => 'Biệt danh', 'visible' => false],
        'position_name' => ['label' => 'Chức vụ'],
        'role_name' => ['label' => 'Vai trò', 'type' => 'badge'],
        'phone' => ['label' => 'Điện thoại'],
        'birth_date' => ['label' => 'Ngày sinh', 'type' => 'date', 'visible' => false],
        'address' => ['label' => 'Địa chỉ', 'visible' => false],
        'email' => ['label' => 'Email'],
        'note' => ['label' => 'Ghi chú'],
    ]"
    :form-fields="[
        ['key' => 'avatar_url', 'label' => 'Hình đại diện', 'type' => 'avatar'],
        ['key' => 'employee_id', 'label' => 'Mã số', 'required' => true, 'readonlyOnEdit' => true, 'icon' => 'id-card', 'iconTone' => 'blue'],
        ['key' => 'name', 'label' => 'Họ và tên', 'required' => true, 'icon' => 'user', 'iconTone' => 'blue'],
        ['key' => 'nickname', 'label' => 'Biệt danh', 'icon' => 'sparkles', 'iconTone' => 'blue'],
        ['key' => 'position', 'label' => 'Chức vụ', 'type' => 'select', 'options' => array_merge([['value' => '', 'label' => 'Không có']], $positionOptions), 'icon' => 'briefcase', 'iconTone' => 'blue'],
        ['key' => 'role_id', 'label' => 'Vai trò', 'type' => 'select', 'options' => $roleOptions, 'icon' => 'shield', 'iconTone' => 'blue'],
        ['key' => 'email', 'label' => 'Email', 'required' => true, 'type' => 'email', 'icon' => 'mail', 'iconTone' => 'blue'],
        ['key' => 'birth_date', 'label' => 'Ngày sinh', 'type' => 'date', 'icon' => 'calendar', 'iconTone' => 'orange'],
        ['key' => 'phone', 'label' => 'Số điện thoại', 'icon' => 'phone', 'iconTone' => 'orange'],
        ['key' => 'address', 'label' => 'Địa chỉ', 'icon' => 'map-pin', 'iconTone' => 'orange'],
        ['key' => 'note', 'label' => 'Ghi chú', 'colSpan' => 3, 'icon' => 'note', 'iconTone' => 'orange'],
    ]"
    :form-sections="[
        ['id' => 'job-info', 'title' => 'THÔNG TIN CÔNG VIỆC', 'icon' => 'user-cog', 'tone' => 'primary', 'fields' => ['employee_id', 'name', 'nickname', 'position', 'role_id', 'email']],
        ['id' => 'personal-info', 'title' => 'THÔNG TIN CÁ NHÂN', 'icon' => 'user-circle', 'tone' => 'orange', 'fields' => ['birth_date', 'phone', 'address', 'note']],
    ]"
    :import-columns="[
        ['key' => 'employee_id', 'label' => 'Mã số'],
        ['key' => 'name', 'label' => 'Họ và tên'],
        ['key' => 'email', 'label' => 'Email'],
        ['key' => 'note', 'label' => 'Ghi chú'],
    ]"
    :filter-fields="[
        ['key' => 'name', 'label' => 'Họ và tên'],
        ['key' => 'employee_id', 'label' => 'Mã số'],
        ['key' => 'role_id', 'label' => 'Vai trò', 'type' => 'select', 'options' => array_merge([['value' => '', 'label' => 'Tất cả']], $roleOptions)],
        ['key' => 'position', 'label' => 'Chức vụ', 'type' => 'select', 'options' => array_merge([['value' => '', 'label' => 'Tất cả']], $positionOptions)],
    ]"
    :routes="[
        'list' => route('employees.list'),
        'store' => route('employees.store'),
        'update' => route('employees.update', ['employee' => '__ID__']),
        'destroy' => route('employees.destroy', ['employee' => '__ID__']),
        'export' => route('employees.export'),
        'importPreview' => route('employees.import-preview'),
        'import' => route('employees.import'),
    ]"
/>
@endsection
