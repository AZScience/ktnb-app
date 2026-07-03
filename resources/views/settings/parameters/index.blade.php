@extends('layouts.nttu')
@section('title', 'Tham số hệ thống')
@section('page-section', 'Thiết lập hệ thống')
@section('page-title', 'Tham số hệ thống')
@section('page-description', 'Cấu hình các thông số vận hành và giao diện. Nhấn Lưu tất cả thay đổi để ghi vào cơ sở dữ liệu — các nút kiểm tra kết nối không tự lưu.')
@section('content')
<x-system-parameters-settings
    :params="$params"
    :save-url="$saveUrl"
    :verify-urls="$verifyUrls"
    :lecturer-portal-url="$lecturerPortalUrl"
/>
@endsection
