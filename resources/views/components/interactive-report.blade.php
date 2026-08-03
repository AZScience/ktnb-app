@props([
    'config',
])

@php
    $reportModule = \App\Support\PagePermissionFlags::reportModule($config['variant'] ?? 'comprehensive');
    $reportPerms = $nttuPage($reportModule);
    $config = array_merge($config, [
        'canExport' => $reportPerms['export'],
        'canEdit' => $reportPerms['edit'],
    ]);
@endphp

<div x-data="interactiveReportPage(@js($config))"
     class="nttu-interactive-report print:space-y-2"
     :class="[
         config.variant === 'good-deeds' ? 'space-y-6 report-variant-good-deeds' :
         config.variant === 'incident-reports' ? 'space-y-6 report-variant-incident-reports' :
         config.variant === 'comprehensive' ? 'space-y-4 report-variant-comprehensive' :
         'space-y-4'
     ]"
     :data-active-tab="activeTab"
     :data-report-theme="reportTheme"
     :data-report-variant="config.variant || ''"
     :data-table-card-theme="tableCardTheme"
     :data-table-head-theme="tableHeadTheme">
    <div x-show="toast" x-cloak
        class="fixed bottom-4 right-4 z-[70] rounded-lg px-4 py-3 text-sm shadow-lg"
        :class="toast?.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
        x-text="toast?.message"></div>

    <div x-show="monthlyReportModalOpen" x-cloak
        class="fixed inset-0 z-[80] flex items-center justify-center p-4 print:hidden"
        @keydown.escape.window="cancelMonthlyReportForm()">
        <div class="absolute inset-0 bg-black/50" @click="cancelMonthlyReportForm()"></div>
        <form class="relative w-full max-w-lg rounded-xl bg-white shadow-2xl"
            @submit.prevent="submitMonthlyReportForm()">
            <div class="border-b px-5 py-4">
                <h3 class="text-lg font-semibold text-gray-900">Thông tin Báo cáo tháng</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Cơ sở lọc lớp offline / CVHT offline. Khoa lọc Online / CVHT online (tổng số lớp đã ghi nhận). Nhân viên ghi nhận áp dụng cho toàn bộ số liệu.
                </p>
            </div>
            <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Từ ngày</label>
                    <input type="date" x-model="monthlyReportForm.fromDate" class="nttu-form-control" required>
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Đến ngày</label>
                    <input type="date" x-model="monthlyReportForm.toDate" class="nttu-form-control" required>
                </div>
                <div class="space-y-1 sm:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Cơ sở</label>
                    <select x-model="monthlyReportForm.campus" class="nttu-form-control" required>
                        <option value="">Chọn cơ sở</option>
                        <template x-for="option in monthlyReportCampusOptions" :key="'monthly-report-campus-' + option.value">
                            <option :value="option.value" x-text="option.label"></option>
                        </template>
                    </select>
                </div>
                <div class="space-y-1 sm:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Khoa</label>
                    <select x-model="monthlyReportForm.department" class="nttu-form-control" required>
                        <option value="">Chọn khoa</option>
                        <template x-for="option in monthlyReportDepartmentOptions" :key="'monthly-report-dept-' + option.value">
                            <option :value="option.value" x-text="option.label"></option>
                        </template>
                    </select>
                </div>
                <div class="space-y-1 sm:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Nhân viên ghi nhận</label>
                    <x-nttu-multi-select
                        field="monthlyReportUsers"
                        placeholder="Chọn nhân viên ghi nhận"
                        search-placeholder="Tìm nhân viên..."
                        empty-text="Không tìm thấy nhân viên"
                        :allow-create="false"
                        chip-mode="chips"
                        size="sm"
                    />
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t bg-slate-50 px-5 py-4">
                <button type="button" class="rounded-md border bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    @click="cancelMonthlyReportForm()">Hủy</button>
                <button type="submit" class="rounded-md bg-[var(--nttu-primary)] px-4 py-2 text-sm font-medium text-white hover:opacity-90">
                    Xuất báo cáo
                </button>
            </div>
        </form>
    </div>

    {{-- Sticky filter toolbar --}}
    <div class="report-filter-toolbar overflow-visible rounded-lg print:hidden"
         :class="['good-deeds', 'incident-reports'].includes(config.variant) ? 'border-none bg-white/90 shadow-lg backdrop-blur-md' : 'border border-gray-100 shadow-lg'">
        <div class="p-4">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <svg class="report-accent-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        <span class="font-bold text-gray-700 text-sm sidebar-label" data-i18n="Bộ lọc nâng cao">Bộ lọc nâng cao</span>
                        <button type="button" class="report-accent-btn ml-1 inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs"
                            @click="filtersExpanded = !filtersExpanded">
                            <svg class="h-4 w-4 transition-transform" :class="filtersExpanded && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            <span x-text="filtersExpanded ? labelText('Thu gọn') : labelText('Mở rộng bộ lọc')"></span>
                        </button>
                    </div>
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <button type="button" @click="printReport()" class="report-btn-outline inline-flex items-center gap-1.5 rounded-md border bg-white px-3 py-1.5 text-sm shadow-sm">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span class="sidebar-label" data-i18n="In báo cáo">In báo cáo</span>
                        </button>
                        <button type="button" x-show="canExport" x-cloak @click="exportExcel()" class="report-btn-export inline-flex items-center gap-1.5 rounded-md border bg-white px-3 py-1.5 text-sm shadow-sm">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span class="sidebar-label" data-i18n="Xuất Excel">Xuất Excel</span>
                        </button>
                        <button type="button" x-show="canExport && config.monthlyReportUrl" x-cloak @click="exportMonthlyReport()" class="report-btn-export inline-flex items-center gap-1.5 rounded-md border bg-white px-3 py-1.5 text-sm shadow-sm">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span class="sidebar-label" data-i18n="Báo cáo tháng">Báo cáo tháng</span>
                        </button>
                    </div>
                </div>

                <p x-show="googleSheetsEnabled && !googleSheetsConfigured" x-cloak class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
                    <span class="sidebar-label" data-i18n="Chưa cấu hình Google Sheet báo cáo tổng hợp học kỳ. Vào">Chưa cấu hình Google Sheet báo cáo tổng hợp học kỳ. Vào</span>
                    <a href="{{ route('parameters.index') }}" class="font-medium underline sidebar-label" data-i18n="Tham số hệ thống">Tham số hệ thống</a>
                    <span class="sidebar-label" data-i18n="(tab Tích hợp) và thêm summaryReportGoogleSheetId cùng Service Account."> (tab Tích hợp) và thêm <strong>Google Sheet ID – Báo cáo tổng hợp học kỳ</strong> cùng Service Account.</span>
                </p>

                <div x-show="filtersExpanded" x-cloak
                    class="animate-in fade-in duration-200 rounded-lg border border-gray-100 bg-gray-50 p-4 grid grid-cols-1 sm:grid-cols-2 gap-3
                    @if(($config['variant'] ?? '') === 'comprehensive')
                        md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5
                    @elseif(($config['filterGridCols'] ?? 5) === 4)
                        lg:grid-cols-4
                    @elseif(($config['filterGridCols'] ?? 5) === 3)
                        lg:grid-cols-3
                    @else
                        lg:grid-cols-3 xl:grid-cols-5
                    @endif">
                    <div class="flex flex-col gap-1">
                        <x-filter-label data-i18n="Từ ngày">Từ ngày</x-filter-label>
                        <input type="date" x-model="fromDate" class="rounded-md border-gray-300 bg-white text-sm shadow-sm h-9" :disabled="loadingRows">
                    </div>
                    <div class="flex flex-col gap-1">
                        <x-filter-label data-i18n="Đến ngày">Đến ngày</x-filter-label>
                        <input type="date" x-model="toDate" class="rounded-md border-gray-300 bg-white text-sm shadow-sm h-9" :disabled="loadingRows">
                    </div>
                    <div x-show="loadingRows && pagedRows.length > 0" x-cloak class="flex items-end pb-1 text-xs text-[var(--nttu-primary)]">
                        Đang tải dữ liệu...
                    </div>

                    <template x-if="config.advancedFilters?.includes('period')">
                        <div class="report-filter-period flex flex-col gap-1 min-w-0">
                            <x-filter-label>Ca trực (Tiết)</x-filter-label>
                            <select x-model="advanced.periodSession" @change="currentPage = 1"
                                class="report-filter-period-select w-full rounded-md border-gray-300 bg-white shadow-sm h-8 text-xs">
                                <option value="all">Tất cả ca</option>
                                <option value="ca1">Ca 1 (Tiết 1 - 5)</option>
                                <option value="ca2">Ca 2 (Tiết 6 - 10)</option>
                                <option value="ca3">Ca 3 (Tiết 11 - 16)</option>
                                <option value="custom">Tùy chọn...</option>
                            </select>
                            <div x-show="advanced.periodSession === 'custom'" x-cloak class="flex items-center gap-1.5">
                                <span class="text-[10px] font-medium text-gray-500 shrink-0">Từ</span>
                                <input type="number" min="1" max="20" x-model="advanced.periodStart" @input="currentPage = 1"
                                    class="h-7 w-12 rounded-md border-gray-300 bg-white px-1.5 text-xs text-center">
                                <span class="text-[10px] font-medium text-gray-500 shrink-0">đến</span>
                                <input type="number" min="1" max="20" x-model="advanced.periodEnd" @input="currentPage = 1"
                                    class="h-7 w-12 rounded-md border-gray-300 bg-white px-1.5 text-xs text-center">
                            </div>
                        </div>
                    </template>

                    <template x-if="config.advancedFilters?.includes('buildings')">
                        <div class="flex flex-col gap-1">
                            <x-filter-label data-i18n="Dãy nhà">Dãy nhà</x-filter-label>
                            @include('components.partials.report-multi-select', [
                                'field' => 'buildings',
                                'placeholder' => $config['filterPlaceholders']['buildings'] ?? 'Chọn dãy nhà...',
                                'emptyText' => $config['filterEmptyText']['buildings'] ?? 'Không tìm thấy dãy nhà',
                            ])
                        </div>
                    </template>

                    <template x-if="config.advancedFilters?.includes('departments')">
                        <div class="flex flex-col gap-1">
                            <x-filter-label>Khoa / Đơn vị</x-filter-label>
                            @include('components.partials.report-multi-select', ['field' => 'departments', 'placeholder' => 'Tất cả khoa/đơn vị'])
                        </div>
                    </template>

                    <template x-if="config.advancedFilters?.includes('employees')">
                        <div class="flex flex-col gap-1">
                            <x-filter-label>Nhân viên</x-filter-label>
                            @include('components.partials.report-multi-select', ['field' => 'employees', 'placeholder' => 'Tất cả nhân viên'])
                        </div>
                    </template>

                    <template x-if="config.advancedFilters?.includes('lecturers')">
                        <div class="flex flex-col gap-1">
                            <x-filter-label>Giảng viên / CBCT</x-filter-label>
                            @include('components.partials.report-multi-select', ['field' => 'lecturers', 'placeholder' => 'Tất cả giảng viên'])
                        </div>
                    </template>

                    <template x-if="config.advancedFilters?.includes('recipients')">
                        <div class="flex flex-col gap-1">
                            <x-filter-label class="sidebar-label">{{ $config['filterLabels']['recipients'] ?? 'Nhân viên tiếp nhận' }}</x-filter-label>
                            @include('components.partials.report-multi-select', [
                                'field' => 'recipients',
                                'placeholder' => $config['filterPlaceholders']['recipients'] ?? 'Chọn nhân viên...',
                                'emptyText' => $config['filterEmptyText']['recipients'] ?? 'Không tìm thấy nhân viên',
                            ])
                        </div>
                    </template>

                    <template x-if="config.advancedFilters?.includes('officers')">
                        <div class="flex flex-col gap-1">
                            <x-filter-label>CB ghi nhận</x-filter-label>
                            @include('components.partials.report-multi-select', ['field' => 'officers', 'placeholder' => 'Tất cả CB ghi nhận'])
                        </div>
                    </template>

                    <template x-if="config.advancedFilters?.includes('violationTypes')">
                        <div class="flex flex-col gap-1">
                            <x-filter-label>Lỗi vi phạm</x-filter-label>
                            @include('components.partials.report-multi-select', ['field' => 'violationTypes', 'placeholder' => 'Tất cả lỗi vi phạm'])
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs above card (good deeds) --}}
    @if (!empty($config['tabsAbove']))
    <div class="report-tabs grid grid-cols-2 gap-1 rounded-lg p-1 print:hidden mb-4" style="max-width: {{ $config['tabMaxWidth'] ?? '28rem' }}">
        @foreach ($config['tabs'] as $tab)
        <button type="button"
            class="inline-flex items-center justify-center gap-1.5 rounded-md px-3 py-2 text-sm font-semibold transition"
            :class="activeTab === @js($tab['key']) ? 'report-tab-active shadow-sm' : 'text-gray-700 hover:bg-white/60'"
            @click="switchTab(@js($tab['key']))">
            @if (($tab['icon'] ?? '') === 'package')
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            @elseif (($tab['icon'] ?? '') === 'star')
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
            @endif
            <span class="sidebar-label" x-text="labelText(@js($tab['label']))">{{ $tab['label'] }}</span>
        </button>
        @endforeach
    </div>
    @endif

    <div class="nttu-card report-table-wrap overflow-hidden shadow-sm border-none">
        <div class="report-card-header border-b px-6 py-3 print:hidden">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <h3 class="{{ $config['cardTitleClass'] ?? 'text-sm' }} font-black uppercase tracking-widest flex items-center gap-2">
                    <template x-if="cardIcon === 'package'">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </template>
                    <template x-if="cardIcon === 'star'">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    </template>
                    <template x-if="cardIcon === 'shield-alert'">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
                    </template>
                    <span x-text="currentTableTitle"></span>
                    (<span x-text="filteredRows.length"></span>)
                </h3>
                <button type="button"
                    x-show="showGoogleSheetsPush && (canEdit || canExport)"
                    x-cloak
                    @click="openGoogleSheetsDialog()"
                    class="inline-flex items-center gap-2 rounded-md border border-orange-500 px-3 py-2 text-xs font-medium text-orange-700 shadow-sm hover:bg-orange-600 hover:text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <span class="sidebar-label" data-i18n="Đẩy lên GoogleSheet">Đẩy lên GoogleSheet</span>
                </button>
            </div>
        </div>

        <div class="report-table-body-wrap" :class="config.tableBodyPadding === false ? 'flush' : (config.tableFrame ? 'p-4' : '')">
            <div :class="config.tableFrame ? 'report-table-frame flex flex-col' : ''">
            <div :class="config.tableFrame ? 'overflow-x-auto overflow-y-visible rounded-t-md border border-b-0 border-blue-200' : 'overflow-x-auto overflow-y-visible'">
            <table class="nttu-table nttu-catalog-table w-full min-w-full"
                :class="headerMode === 'grouped' ? 'min-w-[3000px]' : (headerMode === 'handling' ? 'min-w-[1200px]' : (activeTab === 'deed' ? 'min-w-[1400px]' : ''))"
                x-bind:data-col-resize="'interactive_report_' + (config.tabs?.length ? activeTab : config.variant)">
                <thead class="report-thead">
                    @if (($config['variant'] ?? '') === 'good-deeds')
                        @include('components.partials.good-deeds-thead', ['config' => $config])
                    @elseif (($config['variant'] ?? '') === 'incident-reports')
                        @include('components.partials.incident-reports-thead', ['config' => $config])
                    @else
                    {{-- Flat header (1 row) --}}
                    <tr x-show="headerMode === 'flat'" x-cloak class="report-thead-flat">
                        <th class="catalog-th-index"
                            :class="config.variant === 'good-deeds' ? (activeTab === 'deed' ? 'w-[60px]' : 'w-[50px]') : 'w-[60px]'">#</th>
                        <template x-for="col in visibleColumns" :key="'flat-' + col.key">
                            <th class="catalog-th-col relative" :data-col="col.key" :data-col-label="columnLabel(col)" :class="col.width || ''">
                                @include('components.partials.report-column-header')
                            </th>
                        </template>
                        <th class="catalog-th-settings relative">
                            @include('components.partials.report-settings-column-inner', ['floatWhen' => "settingsOpen && headerMode === 'flat'"])
                        </th>
                    </tr>

                    {{-- Grouped header row 1 --}}
                    <tr x-show="headerMode === 'grouped'" x-cloak class="report-thead-grouped">
                        <th rowspan="2" class="catalog-th-index border-b"
                            :class="config.variant === 'good-deeds' ? 'w-[50px]' : 'w-[60px]'">#</th>
                        <template x-for="col in spanTwoColumns" :key="'span-' + col.key">
                            <th rowspan="2" class="catalog-th-col border-b" :data-col="col.key" :data-col-label="columnLabel(col)" :class="col.width || ''">
                                @include('components.partials.report-column-header')
                            </th>
                        </template>
                        <th x-show="groupColSpan('Tiếp nhận') > 0"
                            :colspan="groupColSpan('Tiếp nhận')"
                            class="report-th-group-label text-center text-sm font-bold uppercase border-r border-b p-2"
                            :class="groupHeaderClass('reception')"
                            x-text="groupLabelText('Tiếp nhận')"></th>
                        <th x-show="groupColSpan('Giao trả') > 0"
                            :colspan="groupColSpan('Giao trả')"
                            class="report-th-group-label text-center text-sm font-bold uppercase border-r border-b p-2"
                            :class="groupHeaderClass('return')"
                            x-text="groupLabelText('Giao trả')"></th>
                        <th x-show="groupColSpan('Tri ân') > 0"
                            :colspan="groupColSpan('Tri ân')"
                            class="report-th-group-label text-center text-sm font-bold uppercase border-b p-2"
                            :class="groupHeaderClass('gratitude')"
                            x-text="groupLabelText('Tri ân')"></th>
                        <th rowspan="2" class="catalog-th-settings relative border-b">
                            @include('components.partials.report-settings-column-inner', ['floatWhen' => "settingsOpen && headerMode === 'grouped'"])
                        </th>
                    </tr>

                    {{-- Grouped header row 2 --}}
                    <tr x-show="headerMode === 'grouped'" x-cloak class="report-thead-grouped-sub">
                        <template x-for="col in visibleGroupedSubColumns" :key="'h2-' + col.key">
                            <th class="catalog-th-col"
                                :data-col="col.key" :data-col-label="columnLabel(col)"
                                :class="[
                                    col.key !== 'note' ? 'border-r' : '',
                                    groupHeaderClass(groupShade(col.group)),
                                    col.width || '',
                                ].filter(Boolean).join(' ')">
                                @include('components.partials.report-column-header')
                            </th>
                        </template>
                    </tr>

                    {{-- Handling header row 1 --}}
                    <tr x-show="headerMode === 'handling'" x-cloak class="report-thead-handling">
                        <th rowspan="2" class="catalog-th-index w-[60px] border-b">#</th>
                        <th x-show="useReceptionGroupHeader && (handlingHeaderParts?.reception || []).length"
                            :colspan="handlingHeaderParts?.reception?.length || 1"
                            class="report-th-group-label report-th-group-reception text-center text-sm font-bold uppercase border-r border-b p-2 h-auto align-middle"
                            x-text="groupLabelText('Tiếp nhận')"></th>
                        <template x-for="col in handlingHeaderParts?.before || []" :key="'hb-' + col.key">
                            <th x-show="!useReceptionGroupHeader"
                                rowspan="2"
                                class="catalog-th-col border-b"
                                :data-col="col.key" :data-col-label="columnLabel(col)"
                                :class="col.width || ''">
                                @include('components.partials.report-column-header')
                            </th>
                        </template>
                        <th x-show="(handlingHeaderParts?.handling || []).length"
                            :colspan="handlingHeaderParts?.handling?.length || 1"
                            class="report-th-group-label report-th-group-handling text-center text-sm font-bold uppercase border-r border-b p-2 h-auto align-middle"
                            x-text="groupLabelText('Hướng xử lý')"></th>
                        <template x-for="col in handlingHeaderParts?.after || []" :key="'ha-' + col.key">
                            <th rowspan="2" class="catalog-th-col border-b" :data-col="col.key" :data-col-label="columnLabel(col)" :class="col.width || ''">
                                @include('components.partials.report-column-header')
                            </th>
                        </template>
                        <th rowspan="2" class="catalog-th-settings relative border-b">
                            @include('components.partials.report-settings-column-inner', ['floatWhen' => "settingsOpen && headerMode === 'handling'"])
                        </th>
                    </tr>

                    {{-- Handling header row 2 --}}
                    <tr x-show="headerMode === 'handling'" x-cloak class="report-thead-handling-sub">
                        <template x-for="col in handlingHeaderParts?.reception || []" :key="'hr-' + col.key">
                            <th x-show="useReceptionGroupHeader"
                                class="catalog-th-col border-r report-th-group-reception"
                                :data-col="col.key" :data-col-label="columnLabel(col)"
                                :class="col.width || ''">
                                @include('components.partials.report-column-header')
                            </th>
                        </template>
                        <template x-for="col in handlingHeaderParts?.handling || []" :key="'hh-' + col.key">
                            <th class="catalog-th-col border-r report-th-group-handling h-[40px]" :data-col="col.key" :data-col-label="columnLabel(col)" :class="col.width || ''">
                                @include('components.partials.report-column-header')
                            </th>
                        </template>
                    </tr>
                    @endif
                </thead>
                <tbody>
                    <template x-if="loadingRows && pagedRows.length === 0">
                        <tr>
                            <td :colspan="visibleColumns.length + 2" class="px-4 py-12 text-center text-sm text-gray-500">
                                <x-table-empty-state loading="loadingRows" />
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loadingRows && pagedRows.length === 0">
                        <tr>
                            <td :colspan="visibleColumns.length + 2" class="px-4 py-12 text-center text-sm text-gray-500">
                                <x-table-empty-state
                                    filters-active="hasActiveFilters"
                                    custom-empty="currentEmptyMessage"
                                    clear-action="clearTableFilters()"
                                />
                            </td>
                        </tr>
                    </template>
                    <template x-for="(row, idx) in pagedRows" :key="row.id || idx">
                        <tr @click="selectRow(row)"
                            :class="selectedRowId === row.id ? 'row-selected font-medium' : ''"
                            class="cursor-pointer border-b border-gray-200 transition-colors">
                            <td class="catalog-td-index text-center font-medium border-r border-gray-200 align-middle"
                                :class="config.variant === 'good-deeds' ? (activeTab === 'deed' ? 'p-2 w-[60px]' : 'p-2') : (config.variant === 'incident-reports' ? 'p-2' : 'py-3')" x-text="(safeCurrentPage - 1) * normalizedRowsPerPage + idx + 1"></td>
                            <template x-for="col in visibleColumns" :key="col.key">
                                <td :data-col="col.key" :data-col-label="columnLabel(col)" :class="cellClass(col, row)">
                                    <template x-if="isHtmlCell(col)">
                                        <div x-html="renderCell(row, col)"></div>
                                    </template>
                                    <template x-if="!isHtmlCell(col)">
                                        <span :class="cellTextClass(col)" x-text="row[col.key] ?? '---'"></span>
                                    </template>
                                </td>
                            </template>
                            <td class="catalog-td-settings sticky-action print:hidden sticky right-0 border-l p-0"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            </div>

        <div class="report-footer flex flex-col sm:flex-row items-center justify-between gap-4 px-4 py-3 text-sm text-gray-600 print:hidden"
             :class="config.tableFrame ? 'report-footer-framed' : 'border-t'">
            <div>
                <span class="sidebar-label" data-i18n="Tổng cộng">Tổng cộng</span> <strong x-text="filteredRows.length"></strong> <span class="sidebar-label" data-i18n="bản ghi">bản ghi</span>.
                <span x-show="selectedRowId" x-cloak x-text="' ' + labelText('Đã chọn 1 dòng.')"></span>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 sidebar-label" data-i18n="Số dòng">Số dòng</span>
                    <select class="nttu-rows-per-page-select" :value="normalizedRowsPerPage" @change="setRowsPerPage($event.target.value)">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="20">20</option>
                        <option value="25">25</option>
                        <option value="30">30</option>
                        <option value="35">35</option>
                        <option value="40">40</option>
                        <option value="45">45</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" class="h-8 w-8 rounded border border-gray-200 bg-white disabled:opacity-40 inline-flex items-center justify-center" :disabled="safeCurrentPage <= 1" @click="currentPage = 1" :title="labelText('Trang đầu')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" class="h-8 w-8 rounded border border-gray-200 bg-white disabled:opacity-40 inline-flex items-center justify-center" :disabled="safeCurrentPage <= 1" @click="currentPage--" :title="labelText('Trang trước')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <div class="flex items-center gap-1 text-xs font-medium">
                        <input type="number" min="1" :max="totalPages" x-model.number="pageInput" @change="goToPage(pageInput)" class="h-8 w-12 rounded border-gray-300 bg-white text-center text-xs">
                        <span>/ <span x-text="totalPages"></span></span>
                    </div>
                    <button type="button" class="h-8 w-8 rounded border border-gray-200 bg-white disabled:opacity-40 inline-flex items-center justify-center" :disabled="safeCurrentPage >= totalPages" @click="currentPage++" :title="labelText('Trang sau')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <button type="button" class="h-8 w-8 rounded border border-gray-200 bg-white disabled:opacity-40 inline-flex items-center justify-center" :disabled="safeCurrentPage >= totalPages" @click="currentPage = totalPages" :title="labelText('Trang cuối')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>
        </div>
    </div>

    {{-- Google Sheet push dialog --}}
    <div x-show="showGoogleSheetsPush && pushDialogOpen" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="pushDialogOpen = false">
        <div class="w-full max-w-lg rounded-xl bg-white shadow-2xl" @click.outside="pushDialogOpen = false">
            <div class="border-b px-6 py-4">
                <h4 class="text-lg font-semibold text-gray-900 sidebar-label" data-i18n="Đẩy dữ liệu lên Google Sheet">Đẩy dữ liệu lên Google Sheet</h4>
                <p class="mt-1 text-sm text-gray-500" x-text="`${labelText('Dữ liệu từ bảng')} &quot;${currentTableTitle}&quot; ${labelText('sẽ được đưa vào Google Sheet đã kết nối.')}`"></p>
            </div>
            <div class="space-y-4 px-6 py-4">
                <div>
                    <x-form-label class="mb-1 sidebar-label" data-i18n="Chọn Tab / Sheet đích">Chọn Tab / Sheet đích</x-form-label>
                    <template x-if="isLoadingTabs">
                        <p class="text-sm text-gray-500 animate-pulse sidebar-label" data-i18n="Đang tải danh sách tab...">Đang tải danh sách tab...</p>
                    </template>
                    <template x-if="!isLoadingTabs">
                        <select class="nttu-form-control w-full" x-model="targetTabName">
                            <template x-for="tab in availableTabs" :key="tab">
                                <option :value="tab" x-text="tab"></option>
                            </template>
                        </select>
                    </template>
                </div>
                <div class="rounded-lg bg-slate-50 p-3 text-xs space-y-2">
                    <p class="font-bold text-gray-700 sidebar-label" data-i18n="Các cột sẽ được xuất (Cột hiện hành):">Các cột sẽ được xuất (Cột hiện hành):</p>
                    <div class="flex flex-wrap gap-1">
                        <template x-for="col in googleSheetsPushColumns()" :key="'push-col-' + col.key">
                            <span class="inline-flex rounded bg-white px-2 py-0.5 text-[11px] border" x-text="googleSheetColumnLabel(col)"></span>
                        </template>
                    </div>
                    <p class="text-[10px] text-gray-500 italic sidebar-label" data-i18n="* Chỉ những dữ liệu mới (chưa có trên Sheet) mới được đẩy vào để tránh trùng lặp.">* Chỉ những dữ liệu mới (chưa có trên Sheet) mới được đẩy vào để tránh trùng lặp.</p>
                    <p class="text-[10px] font-medium text-orange-700">
                        <span x-text="googleSheetsPushRows().length"></span>
                        <span class="sidebar-label" data-i18n="dòng sẽ được kiểm tra để đẩy."> dòng sẽ được kiểm tra để đẩy.</span>
                    </p>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t px-6 py-4">
                <x-nttu-button type="button" action="cancel" @click="pushDialogOpen = false">
                    <span class="sidebar-label" data-i18n="Hủy">Hủy</span>
                </x-nttu-button>
                <button type="button" class="inline-flex items-center gap-2 rounded-md bg-orange-600 px-4 py-2 text-sm text-white hover:bg-orange-700 disabled:opacity-50"
                    :disabled="isPushing || isLoadingTabs || !targetTabName"
                    @click="confirmGoogleSheetsPush()">
                    <x-form-field-icon name="upload" tone="orange" class="h-4 w-4 text-white" />
                    <span x-text="isPushing ? labelText('Đang đẩy...') : labelText('Xác nhận đẩy dữ liệu')"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .nttu-sidebar, .nttu-header, [data-print-hide], .sticky { display: none !important; position: static !important; }
    main { padding: 0 !important; }
}
</style>
