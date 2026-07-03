@extends('layouts.nttu')
@section('title', 'Vai trò')
@section('page-section', 'Vai trò')
@section('content')
<x-catalog-data-table
    title="Danh sách Vai trò"
    entity-label="vai trò"
    storage-key="roles"
    :items="$items"
    :form-fields="[
        ['key' => 'name', 'label' => 'Tên vai trò', 'required' => true, 'icon' => 'shield'],
        ['key' => 'note', 'label' => 'Ghi chú', 'icon' => 'note'],
    ]"
    :columns="[
        'name' => ['label' => 'Tên vai trò', 'primary' => true],
        'note' => ['label' => 'Ghi chú'],
    ]"
    :routes="[
        'store' => route('roles.store'),
        'update' => route('roles.update', ['role' => '__ID__']),
        'destroy' => route('roles.destroy', ['role' => '__ID__']),
        'export' => route('roles.export'),
        'importPreview' => route('roles.import-preview'),
        'import' => route('roles.import'),
    ]"
/>
@endsection
