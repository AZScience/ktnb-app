@extends('layouts.nttu')
@section('title', 'Thống kê việc KPH')
@section('page-section', 'Báo cáo thống kê')
@section('page-title', 'Thống kê việc KPH')
@section('content')
<x-interactive-report :config="$reportConfig" />
@endsection
