<?php



namespace App\Http\Controllers;



use App\Jobs\ProcessCatalogImportJob;
use App\Models\Department;
use App\Models\ServiceRequest;
use App\Models\Student;
use App\Models\StudentViolation;
use App\Services\CatalogExcelService;
use App\Services\CatalogTableQueryService;
use App\Services\ImportProgressService;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

use Symfony\Component\HttpFoundation\StreamedResponse;



class StudentController extends Controller

{

    public function __construct(
        private ReportExportService $export,
        private CatalogTableQueryService $catalogQuery,
        private ImportProgressService $importProgress,
        private CatalogExcelService $catalogExcel,
    ) {}



    public function index(): View

    {

        return view('personnel.students.index', [

            'items' => collect(),

            'serverPaginated' => true,

            'departmentOptions' => Department::orderBy('name')->pluck('name')

                ->map(fn ($name) => ['value' => $name, 'label' => $name])->values()->all(),

        ]);

    }

    public function list(Request $request): JsonResponse
    {
        return $this->catalogQuery->paginate(
            $request,
            Student::query()->orderBy('name'),
            [
                'id', 'name', 'class', 'department', 'major', 'phone', 'email', 'citizen_id',
                'birth_place', 'hometown', 'note',
            ],
            fn (Student $student) => $student->toArray(),
        );
    }



    public function info(Request $request): JsonResponse
    {
        $id = $request->query('id');
        if (!$id) return response()->json(['item' => null]);
        
        $student = Student::where('id', $id)->orWhere('citizen_id', $id)->first();
        return response()->json(['item' => $student]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse

    {

        $data = $this->validatedData($request);



        $student = Student::create($this->payloadFromValidated($data));



        if ($request->wantsJson()) {

            return response()->json([

                'message' => 'Đã thêm sinh viên.',

                'item' => $student,

            ]);

        }



        return redirect()->route('students.index')->with('success', 'Đã thêm sinh viên.');

    }



    public function create(): View

    {

        return view('personnel.students.form', ['item' => null]);

    }



    public function edit(Student $student): View

    {

        return view('personnel.students.form', ['item' => $student]);

    }



    public function update(Request $request, Student $student): JsonResponse|RedirectResponse
    {
        $data = $this->validatedData($request, $student);
        $payload = $this->payloadFromValidated($data, includeId: false);
        $newId = trim($data['id']);
        $oldId = $student->id;

        if ($newId !== $oldId) {
            DB::transaction(function () use ($oldId, $newId, $payload) {
                Student::where('id', $oldId)->update(array_merge($payload, [
                    'id' => $newId,
                    'updated_at' => now(),
                ]));
                $this->syncStudentIdReferences($oldId, $newId);
            });
            $student = Student::findOrFail($newId);
        } else {
            $student->update($payload);
            $student = $student->fresh();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã cập nhật sinh viên.',
                'item' => $student,
            ]);
        }

        return redirect()->route('students.index')->with('success', 'Đã cập nhật sinh viên.');
    }



    public function destroy(Request $request, Student $student): JsonResponse|RedirectResponse

    {

        $student->delete();



        if ($request->wantsJson()) {

            return response()->json(['message' => 'Đã xóa sinh viên.']);

        }



        return redirect()->route('students.index')->with('success', 'Đã xóa sinh viên.');

    }



    public function export(Request $request): StreamedResponse

