<aside
    class="nttu-sidebar fixed inset-y-0 left-0 z-40 flex h-screen shrink-0 flex-col overflow-hidden border-r border-gray-200 bg-white text-sm transition-[width,transform] duration-300 ease-in-out md:relative md:z-auto"
    :class="sidebarOpen ? 'w-64 translate-x-0' : 'w-64 -translate-x-full md:w-[4.5rem] md:translate-x-0'"
    :data-collapsed="!sidebarOpen"
>
    <div class="flex h-16 shrink-0 items-center justify-center overflow-hidden border-b px-2">
        <img
            x-show="sidebarOpen"
            src="{{ $bannerUrl }}"
            alt="Phòng Kiểm tra Nội bộ"
            class="w-full object-contain"
            style="height: {{ $bannerHeight ?? 40 }}px; max-height: {{ $bannerHeight ?? 40 }}px;"
        >
        <span x-show="!sidebarOpen" x-cloak class="text-xl font-bold text-[var(--nttu-primary)]">N</span>
    </div>
    <nav class="flex-1 overflow-y-auto p-2 space-y-0.5">
        @php
            $active = fn (string $pattern) => request()->routeIs($pattern);
            $linkClass = fn (bool $isActive) => 'nttu-sidebar-link ' . ($isActive ? 'active' : '');
            $menuCan = fn (string $module) => $nttuCan($module, 'access');
            $visibleItems = fn (array $items) => array_values(array_filter(
                $items,
                fn (array $item) => $menuCan($item[0])
            ));
        @endphp

        @if ($menuCan('/dashboard'))
        {{-- Tổng quan --}}
        <a href="{{ route('dashboard') }}" class="nttu-sidebar-link nttu-sidebar-top-link {{ $active('dashboard') ? 'active' : '' }}" title="Tổng quan">
            <svg class="h-[18px] w-[18px] shrink-0 {{ $active('dashboard') ? 'text-white' : 'text-sky-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            <span class="sidebar-label font-medium">Tổng quan</span>
        </a>
        @endif

        @php $catalogItems = $visibleItems([
            ['/personnel/positions', 'positions.index', 'Chức vụ', 'positions.*', 'text-indigo-500', 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
            ['/personnel/building-blocks', 'building-blocks.index', 'Dãy nhà', 'building-blocks.*', 'text-amber-500', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['/personnel/departments', 'departments.index', 'Đơn vị', 'departments.*', 'text-rose-500', 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z'],
            ['/personnel/classrooms', 'classrooms.index', 'Phòng học', 'classrooms.*', 'text-yellow-500', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['/personnel/lecturers', 'lecturers.index', 'Giảng viên', 'lecturers.*', 'text-cyan-500', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ['/personnel/employees', 'employees.index', 'Nhân viên', 'employees.*', 'text-fuchsia-500', 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['/personnel/students', 'students.index', 'Sinh viên', 'students.*', 'text-lime-500', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
            ['/personnel/gifts', 'gifts.index', 'Quà tặng', 'gifts.*', 'text-pink-500', 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7'],
            ['/personnel/roles', 'roles.index', 'Vai trò', 'roles.*', 'text-purple-500', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
            ['/personnel/recognitions', 'recognitions.index', 'Việc ghi nhận', 'recognitions.*', 'text-teal-500', 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
            ['/personnel/incident-categories', 'incident-categories.index', 'Việc phát sinh', 'incident-categories.*', 'text-pink-500', 'M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z'],
            ['/personnel/document-types', 'document-types.index', 'Loại văn bản', 'document-types.*', 'text-blue-500', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ]); @endphp
        @if (count($catalogItems) > 0)
        {{-- Bộ danh mục --}}
        <button type="button" @click="toggleMenu('catalog')" class="nttu-sidebar-group-btn w-full nttu-sidebar-link justify-between mt-2 {{ ($sidebarGroupActive['catalog'] ?? false) ? 'active' : '' }}" :class="{ 'open': isMenuOpen('catalog') }" title="Bộ danh mục">
            <span class="flex items-center gap-3">
                <svg class="h-[18px] w-[18px] text-orange-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <span class="sidebar-label font-medium">Bộ danh mục</span>
            </span>
            <svg class="sidebar-chevron h-4 w-4 transition-transform" :class="isMenuOpen('catalog') && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-cloak x-show="isMenuOpen('catalog')" class="nttu-sidebar-sub">
            @foreach ($catalogItems as [, $route, $label, $pattern, $iconColor, $path])
                <a href="{{ route($route) }}" class="{{ $linkClass($active($pattern)) }} text-[13px] py-1.5 gap-2" title="{{ $label }}">
                    <svg class="h-4 w-4 {{ $iconColor }} shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $path }}"/></svg>
                    <span class="sidebar-label">{{ $label }}</span>
                </a>
            @endforeach
        </div>
        @endif

        @php $monitorItems = $visibleItems([
            ['/monitoring/homeroom', 'monitoring.homeroom.index', 'Cố vấn học tập', 'monitoring.homeroom.*', 'homeroom', 'text-lime-500', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ['/monitoring/online', 'monitoring.online.index', 'Lớp học online', 'monitoring.online.*', 'online', 'text-blue-500', 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
            ['/monitoring/in-person', 'monitoring.in-person.index', 'Lớp học trực tiếp', 'monitoring.in-person.*', 'in-person', 'text-green-500', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
            ['/monitoring/exams', 'monitoring.exams.index', 'Thi kết thúc môn', 'monitoring.exams.*', 'exams', 'text-purple-500', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
            ['/monitoring/external-practice', 'monitoring.external-practice.index', 'Thực hành ngoài', 'monitoring.external-practice.*', 'external-practice', 'text-orange-500', 'M13 10V3L4 14h7v7l9-11h-7z'],
            ['/monitoring/student-violations', 'student-violations.index', 'Sinh viên vi phạm', 'student-violations.*', null, 'text-rose-500', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
            ['/monitoring/incident-records', 'incident-records.index', 'Biên bản sự việc', 'incident-records.*', null, 'text-cyan-600', 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
            ['/monitoring/asset-check', 'asset-check.index', 'Nhận - Trả tài sản', 'asset-check.*', null, 'text-pink-500', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['/monitoring/requests', 'requests.index', 'Tiếp nhận yêu cầu', 'requests.*', null, 'text-teal-500', 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
            ['/monitoring/petitions', 'petitions.index', 'Tiếp nhận đơn thư', 'petitions.*', null, 'text-red-500', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ['/monitoring/document-records', 'document-records.index', 'Quản lý hồ sơ', 'document-records.*', null, 'text-amber-500', 'M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z'],
        ]); @endphp
        @if (count($monitorItems) > 0)
        {{-- Công cụ kiểm tra --}}
        <button type="button" @click="toggleMenu('monitor')" class="nttu-sidebar-group-btn w-full nttu-sidebar-link justify-between mt-2 {{ ($sidebarGroupActive['monitor'] ?? false) ? 'active' : '' }}" :class="{ 'open': isMenuOpen('monitor') }" title="Công cụ kiểm tra">
            <span class="flex items-center gap-3">
                <svg class="h-[18px] w-[18px] text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span class="sidebar-label font-medium">Công cụ kiểm tra</span>
            </span>
            <svg class="sidebar-chevron h-4 w-4 transition-transform" :class="isMenuOpen('monitor') && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-cloak x-show="isMenuOpen('monitor')" class="nttu-sidebar-sub">
            @foreach ($monitorItems as [, $route, $label, $pattern, $mod, $iconColor, $path])
                @php
                    $isActive = $active($pattern) || ($mod && request()->routeIs('monitoring.schedules.*') && request()->route('module') === $mod);
                @endphp
                <a href="{{ route($route) }}" class="{{ $linkClass($isActive) }} text-[13px] py-1.5 gap-2" title="{{ $label }}">
                    <svg class="h-4 w-4 {{ $iconColor }} shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $path }}"/></svg>
                    <span class="sidebar-label">{{ $label }}</span>
                </a>
            @endforeach
        </div>
        @endif

        @php $reportItems = $visibleItems([
            ['/reports/daily', 'reports.daily', 'Báo cáo cuối ngày', 'reports.daily', 'text-sky-500', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['/feedback', 'feedback.index', 'Minh chứng ca trực', 'feedback.*', 'text-amber-500', 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
            ['/reports/comprehensive', 'reports.comprehensive', 'Thống kê việc KPH', 'reports.comprehensive', 'text-indigo-500', 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
            ['/reports/student-violations', 'reports.student-violations', 'Sinh viên vi phạm', 'reports.student-violations', 'text-rose-500', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
            ['/reports/good-deeds', 'reports.good-deeds', 'Người tốt việc tốt', 'reports.good-deeds', 'text-green-500', 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
            ['/reports/request-reports', 'reports.request-reports', 'Tiếp nhận yêu cầu', 'reports.request-reports', 'text-teal-500', 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
            ['/reports/incident-records-reports', 'reports.incident-records-reports', 'Thống kê biên bản', 'reports.incident-records-reports', 'text-cyan-600', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['/reports/incident-reports', 'reports.incident-reports', 'Tiếp nhận đơn thư', 'reports.incident-reports', 'text-red-500', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
        ]); @endphp
        @if (count($reportItems) > 0)
        {{-- Báo cáo thống kê --}}
        <button type="button" @click="toggleMenu('reports')" class="nttu-sidebar-group-btn w-full nttu-sidebar-link justify-between mt-2 {{ ($sidebarGroupActive['reports'] ?? false) ? 'active' : '' }}" :class="{ 'open': isMenuOpen('reports') }" title="Báo cáo thống kê">
            <span class="flex items-center gap-3">
                <svg class="h-[18px] w-[18px] text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span class="sidebar-label font-medium">Báo cáo thống kê</span>
            </span>
            <svg class="sidebar-chevron h-4 w-4 transition-transform" :class="isMenuOpen('reports') && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-cloak x-show="isMenuOpen('reports')" class="nttu-sidebar-sub">
            @foreach ($reportItems as [, $route, $label, $pattern, $iconColor, $path])
                <a href="{{ route($route) }}" class="{{ $linkClass($active($pattern)) }} text-[13px] py-1.5 gap-2" title="{{ $label }}">
                    <svg class="h-4 w-4 {{ $iconColor }} shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $path }}"/></svg>
                    <span class="sidebar-label">{{ $label }}</span>
                </a>
            @endforeach
        </div>
        @endif

        @php $systemItems = $visibleItems([
            ['/settings/schedule', 'schedules.index', 'Lịch học theo ngày', 'schedules.*', 'text-pink-500', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['/settings/parameters', 'parameters.index', 'Tham số hệ thống', 'parameters.*', 'text-lime-500', 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'],
            ['/settings/permissions', 'permissions.index', 'Phân quyền truy cập', 'permissions.*', 'text-orange-500', 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
            ['/settings/access-log', 'activity-logs.index', 'Nhật ký truy cập', 'activity-logs.*', 'text-cyan-500', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['/settings/backup', 'backup.index', 'Sao lưu & Phục hồi', 'backup.*', 'text-indigo-500', 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4'],
            ['/settings/project-files', 'project-files.index', 'Quản lý file mã nguồn', 'project-files.*', 'text-violet-500', 'M3 7h5l2 2h11v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z'],
        ]); @endphp
        @if (count($systemItems) > 0)
        {{-- Thiết lập hệ thống --}}
        <button type="button" @click="toggleMenu('system')" class="nttu-sidebar-group-btn w-full nttu-sidebar-link justify-between mt-2 {{ ($sidebarGroupActive['system'] ?? false) ? 'active' : '' }}" :class="{ 'open': isMenuOpen('system') }" title="Thiết lập hệ thống">
            <span class="flex items-center gap-3">
                <svg class="h-[18px] w-[18px] text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="sidebar-label font-medium">Thiết lập hệ thống</span>
            </span>
            <svg class="sidebar-chevron h-4 w-4 transition-transform" :class="isMenuOpen('system') && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-cloak x-show="isMenuOpen('system')" class="nttu-sidebar-sub">
            @foreach ($systemItems as [, $route, $label, $pattern, $iconColor, $path])
                <a href="{{ route($route) }}" class="{{ $linkClass($active($pattern)) }} text-[13px] py-1.5 gap-2" title="{{ $label }}">
                    <svg class="h-4 w-4 {{ $iconColor }} shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $path }}"/></svg>
                    <span class="sidebar-label">{{ $label }}</span>
                </a>
            @endforeach
        </div>
        @endif

        @php
            $toolItems = $visibleItems([
                ['/monitoring/external-checkins', 'external-checkins.index', 'Giám sát thực hành', 'external-checkins.*', 'text-blue-500', 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                ['/monitoring/online-classes', 'online-classes.index', 'Giám sát Online', 'online-classes.*', 'text-purple-500', 'M15 10l4.553-2.276A1 1 0 0121 8.382v7.236a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
                ['/monitoring/evidence', 'monitoring.evidence.index', 'Kho minh chứng', 'monitoring.evidence.*', 'text-blue-500', 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z M15 13a3 3 0 11-6 0 3 3 0 016 0z'],
                ['/ai/assistant', 'ai.assistant', 'Tra cứu thông tin', 'ai.*', 'text-blue-500', 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
                ['/tools/api-documentation', 'api-documentation.index', 'Tài liệu API', 'api-documentation.*', 'text-cyan-500', 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
                ['/tools/announcements', 'announcements.index', 'Thông báo nội bộ', 'announcements.*', 'text-amber-500', 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'],
                ['/monitoring/document-lookup', 'document-lookup.index', 'Tra cứu văn bản', 'document-lookup.*', 'text-sky-500', 'M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z'],
                ['/discussion', 'discussion.index', 'Bảng thảo luận ↗', 'discussion.*', 'text-pink-500', 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z'],
                ['/messaging', 'messaging.index', 'Hộp thư nội bộ', 'messaging.*', 'text-teal-500', 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                ['/lecturer-portal', 'lecturer-portal.index', 'Cổng Check-in Giảng viên ↗', 'lecturer-portal.*', 'text-indigo-500', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ]);
            $toolItemsNewTab = ['discussion.index', 'lecturer-portal.index'];
        @endphp
        @if (count($toolItems) > 0)
        {{-- Công cụ hỗ trợ --}}
        <button type="button" @click="toggleMenu('tools')" class="nttu-sidebar-group-btn w-full nttu-sidebar-link justify-between mt-2 {{ ($sidebarGroupActive['tools'] ?? false) ? 'active' : '' }}" :class="{ 'open': isMenuOpen('tools') }" title="Công cụ hỗ trợ">
            <span class="flex items-center gap-3">
                <svg class="h-[18px] w-[18px] text-purple-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/></svg>
                <span class="sidebar-label font-medium">Công cụ hỗ trợ</span>
            </span>
            <svg class="sidebar-chevron h-4 w-4 transition-transform" :class="isMenuOpen('tools') && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-cloak x-show="isMenuOpen('tools')" class="nttu-sidebar-sub">
            @foreach ($toolItems as [, $route, $label, $pattern, $iconColor, $path])
                <a href="{{ route($route) }}"
                    @if (in_array($route, $toolItemsNewTab, true)) target="_blank" rel="noopener noreferrer" @endif
                    class="{{ $linkClass($active($pattern)) }} text-[13px] py-1.5 gap-2" title="{{ $label }}">
                    <svg class="h-4 w-4 {{ $iconColor }} shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $path }}"/></svg>
                    <span class="sidebar-label">{{ $label }}</span>
                </a>
            @endforeach
        </div>
        @endif
    </nav>
    @php
        $footerName = $employee?->name ?? auth()->user()?->name ?? 'Admin';
    @endphp
    <div class="nttu-sidebar-footer flex h-[55px] shrink-0 items-center justify-center border-t border-gray-200 bg-white p-2">
        <div class="nttu-sidebar-footer-inner flex w-full cursor-pointer items-center gap-3 rounded-md p-2 transition-colors hover:bg-slate-100"
             title="{{ auth()->user()->email ?? '' }}">
            <svg class="h-8 w-8 shrink-0 text-[var(--nttu-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="sidebar-footer-text min-w-0 truncate text-sm font-medium text-gray-900">{{ $footerName }}</span>
        </div>
    </div>
</aside>
