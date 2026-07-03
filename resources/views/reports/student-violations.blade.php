@extends('layouts.nttu')
@section('title', 'Báo cáo SV vi phạm')
@section('page-section', 'Báo cáo thống kê')
@section('page-title', 'Báo cáo Sinh viên vi phạm')
@section('content')
<x-interactive-report :config="$reportConfig" />
@endsection
