<?php

namespace App\Http\Controllers;

use App\Models\BuildingBlock;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\IncidentCategory;
use App\Models\Lecturer;
use App\Services\DailyReportExportService;
use App\Services\DailyReportService;
use App\Services\GoogleSheetService;
use App\Services\ReportGoogleSheetService;
use App\Services\ReportExportService;
use App\Services\StudentViolationReportExportService;
use App\Services\ReportFilterOptionsService;
use App\Services\ReportPresenterService;
use App\Services\ReportQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private ReportQueryService $queries,
        private ReportPresenterService $presenter,
        private ReportExportService $export,
        private ReportFilterOptionsService $filterOptions,
        private DailyReportService $daily,
        private DailyReportExportService $dailyExport,
        private StudentViolationReportExportService $violationExport,
        private GoogleSheetService $googleSheets,
        private ReportGoogleSheetService $reportGoogleSheet,
    ) {}

    public function daily(Request $request): View
    {
        $isoDate = $request->get('date', date('Y-m-d'));
        $displayDate = $this->daily->isoToDisplay($isoDate);
        $officerContext = $this->daily->resolveOfficerContext(Auth::user());
        $datasets = $this->buildDailyDatasets($displayDate, $officerContext['aliases']);

        $sheetConfig = $this->googleSheets->getConfig();

        return view('reports.daily', [
            'dateIso' => $isoDate,
            'displayDate' => $displayDate,
            'officer' => $officerContext['nickname'],
            'tabDefinitions' => $this->daily->tabDefinitions(),
            'datasets' => $datasets,
            'exportUrl' => route('reports.daily.export'),
            'googleSheetsConfigured' => $sheetConfig['sheet_id'] !== ''
                && $sheetConfig['email'] !== ''
                && $sheetConfig['private_key'] !== '',
            'defaultSheetTab' => $sheetConfig['default_tab'],
            'googleSheetTabsUrl' => route('reports.daily.google-sheets.tabs'),
            'googleSheetPushUrl' => route('reports.daily.google-sheets.push'),
        ]);
    }

    public function dailyExport(Request $request): StreamedResponse|JsonResponse
    {
        $isoDate = $request->get('date', date('Y-m-d'));
        $displayDate = $this->daily->isoToDisplay($isoDate);
        $officerContext = $this->daily->resolveOfficerContext(Auth::user());
        $datasets = $this->buildDailyDatasets($displayDate, $officerContext['aliases']);

        $hasData = collect($datasets)->contains(fn (array $items) => $items !== []);
        if (! $hasData) {
            return response()->json(['message' => 'Không có dữ liệu để xuất.'], 422);
        }

        try {
            return $this->dailyExport->download($isoDate, $datasets, $officerContext['fullName']);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['message' => $e->getMessage() ?: 'Lỗi xuất file.'], $e->getStatusCode());
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Lỗi xuất file Excel: '.$e->getMessage()], 500);
        }
    }

    /**
     * @param  list<string>  $officerAliases
     * @return array<string, list<array<string, mixed>>>
     */
    private function buildDailyDatasets(string $displayDate, array $officerAliases): array
    {
        return [
            'in-person' => $this->daily->dailySchedules($displayDate, 'in-person', $officerAliases)->values()->all(),
            'online' => $this->daily->dailySchedules($displayDate, 'online', $officerAliases)->values()->all(),
            'homeroom' => $this->daily->dailySchedules($displayDate, 'homeroom', $officerAliases)->values()->all(),
            'violations' => $this->daily->dailyViolations($displayDate, $officerAliases)->values()->all(),
            'exams' => $this->daily->dailySchedules($displayDate, 'exams', $officerAliases)->values()->all(),
            'external' => $this->daily->dailySchedules($displayDate, 'external-practice', $officerAliases)->values()->all(),
        ];
    }

    public function dailyGoogleSheetTabs(Request $request): JsonResponse
    {
        try {
            $tabKey = trim((string) $request->query('tab_key', ''));
            $profile = $this->resolveGoogleSheetProfile((string) $request->query('sheet_profile', 'daily'));
            $result = $this->googleSheets->getTabs($tabKey !== '' ? $tabKey : null, $profile);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function dailyGoogleSheetPush(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tab_name' => ['required', 'string', 'max:255'],
            'tab_key' => ['nullable', 'string', 'max:50'],
            'sheet_profile' => ['nullable', 'string', 'in:daily,summary'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['required', 'string', 'max:100'],
            'rows' => ['required', 'array'],
        ]);

        $tabKey = (string) ($validated['tab_key'] ?? '');
        $profile = $this->resolveGoogleSheetProfile((string) ($validated['sheet_profile'] ?? 'daily'));

        if ($this->reportGoogleSheet->supportsTabKey($tabKey)) {
            $fields = $this->reportGoogleSheet->pushFieldKeys($tabKey);
            $rows = $this->reportGoogleSheet->prepareRows($tabKey, $validated['rows']);
        } else {
            $pushFields = $this->daily->googleSheetPushFields($tabKey);
            $fields = $pushFields !== [] ? $pushFields : $validated['fields'];
            $rows = $this->daily->prepareRowsForGoogleSheetPush($tabKey, $validated['rows']);
        }

        if ($rows === []) {
            $message = $tabKey === 'online'
                ? 'Không có dòng nào có việc phát sinh khác bình thường để đẩy.'
                : 'Bảng hiện tại không có dữ liệu để đẩy.';

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        $tabName = $validated['tab_name'];
        if ($tabKey !== '') {
            $tabName = $this->googleSheets->resolvePushTabName($tabKey, $tabName, $profile);
        }

        try {
            $result = $this->googleSheets->pushDynamic(
                $rows,
                $fields,
                $tabName,
                $profile,
            );

            return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function interactiveData(Request $request, string $variant): JsonResponse
    {
        [$from, $to] = $this->resolveReportRange(
            $request,
            $variant === 'comprehensive' ? 'comprehensive' : 'default'
        );

        return match ($variant) {
            'comprehensive' => response()->json($this->buildComprehensivePayload($from, $to)),
            'student-violations' => response()->json($this->buildStudentViolationsPayload($from, $to)),
            'good-deeds' => response()->json($this->buildGoodDeedsPayload($from, $to)),
            'request-reports' => response()->json($this->buildRequestReportsPayload($from, $to)),
            'incident-reports' => response()->json($this->buildIncidentReportsPayload($from, $to)),
            default => abort(404),
        };
    }

    public function comprehensive(Request $request): View
    {
        [$from, $to] = $this->resolveReportRange($request, 'comprehensive');

        return view('reports.comprehensive', [
            'rows' => [],
            'reportConfig' => [
                'storageKey' => 'comprehensive_report_colvis',
                'theme' => 'blue',
                'exportAdvancedOnly' => true,
                'variant' => 'comprehensive',
                'dataUrl' => route('reports.interactive-data', ['variant' => 'comprehensive']),
                'exportUrl' => route('reports.comprehensive.export'),
                'rows' => [],
                'lazyLoad' => true,
                'dateField' => 'date',
                'initialFrom' => $from,
                'initialTo' => $to,
                'filterOptions' => $this->filterOptions->comprehensive([]),
                'filterGridCols' => 5,
                'advancedFilters' => ['period', 'buildings', 'departments', 'employees', 'lecturers'],
                'tableTitle' => 'BẢNG TỔNG HỢP VIỆC KHÔNG PHÙ HỢP CÁC LĨNH VỰC',
                'emptyMessage' => 'Tuyệt vời! Không có việc không phù hợp nào được ghi nhận.',
                'exportFileName' => 'ViecKhongPhuHop.xlsx',
                'exportSheetName' => 'Việc Không Phù Hợp',
                'columns' => [
                    ['key' => 'employee', 'label' => 'Nhân viên', 'style' => 'medium', 'icon' => 'shield-check'],
                    ['key' => 'date', 'label' => 'Ngày', 'style' => 'mono-xs', 'icon' => 'calendar'],
                    ['key' => 'room', 'label' => 'Phòng', 'style' => 'bold', 'icon' => 'school'],
                    ['key' => 'period', 'label' => 'Tiết', 'style' => 'xs', 'icon' => 'hash'],
                    ['key' => 'type', 'label' => 'LT/TH', 'style' => 'xs', 'icon' => 'layers'],
                    ['key' => 'department', 'label' => 'Khoa', 'style' => 'xs', 'icon' => 'landmark'],
                    ['key' => 'class', 'label' => 'Lớp', 'style' => 'bold-blue-xs', 'icon' => 'library'],
                    ['key' => 'studentCount', 'label' => 'Sĩ số', 'style' => 'xs', 'icon' => 'users'],
                    ['key' => 'lecturer', 'label' => 'Giảng viên', 'style' => 'xs-medium', 'icon' => 'user'],
                    ['key' => 'content', 'label' => 'Nội dung', 'style' => 'xs', 'icon' => 'book'],
                    ['key' => 'incident', 'label' => 'Việc phát sinh', 'tone' => 'badge-danger', 'icon' => 'alert-triangle'],
                    ['key' => 'incidentDetail', 'label' => 'Chi tiết sự cố', 'style' => 'xs', 'icon' => 'info'],
                ],
            ],
        ]);
    }

    public function studentViolations(Request $request): View
    {
        [$from, $to] = $this->resolveReportRange($request, 'default');

        return view('reports.student-violations', [
            'rows' => [],
            'reportConfig' => array_merge([
                'storageKey' => 'violation-report-cols',
                'theme' => 'red',
                'exportAdvancedOnly' => true,
                'variant' => 'student-violations',
                'dataUrl' => route('reports.interactive-data', ['variant' => 'student-violations']),
                'exportUrl' => route('reports.student-violations.export'),
                'rows' => [],
                'lazyLoad' => true,
                'dateField' => 'violationDate',
                'initialFrom' => $from,
                'initialTo' => $to,
                'filterOptions' => $this->filterOptions->violations([]),
                'advancedFilters' => ['buildings', 'officers', 'violationTypes'],
                'tableTitle' => 'BÁO CÁO GHI NHẬN SINH VIÊN VI PHẠM',
                'emptyMessage' => 'Không có sinh viên vi phạm bị ghi nhận trong khoảng thời gian này.',
                'exportFileName' => 'BaoCao_SVViPham.xlsx',
                'exportSheetName' => 'Sinh Viên Vi Phạm',
                'columns' => [
                    ['key' => 'fullName', 'label' => 'Họ tên', 'tone' => 'danger', 'icon' => 'user'],
                    ['key' => 'class', 'label' => 'Lớp', 'tone' => 'primary', 'icon' => 'library'],
                    ['key' => 'studentId', 'label' => 'Mã số SV', 'style' => 'mono-xs', 'align' => 'center', 'icon' => 'id-card'],
                    ['key' => 'violationDate', 'label' => 'Ngày vi phạm', 'style' => 'mono-xs', 'align' => 'center', 'icon' => 'calendar'],
                    ['key' => 'violationType', 'label' => 'Lỗi vi phạm', 'tone' => 'badge-danger', 'icon' => 'alert-triangle'],
                    ['key' => 'signature', 'label' => 'Ký tên', 'type' => 'signature', 'sortable' => false, 'icon' => 'pen-tool'],
                    ['key' => 'officer', 'label' => 'CB ghi nhận', 'style' => 'medium', 'icon' => 'shield-check'],
                    ['key' => 'note', 'label' => 'Ghi chú', 'style' => 'italic-gray', 'icon' => 'note'],
                ],
            ]),
        ]);
    }

    public function goodDeeds(Request $request): View
    {
        [$from, $to] = $this->resolveReportRange($request, 'default');
        $filterOptions = ['buildings' => [], 'recipients' => []];

        $propertyColumns = [
            ['key' => 'code', 'label' => 'Số vào sổ', 'group' => 'Chung', 'icon' => 'hash', 'width' => 'w-[80px]'],
            ['key' => 'campus', 'label' => 'Cơ sở', 'group' => 'Chung', 'icon' => 'building', 'width' => 'w-[100px]'],
            ['key' => 'receptionDate', 'label' => 'Ngày tiếp nhận', 'group' => 'Tiếp nhận', 'icon' => 'calendar', 'width' => 'w-[110px]'],
            ['key' => 'recipient', 'label' => 'Nhân sự tiếp nhận', 'group' => 'Tiếp nhận', 'icon' => 'shield', 'width' => 'w-[130px]'],
            ['key' => 'finderName', 'label' => 'Họ và tên người giao', 'settingsLabel' => 'Tên người giao', 'group' => 'Tiếp nhận', 'icon' => 'user', 'width' => 'w-[150px]'],
            ['key' => 'finderId', 'label' => 'MSSV/CCCD/SĐT', 'group' => 'Tiếp nhận', 'icon' => 'id-card', 'width' => 'w-[130px]'],
            ['key' => 'finderDept', 'label' => 'Đơn vị', 'group' => 'Tiếp nhận', 'icon' => 'landmark', 'width' => 'w-[150px]'],
            ['key' => 'property', 'label' => 'Nội dung TS, đồ vật', 'settingsLabel' => 'Nội dung TS', 'group' => 'Tiếp nhận', 'icon' => 'package', 'width' => 'w-[200px]'],
            ['key' => 'returnDate', 'label' => 'Ngày giao trả', 'group' => 'Giao trả', 'icon' => 'calendar', 'width' => 'w-[110px]'],
            ['key' => 'returner', 'label' => 'Nhân sự giao trả', 'group' => 'Giao trả', 'icon' => 'shield', 'width' => 'w-[130px]'],
            ['key' => 'ownerName', 'label' => 'Họ và tên người nhận', 'settingsLabel' => 'Tên người nhận', 'group' => 'Giao trả', 'icon' => 'user', 'width' => 'w-[150px]'],
            ['key' => 'ownerId', 'label' => 'MSSV/CCCD', 'group' => 'Giao trả', 'icon' => 'id-card', 'width' => 'w-[100px]'],
            ['key' => 'ownerClass', 'label' => 'Lớp', 'group' => 'Giao trả', 'icon' => 'library', 'width' => 'w-[80px]'],
            ['key' => 'ownerDept', 'label' => 'Đơn vị Khoa/Viện', 'group' => 'Giao trả', 'icon' => 'landmark', 'width' => 'w-[150px]'],
            ['key' => 'ownerPhone', 'label' => 'Điện thoại', 'group' => 'Giao trả', 'icon' => 'phone', 'width' => 'w-[100px]'],
        ];

        $propertyExportLabels = [
            'code' => 'Số vào sổ',
            'campus' => 'Cơ sở',
            'receptionDate' => 'Ngày tiếp nhận',
            'recipient' => 'Nhân sự tiếp nhận',
            'finderName' => 'Họ và tên người giao TS',
            'finderId' => 'MSSV/CCCD/SĐT',
            'finderDept' => 'Đơn vị người giao',
            'property' => 'Nội dung TS',
            'returnDate' => 'Ngày giao trả',
            'returner' => 'Nhân sự giao trả',
            'ownerName' => 'Họ và tên người nhận',
            'ownerId' => 'MSSV/CCCD người nhận',
            'ownerClass' => 'Lớp người nhận',
            'ownerDept' => 'Đơn vị người nhận',
            'ownerPhone' => 'SĐT người nhận',
        ];

        $propertyColumns = array_map(function (array $col) use ($propertyExportLabels) {
            if (isset($propertyExportLabels[$col['key']])) {
                $col['exportLabel'] = $propertyExportLabels[$col['key']];
            }

            return $col;
        }, $propertyColumns);

        return view('reports.good-deeds', [
            'reportConfig' => array_merge([
                'storageKey' => 'gooddeeds',
                'variant' => 'good-deeds',
                'theme' => 'green',
                'tableCardTheme' => 'blue',
                'tableHeadTheme' => 'blue',
                'tableFrame' => true,
                'tabsAbove' => true,
                'tabMaxWidth' => '400px',
                'filterGridCols' => 4,
                'exportAdvancedOnly' => true,
                'settingsTitle' => 'Cấu hình hiển thị cột',
                'groupLabels' => [
                    'Tiếp nhận' => 'Phần tiếp nhận',
                    'Giao trả' => 'Phần giao trả',
                ],
                'dateField' => 'receptionDate',
                'dateFields' => ['receptionDate', 'returnDate'],
                'filterPlaceholders' => [
                    'buildings' => 'Chọn dãy nhà...',
                    'recipients' => 'Chọn nhân viên...',
                ],
                'filterEmptyText' => [
                    'buildings' => 'Không tìm thấy dãy nhà',
                    'recipients' => 'Không tìm thấy nhân viên',
                ],
                'initialFrom' => $from,
                'initialTo' => $to,
                'dataUrl' => route('reports.interactive-data', ['variant' => 'good-deeds']),
                'exportUrl' => route('reports.good-deeds.export'),
                'filterOptions' => $filterOptions,
                'advancedFilters' => ['buildings', 'recipients'],
                'filterLabels' => [
                    'recipients' => 'Nhân viên tiếp nhận',
                    'buildings' => 'Dãy nhà',
                ],
                'tabs' => [
                    [
                        'key' => 'property',
                        'label' => 'Tiếp nhận tài sản',
                        'icon' => 'package',
                        'cardIcon' => 'package',
                        'headerMode' => 'grouped',
                        'tableTitle' => 'DANH SÁCH TIẾP NHẬN TÀI SẢN',
                        'emptyMessage' => 'Chưa có ghi nhận tiếp nhận tài sản nào.',
                        'exportEmptyMessage' => 'Không có dữ liệu Tiếp nhận tài sản để xuất!',
                        'rows' => [],
                        'columns' => $propertyColumns,
                        'exportFields' => $this->reportGoogleSheet->pushFieldDefinitions('good-deeds-property'),
                        'googleSheetTabKey' => 'good-deeds-property',
                    ],
                    [
                        'key' => 'deed',
                        'label' => 'Tri ân người/việc tốt',
                        'icon' => 'star',
                        'cardIcon' => 'star',
                        'headerMode' => 'flat',
                        'tableTitle' => 'DANH SÁCH TRI ÂN NGƯỜI/VIỆC TỐT',
                        'emptyMessage' => 'Chưa có ghi nhận tri ân nào trong ngày đã chọn.',
                        'exportEmptyMessage' => 'Không có dữ liệu Tri ân để xuất!',
                        'settingsTitle' => 'Hiển thị cột',
                        'dateFields' => ['receptionDate', 'returnDate', 'appreciationGiveDate', 'appreciationRecDate'],
                        'rows' => [],
                        'columns' => [
                            ['key' => 'appreciationCode', 'label' => 'Số vào sổ', 'icon' => 'hash', 'width' => 'min-w-[100px]', 'exportLabel' => 'Số vào sổ'],
                            ['key' => 'appreciationCampus', 'label' => 'Cơ sở', 'icon' => 'building', 'width' => 'min-w-[100px]', 'exportLabel' => 'Cơ sở'],
                            ['key' => 'appreciationName', 'label' => 'Họ và tên', 'icon' => 'user', 'width' => 'min-w-[150px]', 'exportLabel' => 'Họ và tên'],
                            ['key' => 'appreciationRecDate', 'label' => 'Ngày tiếp nhận tài sản', 'icon' => 'calendar', 'width' => 'min-w-[140px]', 'exportLabel' => 'Ngày tiếp nhận tài sản'],
                            ['key' => 'appreciationGiveDate', 'label' => 'Ngày trao tặng thư', 'icon' => 'star', 'width' => 'min-w-[140px]', 'exportLabel' => 'Ngày trao tặng thư'],
                            ['key' => 'gift', 'label' => 'Quà', 'icon' => 'gift', 'width' => 'min-w-[120px]', 'exportLabel' => 'Quà'],
                            ['key' => 'appreciationId', 'label' => 'MSSV/CCCD; SĐT', 'icon' => 'id-card', 'width' => 'min-w-[150px]', 'exportLabel' => 'MSSV/CCCD; Số điện thoại'],
                            ['key' => 'appreciationDept', 'label' => 'Đơn vị', 'icon' => 'landmark', 'width' => 'min-w-[130px]', 'exportLabel' => 'Đơn vị'],
                            ['key' => 'refCode', 'label' => 'Số vào sổ tiếp nhận và bàn giao', 'icon' => 'hash', 'width' => 'min-w-[150px]', 'exportLabel' => 'Số vào sổ tiếp nhận và bàn giao tài sản'],
                            ['key' => 'note', 'label' => 'Ghi chú', 'icon' => 'note', 'width' => 'min-w-[180px]', 'exportLabel' => 'Ghi chú'],
                        ],
                        'exportFields' => $this->reportGoogleSheet->pushFieldDefinitions('good-deeds-deed'),
                        'googleSheetTabKey' => 'good-deeds-deed',
                    ],
                ],
                'exportFileName' => 'TiepNhanTaiSan.xlsx',
                'exportSheetName' => 'Tiếp nhận tài sản',
            ], $this->googleSheetsInteractiveConfig()),
        ]);
    }

    public function requestReports(Request $request): View
    {
        [$from, $to] = $this->resolveReportRange($request, 'default');

        return view('reports.request-reports', [
            'rows' => [],
            'reportConfig' => array_merge([
                'storageKey' => 'report-request-cols',
                'theme' => 'teal',
                'variant' => 'request-reports',
                'dataUrl' => route('reports.interactive-data', ['variant' => 'request-reports']),
                'exportUrl' => route('reports.request-reports.export'),
                'lazyLoad' => true,
                'rows' => [],
                'dateField' => 'receptionDate',
                'initialFrom' => $from,
                'initialTo' => $to,
                'filterOptions' => [
                    'buildings' => [],
                    'recipients' => [],
                ],
                'advancedFilters' => ['buildings', 'recipients'],
                'tableTitle' => 'THỐNG KÊ YÊU CẦU ĐÃ TIẾP NHẬN',
                'emptyMessage' => 'Không có yêu cầu nào trong khoảng thời gian đã chọn.',
                'exportFileName' => 'BaoCaoTiepNhanYeuCau.xlsx',
                'exportSheetName' => 'Tiếp nhận yêu cầu',
                'columns' => [
                    ['key' => 'code', 'label' => 'Số vào sổ', 'align' => 'center', 'icon' => 'hash'],
                    ['key' => 'recipient', 'label' => 'Nhân sự tiếp nhận', 'icon' => 'shield-check'],
                    ['key' => 'receptionDate', 'label' => 'Ngày tiếp nhận', 'align' => 'center', 'style' => 'mono-xs', 'icon' => 'calendar'],
                    ['key' => 'building', 'label' => 'Dãy nhà', 'icon' => 'building'],
                    ['key' => 'studentName', 'label' => 'Họ và tên SV', 'icon' => 'user'],
                    ['key' => 'studentId', 'label' => 'MSSV/CCCD', 'align' => 'center', 'style' => 'mono-xs', 'icon' => 'id-card'],
                    ['key' => 'class', 'label' => 'Lớp', 'tone' => 'primary', 'align' => 'center', 'icon' => 'library'],
                    ['key' => 'department', 'label' => 'Đơn vị Khoa/Viện', 'icon' => 'landmark'],
                    ['key' => 'requestType', 'label' => 'Nội dung', 'icon' => 'note'],
                    ['key' => 'content', 'label' => 'Ghi rõ nội dung', 'style' => 'leading', 'icon' => 'file-text'],
                    ['key' => 'status', 'label' => 'Tình trạng giải quyết', 'icon' => 'activity'],
                ],
                'exportFields' => $this->reportGoogleSheet->pushFieldDefinitions('request-reports'),
                'googleSheetTabKey' => 'request-reports',
            ], $this->googleSheetsInteractiveConfig()),
        ]);
    }

    public function incidentReports(Request $request): View
    {
        [$from, $to] = $this->resolveReportRange($request, 'default');

        return view('reports.incident-reports', [
            'rows' => [],
            'reportConfig' => array_merge([
                'storageKey' => 'report-petition-cols',
                'variant' => 'incident-reports',
                'dataUrl' => route('reports.interactive-data', ['variant' => 'incident-reports']),
                'exportUrl' => route('reports.incident-reports.export'),
                'theme' => 'red',
                'tableCardTheme' => 'red',
                'tableHeadTheme' => 'blue',
                'tableFrame' => false,
                'tableBodyPadding' => false,
                'cardIcon' => 'shield-alert',
                'cardTitleClass' => 'text-xs',
                'headerMode' => 'handling',
                'receptionGroupHeader' => false,
                'columnHeaderAlign' => 'center',
                'settingsFlatList' => true,
                'settingsTitle' => 'Hiển thị cột',
                'groupLabels' => [
                    'Hướng xử lý' => 'Hướng xử lý',
                ],
                'exportAdvancedOnly' => true,
                'exportEmptyMessage' => 'Không có dữ liệu để xuất trong ngày này!',
                'rows' => [],
                'dateField' => 'receptionDate',
                'initialFrom' => $from,
                'initialTo' => $to,
                'filterGridCols' => 4,
                'filterOptions' => ['buildings' => [], 'recipients' => []],
                'advancedFilters' => ['buildings', 'recipients'],
                'filterLabels' => [
                    'recipients' => 'Cán bộ tiếp nhận',
                ],
                'filterPlaceholders' => [
                    'buildings' => 'Chọn dãy nhà...',
                    'recipients' => 'Chọn cán bộ...',
                ],
                'filterEmptyText' => [
                    'buildings' => 'Không tìm thấy dãy nhà',
                    'recipients' => 'Không tìm thấy cán bộ',
                ],
                'tableTitle' => 'THỐNG KÊ ĐƠN THƯ/KHIẾU NẠI ĐÃ TIẾP NHẬN',
                'emptyMessage' => 'Không có đơn thư nào được tìm thấy.',
                'exportFileName' => 'BaoCaoTiepNhanDonThu.xlsx',
                'exportSheetName' => 'Tiếp nhận đơn thư',
                'exportFields' => [
                    ['key' => 'receptionDate', 'label' => 'Ngày tiếp'],
                    ['key' => 'senderName', 'label' => 'Họ và tên'],
                    ['key' => 'senderId', 'label' => 'MSSV/CCCD'],
                    ['key' => 'address', 'label' => 'Địa chỉ'],
                    ['key' => 'phone', 'label' => 'Điện thoại'],
                    ['key' => 'content', 'label' => 'Tóm tắt nội dung vụ việc'],
                    ['key' => 'petitionType', 'label' => 'Phân loại đơn'],
                    ['key' => 'peopleCount', 'label' => 'Số người'],
                    ['key' => 'previousResolver', 'label' => 'Cơ quan đã giải quyết (nếu có)'],
                    ['key' => 'accept', 'label' => 'Hướng xử lý - Thụ lý để giải quyết'],
                    ['key' => 'returnAndGuide', 'label' => 'Hướng xử lý - Trả lại đơn và hướng dẫn'],
                    ['key' => 'transfer', 'label' => 'Hướng xử lý - Chuyển đơn'],
                    ['key' => 'result', 'label' => 'Theo dõi kết quả giải quyết'],
                    ['key' => 'note', 'label' => 'Ghi chú'],
                ],
                'columns' => [
                    ['key' => 'receptionDate', 'label' => 'Ngày tiếp', 'group' => 'Tiếp nhận', 'icon' => 'calendar', 'align' => 'center', 'width' => 'w-[100px]'],
                    ['key' => 'senderName', 'label' => 'Thông tin công dân', 'group' => 'Tiếp nhận', 'type' => 'citizen', 'icon' => 'user', 'align' => 'left', 'width' => 'min-w-[200px]'],
                    ['key' => 'content', 'label' => 'Tóm tắt nội dung', 'headerLabel' => 'Tóm tắt nội dung vụ việc', 'group' => 'Tiếp nhận', 'style' => 'leading', 'icon' => 'book', 'align' => 'left', 'width' => 'min-w-[200px]'],
                    ['key' => 'petitionType', 'label' => 'Phân loại đơn', 'group' => 'Tiếp nhận', 'icon' => 'layers', 'align' => 'center', 'width' => 'min-w-[120px]'],
                    ['key' => 'peopleCount', 'label' => 'Số người', 'group' => 'Tiếp nhận', 'icon' => 'users', 'align' => 'center', 'width' => 'w-[80px]'],
                    ['key' => 'previousResolver', 'label' => 'Cơ quan đã giải quyết', 'headerLabel' => 'Cơ quan đã giải quyết (nếu có)', 'group' => 'Tiếp nhận', 'icon' => 'building', 'align' => 'left', 'width' => 'min-w-[120px]'],
                    ['key' => 'accept', 'label' => 'Thụ lý để giải quyết', 'group' => 'Hướng xử lý', 'icon' => 'check-square', 'align' => 'left', 'width' => 'min-w-[100px]'],
                    ['key' => 'returnAndGuide', 'label' => 'Trả lại đơn và hướng dẫn', 'group' => 'Hướng xử lý', 'icon' => 'rotate-ccw', 'align' => 'left', 'width' => 'min-w-[120px]'],
                    ['key' => 'transfer', 'label' => 'Chuyển đơn', 'group' => 'Hướng xử lý', 'icon' => 'forward', 'align' => 'left', 'width' => 'min-w-[100px]'],
                    ['key' => 'result', 'label' => 'Theo dõi kết quả', 'headerLabel' => 'Theo dõi kết quả giải quyết', 'tone' => 'danger', 'icon' => 'file-text', 'align' => 'left', 'width' => 'min-w-[120px]'],
                    ['key' => 'note', 'label' => 'Ghi chú', 'style' => 'italic-gray', 'icon' => 'note', 'align' => 'left', 'width' => 'min-w-[100px]'],
                ],
            ], $this->googleSheetsInteractiveConfig(['enabled' => false])),
        ]);
    }

    public function exportComprehensive(Request $request): StreamedResponse
    {
        $from = $this->queries->normalizeDate($request->get('from'));
        $to = $this->queries->normalizeDate($request->get('to', $from));
        $rows = $this->presenter->comprehensiveRows($this->queries->schedulesWithIncidents($from, $to));

        return $this->export->downloadFromArrays($rows, [
            'employee' => 'Nhân viên',
            'date' => 'Ngày',
            'room' => 'Phòng',
            'period' => 'Tiết',
            'type' => 'LT/TH',
            'department' => 'Khoa',
            'class' => 'Lớp',
            'studentCount' => 'Sĩ số',
            'lecturer' => 'Giảng viên',
            'content' => 'Nội dung',
            'incident' => 'Việc phát sinh',
            'incidentDetail' => 'Chi tiết sự cố',
        ], "ViecKhongPhuHop_{$from}_{$to}.xlsx", 'Việc Không Phù Hợp');
    }

    public function exportStudentViolations(Request $request): StreamedResponse
    {
        $from = $this->queries->normalizeDate($request->get('from'));
        $to = $this->queries->normalizeDate($request->get('to', $from));
        $rows = $this->presenter->violationRows($this->queries->violations($from, $to));

        return $this->violationExport->download(
            $from,
            $to,
            $rows,
            "BaoCao_SVViPham_{$from}_to_{$to}.xlsx",
            $this->daily->resolveOfficerFullName(Auth::user()),
        );
    }

    public function exportGoodDeeds(Request $request): StreamedResponse
    {
        $from = $this->queries->normalizeDate($request->get('from'));
        $to = $this->queries->normalizeDate($request->get('to', $from));
        $items = $this->queries->assetReceptions($from, $to);
        $tab = $request->get('tab', 'property');
        $rows = $tab === 'deed'
            ? $this->presenter->goodDeedsGratitudeRows($items)
            : $this->presenter->goodDeedsPropertyRows(
                $items->where('return_status', 'Đã trả')->values()
            );

        $headers = $tab === 'deed' ? [
            'appreciationCode' => 'Số vào sổ',
            'appreciationCampus' => 'Cơ sở',
            'appreciationName' => 'Họ và tên',
            'appreciationRecDate' => 'Ngày tiếp nhận TS',
            'appreciationGiveDate' => 'Ngày trao tặng thư',
            'gift' => 'Quà',
            'appreciationId' => 'MSSV/CCCD/SĐT',
            'appreciationDept' => 'Đơn vị',
            'refCode' => 'Số vào sổ TN & BG',
            'note' => 'Ghi chú',
        ] : [
            'code' => 'Số vào sổ',
            'campus' => 'Cơ sở',
            'receptionDate' => 'Ngày tiếp nhận',
            'recipient' => 'Nhân sự tiếp nhận',
            'finderName' => 'Họ và tên người giao TS',
            'finderId' => 'MSSV/CCCD/SĐT',
            'finderDept' => 'Đơn vị người giao',
            'property' => 'Nội dung TS',
            'returnDate' => 'Ngày giao trả',
            'returner' => 'Nhân sự giao trả',
            'ownerName' => 'Tên người nhận',
            'ownerId' => 'MSSV/CCCD',
            'ownerClass' => 'Lớp',
            'ownerDept' => 'Đơn vị Khoa/Viện',
            'ownerPhone' => 'Điện thoại',
        ];

        $filename = $tab === 'deed'
            ? "TriAnNguoiViecTot_{$from}_to_{$to}.xlsx"
            : "TiepNhanTaiSan_{$from}_to_{$to}.xlsx";

        return $this->export->downloadFromArrays(
            $rows,
            $headers,
            $filename,
            $tab === 'deed' ? 'Tri ân người việc tốt' : 'Tiếp nhận tài sản',
        );
    }

    public function exportRequestReports(Request $request): StreamedResponse
    {
        $from = $this->queries->normalizeDate($request->get('from'));
        $to = $this->queries->normalizeDate($request->get('to', $from));
        $rows = $this->presenter->requestRows($this->queries->serviceRequests($from, $to));

        return $this->export->downloadFromArrays($rows, [
            'code' => 'Số vào sổ',
            'recipient' => 'Nhân sự tiếp nhận',
            'receptionDate' => 'Ngày tiếp nhận',
            'building' => 'Dãy nhà',
            'studentName' => 'Họ và tên SV',
            'studentId' => 'MSSV/CCCD',
            'class' => 'Lớp',
            'department' => 'Đơn vị Khoa/Viện',
            'requestType' => 'Nội dung',
            'content' => 'Ghi rõ nội dung',
            'status' => 'Tình trạng giải quyết',
        ], "BaoCaoTiepNhanYeuCau_{$from}_to_{$to}.xlsx", 'Tiếp nhận yêu cầu');
    }

    public function exportIncidentReports(Request $request): StreamedResponse
    {
        $from = $this->queries->normalizeDate($request->get('from'));
        $to = $this->queries->normalizeDate($request->get('to', $from));
        $rows = $this->presenter->petitionRows($this->queries->petitions($from, $to));

        return $this->export->downloadFromArrays($rows, [
            'receptionDate' => 'Ngày tiếp',
            'citizenInfo' => 'Thông tin công dân',
            'summary' => 'Tóm tắt nội dung vụ việc',
            'petitionType' => 'Phân loại đơn',
            'peopleCount' => 'Số người',
            'previousAuthority' => 'Cơ quan đã giải quyết',
            'accept' => 'Thụ lý để giải quyết',
            'returnAndGuide' => 'Trả lại đơn và hướng dẫn',
            'transfer' => 'Chuyển đơn',
            'followUp' => 'Theo dõi kết quả giải quyết',
            'note' => 'Ghi chú',
        ], "BaoCaoTiepNhanDonThu_{$from}_to_{$to}.xlsx", 'Tiếp nhận đơn thư');
    }

    /** @return array{0: string, 1: string} */
    private function resolveReportRange(Request $request, string $variant): array
    {
        [$defaultFrom, $defaultTo] = $this->queries->defaultInitialRange($variant);

        return [
            $request->get('from', $defaultFrom),
            $request->get('to', $defaultTo),
        ];
    }

    /** @return array{rows: list<array<string, mixed>>, filterOptions: array<string, mixed>} */
    private function buildComprehensivePayload(string $from, string $to): array
    {
        $rows = $this->presenter->comprehensiveRows($this->queries->schedulesWithIncidents($from, $to));

        return [
            'rows' => $rows,
            'filterOptions' => $this->filterOptions->comprehensive($rows),
        ];
    }

    /** @return array{rows: list<array<string, mixed>>, filterOptions: array<string, mixed>} */
    private function buildStudentViolationsPayload(string $from, string $to): array
    {
        $rows = $this->presenter->violationRows($this->queries->violations($from, $to));

        return [
            'rows' => $rows,
            'filterOptions' => $this->filterOptions->violations($rows),
        ];
    }

    /** @return array{filterOptions: array<string, mixed>, tabs: array<string, array{rows: list<array<string, mixed>>}>} */
    private function buildGoodDeedsPayload(string $from, string $to): array
    {
        $items = $this->queries->assetReceptions($from, $to);
        $propertyItems = $items->where('return_status', 'Đã trả')->values();
        $propertyRows = $this->presenter->goodDeedsPropertyRows($propertyItems);

        return [
            'filterOptions' => $this->filterOptions->withBuildingsAndRecipients($propertyRows),
            'tabs' => [
                'property' => ['rows' => $propertyRows],
                'deed' => ['rows' => $this->presenter->goodDeedsGratitudeRows($items)],
            ],
        ];
    }

    /** @return array{rows: list<array<string, mixed>>, filterOptions: array<string, mixed>} */
    private function buildRequestReportsPayload(string $from, string $to): array
    {
        $rows = $this->presenter->requestRows($this->queries->serviceRequests($from, $to));

        return [
            'rows' => $rows,
            'filterOptions' => $this->filterOptions->withBuildingsAndRecipients($rows),
        ];
    }

    /** @return array{rows: list<array<string, mixed>>, filterOptions: array<string, mixed>} */
    private function buildIncidentReportsPayload(string $from, string $to): array
    {
        $rows = $this->presenter->petitionRows($this->queries->petitions($from, $to));

        return [
            'rows' => $rows,
            'filterOptions' => $this->filterOptions->withBuildingsAndRecipients($rows),
        ];
    }

    /**
     * @param  array{enabled?: bool}  $options
     * @return array<string, mixed>
     */
    private function googleSheetsInteractiveConfig(array $options = []): array
    {
        $enabled = $options['enabled'] ?? true;
        $sheetConfig = $this->googleSheets->getConfig('summary');

        return [
            'googleSheets' => $enabled,
            'googleSheetsConfigured' => $enabled && $sheetConfig['sheet_id'] !== ''
                && $sheetConfig['email'] !== ''
                && $sheetConfig['private_key'] !== '',
            'googleSheetProfile' => 'summary',
            'defaultSheetTab' => $sheetConfig['default_tab'],
            'googleSheetTabsUrl' => route('reports.daily.google-sheets.tabs'),
            'googleSheetPushUrl' => route('reports.daily.google-sheets.push'),
        ];
    }

    private function resolveGoogleSheetProfile(string $profile): string
    {
        return in_array($profile, ['daily', 'summary'], true) ? $profile : 'daily';
    }
}
