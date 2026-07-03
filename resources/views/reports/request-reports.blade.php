@extends('layouts.nttu')
@section('title', 'Báo cáo tiếp nhận yêu cầu')
@section('page-section', 'Báo cáo thống kê')
@section('page-title', 'Báo cáo Tiếp nhận yêu cầu')
@section('content')
<x-interactive-report :config="$reportConfig" />
@endsection
