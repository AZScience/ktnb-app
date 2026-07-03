@extends('layouts.nttu')
@section('title', 'Giám sát thực hành')
@section('page-section', 'Công cụ hỗ trợ')
@section('page-title', 'Giám sát thực hành')
@section('page-description', 'Kiểm duyệt minh chứng vị trí và hình ảnh — xem dạng lưới hoặc bảng chi tiết')
@section('content')

<x-checkin-monitor
    mode="external"
    :items="$items"
    :routes="[
        'update' => route('external-checkins.update', ['external_checkin' => '__ID__']),
        'bulkDestroy' => route('external-checkins.bulk-destroy'),
        'feed' => route('external-checkins.feed'),
    ]"
/>

@endsection
