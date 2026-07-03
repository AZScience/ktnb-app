@extends('layouts.nttu')
@section('title', 'Quà tặng')
@section('page-section', 'Quà tặng')
@section('content')
<x-catalog-data-table
    title="Danh sách Quà tặng"
    entity-label="quà tặng"
    storage-key="gifts"
    :items="$items"
    :form-fields="[
        ['key' => 'name', 'label' => 'Tên quà tặng', 'required' => true, 'icon' => 'gift'],
        ['key' => 'note', 'label' => 'Ghi chú', 'icon' => 'note'],
    ]"
    :columns="[
        'name' => ['label' => 'Tên quà tặng', 'primary' => true],
        'note' => ['label' => 'Ghi chú'],
    ]"
    :routes="[
        'store' => route('gifts.store'),
        'update' => route('gifts.update', ['gift' => '__ID__']),
        'destroy' => route('gifts.destroy', ['gift' => '__ID__']),
        'export' => route('gifts.export'),
        'importPreview' => route('gifts.import-preview'),
        'import' => route('gifts.import'),
    ]"
/>
@endsection
