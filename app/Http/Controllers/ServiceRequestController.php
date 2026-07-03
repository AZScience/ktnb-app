<?php



namespace App\Http\Controllers;



use App\Models\Department;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Services\BuildingBlockOptionService;
use App\Services\CatalogExcelService;
use App\Services\ReportExportService;
use App\Services\ServiceRequestTypeService;
use App\Services\ServiceRequestStatusService;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Str;

use Illuminate\View\View;

use Symfony\Component\HttpFoundation\StreamedResponse;



class ServiceRequestController extends Controller

{

    public function __construct(
        private ReportExportService $export,
        private CatalogExcelService $excel,
        private ServiceRequestTypeService $requestTypes,
        private BuildingBlockOptionService $buildingBlocks,
        private ServiceRequestStatusService $serviceRequestStatus,
    ) {}

    public function index(): View

    {

        $this->buildingBlocks->syncMissingFromRecords();

        $buildingOptions = $this->buildingBlocks->options();

        $mapOption = fn (string $name) => ['value' => $name, 'label' => $name];

        $departmentOptions = Department::orderBy('name')->pluck('name')->map($mapOption)->values()->all();



        $items = ServiceRequest::orderByDesc('created_at')->get()->map(function (ServiceRequest $item) {

            $row = $item->toArray();

            $row['evidence'] = $item->attachments ?? '';
            $row['building_block_label'] = $this->buildingBlocks->displayLabel($item->building_block);
            $row['resolution_outcome'] = $this->serviceRequestStatus->outcomeLabel($item);

            return $row;

        });



        return view('monitoring.requests.index', [
            'items' => $items,
            'staffDefault' => $this->staffDefault(),
            'requestTypeOptions' => $this->requestTypes->options(),
            'buildingOptions' => $buildingOptions,
            'departmentOptions' => $departmentOptions,
            'advancedFilterOptions' => [

                'buildings' => $this->buildingBlocks->filterValues(),

                'dateLabel' => 'Ngày tiếp nhận',

                'dateField' => 'reception_date',

                'buildingFilterField' => 'building_block',

            ],

        ]);

    }



    public function create(): View

    {

        return view('monitoring.requests.form', ['item' => null]);

    }



    public function store(Request $request): JsonResponse|RedirectResponse

    {

        $data = $this->preparePayload($this->validated($request));

        $staffDefault = $this->staffDefault();



        $item = ServiceRequest::create(array_merge($data, [

            'id' => (string) Str::uuid(),

            'ticket_number' => $data['ticket_number'] ?: $this->nextTicketNumber(),

            'recipient' => Employee::nicknameFor($data['recipient'] ?: $staffDefault),

            'reception_date' => $data['reception_date'] ?? date('d/m/Y'),

            'request_date' => $data['request_date'] ?? date('d/m/Y'),

            'status' => $data['status'] ?? 'pending',

        ]));



        if ($request->wantsJson()) {

            return response()->json([

                'message' => 'Đã tiếp nhận yêu cầu.',

                'item' => array_merge($item->toArray(), ['evidence' => $item->attachments ?? '']),

            ]);

        }



        return redirect()->route('requests.index')->with('success', 'Đã tiếp nhận yêu cầu.');

    }



    public function edit(ServiceRequest $service_request): View

    {

        return view('monitoring.requests.form', ['item' => $service_request]);

    }



    public function update(Request $httpRequest, ServiceRequest $service_request): JsonResponse|RedirectResponse

    {

        $data = $this->preparePayload($this->validated($httpRequest));

        $staffDefault = $this->staffDefault();



        $service_request->update(array_merge($data, [

            'recipient' => Employee::nicknameFor($data['recipient'] ?: $service_request->recipient ?: $staffDefault),

        ]));



        if ($httpRequest->wantsJson()) {

            $fresh = $service_request->fresh();



            return response()->json([

                'message' => 'Đã cập nhật.',

                'item' => array_merge($fresh->toArray(), ['evidence' => $fresh->attachments ?? '']),

            ]);

        }



        return redirect()->route('requests.index')->with('success', 'Đã cập nhật.');

    }



    public function destroy(Request $request, ServiceRequest $service_request): JsonResponse|RedirectResponse

    {

        $service_request->delete();



        if ($request->wantsJson()) {

            return response()->json(['message' => 'Đã xóa.']);

        }



        return redirect()->route('requests.index')->with('success', 'Đã xóa.');

    }



    public function export(): StreamedResponse

    {

        $rows = ServiceRequest::orderByDesc('created_at')->get();

        return $this->export->download($rows, $this->excelFieldLabels(), 'DS_TiepNhanYeuCau_'.now()->format('Ymd').'.xlsx');

    }



    public function importPreview(Request $request): JsonResponse

    {

        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

        return response()->json([

            'rows' => $this->excel->parseRows($request->file('file')->getPathname(), $this->excelFieldLabels()),

        ]);

    }



    public function import(Request $request): JsonResponse

    {

        $rules = ['rows' => 'required|array|min:1', 'rows.*.student_name' => 'required|string|max:255'];

        foreach (array_keys($this->excelFieldLabels()) as $field) {

            if ($field !== 'student_name') {

                $rules['rows.*.'.$field] = 'nullable|string';

            }

        }

        $data = $request->validate($rules);

        foreach ($data['rows'] as $row) {

            $this->importRow($row);

        }

        return response()->json(['message' => 'Import thành công.', 'items' => $this->indexItems()]);

    }



    private function excelFieldLabels(): array

