@extends('layouts.nttu')
@section('title', 'Đơn vị')
@section('page-section', 'Đơn vị')
@section('content')
<x-catalog-data-table
    title="Danh sách Đơn vị"
    entity-label="đơn vị"
    storage-key="departments"
    modal-wide
    :items="$items"
    :columns="[
        'department_id' => ['label' => 'Mã đơn vị', 'primary' => true],
        'name' => ['label' => 'Tên đơn vị', 'primary' => true],
        'head' => ['label' => 'Trưởng đơn vị'],
        'deputy_head' => ['label' => 'Phó đơn vị', 'visible' => false],
        'secretary' => ['label' => 'Thư ký', 'visible' => false],
        'spokesperson' => ['label' => 'Phát ngôn', 'visible' => false],
        'phone' => ['label' => 'Điện thoại'],
        'email' => ['label' => 'Email'],
        'note' => ['label' => 'Ghi chú'],
    ]"
    :form-fields="[
        ['key' => 'department_id', 'label' => 'Mã đơn vị', 'required' => true, 'icon' => 'id-card'],
        ['key' => 'name', 'label' => 'Tên đơn vị', 'required' => true, 'icon' => 'landmark'],
        ['key' => 'head', 'label' => 'Trưởng đơn vị', 'icon' => 'user', 'iconTone' => 'blue'],
        ['key' => 'deputy_head', 'label' => 'Phó đơn vị', 'icon' => 'users', 'iconTone' => 'blue'],
        ['key' => 'secretary', 'label' => 'Thư ký / Giáo vụ', 'icon' => 'user', 'iconTone' => 'blue'],
        ['key' => 'spokesperson', 'label' => 'Người phát ngôn', 'icon' => 'megaphone', 'iconTone' => 'blue'],
        ['key' => 'phone', 'label' => 'Điện thoại', 'icon' => 'phone'],
        ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'colSpan' => 2, 'icon' => 'mail'],
        ['key' => 'note', 'label' => 'Ghi chú', 'type' => 'textarea', 'colSpan' => 3, 'icon' => 'note'],
    ]"
    :import-columns="[
        ['key' => 'department_id', 'label' => 'Mã đơn vị'],
        ['key' => 'name', 'label' => 'Tên đơn vị'],
        ['key' => 'head', 'label' => 'Trưởng đơn vị'],
        ['key' => 'phone', 'label' => 'Điện thoại'],
        ['key' => 'email', 'label' => 'Email'],
        ['key' => 'note', 'label' => 'Ghi chú'],
    ]"
    :filter-fields="[
        ['key' => 'department_id', 'label' => 'Mã đơn vị'],
        ['key' => 'name', 'label' => 'Tên đơn vị'],
        ['key' => 'head', 'label' => 'Trưởng đơn vị'],
    ]"
    :routes="[
        'store' => route('departments.store'),
        'update' => route('departments.update', ['department' => '__ID__']),
        'destroy' => route('departments.destroy', ['department' => '__ID__']),
        'export' => route('departments.export'),
        'importPreview' => route('departments.import-preview'),
        'import' => route('departments.import'),
    ]"
/>
@endsection
