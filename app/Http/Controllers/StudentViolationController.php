<?php



namespace App\Http\Controllers;



use App\Models\BuildingBlock;

use App\Models\Department;

use App\Models\Employee;

use App\Models\IncidentCategory;

use App\Models\Recognition;

use App\Models\Student;

use App\Models\StudentViolation;

use App\Services\CatalogExcelService;
use App\Services\FaceComparisonService;
use App\Services\ReportExportService;
use App\Services\ViolationCardExtractionService;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Str;

use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;



class StudentViolationController extends Controller

{

    public function __construct(
        private FaceComparisonService $faceComparison,
        private ViolationCardExtractionService $cardExtraction,
        private ReportExportService $export,
        private CatalogExcelService $excel,
    ) {}



    public function index(): View

    {

        $officerNames = StudentViolation::query()

            ->whereNotNull('officer')

            ->where('officer', '!=', '')

            ->distinct()

            ->orderBy('officer')

            ->pluck('officer');



        $employeeNicknames = Employee::query()

            ->whereNotNull('nickname')

            ->where('nickname', '!=', '')

            ->orderBy('nickname')

            ->pluck('nickname');



        $officerFilterOptions = $officerNames

            ->merge($employeeNicknames)

            ->unique()

            ->sort()

            ->values()

            ->all();



        return view('monitoring.student-violations.index', [

            'items' => $this->indexItems(),

            'students' => Student::orderBy('name')->get(['id', 'name', 'class', 'major', 'department', 'citizen_id']),

            'violationTypeOptions' => $this->violationTypeOptions(),

            'buildingOptions' => BuildingBlock::orderBy('name')->pluck('name')

                ->map(fn ($name) => ['value' => $name, 'label' => $name])->values()->all(),

            'departmentOptions' => Department::orderBy('name')->pluck('name')

                ->map(fn ($name) => ['value' => $name, 'label' => $name])->values()->all(),

            'officerFilterOptions' => $officerFilterOptions,

            'officerDefault' => $this->currentOfficerName(),

        ]);

    }



    public function create(): View

    {

        return view('monitoring.student-violations.form', ['item' => null]);

    }

    public function show(StudentViolation $studentViolation): JsonResponse
    {
        return response()->json(['item' => $studentViolation]);
    }



    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $syncStudent = $request->boolean('sync_student');
        $data = collect($this->validated($request))->except('sync_student')->all();
        $item = StudentViolation::create(array_merge($data, [

            'id' => (string) Str::uuid(),

            'officer' => $data['officer'] ?: $this->currentOfficerName(),

            'violation_date' => $data['violation_date'] ?? date('d/m/Y'),

            'signed' => $data['signed'] ?? 'Chưa ký',

        ]));



        $syncedStudent = null;
        if ($syncStudent) {
            $syncedStudent = $this->syncStudentFromViolation($data);
        }

        $message = $syncedStudent
            ? 'Đã ghi nhận vi phạm và thêm sinh viên vào danh mục Sinh viên.'
            : 'Đã ghi nhận vi phạm.';

        if ($request->wantsJson()) {

            return response()->json([

                'message' => $message,

                'item' => $item,

                'synced_student' => $syncedStudent,

            ]);

        }