    {

        $items = $this->catalogQuery->collect(
            $request,
            Student::query()->orderBy('name'),
            [
                'id', 'name', 'class', 'department', 'major', 'phone', 'email', 'citizen_id',
                'birth_place', 'hometown', 'note',
            ],
            fn (Student $student) => $student->toArray(),
        );

        return $this->export->download(collect($items), [
            'id' => 'Mã sinh viên',
            'name' => 'Họ và tên',
            'gender' => 'Giới tính',
            'birth_date' => 'Ngày sinh',
            'birth_place' => 'Nơi sinh',
            'hometown' => 'Nguyên quán',
            'ethnicity' => 'Dân tộc',
            'religion' => 'Tôn giáo',
            'class' => 'Lớp',
            'major' => 'Ngành',
            'department' => 'Khoa',
            'permanent_address' => 'Thường trú',
            'temporary_address' => 'Tạm trú',
            'contact_address' => 'Liên lạc',
            'region' => 'Khu vực',
            'address' => 'Địa chỉ',
            'phone' => 'Điện thoại',
            'email' => 'Email',
            'citizen_id' => 'Số CMND/CCCD',
            'father_name' => 'Tên cha',
            'father_occupation' => 'Nghề nghiệp cha',
            'mother_name' => 'Tên mẹ',
            'mother_occupation' => 'Nghề nghiệp mẹ',
            'parent_phone' => 'ĐT phụ huynh',
            'note' => 'Ghi chú',
        ], 'DS_SinhVien_'.now()->format('Ymd').'.xlsx');

    }



    public function importPreview(Request $request): JsonResponse

    {

        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);



