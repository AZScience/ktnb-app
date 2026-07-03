@extends('layouts.nttu')

@section('title', 'Sửa nhân viên')
@section('page-title', 'Sửa nhân viên')

@section('content')
@include('personnel.employees._form', ['employee' => $employee])
@endsection
