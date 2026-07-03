@extends('layouts.nttu')
@section('title', 'Việc ghi nhận')
@section('page-section', 'Việc ghi nhận')
@section('content')
<x-catalog-data-table
    title="Danh sách Việc ghi nhận"
    entity-label="việc ghi nhận"
    storage-key="recognitions"
    :items="$items"
    :form-fields="[
        ['key' => 'name', 'label' => 'Tên việc ghi nhận', 'required' => true, 'icon' => 'briefcase'],
        ['key' => 'note', 'label' => 'Ghi chú', 'icon' => 'note'],
    ]"
    :columns="[
        'name' => ['label' => 'Tên việc ghi nhận', 'primary' => true],
        'note' => ['label' => 'Ghi chú'],
    ]"
    :routes="[
        'store' => route('recognitions.store'),
        'update' => route('recognitions.update', ['recognition' => '__ID__']),
        'destroy' => route('recognitions.destroy', ['recognition' => '__ID__']),
        'export' => route('recognitions.export'),
        'importPreview' => route('recognitions.import-preview'),
        'import' => route('recognitions.import'),
    ]"
/>
@endsection
