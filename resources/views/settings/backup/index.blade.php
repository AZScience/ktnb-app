@extends('layouts.nttu')

@section('hide-page-heading', '')
@section('title', 'Sao lưu và Phục hồi')
@section('page-section', 'Thiết lập hệ thống')
@section('content')

@php
    $tabs = [
        'database' => ['label' => 'Cơ sở dữ liệu', 'icon' => '🗄️'],
        'project' => ['label' => 'Mã nguồn project', 'icon' => '📁'],
    ];
@endphp

@if (session('success'))
    <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        {{ $errors->first() }}
    </div>
@endif

<div class="mx-auto max-w-3xl space-y-6">
    <div class="space-y-3">
        <div class="flex items-start gap-3">
            <div class="rounded-lg bg-indigo-50 p-2.5">
                <svg class="h-7 w-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 md:text-3xl">Sao lưu và Phục hồi</h1>
                <p class="mt-2 text-sm text-gray-600 md:text-base">
                    Tạo bản sao lưu, tải về và phục hồi <strong>dữ liệu CSDL</strong> hoặc <strong>file mã nguồn project</strong>.
                </p>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2 border-b pb-3">
        @foreach ($tabs as $key => $info)
            <a href="{{ route('backup.index', ['tab' => $key]) }}"
                class="flex items-center gap-1.5 rounded-md px-4 py-2 text-sm font-medium transition {{ $tab === $key ? 'bg-[var(--nttu-table-head)] text-white shadow' : 'bg-white border hover:bg-slate-50' }}">
                <span>{{ $info['icon'] }}</span>{{ $info['label'] }}
            </a>
        @endforeach
    </div>

    @if ($tab === 'project')
        <x-backup-settings
            scope="project"
            :files="$projectFiles"
            :project-root-label="$projectRootLabel"
        />
    @else
        <x-backup-settings
            scope="database"
            :files="$databaseFiles"
            :purge-data-types="$purgeDataTypes"
        />
    @endif
</div>

@if (session('download_backup'))
    @php
        $downloadUrl = route('backup.download', [
            'filename' => session('download_backup'),
            'scope' => session('download_scope', 'database'),
        ]);
    @endphp
    <script>
        (function () {
            const url = @json($downloadUrl);
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = url;
            document.body.appendChild(iframe);
        })();
    </script>
@endif
@endsection
