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
            'father_name' => 'Họ tên cha',
            'father_occupation' => 'Nghề nghiệp cha',
            'mother_name' => 'Họ tên mẹ',
            'mother_occupation' => 'Nghề nghiệp mẹ',
            'class' => 'Lớp',
            'hometown' => 'Nguyên quán',
            'ethnicity' => 'Dân tộc',
            'religion' => 'Tôn giáo',
            'permanent_address' => 'Thường trú',
            'contact_address' => 'Liên lạc',
            'phone' => 'Điện thoại',
            'parent_phone' => 'ĐT Phụ huynh',
            'email' => 'Email',
            'citizen_id' => 'Số CMND',
            'region' => 'Khu vực',
            'department' => 'Khoa',
            'major' => 'Ngành',
            'note' => 'Ghi chú',
        ], 'DS_SinhVien_'.now()->format('Ymd').'.xlsx');

    }



    public function importPreview(Request $request): JsonResponse
    {
        @ini_set('memory_limit', '512M');
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

        $parsedRows = $this->parseSpreadsheet($request->file('file')->getPathname());
        $validRows = $this->normalizeImportRows($parsedRows);

        return response()->json([
            'rows' => $validRows,
            'total_parsed' => count($parsedRows),
            'valid_count' => count($validRows),
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        @ini_set('memory_limit', '512M');
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

            'rows.*.contact_address' => 'nullable|string',

            'rows.*.region' => 'nullable|string|max:255',

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

        if ($request->boolean('async')) {
            $progress = $this->importProgress->start(count($data['rows']), 'Import sinh viên');
            ProcessCatalogImportJob::dispatch($progress['id'], 'students', $data['rows'])->afterResponse();

            return response()->json([
                'message' => 'Đang import nền...',
                'async' => true,
                'progress_id' => $progress['id'],
                'progress_url' => route('imports.status', ['id' => $progress['id']]),
            ]);
        }

        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '512M');

        DB::transaction(function () use ($data) {
            foreach ($data['rows'] as $row) {
                $id = trim($row['id']);
                $name = trim($row['name']);
                if ($id === '' || $name === '') {
                    continue;
                }

                $payload = $this->studentRowPayload($row);
                Student::updateOrCreate(['id' => $id], $payload);
            }
        });

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
            'permanent_address' => $data['permanent_address'] ?? ($data['address'] ?? ''),
            'contact_address' => $data['contact_address'] ?? ($data['temporary_address'] ?? ''),
            'region' => $data['region'] ?? '',
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
            'contact_address' => 'nullable|string',
            'region' => 'nullable|string|max:255',
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
            'father_name' => 'Họ tên cha',
            'father_occupation' => 'Nghề nghiệp cha',
            'mother_name' => 'Họ tên mẹ',
            'mother_occupation' => 'Nghề nghiệp mẹ',
            'class' => 'Lớp',
            'hometown' => 'Nguyên quán',
            'ethnicity' => 'Dân tộc',
            'religion' => 'Tôn giáo',
            'permanent_address' => 'Thường trú',
            'contact_address' => 'Liên lạc',
            'phone' => 'Điện thoại',
            'parent_phone' => 'ĐT Phụ huynh',
            'email' => 'Email',
            'citizen_id' => 'Số CMND',
            'region' => 'Khu vực',
            'department' => 'Khoa',
            'major' => 'Ngành',
            'note' => 'Ghi chú',
        ];
    }

    private function parseSpreadsheet(string $path): array
    {
        @ini_set('memory_limit', '512M');

        return $this->catalogExcel->parseRows($path, $this->studentImportFieldLabels(), [
            'aliases' => [
                // Mã sinh viên
                'mssv' => 'id',
                'mã sv' => 'id',
                'ma sv' => 'id',
                'mã số sv' => 'id',
                'ma so sv' => 'id',
                'mã số sinh viên' => 'id',
                'ma so sinh vien' => 'id',
                'mã sinh viên' => 'id',
                'ma sinh vien' => 'id',
                'mã học viên' => 'id',
                'ma hoc vien' => 'id',
                'mã định danh' => 'id',
                'student id' => 'id',
                'student_id' => 'id',
                'student code' => 'id',
                'code' => 'id',

                // Họ và tên
                'họ và tên' => 'name',
                'ho va ten' => 'name',
                'họ tên' => 'name',
                'ho ten' => 'name',
                'họ và tên sinh viên' => 'name',
                'tên sinh viên' => 'name',
                'họ tên sinh viên' => 'name',
                'full name' => 'name',
                'fullname' => 'name',

                // Cột họ lót + tên riêng biệt
                'họ lót' => 'last_name',
                'ho lot' => 'last_name',
                'họ và đệm' => 'last_name',
                'ho va dem' => 'last_name',
                'họ và tên đệm' => 'last_name',
                'ho va ten dem' => 'last_name',
                'họ đệm' => 'last_name',
                'ho dem' => 'last_name',
                'họ' => 'last_name',
                'ho' => 'last_name',
                'tên' => 'first_name',
                'ten' => 'first_name',
                'tên sv' => 'first_name',
                'ten sv' => 'first_name',

                // Lớp
                'lớp' => 'class',
                'lop' => 'class',
                'lớp học' => 'class',
                'lop hoc' => 'class',
                'mã lớp' => 'class',
                'ma lop' => 'class',
                'tên lớp' => 'class',
                'ten lop' => 'class',
                'lớp sinh hoạt' => 'class',
                'lop sinh hoat' => 'class',
                'lớp danh nghĩa' => 'class',
                'lớp học phần' => 'class',
                'khóa/lớp' => 'class',
                'class' => 'class',

                // Giới tính
                'giới tính' => 'gender',
                'gioi tinh' => 'gender',
                'phái' => 'gender',
                'phai' => 'gender',
                'gender' => 'gender',
                'sex' => 'gender',

                // Ngày sinh
                'ngày sinh' => 'birth_date',
                'ngay sinh' => 'birth_date',
                'ngaysinh' => 'birth_date',
                'ngày tháng năm sinh' => 'birth_date',
                'ngay thang nam sinh' => 'birth_date',
                'năm sinh' => 'birth_date',
                'nam sinh' => 'birth_date',
                'dob' => 'birth_date',
                'birth date' => 'birth_date',
                'birthday' => 'birth_date',

                // Nơi sinh / Quê quán
                'nơi sinh' => 'birth_place',
                'noi sinh' => 'birth_place',
                'noisinh' => 'birth_place',
                'quê quán' => 'hometown',
                'que quan' => 'hometown',
                'nguyên quán' => 'hometown',
                'nguyen quan' => 'hometown',

                // Điện thoại
                'điện thoại' => 'phone',
                'dien thoai' => 'phone',
                'sđt' => 'phone',
                'sdt' => 'phone',
                'số điện thoại' => 'phone',
                'so dien thoai' => 'phone',
                'phone' => 'phone',
                'mobile' => 'phone',
                'di động' => 'phone',
                'di dong' => 'phone',
                'dđ' => 'phone',

                // Điện thoại phụ huynh
                'đt phụ huynh' => 'parent_phone',
                'dt phu huynh' => 'parent_phone',
                'sđt phụ huynh' => 'parent_phone',
                'sdt phu huynh' => 'parent_phone',
                'số điện thoại phụ huynh' => 'parent_phone',
                'so dien thoai phu huynh' => 'parent_phone',
                'đt cha mẹ' => 'parent_phone',
                'sđt liên hệ gia đình' => 'parent_phone',
                'số điện thoại cha' => 'father_phone',
                'so dien thoai cha' => 'father_phone',
                'sđt cha' => 'father_phone',
                'sdt cha' => 'father_phone',
                'số điện thoại mẹ' => 'mother_phone',
                'so dien thoai me' => 'mother_phone',
                'sđt mẹ' => 'mother_phone',
                'sdt me' => 'mother_phone',
                'điện thoại 2' => 'secondary_phone',
                'điện thoại 3' => 'tertiary_phone',

                // Lớp & Nơi sinh
                'mã lớp' => 'class_code',
                'ma lop' => 'class_code',
                'nơi sinh phường xã' => 'birth_place_ward',
                'noi sinh phuong xa' => 'birth_place_ward',

                // Email
                'email' => 'email',
                'hộp thư' => 'email',
                'hop thu' => 'email',
                'e-mail' => 'email',
                'mail' => 'email',
                'thư điện tử' => 'email',
                'email sinh viên' => 'email',
                'email trường' => 'school_email',
                'email truong' => 'school_email',
                'mail trường' => 'school_email',
                'mail truong' => 'school_email',

                // CCCD / CMND
                'số cmnd' => 'citizen_id',
                'so cmnd' => 'citizen_id',
                'số cccd' => 'citizen_id',
                'so cccd' => 'citizen_id',
                'cccd' => 'citizen_id',
                'cmnd' => 'citizen_id',
                'số cmnd/cccd' => 'citizen_id',
                'so cmnd/cccd' => 'citizen_id',
                'số cmnd / cccd' => 'citizen_id',
                'căn cước công dân' => 'citizen_id',
                'can cuoc cong dan' => 'citizen_id',
                'căn cước' => 'citizen_id',
                'can cuoc' => 'citizen_id',
                'số định danh' => 'citizen_id',
                'so dinh danh' => 'citizen_id',
                'số cmt' => 'citizen_id',
                'cmt' => 'citizen_id',

                // Khoa / Viện / Ngành
                'khoa' => 'department',
                'viện' => 'department',
                'khoa/viện' => 'department',
                'khoa / viện' => 'department',
                'đơn vị' => 'department',
                'ngành' => 'major',
                'nganh' => 'major',
                'chuyên ngành' => 'specialization',
                'chuyen nganh' => 'specialization',
                'ngành học' => 'major',
                'nganh hoc' => 'major',

                // Địa chỉ
                'đc thường trú' => 'permanent_address',
                'dc thuong tru' => 'permanent_address',
                'đc thường tru' => 'permanent_address',
                'thường trú' => 'permanent_address',
                'thuong tru' => 'permanent_address',
                'hộ khẩu thường trú' => 'permanent_address',
                'địa chỉ thường trú' => 'permanent_address',
                'địa chỉ' => 'permanent_address',
                'dia chi' => 'permanent_address',
                'hktt' => 'permanent_address',
                'hộ khẩu' => 'permanent_address',
                'đc liên lạc' => 'contact_address',
                'dc lien lac' => 'contact_address',
                'liên lạc' => 'contact_address',
                'lien lac' => 'contact_address',
                'địa chỉ liên lạc' => 'contact_address',
                'dia chi lien lac' => 'contact_address',
                'tạm trú' => 'contact_address',
                'tam tru' => 'contact_address',
                'địa chỉ tạm trú' => 'contact_address',
                'nơi ở hiện nay' => 'contact_address',
                'chỗ ở hiện nay' => 'contact_address',

                // Dân tộc / Tôn giáo
                'dân tộc' => 'ethnicity',
                'dan toc' => 'ethnicity',
                'tôn giáo' => 'religion',
                'ton giao' => 'religion',

                // Thông tin gia đình / Phụ huynh
                'tên cha' => 'father_name',
                'ten cha' => 'father_name',
                'họ tên cha' => 'father_name',
                'ho ten cha' => 'father_name',
                'họ và tên cha' => 'father_name',
                'ho va ten cha' => 'father_name',
                'cha' => 'father_name',
                'bố' => 'father_name',
                'bo' => 'father_name',
                'họ tên bố' => 'father_name',
                'ho ten bo' => 'father_name',
                'nghề nghiệp cha' => 'father_occupation',
                'nghe nghiep cha' => 'father_occupation',
                'nghề cha' => 'father_occupation',
                'nghề nghiệp bố' => 'father_occupation',
                'công việc cha' => 'father_occupation',
                'tên mẹ' => 'mother_name',
                'ten me' => 'mother_name',
                'họ tên mẹ' => 'mother_name',
                'ho ten me' => 'mother_name',
                'họ và tên mẹ' => 'mother_name',
                'ho va ten me' => 'mother_name',
                'mẹ' => 'mother_name',
                'má' => 'mother_name',
                'ma' => 'mother_name',
                'nghề nghiệp mẹ' => 'mother_occupation',
                'nghe nghiep me' => 'mother_occupation',
                'nghề mẹ' => 'mother_occupation',
                'công việc mẹ' => 'mother_occupation',

                // Khu vực
                'khu vực' => 'region',
                'khu vuc' => 'region',

                // Ghi chú
                'ghi chú' => 'note',
                'ghi chu' => 'note',
                'note' => 'note',
            ],
            'headerRowIndices' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
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

        // Bỏ qua dòng ký tên / footer ngày tháng
        if (str_contains($id, 'tp.hcm') || str_contains($name, 'tp.hcm')
            || (str_contains($id, 'ngày') && str_contains($id, 'năm'))
            || (str_contains($name, 'ngày') && str_contains($name, 'năm'))
            || str_contains($id, 'người lập') || str_contains($name, 'người lập')
            || str_contains($id, 'trưởng phòng') || str_contains($name, 'trưởng phòng')
            || str_contains($id, 'trưởng khoa') || str_contains($name, 'trưởng khoa')
        ) {
            return false;
        }

        // Bỏ qua các dòng header / tổng cộng / số thứ tự
        $headerWords = [
            'mã sinh viên', 'ma sinh vien', 'mã sv', 'ma sv', 'mssv', 'stt', 'tổng cộng', 'tong cong',
            'tổng số', 'tong so', 'số cmnd', 'so cmnd', 'số cmnd/cccd', 'cccd', 'cmnd', 'id',
            'họ và tên', 'ho va ten', 'họ tên', 'ho ten', 'họ và tên sinh viên', 'tên sinh viên',
            'giới tính', 'ngày sinh', 'nơi sinh', 'lớp', 'khoa', 'ngành',
        ];

        if (in_array($id, $headerWords, true) || in_array($name, $headerWords, true)) {
            return false;
        }

        if (str_starts_with($id, 'tổng') || str_starts_with($name, 'tổng') || str_starts_with($id, 'tong') || str_starts_with($name, 'tong')) {
            return false;
        }

        if (str_starts_with($id, 'lớp:') || str_starts_with($name, 'lớp:') || str_starts_with($id, 'lop:') || str_starts_with($name, 'lop:')) {
            return false;
        }

        if (str_starts_with($id, 'khoa:') || str_starts_with($name, 'khoa:')) {
            return false;
        }

        if (str_contains($id, 'sinh viên') || str_contains($id, 'mã số') || $id === 'mã' || $id === 'ma') {
            return false;
        }

        // Mã sinh viên hợp lệ phải chứa ít nhất một chữ số và độ dài không quá 30 ký tự
        if (! preg_match('/\d/', $id) || mb_strlen($id) > 30) {
            return false;
        }

        return true;
    }

    /** @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeImportRow(array $row): array
    {
        // Ghép Họ và Tên nếu cột name đang trống
        if ((! isset($row['name']) || trim((string) $row['name']) === '') && (isset($row['last_name']) || isset($row['first_name']))) {
            $row['name'] = trim(($row['last_name'] ?? '').' '.($row['first_name'] ?? ''));
        }

        // Fallback Email: nếu email trống thì lấy Email trường
        if (empty($row['email']) && ! empty($row['school_email'])) {
            $row['email'] = $row['school_email'];
        }

        // Fallback Địa chỉ thường trú
        if (empty($row['permanent_address']) && ! empty($row['address'])) {
            $row['permanent_address'] = $row['address'];
        }

        // Fallback Địa chỉ liên lạc
        if (empty($row['contact_address']) && ! empty($row['temporary_address'])) {
            $row['contact_address'] = $row['temporary_address'];
        }

        // Fallback Điện thoại phụ huynh: lấy từ cha hoặc mẹ nếu trống
        if (empty($row['parent_phone'])) {
            $row['parent_phone'] = $row['father_phone'] ?? ($row['mother_phone'] ?? ($row['secondary_phone'] ?? ''));
        }

        // Fallback Nơi sinh
        if (empty($row['birth_place']) && ! empty($row['birth_place_ward'])) {
            $row['birth_place'] = $row['birth_place_ward'];
        }

        // Fallback Lớp
        if (empty($row['class']) && ! empty($row['class_code'])) {
            $row['class'] = $row['class_code'];
        }

        // Fallback Ngành / Khoa
        if (empty($row['major']) && ! empty($row['specialization'])) {
            $row['major'] = $row['specialization'];
        }

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
            'permanent_address' => $row['permanent_address'] ?? ($row['address'] ?? ''),
            'contact_address' => $row['contact_address'] ?? ($row['temporary_address'] ?? ''),
            'region' => $row['region'] ?? '',
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
