@extends('layouts.nttu')

@section('title', 'Sinh viên')
@section('page-section', 'Sinh viên')

@section('content')

@php
    $genderOptions = [
        ['value' => 'Nam', 'label' => 'Nam'],
        ['value' => 'Nữ', 'label' => 'Nữ'],
    ];
@endphp

<x-catalog-data-table
    title="Danh sách Sinh viên"
    entity-label="sinh viên"
    storage-key="students"
    modal-wide
    :form-defaults="['gender' => 'Nam']"
    :items="$items"
    :server-paginated="$serverPaginated ?? false"
    :columns="[
        'avatar_url' => ['label' => 'Hình ảnh', 'type' => 'avatar'],
        'id' => ['label' => 'Mã sinh viên'],
        'name' => ['label' => 'Họ và tên', 'primary' => true],
        'gender' => ['label' => 'Giới tính'],
        'birth_date' => ['label' => 'Ngày sinh', 'type' => 'date'],
        'birth_place' => ['label' => 'Nơi sinh', 'visible' => false],
        'hometown' => ['label' => 'Nguyên quán', 'visible' => false],
        'ethnicity' => ['label' => 'Dân tộc', 'visible' => false],
        'religion' => ['label' => 'Tôn giáo', 'visible' => false],
        'class' => ['label' => 'Lớp'],
        'major' => ['label' => 'Ngành', 'visible' => false],
        'department' => ['label' => 'Khoa', 'visible' => false],
        'permanent_address' => ['label' => 'Thường trú', 'visible' => false],
        'temporary_address' => ['label' => 'Tạm trú', 'visible' => false],
        'contact_address' => ['label' => 'Liên lạc', 'visible' => false],
        'region' => ['label' => 'Khu vực', 'visible' => false],
        'address' => ['label' => 'Địa chỉ', 'visible' => false],
        'phone' => ['label' => 'Điện thoại'],
        'email' => ['label' => 'Email', 'visible' => false],
        'citizen_id' => ['label' => 'Số CMND/CCCD', 'visible' => false],
        'father_name' => ['label' => 'Tên cha', 'visible' => false],
        'father_occupation' => ['label' => 'Nghề nghiệp cha', 'visible' => false],
        'mother_name' => ['label' => 'Tên mẹ', 'visible' => false],
        'mother_occupation' => ['label' => 'Nghề nghiệp mẹ', 'visible' => false],
        'parent_phone' => ['label' => 'ĐT phụ huynh', 'visible' => false],
        'note' => ['label' => 'Ghi chú'],
    ]"
    :form-fields="[
        ['key' => 'avatar_url', 'label' => 'Hình ảnh', 'type' => 'avatar'],
        ['key' => 'id', 'label' => 'Mã sinh viên', 'required' => true, 'icon' => 'id-card', 'iconTone' => 'blue'],
        ['key' => 'name', 'label' => 'Họ và tên', 'required' => true, 'icon' => 'user', 'iconTone' => 'blue'],
        ['key' => 'class', 'label' => 'Lớp', 'required' => true, 'icon' => 'users', 'iconTone' => 'blue'],
        ['key' => 'gender', 'label' => 'Giới tính', 'type' => 'select', 'options' => $genderOptions, 'icon' => 'users', 'iconTone' => 'blue'],
        ['key' => 'birth_date', 'label' => 'Ngày sinh', 'type' => 'date', 'icon' => 'calendar', 'iconTone' => 'blue'],
        ['key' => 'birth_place', 'label' => 'Nơi sinh', 'icon' => 'building', 'iconTone' => 'blue'],
        ['key' => 'hometown', 'label' => 'Nguyên quán', 'icon' => 'map-pin', 'iconTone' => 'blue'],
        ['key' => 'ethnicity', 'label' => 'Dân tộc', 'icon' => 'users', 'iconTone' => 'blue'],
        ['key' => 'religion', 'label' => 'Tôn giáo', 'icon' => 'users', 'iconTone' => 'blue'],
        ['key' => 'father_name', 'label' => 'Tên cha', 'icon' => 'user', 'iconTone' => 'orange'],
        ['key' => 'father_occupation', 'label' => 'Nghề nghiệp cha', 'icon' => 'briefcase', 'iconTone' => 'orange'],
        ['key' => 'mother_name', 'label' => 'Tên mẹ', 'icon' => 'user', 'iconTone' => 'orange'],
        ['key' => 'mother_occupation', 'label' => 'Nghề nghiệp mẹ', 'icon' => 'briefcase', 'iconTone' => 'orange'],
        ['key' => 'parent_phone', 'label' => 'ĐT phụ huynh', 'icon' => 'phone', 'iconTone' => 'orange'],
        ['key' => 'region', 'label' => 'Khu vực', 'icon' => 'map-pin', 'iconTone' => 'green'],
        ['key' => 'phone', 'label' => 'Điện thoại', 'icon' => 'phone', 'iconTone' => 'green'],
        ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'icon' => 'mail', 'iconTone' => 'green'],
        ['key' => 'address', 'label' => 'Địa chỉ', 'type' => 'textarea', 'colSpan' => 3, 'icon' => 'map-pin', 'iconTone' => 'green'],
        ['key' => 'permanent_address', 'label' => 'Thường trú', 'type' => 'textarea', 'colSpan' => 3, 'icon' => 'building', 'iconTone' => 'green'],
        ['key' => 'temporary_address', 'label' => 'Tạm trú', 'type' => 'textarea', 'colSpan' => 3, 'icon' => 'building', 'iconTone' => 'green'],
        ['key' => 'contact_address', 'label' => 'Liên lạc', 'type' => 'textarea', 'colSpan' => 3, 'icon' => 'note', 'iconTone' => 'green'],
        ['key' => 'citizen_id', 'label' => 'Số CMND/CCCD', 'icon' => 'id-card', 'iconTone' => 'green'],
        ['key' => 'major', 'label' => 'Ngành', 'icon' => 'briefcase', 'iconTone' => 'green'],
        ['key' => 'department', 'label' => 'Khoa', 'type' => 'select', 'options' => array_merge([['value' => '', 'label' => '---']], $departmentOptions), 'icon' => 'landmark', 'iconTone' => 'green'],
        ['key' => 'note', 'label' => 'Ghi chú', 'type' => 'textarea', 'colSpan' => 3, 'icon' => 'note', 'iconTone' => 'green'],
    ]"
    :form-sections="[
        ['id' => 'basic-info', 'title' => 'THÔNG TIN CƠ BẢN', 'icon' => 'users', 'tone' => 'primary', 'fields' => ['id', 'name', 'class', 'gender', 'birth_date', 'birth_place', 'hometown', 'ethnicity', 'religion']],
        ['id' => 'family-info', 'title' => 'THÔNG TIN GIA ĐÌNH', 'icon' => 'user-circle', 'tone' => 'orange', 'fields' => ['father_name', 'father_occupation', 'parent_phone', 'mother_name', 'mother_occupation']],
        ['id' => 'contact-info', 'title' => 'LIÊN LẠC & HỌC TẬP', 'icon' => 'phone', 'tone' => 'blue', 'columns' => 3, 'fields' => ['region', 'phone', 'email', 'address', 'permanent_address', 'temporary_address', 'contact_address', 'citizen_id', 'major', 'department', 'note']],
    ]"
    :import-columns="[
        ['key' => 'id', 'label' => 'Mã sinh viên'],
        ['key' => 'name', 'label' => 'Họ và tên'],
        ['key' => 'gender', 'label' => 'Giới tính'],
        ['key' => 'birth_date', 'label' => 'Ngày sinh'],
        ['key' => 'birth_place', 'label' => 'Nơi sinh'],
        ['key' => 'class', 'label' => 'Lớp'],
        ['key' => 'permanent_address', 'label' => 'Thường trú'],
        ['key' => 'contact_address', 'label' => 'Liên lạc'],
        ['key' => 'phone', 'label' => 'Điện thoại'],
        ['key' => 'email', 'label' => 'Email'],
        ['key' => 'citizen_id', 'label' => 'Số CMND/CCCD'],
        ['key' => 'department', 'label' => 'Khoa'],
        ['key' => 'note', 'label' => 'Ghi chú'],
    ]"
    :import-template-options="[
        'startColumn' => 1,
        'indexColumnLabel' => 'STT',
        'sheetName' => 'Sinh_vien',
        'sampleRow' => [
            'id' => 'SV240001',
            'name' => 'Nguyễn Văn A',
            'gender' => 'Nam',
            'birth_date' => '01/01/2006',
            'birth_place' => 'TP. Hồ Chí Minh',
            'class' => 'CNTT2401',
            'permanent_address' => '123 Đường ABC, Quận 1',
            'contact_address' => 'KTX NTTU',
            'phone' => '0901234567',
            'email' => 'sv240001@student.nttu.edu.vn',
            'citizen_id' => '079206001234',
            'department' => 'Công nghệ thông tin',
            'note' => '',
        ],
    ]"
    :filter-fields="[
        ['key' => 'name', 'label' => 'Họ và tên'],
        ['key' => 'id', 'label' => 'Mã sinh viên'],
        ['key' => 'class', 'label' => 'Lớp'],
        ['key' => 'department', 'label' => 'Khoa'],
        ['key' => 'major', 'label' => 'Ngành'],
    ]"
    :routes="[
        'list' => route('students.list'),
        'store' => route('students.store'),
        'update' => route('students.update', ['student' => '__ID__']),
        'destroy' => route('students.destroy', ['student' => '__ID__']),
        'export' => route('students.export'),
        'importPreview' => route('students.import-preview'),
        'import' => route('students.import'),
    ]"
/>

@endsection
