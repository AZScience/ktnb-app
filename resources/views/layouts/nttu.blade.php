<!DOCTYPE html>

@php
    $pageTitleKey = 'Công cụ Kiểm soát';
    if (View::hasSection('title')) {
        $pageTitleKey = trim(strip_tags(View::yieldContent('title')));
    } elseif (View::hasSection('page-section')) {
        $pageTitleKey = trim(strip_tags(View::yieldContent('page-section')));
    }
@endphp
<html lang="vi" data-page-title-key="{{ $pageTitleKey }}">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="ckeditor-upload-url" content="{{ \Illuminate\Support\Facades\Route::has('ckeditor.upload') ? route('ckeditor.upload') : url('/tools/ckeditor/upload') }}">

    <title>
        @hasSection('title')
            @yield('title')
        @elseif (View::hasSection('page-section'))
            @yield('page-section')
        @else
            Công cụ Kiểm soát
        @endif
    </title>

    <script>

        try {

            const match = document.cookie.match(/(?:^|; )sidebar_state=(true|false)/);

            if (match) {

                document.documentElement.dataset.sidebar = match[1] === 'true' ? 'expanded' : 'collapsed';

            }

        } catch (e) {}

    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body class="font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden" x-data="nttuShell(@js($sidebarInitialMenu ?? null))">
        <div
            x-show="sidebarOpen"
            x-cloak
            class="fixed inset-0 z-30 bg-black/50 md:hidden"
            @click="toggleSidebar()"
        ></div>
        <x-nttu-sidebar />

        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">

            <x-nttu-header />

            <main class="flex-1 overflow-y-auto bg-[var(--nttu-bg)] p-4 md:p-6">

                @php
                    use App\Support\PageHeading;

                    $pageSectionLabel = View::hasSection('page-section')
                        ? trim(strip_tags(View::yieldContent('page-section')))
                        : '';
                    $pageTitleLabel = View::hasSection('page-title')
                        ? trim(strip_tags(View::yieldContent('page-title')))
                        : '';
                    $pageTitleIsSubtitle = PageHeading::isSubtitle($pageTitleLabel);
                    $showPageTitleHeading = $pageTitleLabel !== '' && ! $pageTitleIsSubtitle;
                    $mainHeadingLabel = $showPageTitleHeading ? $pageTitleLabel : $pageSectionLabel;
                @endphp

                @if ($mainHeadingLabel !== '' && ! View::hasSection('hide-page-heading'))
                    <div class="mb-4">
                        @if ($showPageTitleHeading && $pageSectionLabel !== '' && $pageSectionLabel !== $pageTitleLabel)
                            <div class="mb-1 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                <x-nttu-page-heading-icon :label="$pageSectionLabel" size="sm" />
                                <span class="page-section-title">{{ $pageSectionLabel }}</span>
                            </div>
                        @endif
                        <div class="flex items-center gap-3">
                            <x-nttu-page-heading-icon :label="$mainHeadingLabel" />
                            <div>
                                <h1 class="text-2xl font-bold tracking-tight text-gray-900 page-section-title">{{ $mainHeadingLabel }}</h1>
                                @hasSection('page-description')
                                    <p class="mt-1 text-sm text-gray-500 i18n-auto">@yield('page-description')</p>
                                @elseif ($pageTitleIsSubtitle)
                                    <p class="mt-1 text-sm text-gray-500 i18n-auto">{{ $pageTitleLabel }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif ($pageTitleIsSubtitle)
                    <div class="mb-4 mt-0.5 text-sm text-gray-500 i18n-auto">{{ $pageTitleLabel }}</div>
                @endif

                @if (session('success'))

                    <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">

                        {{ session('success') }}

                    </div>

                @endif

                @if (session('error'))

                    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">

                        {{ session('error') }}

                    </div>

                @endif

                @yield('content')

            </main>

            <footer class="flex h-[55px] shrink-0 items-center justify-center border-t bg-white px-4 text-center text-[10px] text-gray-500 md:text-xs">
                <span class="nttu-footer-text" data-i18n="Bản quyền © 2026 thuộc về Đại học Nguyễn Tất Thành - Phòng Kiểm tra Nội bộ. All rights reserved.">Bản quyền © 2026 thuộc về Đại học Nguyễn Tất Thành - Phòng Kiểm tra Nội bộ. All rights reserved.</span>
            </footer>

        </div>

    </div>

</body>

</html>

