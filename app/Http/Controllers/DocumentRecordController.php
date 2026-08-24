<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCatalogImportJob;
use App\Models\Department;
use App\Models\DocumentRecord;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Services\CatalogExcelService;
use App\Services\DocumentRecordExtractionService;
use App\Services\DocumentRecordQueryService;
use App\Services\EvidenceStorageService;
use App\Services\ImportProgressService;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DocumentRecordController extends Controller
{
    public function __construct(
        private DocumentRecordQueryService $query,
        private EvidenceStorageService $storage,
        private ReportExportService $export,
        private DocumentRecordExtractionService $extraction,
        private CatalogExcelService $catalogExcel,
        private ImportProgressService $importProgress,
    ) {}

        public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $perPage = $request->integer('per_page', 25);
            $paginator = $this->query->filter($request)->paginate($perPage);
            return response()->json([
                'items' => $paginator->getCollection()->map(fn ($record) => $this->recordTableRow($record)),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                ]
            ]);
        }

        $mapOption = fn ($name) => ['value' => $name, 'label' => $name];

        return view('monitoring.document-records.index', [
            'items' => collect(), // will be loaded via ajax
            'docTypes' => DocumentType::orderBy('name')->get(),
            'docTypeOptions' => DocumentType::orderBy('name')->pluck('name')->map($mapOption)->values()->all(),
            'departmentOptions' => Department::orderBy('name')->pluck('name')->map($mapOption)->values()->all(),
            'employeeOptions' => Employee::orderBy('name')->pluck('name')->map($mapOption)->values()->all(),
            'statusOptions' => DocumentRecord::statusOptions(),
            'urgencyOptions' => DocumentRecord::urgencyOptions(),
            'confidentialityOptions' => DocumentRecord::confidentialityOptions(),
        ]);
    }

    public function create(): View
    {
        return view('monitoring.document-records.form', $this->formData(null));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $this->validated($request);
        $file = $request->file('original_file_upload');

        $record = DocumentRecord::create(array_merge($data, [
            'id' => (string) Str::uuid(),
            'created_by_user_id' => $request->user()?->id,
            'original_file' => $data['original_file'] ?? ($file ? $this->storeDocument($file) : null),
            'keywords' => $this->parseKeywords($request->input('keywords')),
        ]));

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã thêm hồ sơ văn bản.',
                'item' => $this->recordTableRow($record),
            ]);
        }

        return redirect()->route('document-records.index')->with('success', 'Đã thêm hồ sơ văn bản.');
    }

    public function edit(DocumentRecord $document_record): View
    {
        return view('monitoring.document-records.form', $this->formData($document_record));
    }

    public function update(Request $request, DocumentRecord $document_record): JsonResponse|RedirectResponse
    {
        $data = $this->validated($request);
        $file = $request->file('original_file_upload');

        if ($file) {
            $data['original_file'] = $this->storeDocument($file);
        } elseif (empty($data['original_file'])) {
            unset($data['original_file']);
        }

        $data['keywords'] = $this->parseKeywords($request->input('keywords'));
        unset($data['original_file_upload']);
        if (! $document_record->created_by_user_id && $request->user()) {
            $data['created_by_user_id'] = $request->user()->id;
        }
        $document_record->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã cập nhật hồ sơ.',
                'item' => $this->recordTableRow($document_record->fresh()),
            ]);
        }

        return redirect()->route('document-records.index')->with('success', 'Đã cập nhật hồ sơ.');
    }

    public function destroy(Request $request, DocumentRecord $document_record): JsonResponse|RedirectResponse
    {
        $document_record->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã xóa hồ sơ.']);
        }

        return redirect()->route('document-records.index')->with('success', 'Đã xóa hồ sơ.');
    }

    public function extract(Request $request): JsonResponse
    {
        $data = $request->validate([
            'original_file' => 'required|string|max:4000',
            'doc_type' => 'nullable|string|max:255',
            'file_name' => 'nullable|string|max:255',
        ]);

        $result = $this->extraction->extract(
            $data['original_file'],
            $data['doc_type'] ?? null,
            $data['file_name'] ?? null,
        );

        if ($result['title'] === '' && $result['extracted_text'] === '') {
            return response()->json(['message' => $result['message']], 422);
        }

        return response()->json($result);
    }

    public function export(Request $request)
    {
        $rows = $this->query->filter($request)->get();

        return $this->export->download($rows, [
            'doc_code' => 'Mã hồ sơ',
            'doc_number' => 'Số văn bản',
            'title' => 'Trích yếu',
            'doc_type' => 'Loại',
            'issue_date' => 'Ngày ban hành',
            'received_date' => 'Ngày đến',
            'issuing_body' => 'Cơ quan ban hành',
            'signer' => 'Người ký',
            'department' => 'Đơn vị',
            'assignee' => 'Phụ trách',
            'urgency' => 'Độ khẩn',
            'confidentiality' => 'Độ mật',
            'status' => 'Trạng thái',
        ], 'ho-so-van-ban-'.date('Y-m-d').'.xlsx');
    }

    public function importPreview(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

        return response()->json([
            'rows' => $this->parseSpreadsheet($request->file('file')->getPathname()),
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rows' => 'required|array|min:1',
            'rows.*.title' => 'required|string|max:500',
        ]);

        if ($request->boolean('async') || count($data['rows']) > 50) {
            $progress = $this->importProgress->start(count($data['rows']), 'Import hồ sơ văn bản');
            ProcessCatalogImportJob::dispatch($progress['id'], 'document-records', $data['rows'])->afterResponse();

            return response()->json([
                'message' => 'Đang import nền...',
                'async' => true,
                'progress_id' => $progress['id'],
                'progress_url' => route('imports.status', ['id' => $progress['id']]),
            ]);
        }

        $year = date('Y');
        $count = DocumentRecord::count();

        foreach ($data['rows'] as $index => $row) {
            $title = trim($row['title'] ?? '');
            if ($title === '') {
                continue;
            }

            $docCode = trim($row['doc_code'] ?? '');
            if ($docCode === '') {
                $count++;
                $docCode = "CV-{$year}-".str_pad((string) $count, 3, '0', STR_PAD_LEFT);
            }

            DocumentRecord::create([
                'id' => (string) Str::uuid(),
                'doc_code' => $docCode,
                'doc_number' => $row['doc_number'] ?? '',
                'title' => $title,
                'abstract' => $row['abstract'] ?? '',
                'doc_type' => $row['doc_type'] ?? '',
                'issue_date' => $row['issue_date'] ?? '',
                'received_date' => $row['received_date'] ?? '',
                'issuing_body' => $row['issuing_body'] ?? '',
                'signer' => $row['signer'] ?? '',
                'department' => $row['department'] ?? '',
                'assignee' => $row['assignee'] ?? '',
                'urgency' => $row['urgency'] ?? 'Thường',
                'confidentiality' => $row['confidentiality'] ?? 'Thường',
                'file_password' => $row['file_password'] ?? '',
                'status' => $row['status'] ?? 'Mới',
                'original_file' => $row['original_file'] ?? '',
            ]);
        }

        return response()->json([
            'message' => 'Đã nhập hồ sơ từ Excel.',
            'items' => DocumentRecord::orderByDesc('created_at')->get()->map(fn (DocumentRecord $record) => $this->recordTableRow($record)),
        ]);
    }

    private function formData(?DocumentRecord $item): array
    {
        return [
            'item' => $item,
            'docTypes' => DocumentType::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'employees' => Employee::orderBy('name')->get(),
            'urgencyOptions' => DocumentRecord::urgencyOptions(),
            'confidentialityOptions' => DocumentRecord::confidentialityOptions(),
            'statusOptions' => DocumentRecord::statusOptions(),
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'doc_code' => 'nullable|string|max:100',
            'doc_number' => 'nullable|string|max:100',
            'title' => 'required|string|max:500',
            'abstract' => 'nullable|string',
            'doc_type' => 'nullable|string|max:255',
            'issue_date' => 'nullable|string|max:20',
            'received_date' => 'nullable|string|max:20',
            'issuing_body' => 'nullable|string|max:255',
            'signer' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'assignee' => 'nullable|string|max:255',
            'urgency' => 'nullable|string|max:50',
            'confidentiality' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'extracted_text' => 'nullable|string',
            'ai_summary' => 'nullable|string',
            'file_password' => 'nullable|string|max:100',
            'original_file' => 'nullable|string',
            'original_file_upload' => 'nullable|file|max:20480',
        ]);
    }

    private function storeDocument($file): string
    {
        $name = $file->getClientOriginalName();
        $path = $file->store('documents/'.date('Y/m'), 'public');
        $url = Storage::disk('public')->url($path);

        return $name.':::'.$url;
    }

    private function parseKeywords(?string $raw): array
    {
        if (! $raw) {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[,;]+/', $raw))));
    }

    private function parseSpreadsheet(string $path): array
    {
        $rows = $this->catalogExcel->parseRows($path, [
            'doc_code' => 'Mã hồ sơ',
            'doc_number' => 'Số văn bản',
            'title' => 'Tiêu đề',
            'abstract' => 'Trích yếu',
            'doc_type' => 'Loại',
            'issue_date' => 'Ngày ban hành',
            'received_date' => 'Ngày đến',
            'issuing_body' => 'Cơ quan ban hành',
            'signer' => 'Người ký',
            'department' => 'Đơn vị',
            'assignee' => 'Phụ trách',
            'urgency' => 'Độ khẩn',
            'confidentiality' => 'Độ mật',
            'file_password' => 'Mật khẩu',
            'status' => 'Trạng thái',
            'original_file' => 'Đường dẫn file',
        ], [
            'requiredKey' => 'title',
            'aliases' => [
                'mã văn bản' => 'doc_code',
                'mã hồ sơ' => 'doc_code',
                'số/ký hiệu' => 'doc_number',
                'số văn bản' => 'doc_number',
                'tiêu đề' => 'title',
                'loại văn bản' => 'doc_type',
                'loại' => 'doc_type',
                'ngày nhận' => 'received_date',
                'phòng ban xử lý' => 'department',
                'người phụ trách' => 'assignee',
            ],
        ]);

        return array_map(function (array $row): array {
            if (trim((string) ($row['title'] ?? '')) === '' && trim((string) ($row['abstract'] ?? '')) !== '') {
                $row['title'] = $row['abstract'];
            }

            foreach ([
                'urgency' => 'Thường',
                'confidentiality' => 'Thường',
                'status' => 'Mới',
            ] as $key => $default) {
                if (trim((string) ($row[$key] ?? '')) === '') {
                    $row[$key] = $default;
                }
            }

            return $row;
        }, $rows);
    }

    /** @return array<string, mixed> */
    private function recordTableRow(DocumentRecord $record): array
    {
        return [
            'id' => $record->id,
            'doc_code' => $record->doc_code,
            'doc_number' => $record->doc_number,
            'title' => $record->title,
            'abstract' => $record->abstract,
            'doc_type' => $record->doc_type,
            'issue_date' => $record->issue_date,
            'received_date' => $record->received_date,
            'issuing_body' => $record->issuing_body,
            'signer' => $record->signer,
            'department' => $record->department,
            'assignee' => $record->assignee,
            'urgency' => $record->urgency,
            'confidentiality' => $record->confidentiality,
            'status' => $record->status,
            'original_file' => $record->original_file,
            'ai_summary' => $record->ai_summary,
            'extracted_text' => $record->extracted_text,
            'file_password' => $record->file_password,
        ];
    }
}
