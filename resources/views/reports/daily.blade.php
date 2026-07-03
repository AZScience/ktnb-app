@extends('layouts.nttu')
@section('title', 'Báo cáo cuối ngày')
@section('page-section', 'Báo cáo thống kê')
@section('page-title', 'Báo cáo cuối ngày')
@section('content')
<x-daily-report-viewer
    :date-iso="$dateIso"
    :display-date="$displayDate"
    :officer="$officer"
    :tab-definitions="$tabDefinitions"
    :datasets="$datasets"
    :export-url="$exportUrl"
    :google-sheets-configured="$googleSheetsConfigured"
    :default-sheet-tab="$defaultSheetTab"
    :google-sheet-tabs-url="$googleSheetTabsUrl"
    :google-sheet-push-url="$googleSheetPushUrl"
/>
@endsection
