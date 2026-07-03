@extends('layouts.nttu')
@section('title', 'Loại văn bản')
@section('page-section', 'Loại văn bản')
@section('content')
<x-catalog-data-table
    title="Danh sách Loại văn bản"
    entity-label="loại văn bản"
    storage-key="document-types"
    :items="$items"
    :form-fields="[
        ['key' => 'name', 'label' => 'Loại văn bản', 'required' => true, 'icon' => 'file-text'],
        ['key' => 'note', 'label' => 'Ghi chú', 'icon' => 'note'],
    ]"
    :columns="[
        'name' => ['label' => 'Loại văn bản', 'primary' => true],
        'note' => ['label' => 'Ghi chú'],
    ]"
    :routes="[
        'store' => route('document-types.store'),
        'update' => route('document-types.update', ['document_type' => '__ID__']),
        'destroy' => route('document-types.destroy', ['document_type' => '__ID__']),
        'export' => route('document-types.export'),
        'importPreview' => route('document-types.import-preview'),
        'import' => route('document-types.import'),
    ]"
/>
@endsection
