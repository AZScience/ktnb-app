@extends('layouts.nttu')
@section('title', 'Giám sát Online')
@section('page-section', 'Công cụ hỗ trợ')
@section('page-title', 'Giám sát Online')
@section('page-description', 'Duyệt báo cáo lớp học trực tuyến từ extension Chrome')
@section('content')

<x-checkin-monitor
    mode="online"
    :items="$items"
    :routes="[
        'update' => route('online-classes.update', ['online_checkin' => '__ID__']),
        'bulkDestroy' => route('online-classes.bulk-destroy'),
        'feed' => route('online-classes.feed'),
    ]"
/>

@endsection
