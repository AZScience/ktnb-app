@extends('layouts.nttu')
@section('title', 'Thống kê biên bản')
@section('page-section', 'Báo cáo thống kê')
@section('page-title', 'Thống kê biên bản')
@section('content')
<x-interactive-report :config="$reportConfig" />
@endsection