    {

        return [

            'ticket_number' => 'Số phiếu',
            'request_type' => 'Loại yêu cầu',
            'reception_date' => 'Ngày tiếp',

            'request_date' => 'Ngày yêu cầu',

            'student_name' => 'Họ tên SV',

            'student_id' => 'MSSV',

            'class' => 'Lớp',

            'department' => 'Đơn vị',

            'phone' => 'Điện thoại',

            'content' => 'Nội dung',

            'recipient' => 'Người tiếp',

            'status' => 'Trạng thái',

            'building_block' => 'Dãy nhà',

            'is_processed_immediately' => 'Xử lý ngay',

            'appointment_date' => 'Ngày hẹn',

            'resolution_date' => 'Ngày giải quyết',

            'resolver_name' => 'Người giải quyết',

            'feedback' => 'Phản hồi',

            'note' => 'Ghi chú',

        ];

    }



    private function importRow(array $row): void

    {

        $studentName = trim($row['student_name'] ?? '');

        if ($studentName === '') {

            return;

        }

        $ticket = trim($row['ticket_number'] ?? '');

        $existing = $ticket !== '' ? ServiceRequest::where('ticket_number', $ticket)->first() : null;

        $payload = [

            'ticket_number' => $ticket ?: null,
            'request_type' => $this->requestTypes->ensureType($row['request_type'] ?? null),
            'building_block' => $row['building_block'] ?? '',

            'student_name' => $studentName,

            'student_id' => $row['student_id'] ?? '',

            'class' => $row['class'] ?? '',

            'department' => $row['department'] ?? '',

            'phone' => $row['phone'] ?? '',

            'content' => $row['content'] ?? '',

            'request_date' => $row['request_date'] ?? date('d/m/Y'),

            'reception_date' => $row['reception_date'] ?? date('d/m/Y'),

            'recipient' => Employee::nicknameFor($row['recipient'] ?? $this->staffDefault()),

            'is_processed_immediately' => CatalogExcelService::toBool($row['is_processed_immediately'] ?? ''),

            'appointment_date' => $row['appointment_date'] ?? '',

            'resolution_date' => $row['resolution_date'] ?? '',

            'resolver_name' => $row['resolver_name'] ?? '',

            'feedback' => $row['feedback'] ?? '',

            'status' => $row['status'] ?? 'pending',

            'note' => $row['note'] ?? '',

        ];

        if ($existing) {

            $existing->update(array_merge($payload, ['ticket_number' => $ticket ?: $existing->ticket_number]));

            return;

        }

        ServiceRequest::create(array_merge($payload, [

            'id' => (string) Str::uuid(),

            'ticket_number' => $ticket ?: $this->nextTicketNumber(),

        ]));

    }



    private function indexItems(): array

    {

        return ServiceRequest::orderByDesc('created_at')->get()->map(function (ServiceRequest $item) {

            $row = $item->toArray();

            $row['evidence'] = $item->attachments ?? '';
            $row['building_block_label'] = $this->buildingBlocks->displayLabel($item->building_block);

            return $row;

        })->all();

    }



    private function validated(Request $request): array

    {

        $data = $request->validate([

            'ticket_number' => 'nullable|string|max:50',
            'request_type' => 'nullable|string|max:255',
            'building_block' => 'nullable|string|max:50',

            'student_name' => 'required|string|max:255',

            'student_id' => 'nullable|string|max:50',

            'class' => 'nullable|string|max:50',

            'department' => 'nullable|string|max:255',

            'phone' => 'nullable|string|max:20',

            'content' => 'required|string',

            'attachments' => 'nullable|string',

            'evidence' => 'nullable|string',

            'request_date' => 'nullable|string|max:20',

            'reception_date' => 'nullable|string|max:20',

            'recipient' => 'nullable|string|max:255',

            'is_processed_immediately' => 'nullable|boolean',

            'appointment_date' => 'nullable|string|max:20',

            'resolution_date' => 'nullable|string|max:20',

            'resolver_name' => 'nullable|string|max:255',

            'feedback' => 'nullable|string',

            'status' => 'nullable|string|max:50',

            'note' => 'nullable|string',

        ]);



        if ($request->has('is_processed_immediately')) {

            $data['is_processed_immediately'] = $request->boolean('is_processed_immediately');

        }



        return $data;

    }



    private function preparePayload(array $data): array

    {

        if (array_key_exists('evidence', $data)) {

            $data['attachments'] = $data['evidence'] ?: ($data['attachments'] ?? null);

            unset($data['evidence']);

        }



        if (array_key_exists('request_type', $data)) {
            $data['request_type'] = $this->requestTypes->ensureType($data['request_type']);
        }

        if (array_key_exists('building_block', $data)) {
            $data['building_block'] = $this->buildingBlocks->normalizeStoredValue($data['building_block']);
        }

        return $data;
    }

    private function nextTicketNumber(): string
    {
        $maxNum = 0;

        ServiceRequest::query()

            ->whereNotNull('ticket_number')

            ->pluck('ticket_number')

            ->each(function (string $ticket) use (&$maxNum) {

                if (str_ends_with($ticket, '/PYC')) {

                    $num = (int) strtok($ticket, '/');

                    if ($num > $maxNum) {

                        $maxNum = $num;

                    }

                }

            });



        return sprintf('%04d/PYC', $maxNum + 1);

    }



    private function staffDefault(): string

    {

        $user = auth()->user();

        if (! $user) {

            return '';

        }



        $employee = Employee::where('email', $user->email)->first();



        return $employee?->nickname ?: $employee?->name ?: $user->name;

    }

}


