@extends('layouts.nttu')

@section('title', 'Nhật ký truy cập')

@section('page-section', 'Thiết lập hệ thống')
@section('page-title', 'Nhật ký truy cập')
@section('content')

<x-activity-log-settings

    :logs="$logs"

    :user-options="$userOptions"

/>

@endsection

