@extends('layouts.nttu')



@section('title', 'Vi phạm sinh viên')



@section('page-section', 'Vi phạm sinh viên')



@section('content')



@php



    $signedOptions = [



        ['value' => 'Chưa ký', 'label' => 'Chưa ký'],



        ['value' => 'Đã ký', 'label' => 'Đã ký'],



    ];



    $classOptions = App\Models\Student::select('class')->distinct()->pluck('class')
        ->merge(App\Models\StudentViolation::select('class')->distinct()->pluck('class'))
        ->filter()
        ->unique()
        ->sort()
        ->map(fn ($c) => ['value' => $c, 'label' => $c])
        ->values()
        ->all();



    $violationImportColumns = collect([
        'building' => 'Dãy nhà',
        'full_name' => 'Họ tên',
        'department' => 'Khoa',
        'class' => 'Lớp',
        'student_id' => 'Mã số SV',
        'violation_date' => 'Ngày vi phạm',
        'violation_type' => 'Lỗi vi phạm',
        'signed' => 'Ký tên',
        'officer' => 'CB ghi nhận',
        'note' => 'Ghi chú',
        'identifier' => 'Mã định danh',
    ])->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values()->all();

@endphp



<x-catalog-data-table



    title="Danh sách vi phạm sinh viên"



    entity-label="vi phạm"



    storage-key="student-violations"



    modal-wide



    form-mode="violations"



    :
    server-paginated="$serverPaginated"



    :violation-routes="[



        'compareFaces' => route('student-violations.compare-faces'),
        'extractCard' => route('student-violations.extract-card'),
    ]"



    :form-defaults="[



        'violation_date' => date('d/m/Y'),



        'signed' => 'Chưa ký',



        'officer' => $officerDefault,



    ]"



    :columns="[



        'building' => ['label' => 'Dãy nhà', 'visible' => false],



        'full_name' => ['label' => 'Họ tên', 'primary' => true],



        'department' => ['label' => 'Khoa', 'visible' => false],



        'class' => ['label' => 'Lớp'],



        'student_id' => ['label' => 'MSSV/CCCD'],



        'violation_date' => ['label' => 'Ngày vi phạm'],



        'violation_type' => ['label' => 'Lỗi vi phạm'],



        'signed' => ['label' => 'Ký xác nhận'],



        'officer' => ['label' => 'Người ghi nhận'],



        'note' => ['label' => 'Ghi chú', 'visible' => false],



    ]"



    :form-fields="[



        ['key' => 'student_id', 'label' => 'Mã số SV / CCCD', 'type' => 'violation_student_id', 'icon' => 'id-card', 'iconTone' => 'blue'],



        ['key' => 'full_name', 'label' => 'Họ tên sinh viên', 'required' => true, 'icon' => 'user', 'iconTone' => 'blue', 'readonlyOnModes' => ['view']],



        ['key' => 'class', 'label' => 'Lớp', 'type' => 'violation_class', 'options' => array_merge([['value' => '', 'label' => '---']], $classOptions), 'icon' => 'users', 'iconTone' => 'blue', 'readonlyOnModes' => ['view']],



        ['key' => 'building', 'label' => 'Dãy nhà', 'type' => 'select', 'options' => array_merge([['value' => '', 'label' => '---']], $buildingOptions), 'icon' => 'building', 'iconTone' => 'blue'],



        ['key' => 'department', 'label' => 'Khoa', 'type' => 'violation_department', 'options' => array_merge([['value' => '', 'label' => '---']], $departmentOptions), 'icon' => 'landmark', 'iconTone' => 'blue', 'readonlyOnModes' => ['view']],



        ['key' => 'violation_type', 'label' => 'Lỗi vi phạm', 'type' => 'select', 'options' => array_merge([['value' => '', 'label' => '---']], $violationTypeOptions), 'icon' => 'ban', 'iconTone' => 'orange'],



        ['key' => 'violation_date', 'label' => 'Ngày vi phạm', 'icon' => 'calendar', 'iconTone' => 'orange', 'hideOnModes' => ['add', 'edit']],



        ['key' => 'note', 'label' => 'Ghi chú thêm', 'type' => 'textarea', 'colSpan' => 2, 'icon' => 'note', 'iconTone' => 'orange'],



        ['key' => 'signed', 'label' => 'Ký xác nhận', 'type' => 'hidden'],



        ['key' => 'identifier', 'label' => 'Mã định danh', 'type' => 'hidden'],



        ['key' => 'signature_base64', 'label' => 'Ký tên xác nhận', 'type' => 'signature_pad', 'icon' => 'briefcase', 'iconTone' => 'blue'],



        ['key' => 'violation_verification', 'label' => 'Xác minh hình ảnh', 'type' => 'violation_verification', 'colSpan' => 2],



        ['key' => 'officer', 'label' => 'Người ghi nhận', 'icon' => 'user-cog', 'iconTone' => 'blue', 'hideOnModes' => ['add', 'edit']],



    ]"



    :form-sections="[



        ['id' => 'student-info', 'title' => 'THÔNG TIN SINH VIÊN', 'icon' => 'users', 'tone' => 'primary', 'columns' => 2, 'fields' => ['student_id', 'full_name', 'class', 'department']],



        ['id' => 'violation-info', 'title' => 'CHI TIẾT VI PHẠM', 'icon' => 'ban', 'tone' => 'orange', 'columns' => 2, 'fields' => ['building', 'violation_type', 'violation_date', 'note']],



        ['id' => 'verification-info', 'title' => 'XÁC MINH & HÌNH ẢNH', 'icon' => 'shield', 'tone' => 'blue', 'fields' => ['signature_base64', 'violation_verification', 'officer']],



    ]"



    advanced-filter-mode="violations"



    :advanced-filter-options="[



        'buildings' => collect($buildingOptions)->pluck('value')->values()->all(),



        'officers' => $officerFilterOptions,



    ]"



    :routes="[

        'store' => route('student-violations.store'),
        'show' => route('student-violations.show', ['student_violation' => '__ID__']),
        'update' => route('student-violations.update', ['student_violation' => '__ID__']),
        'destroy' => route('student-violations.destroy', ['student_violation' => '__ID__']),
        'export' => route('student-violations.export'),
        'importPreview' => route('student-violations.import-preview'),
        'import' => route('student-violations.import'),
            'list' => route('student-violations.index'),
    ]"
    :import-columns="$violationImportColumns"
/>



@endsection


