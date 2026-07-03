@extends('layouts.nttu')
@section('title', $title)
@section('page-section', $title)
@section('content')
<x-monitoring-schedule-table
    :items="$items"
    :module="$module"
    :date="$date"
    :date-notice="$dateNotice ?? null"
    :title="$title"
    :card-title="$cardTitle ?? $title"
    :modal-entity="$modalEntity ?? null"
    :ui-config="$uiConfig ?? []"
    :incident-categories="$incidentCategories"
    :master-data="$masterData"
    :employee-default="$employeeDefault ?? null"
/>
@endsection
