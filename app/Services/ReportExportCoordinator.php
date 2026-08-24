<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportCoordinator
{
    public function __construct(
        private ReportQueryService $queries,
        private ReportPresenterService $presenter,
        private ReportExportService $export,
        private DailyReportService $daily,
        private StudentViolationReportExportService $violationExport,
        private GoodDeedsGratitudeTemplateExportService $goodDeedsGratitudeExport,
        private IncidentMonthlyReportExportService $incidentMonthlyReportExport,
        private MonthlyReportStatsService $monthlyReportStats,
    ) {}

    public function comprehensive(Request $request): StreamedResponse
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

    public function comprehensiveMonthlyReport(Request $request): StreamedResponse
    {
        $from = $this->queries->normalizeDate($request->get('from'));
        $to = $this->queries->normalizeDate($request->get('to', $from));
        $titleFrom = $this->queries->normalizeDate($request->get('titleFromDate', $from));
        $titleTo = $this->queries->normalizeDate($request->get('titleToDate', $to));
        $campus = trim((string) $request->get('campus', ''));
        $departments = collect($request->input('departments', []))
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values()
            ->all();

        $users = collect($request->input('users', []))
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values()
            ->all();
        if ($users === [] && $request->filled('user')) {
            $users = [trim((string) $request->get('user'))];
        }

        $schedules = $this->monthlyReportStats->filterSchedulesForReport(
            $this->queries->schedulesWithIncidents($from, $to),
            $campus,
            $users,
            $departments,
        );
        $rows = $this->presenter->comprehensiveRows($schedules);
        $fileFrom = str_replace('/', '-', $titleFrom);
        $fileTo = str_replace('/', '-', $titleTo);

        return $this->incidentMonthlyReportExport->download(
            $rows,
            $titleFrom,
            $titleTo,
            "BaoCaoThang_KPH_{$fileFrom}_to_{$fileTo}.docx",
            [
                'campus' => $campus,
                'users' => $users,
                'titleFromDate' => $titleFrom,
                'titleToDate' => $titleTo,
            ],
        );
    }

    public function studentViolations(Request $request): StreamedResponse
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

    public function goodDeeds(Request $request): StreamedResponse
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

        if ($tab === 'deed') {
            return $this->goodDeedsGratitudeExport->download(
                $rows,
                $from,
                $to,
                $filename,
                $this->daily->resolveOfficerFullName(Auth::user()),
                $request->only([
                    'reportMonth',
                    'reportYear',
                    'titleFromDate',
                    'titleToDate',
                    'summaryMonth',
                    'giftMonth',
                    'campus',
                    'letterRemain',
                    'giftRemain',
                ]),
            );
        }

        return $this->export->downloadFromArrays(
            $rows,
            $headers,
            $filename,
            $tab === 'deed' ? 'Tri ân người việc tốt' : 'Tiếp nhận tài sản',
        );
    }

    public function requestReports(Request $request): StreamedResponse
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

        public function incidentRecordsReports(Request $request): StreamedResponse
    {
        $from = $this->queries->normalizeDate($request->get('from'));
        $to = $this->queries->normalizeDate($request->get('to', $from));
        $rows = $this->presenter->incidentRecordsRows($this->queries->incidentRecords($from, $to));

        return $this->export->downloadFromArrays($rows, [
            'incident_time' => 'Ngày ghi nhận',
            'location' => 'Địa điểm',
            'creator_name' => 'Người lập biên bản',
            'witness_name' => 'Người chứng kiến',
            'creator_signature' => 'Chữ ký người lập',
            'witness_signature' => 'Chữ ký người chứng kiến',
        ], 'ThongKeBienBan.xlsx');
    }

    public function incidentReports(Request $request): StreamedResponse
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
}