        return response()->json([

            'rows' => $this->normalizeImportRows($this->parseSpreadsheet($request->file('file')->getPathname())),

        ]);

    }



    public function import(Request $request): JsonResponse

    {

        $request->merge([
            'rows' => $this->normalizeImportRows($request->input('rows', [])),
        ]);

        $data = $request->validate([

            'rows' => 'required|array|min:1',

            'rows.*.id' => 'required|string|max:50',

            'rows.*.name' => 'required|string|max:255',

            'rows.*.class' => 'nullable|string|max:50',

            'rows.*.gender' => 'nullable|in:Nam,Nữ',

            'rows.*.birth_date' => 'nullable|string|max:20',

            'rows.*.birth_place' => 'nullable|string|max:255',

            'rows.*.hometown' => 'nullable|string|max:255',

            'rows.*.ethnicity' => 'nullable|string|max:100',

            'rows.*.religion' => 'nullable|string|max:100',

            'rows.*.permanent_address' => 'nullable|string',

            'rows.*.temporary_address' => 'nullable|string',

            'rows.*.contact_address' => 'nullable|string',

            'rows.*.region' => 'nullable|string|max:255',

            'rows.*.address' => 'nullable|string',

            'rows.*.major' => 'nullable|string|max:255',

            'rows.*.father_name' => 'nullable|string|max:255',

            'rows.*.father_occupation' => 'nullable|string|max:255',

            'rows.*.mother_name' => 'nullable|string|max:255',

            'rows.*.mother_occupation' => 'nullable|string|max:255',

            'rows.*.parent_phone' => 'nullable|string|max:20',

            'rows.*.phone' => 'nullable|string|max:20',

            'rows.*.email' => 'nullable|string|max:255',

            'rows.*.citizen_id' => 'nullable|string|max:20',

            'rows.*.department' => 'nullable|string|max:255',

            'rows.*.note' => 'nullable|string',

            'async' => 'sometimes|boolean',

        ]);

        if ($request->boolean('async') || count($data['rows']) > 50) {
            $progress = $this->importProgress->start(count($data['rows']), 'Import sinh viên');
            ProcessCatalogImportJob::dispatch($progress['id'], 'students', $data['rows'])->afterResponse();

            return response()->json([
                'message' => 'Đang import nền...',
                'async' => true,
                'progress_id' => $progress['id'],
                'progress_url' => route('imports.status', ['id' => $progress['id']]),
            ]);
        }



        foreach ($data['rows'] as $row) {

            $id = trim($row['id']);

            $name = trim($row['name']);

            if ($id === '' || $name === '') {

                continue;

            }



            $payload = $this->studentRowPayload($row);

            $existing = Student::find($id);

            if ($existing) {

                $existing->update($payload);

            } else {

                Student::create(array_merge($payload, [

                    'id' => $id,

                    'avatar_url' => '',

                ]));

            }

        }



        return response()->json([

            'message' => 'Import thành công.',

            'async' => false,

        ]);

    }



    private function payloadFromValidated(array $data, bool $includeId = true): array

    {

        $payload = [
            'name' => $data['name'],
            'gender' => $data['gender'],
            'birth_date' => $data['birth_date'] ?? '',
            'birth_place' => $data['birth_place'] ?? '',
            'hometown' => $data['hometown'] ?? '',
            'ethnicity' => $data['ethnicity'] ?? '',
            'religion' => $data['religion'] ?? '',
            'class' => $data['class'] ?? '',
            'permanent_address' => $data['permanent_address'] ?? '',
            'temporary_address' => $data['temporary_address'] ?? '',
            'contact_address' => $data['contact_address'] ?? '',
            'region' => $data['region'] ?? '',
            'address' => $data['address'] ?? '',
            'department' => $data['department'] ?? '',
            'major' => $data['major'] ?? ($data['department'] ?? ''),
            'father_name' => $data['father_name'] ?? '',
            'father_occupation' => $data['father_occupation'] ?? '',
            'mother_name' => $data['mother_name'] ?? '',
            'mother_occupation' => $data['mother_occupation'] ?? '',
            'parent_phone' => $data['parent_phone'] ?? '',
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'] ?? '',
            'citizen_id' => $data['citizen_id'] ?? '',
            'note' => $data['note'] ?? '',
            'avatar_url' => $data['avatar_url'] ?? '',
        ];



        if ($includeId) {

            $payload['id'] = $data['id'];

        }



        return $payload;

    }



    private function validatedData(Request $request, ?Student $student = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'gender' => 'required|in:Nam,Nữ',
            'birth_date' => 'nullable|string|max:20',
            'birth_place' => 'nullable|string|max:255',
            'hometown' => 'nullable|string|max:255',
            'ethnicity' => 'nullable|string|max:100',
            'religion' => 'nullable|string|max:100',
            'class' => 'nullable|string|max:50',
            'permanent_address' => 'nullable|string',
            'temporary_address' => 'nullable|string',
            'contact_address' => 'nullable|string',
            'region' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'department' => 'nullable|string|max:255',
            'major' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'father_occupation' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'mother_occupation' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'citizen_id' => 'nullable|string|max:20',
            'note' => 'nullable|string',
            'avatar_url' => 'nullable|string',
            'id' => $student
                ? ['required', 'string', 'max:50', Rule::unique('students', 'id')->ignore($student->id, 'id')]
                : 'required|string|max:50|unique:students,id',
        ];

        return $request->validate($rules);
    }

    private function syncStudentIdReferences(string $oldId, string $newId): void
    {
        if ($oldId === $newId) {
            return;
        }

        StudentViolation::where('student_id', $oldId)->update(['student_id' => $newId]);
        ServiceRequest::where('student_id', $oldId)->update(['student_id' => $newId]);
    }



    /** @return array<string, string> */
    private function studentImportFieldLabels(): array
    {
        return [
            'id' => 'Mã sinh viên',
            'name' => 'Họ và tên',
            'gender' => 'Giới tính',
            'birth_date' => 'Ngày sinh',
            'birth_place' => 'Nơi sinh',
            'class' => 'Lớp',
            'permanent_address' => 'Thường trú',
            'contact_address' => 'Liên lạc',
            'phone' => 'Điện thoại',
            'email' => 'Email',
            'citizen_id' => 'Số CMND/CCCD',
            'department' => 'Khoa',
            'note' => 'Ghi chú',
        ];
    }

    private function parseSpreadsheet(string $path): array
    {
        return $this->catalogExcel->parseRows($path, $this->studentImportFieldLabels(), [
            'aliases' => [
                'mssv' => 'id',
                'số cmnd' => 'citizen_id',
                'cccd' => 'citizen_id',
                'số cmnd/cccd' => 'citizen_id',
            ],
            'headerRowIndices' => [0, 7],
            'requiredKey' => 'id',
        ]);
    }

    /** @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function normalizeImportRows(array $rows): array
    {
        return array_values(array_filter(
            array_map(fn (array $row) => $this->normalizeImportRow($row), $rows),
            fn (array $row) => $this->isImportableStudentRow($row),
        ));
    }

    private function isImportableStudentRow(array $row): bool
    {
        $id = mb_strtolower(trim((string) ($row['id'] ?? '')));
        $name = mb_strtolower(trim((string) ($row['name'] ?? '')));

        if ($id === '' || $name === '') {
            return false;
        }

        if (in_array($id, ['mã sinh viên', 'mssv', 'stt'], true)) {
            return false;
        }

        if (in_array($name, ['họ và tên', 'họ tên'], true)) {
            return false;
        }

        return true;
    }

    /** @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeImportRow(array $row): array
    {
        $row['gender'] = $this->normalizeStudentGender($row['gender'] ?? '');
        $row['birth_date'] = $this->normalizeStudentBirthDate($row['birth_date'] ?? '');
        $row['phone'] = $this->normalizeStudentPhone($row['phone'] ?? '');
        $row['parent_phone'] = $this->normalizeStudentPhone($row['parent_phone'] ?? '');
        $row['citizen_id'] = $this->normalizeStudentCitizenId($row['citizen_id'] ?? '');
        $row['email'] = $this->normalizeStudentEmail($row['email'] ?? '');

        return $row;
    }

    private function normalizeStudentGender(mixed $value): string
    {
        $text = trim((string) $value);
        $lower = mb_strtolower($text);

        if ($text === 'Nữ' || $text === 'Nam') {
            return $text;
        }

        if ($lower === '' || $lower === 'giới tính') {
            return 'Nam';
        }

        if (in_array($lower, ['nữ', 'nu', 'nư', 'female', 'f', '2', 'n'], true) || str_contains($lower, 'nữ')) {
            return 'Nữ';
        }

        if (in_array($lower, ['nam', 'male', 'm', '1'], true)) {
            return 'Nam';
        }

        return 'Nam';
    }

    private function normalizeStudentBirthDate(mixed $value): string
    {
        $text = trim((string) $value);

        if ($text === '' || mb_strtolower($text) === 'ngày sinh') {
            return '';
        }

        if (mb_strlen($text) <= 20) {
            return $text;
        }

        if (preg_match('/\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}/u', $text, $matches)) {
            return mb_substr($matches[0], 0, 20);
        }

        return '';
    }

    private function normalizeStudentPhone(mixed $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        return mb_substr($digits, 0, 20);
    }

    private function normalizeStudentCitizenId(mixed $value): string
    {
        $text = preg_replace('/\s+/', '', (string) $value) ?? '';

        return mb_substr($text, 0, 20);
    }

    private function normalizeStudentEmail(mixed $value): string
    {
        $email = mb_strtolower(trim((string) $value));

        if ($email === '' || mb_strtolower($email) === 'email') {
            return '';
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }

    /** @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function studentRowPayload(array $row): array
    {
        $gender = $this->normalizeStudentGender($row['gender'] ?? '');
        $department = (string) ($row['department'] ?? '');

        return [
            'name' => trim((string) ($row['name'] ?? '')),
            'class' => $row['class'] ?? '',
            'gender' => $gender,
            'birth_date' => $this->normalizeStudentBirthDate($row['birth_date'] ?? ''),
            'birth_place' => $row['birth_place'] ?? '',
            'hometown' => $row['hometown'] ?? '',
            'ethnicity' => $row['ethnicity'] ?? '',
            'religion' => $row['religion'] ?? '',
            'permanent_address' => $row['permanent_address'] ?? '',
            'temporary_address' => $row['temporary_address'] ?? '',
            'contact_address' => $row['contact_address'] ?? '',
            'region' => $row['region'] ?? '',
            'address' => $row['address'] ?? '',
            'phone' => $this->normalizeStudentPhone($row['phone'] ?? ''),
            'email' => $this->normalizeStudentEmail($row['email'] ?? ''),
            'citizen_id' => $this->normalizeStudentCitizenId($row['citizen_id'] ?? ''),
            'department' => $department,
            'major' => $row['major'] ?? $department,
            'father_name' => $row['father_name'] ?? '',
            'father_occupation' => $row['father_occupation'] ?? '',
            'mother_name' => $row['mother_name'] ?? '',
            'mother_occupation' => $row['mother_occupation'] ?? '',
            'parent_phone' => $this->normalizeStudentPhone($row['parent_phone'] ?? ''),
            'note' => $row['note'] ?? '',
        ];
    }

}
