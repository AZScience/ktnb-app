@extends('layouts.nttu')
@section('title', 'Phân quyền truy cập')
@section('page-section', 'Thiết lập hệ thống')
@section('page-title', 'Phân quyền truy cập')
@section('content')
<x-permission-settings
    :module-categories="$moduleCategories"
    :actions="$actions"
    :roles="$roles"
/>
@endsection
