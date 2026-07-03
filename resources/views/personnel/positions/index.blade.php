@extends('layouts.nttu')
@section('title', 'Chức vụ')
@section('page-section', 'Chức vụ')
@section('content')
<x-catalog-data-table
    title="Danh sách Chức vụ"
    entity-label="chức vụ"
    storage-key="positions"
    :items="$items"
    :form-fields="[
        ['key' => 'name', 'label' => 'Tên chức vụ', 'required' => true, 'icon' => 'briefcase'],
        ['key' => 'note', 'label' => 'Ghi chú', 'icon' => 'note'],
    ]"
    :columns="[
        'name' => ['label' => 'Tên chức vụ', 'primary' => true],
        'note' => ['label' => 'Ghi chú'],
    ]"
    :routes="[
        'store' => route('positions.store'),
        'update' => route('positions.update', ['position' => '__ID__']),
        'destroy' => route('positions.destroy', ['position' => '__ID__']),
        'export' => route('positions.export'),
        'importPreview' => route('positions.import-preview'),
        'import' => route('positions.import'),
    ]"
/>
@endsection
