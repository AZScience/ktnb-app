@extends('layouts.nttu')
@section('title', 'Người tốt việc tốt')
@section('page-section', 'Báo cáo thống kê')
@section('page-title', 'Người tốt việc tốt')
@section('content')
<div class="space-y-6">
    <x-interactive-report :config="$reportConfig" />
</div>
@endsection