        return redirect()->route('student-violations.index')->with('success', $message);

    }



    public function edit(StudentViolation $student_violation): View

    {

        return view('monitoring.student-violations.form', ['item' => $student_violation]);

    }



    public function update(Request $request, StudentViolation $student_violation): JsonResponse|RedirectResponse

    {

        $syncStudent = $request->boolean('sync_student');

        $data = collect($this->validated($request))->except('sync_student')->all();

        $student_violation->update($data);



        $syncedStudent = null;
        if ($syncStudent) {
            $syncedStudent = $this->syncStudentFromViolation($data);
        }

        $message = $syncedStudent
            ? 'Đã cập nhật vi phạm và thêm sinh viên vào danh mục Sinh viên.'
            : 'Đã cập nhật.';

        if ($request->wantsJson()) {

            return response()->json([

                'message' => $message,

                'item' => $student_violation->fresh(),

                'synced_student' => $syncedStudent,

            ]);

        }



        return redirect()->route('student-violations.index')->with('success', $message);

    }



    public function destroy(Request $request, StudentViolation $student_violation): JsonResponse|RedirectResponse

    {

        $student_violation->delete();



        if ($request->wantsJson()) {

            return response()->json(['message' => 'Đã xóa.']);

        }



        return redirect()->route('student-violations.index')->with('success', 'Đã xóa.');

    }

    public function export(): StreamedResponse
    {
        return $this->export->download(
            StudentViolation::query()
                ->select(StudentViolation::INDEX_COLUMNS)
                ->orderByDesc('created_at')
                ->get(),
            $this->excelFieldLabels(),
            'SV_ViPham_'.now()->format('Ymd').'.xlsx',
            'Vi phạm sinh viên'
        );
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
        $rules = ['rows' => 'required|array|min:1', 'rows.*.full_name' => 'required|string|max:255'];
        foreach (array_keys($this->excelFieldLabels()) as $field) {
            if ($field !== 'full_name') {
                $rules['rows.*.'.$field] = 'nullable|string';
            }
        }
        $data = $request->validate($rules);
        foreach ($data['rows'] as $row) {
            $this->importRow($row);
        }

        return response()->json([
            'message' => 'Import thành công '.count($data['rows']).' bản ghi.',
            'items' => $this->indexItems(),
        ]);
    }



    public function compareFaces(Request $request): JsonResponse

    {

        $data = $request->validate([

            'portrait_photo' => 'required|string',

            'document_photo' => 'required|string',

        ]);



        $result = $this->faceComparison->compare(

            $data['portrait_photo'],

            $data['document_photo']

        );



        return response()->json($result);

    }

    public function extractCardInfo(Request $request): JsonResponse
    {
        $data = $request->validate([
            'document_photo' => 'required|string',
        ]);

        return response()->json($this->cardExtraction->extract($data['document_photo']));
    }



    private function violationTypeOptions(): array

    {

        $recognition = Recognition::query()

            ->whereRaw('LOWER(name) LIKE ?', ['%vi phạm%'])

            ->first();



        $query = IncidentCategory::query()->orderBy('name');

        if ($recognition) {

            $filtered = (clone $query)->where('recognition_id', $recognition->id);

            if ($filtered->exists()) {

                $query = $filtered;

            }

        }



        return $query->pluck('name')

            ->map(fn ($name) => ['value' => $name, 'label' => $name])

            ->values()

            ->all();

    }



    private function syncStudentFromViolation(array $data): ?Student
    {
        $studentId = trim((string) ($data['student_id'] ?? ''));
        $identifier = trim((string) ($data['identifier'] ?? ''));
        $catalogId = $studentId !== '' ? $studentId : $identifier;

        if ($catalogId === '' || $this->studentExistsInCatalog($studentId, $identifier)) {
            return null;
        }

        $citizenId = $identifier !== '' ? $identifier : $catalogId;

        return Student::create([
            'id' => $catalogId,
            'name' => trim((string) ($data['full_name'] ?? '')),
            'class' => trim((string) ($data['class'] ?? '')),
            'department' => trim((string) ($data['department'] ?? '')),
            'major' => trim((string) ($data['department'] ?? '')),
            'citizen_id' => $citizenId,
            'avatar_url' => trim((string) ($data['portrait_photo'] ?? '')),
            'gender' => 'Nam',
        ]);
    }

    private function studentExistsInCatalog(string $studentId, string $identifier): bool
    {
        $codes = array_values(array_unique(array_filter([$studentId, $identifier])));

        if ($codes === []) {
            return true;
        }

        return Student::query()
            ->where(function ($query) use ($codes) {
                foreach ($codes as $code) {
                    $query->orWhere('id', $code)->orWhere('citizen_id', $code);
                }
            })
            ->exists();
    }



    private function validated(Request $request): array

    {

        return $request->validate([

            'full_name' => 'required|string|max:255',

            'class' => 'nullable|string|max:50',

            'student_id' => 'nullable|string|max:50',

            'identifier' => 'nullable|string|max:50',

            'violation_date' => 'nullable|string|max:20',

            'violation_type' => 'nullable|string|max:255',

            'signed' => 'nullable|string|max:50',

            'officer' => 'nullable|string|max:255',

            'building' => 'nullable|string|max:100',

            'department' => 'nullable|string|max:255',

            'note' => 'nullable|string',

            'signature_base64' => 'nullable|string',

            'portrait_photo' => 'nullable|string',

            'document_photo' => 'nullable|string',

            'sync_student' => 'sometimes|boolean',

        ]);

    }

    private function currentOfficerName(): string
    {
        $user = auth()->user();
        if (! $user) {
            return '';
        }

        $employee = Employee::where('email', $user->email)->first();

        return $employee?->nickname ?: $employee?->name ?: $user->name;
    }

    /** @return array<string, string> */
    private function excelFieldLabels(): array
    {
        return [
            'building' => 'Dãy nhà',
            'full_name' => 'Họ tên',
            'department' => 'Khoa',
            'class' => 'Lớp',
            'student_id' => 'Mã số SV',
            'violation_date' => 'Ngày vi phạm',
            'violation_type' => 'Lỗi vi phạm',
            'signed' => 'Ký tên',
            'officer' => 'CB ghi nhận',
            'note' => 'Ghi chú',
            'identifier' => 'Mã định danh',
        ];
    }

    private function importRow(array $row): void
    {
        $fullName = trim($row['full_name'] ?? '');
        if ($fullName === '') {
            return;
        }

        StudentViolation::create([
            'id' => (string) Str::uuid(),
            'full_name' => $fullName,
            'class' => trim($row['class'] ?? ''),
            'student_id' => trim($row['student_id'] ?? ''),
            'identifier' => trim($row['identifier'] ?? '') ?: trim($row['student_id'] ?? ''),
            'violation_date' => trim($row['violation_date'] ?? '') ?: date('d/m/Y'),
            'violation_type' => trim($row['violation_type'] ?? ''),
            'signed' => trim($row['signed'] ?? '') ?: 'Chưa ký',
            'officer' => trim($row['officer'] ?? '') ?: $this->currentOfficerName(),
            'note' => trim($row['note'] ?? ''),
            'building' => trim($row['building'] ?? ''),
            'department' => trim($row['department'] ?? ''),
        ]);
    }

    private function indexItems()
    {
        return StudentViolation::query()
            ->select(StudentViolation::INDEX_COLUMNS)
            ->orderByDesc('created_at')
            ->get();
    }

}
