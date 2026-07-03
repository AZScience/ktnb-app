@extends('layouts.nttu')
@section('title', 'Lịch học theo ngày')
@section('page-section', 'Thiết lập hệ thống')
@section('page-title', 'Lịch học theo ngày')
@section('page-description', 'Quản lý lịch học chi tiết hàng ngày.')
@section('content')
<x-daily-schedule-settings :master-data="$masterData" :today-date="$todayDate" />
@endsection
