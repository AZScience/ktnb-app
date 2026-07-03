@extends('layouts.nttu')
@section('title', 'Thông báo nội bộ')
@section('page-section', 'Công cụ hỗ trợ')
@section('content')
<x-catalog-data-table
    title="Quản lý thông báo"
    entity-label="thông báo"
    storage-key="announcements"
    modal-wide
    form-layout="grid"
    advanced-filter-mode="announcements"
    :items="$items"
    :form-fields="[
        ['key' => 'title', 'label' => 'Tiêu đề', 'required' => true, 'colSpan' => 3, 'icon' => 'megaphone', 'iconTone' => 'amber'],
        ['key' => 'body', 'label' => 'Nội dung', 'required' => true, 'type' => 'ckeditor', 'colSpan' => 3, 'icon' => 'note', 'iconTone' => 'amber'],
        ['key' => 'published_from', 'label' => 'Hiển thị từ ngày', 'type' => 'date', 'icon' => 'calendar', 'iconTone' => 'blue'],
        ['key' => 'published_until', 'label' => 'Hiển thị đến ngày', 'type' => 'date', 'icon' => 'calendar', 'iconTone' => 'blue'],
    ]"
    :columns="[
        'title' => ['label' => 'Tiêu đề', 'primary' => true],
        'body' => ['label' => 'Nội dung', 'visible' => false],
        'body_preview' => ['label' => 'Nội dung'],
        'author_name' => ['label' => 'Người đăng'],
        'published_from' => ['label' => 'Từ ngày', 'type' => 'date', 'visible' => false],
        'published_until' => ['label' => 'Đến ngày', 'type' => 'date', 'visible' => false],
        'created_at_label' => ['label' => 'Thời gian đăng'],
    ]"
    :routes="[
        'store' => route('announcements.store'),
        'update' => route('announcements.update', ['announcement' => '__ID__']),
        'destroy' => route('announcements.destroy', ['announcement' => '__ID__']),
    ]"
/>
@endsection
