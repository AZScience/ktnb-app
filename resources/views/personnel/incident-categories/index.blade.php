@extends('layouts.nttu')
@section('title', 'Việc phát sinh')
@section('page-section', 'Việc phát sinh')
@section('content')
@php
    $recognitionOptions = $recognitions->map(fn ($r) => ['value' => $r->id, 'label' => $r->name])->values()->all();
@endphp
<x-catalog-data-table
    title="Danh sách Việc phát sinh"
    entity-label="việc phát sinh"
    storage-key="incident-categories"
    :items="$items"
    :form-fields="[
        ['key' => 'recognition_id', 'label' => 'Việc ghi nhận', 'required' => true, 'type' => 'select', 'options' => $recognitionOptions, 'icon' => 'briefcase'],
        ['key' => 'name', 'label' => 'Tên việc phát sinh', 'required' => true, 'icon' => 'folder'],
        ['key' => 'note', 'label' => 'Ghi chú', 'icon' => 'note'],
    ]"
    :columns="[
        'recognition_name' => ['label' => 'Việc ghi nhận'],
        'name' => ['label' => 'Tên việc phát sinh', 'primary' => true],
        'note' => ['label' => 'Ghi chú'],
    ]"
    :import-columns="[
        ['key' => 'recognition_name', 'label' => 'Việc ghi nhận'],
        ['key' => 'name', 'label' => 'Tên việc phát sinh'],
        ['key' => 'note', 'label' => 'Ghi chú'],
    ]"
    :filter-fields="[
        ['key' => 'name', 'label' => 'Tên việc phát sinh'],
        ['key' => 'recognition_id', 'label' => 'Việc ghi nhận', 'type' => 'select', 'options' => array_merge([['value' => '', 'label' => 'Tất cả']], $recognitionOptions)],
    ]"
    :routes="[
        'store' => route('incident-categories.store'),
        'update' => route('incident-categories.update', ['incident_category' => '__ID__']),
        'destroy' => route('incident-categories.destroy', ['incident_category' => '__ID__']),
        'export' => route('incident-categories.export'),
        'importPreview' => route('incident-categories.import-preview'),
        'import' => route('incident-categories.import'),
    ]"
/>
@endsection
