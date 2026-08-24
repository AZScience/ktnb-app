<?php

namespace App\Jobs;

use App\Models\BuildingBlock;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\DocumentRecord;
use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Position;
use App\Models\Role;
use App\Models\Student;
use App\Services\AuthLoginService;
use App\Services\ImportProgressService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessCatalogImportJob implements ShouldQueue
{
    use Queueable;

    /** @param  list<array<string, mixed>>  $rows */
    public function __construct(
        public string $progressId,
        public string $catalog,
        public array $rows,
    ) {}

    public function handle(ImportProgressService $progress, AuthLoginService $authLogin): void
    {
        try {
            match ($this->catalog) {
                'classrooms' => $this->importClassrooms($progress),
                'document-records' => $this->importDocumentRecords($progress),
                'employees' => $this->importEmployees($progress, $authLogin),
                'lecturers' => $this->importLecturers($progress),
                'students' => $this->importStudents($progress),
                default => throw new \InvalidArgumentException('Catalog không hỗ trợ import nền: '.$this->catalog),
            };

            $progress->finish($this->progressId, 'Import thành công '.count($this->rows).' dòng.');
        } catch (\Throwable $e) {
            Log::error('Catalog import failed', ['catalog' => $this->catalog, 'error' => $e->getMessage()]);
            $progress->fail($this->progressId, $e->getMessage() ?: 'Import thất bại.');
        }
    }

    private function importEmployees(ImportProgressService $progress, AuthLoginService $authLogin): void
    {
        $defaultRoleId = Role::where('name', 'Nhân viên')->value('id') ?? 'staff';
        $total = count($this->rows);
        $processed = 0;

        foreach ($this->rows as $row) {
            $employeeId = trim((string) ($row['employee_id'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            if ($employeeId === '' || $name === '') {
                $processed++;
                $progress->update($this->progressId, compact('processed'));

                continue;
            }

            $payload = [
                'name' => $name,
                'email' => strtolower(trim((string) ($row['email'] ?? $employeeId.'@ntt.local'))),
                'note' => $row['note'] ?? '',
                'role_id' => $defaultRoleId,
            ];

            $existing = Employee::where('employee_id', $employeeId)->first();
            if ($existing) {
                $existing->update($payload);
                $employee = $existing->fresh();
            } else {
                $employee = Employee::create(array_merge($payload, [
                    'id' => (string) Str::uuid(),
                    'employee_id' => $employeeId,
                    'nickname' => '',
                    'position' => '',
                    'birth_date' => '',
                    'address' => '',
                    'phone' => '',
                    'avatar_url' => '',
                ]));
            }

            if ($employee && ! $authLogin->isSuperAdminEmail($employee->email)) {
                $authLogin->provisionUserFromEmployee($employee);
            }

            $processed++;
            if ($processed % 10 === 0 || $processed === $total) {
                $progress->update($this->progressId, [
                    'processed' => $processed,
                    'message' => "Đang import nhân viên ({$processed}/{$total})...",
                ]);
            }
        }
    }

    private function importStudents(ImportProgressService $progress): void
    {
        $total = count($this->rows);
        $processed = 0;

        foreach ($this->rows as $row) {
            $id = trim((string) ($row['id'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            if ($id === '' || $name === '') {
                $processed++;
                $progress->update($this->progressId, compact('processed'));

                continue;
            }

            $payload = [
                'name' => $name,
                'class' => $row['class'] ?? '',
                'gender' => in_array($row['gender'] ?? '', ['Nam', 'Nữ'], true) ? $row['gender'] : 'Nam',
                'birth_date' => $row['birth_date'] ?? '',
                'birth_place' => $row['birth_place'] ?? '',
                'hometown' => $row['hometown'] ?? '',
                'ethnicity' => $row['ethnicity'] ?? '',
                'religion' => $row['religion'] ?? '',
                'permanent_address' => $row['permanent_address'] ?? '',
                'temporary_address' => $row['temporary_address'] ?? '',
                'contact_address' => $row['contact_address'] ?? '',
                'region' => $row['region'] ?? '',
                'address' => $row['address'] ?? '',
                'phone' => $row['phone'] ?? '',
                'email' => $row['email'] ?? '',
                'citizen_id' => $row['citizen_id'] ?? '',
                'department' => $row['department'] ?? '',
                'major' => $row['major'] ?? ($row['department'] ?? ''),
                'father_name' => $row['father_name'] ?? '',
                'father_occupation' => $row['father_occupation'] ?? '',
                'mother_name' => $row['mother_name'] ?? '',
                'mother_occupation' => $row['mother_occupation'] ?? '',
                'parent_phone' => $row['parent_phone'] ?? '',
                'note' => $row['note'] ?? '',
            ];

            Student::updateOrCreate(['id' => $id], $payload);

            $processed++;
            if ($processed % 25 === 0 || $processed === $total) {
                $progress->update($this->progressId, [
                    'processed' => $processed,
                    'message' => "Đang import sinh viên ({$processed}/{$total})...",
                ]);
            }
        }
    }

    private function importLecturers(ImportProgressService $progress): void
    {
        $total = count($this->rows);
        $processed = 0;

        foreach ($this->rows as $row) {
            $id = trim((string) ($row['id'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            if ($id === '' || $name === '') {
                $this->tick($progress, $processed, $total, 'Đang import giảng viên');

                continue;
            }

            Lecturer::updateOrCreate(['id' => $id], [
                'name' => $name,
                'department' => $this->resolveDepartment((string) ($row['department'] ?? '')),
                'position' => $this->resolvePosition((string) ($row['position'] ?? '')),
                'birth_date' => trim((string) ($row['birth_date'] ?? '')),
                'address' => trim((string) ($row['address'] ?? '')),
                'phone' => trim((string) ($row['phone'] ?? '')),
                'email' => trim((string) ($row['email'] ?? '')),
                'note' => trim((string) ($row['note'] ?? '')),
                'avatar_url' => '',
            ]);

            $this->tick($progress, $processed, $total, 'Đang import giảng viên');
        }
    }

    private function importClassrooms(ImportProgressService $progress): void
    {
        $total = count($this->rows);
        $processed = 0;

        foreach ($this->rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                $this->tick($progress, $processed, $total, 'Đang import phòng học');

                continue;
            }

            $buildingBlockId = $this->resolveBuildingBlockId((string) ($row['building_block'] ?? ''));
            $existing = Classroom::where('name', $name)->first();
            if (! $buildingBlockId && ! $existing) {
                $this->tick($progress, $processed, $total, 'Đang import phòng học');

                continue;
            }

            $payload = [
                'name' => $name,
                'room_type' => $row['room_type'] ?? 'Lý thuyết',
                'seating_capacity' => $row['seating_capacity'] ?? null,
                'table_count' => $row['table_count'] ?? null,
                'exam_capacity' => $row['exam_capacity'] ?? null,
                'note' => $row['note'] ?? '',
                'is_inactive' => false,
            ];

            if ($buildingBlockId) {
                $payload['building_block_id'] = $buildingBlockId;
            }

            if ($existing) {
                $existing->update($payload);
            } else {
                Classroom::create(array_merge($payload, [
                    'id' => $this->makeClassroomId($name),
                    'building_block_id' => $buildingBlockId,
                    'subject_nature' => '',
                    'has_projector' => true,
                ]));
            }

            $this->tick($progress, $processed, $total, 'Đang import phòng học');
        }
    }

    private function importDocumentRecords(ImportProgressService $progress): void
    {
        $total = count($this->rows);
        $processed = 0;
        $year = date('Y');
        $count = DocumentRecord::count();

        foreach ($this->rows as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                $this->tick($progress, $processed, $total, 'Đang import hồ sơ văn bản');

                continue;
            }

            $docCode = trim((string) ($row['doc_code'] ?? ''));
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

            $this->tick($progress, $processed, $total, 'Đang import hồ sơ văn bản');
        }
    }

    private function tick(ImportProgressService $progress, int &$processed, int $total, string $label): void
    {
        $processed++;
        if ($processed % 25 === 0 || $processed === $total) {
            $progress->update($this->progressId, [
                'processed' => $processed,
                'message' => "{$label} ({$processed}/{$total})...",
            ]);
        }
    }

    private function resolveDepartment(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $department = Department::find($value)
            ?? Department::where('department_id', $value)->first()
            ?? Department::where('name', $value)->first();

        return $department?->id ?? $value;
    }

    private function resolvePosition(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $position = Position::find($value) ?? Position::where('name', $value)->first();

        return $position?->id ?? $value;
    }

    private function resolveBuildingBlockId(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $block = BuildingBlock::find($value)
            ?? BuildingBlock::where('name', $value)->first()
            ?? BuildingBlock::where('code', $value)->first();

        return $block?->id;
    }

    private function makeClassroomId(string $name): string
    {
        if (! Classroom::where('id', $name)->exists()) {
            return $name;
        }

        $base = 'cr_'.(Str::slug($name) ?: 'room');
        $id = $base;
        $suffix = 1;

        while (Classroom::where('id', $id)->exists()) {
            $id = $base.'-'.$suffix;
            $suffix++;
        }

        return $id;
    }
}
