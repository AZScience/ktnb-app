@extends('layouts.nttu')
@section('title', 'Báo cáo tiếp nhận đơn thư')
@section('page-section', 'Báo cáo thống kê')
@section('page-title', 'Báo cáo Tiếp nhận đơn thư')
@section('content')
<div class="space-y-6">
    <x-interactive-report :config="$reportConfig" />
</div>
@endsection
