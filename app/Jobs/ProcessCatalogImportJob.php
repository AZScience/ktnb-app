<?php

namespace App\Jobs;

use App\Models\Employee;
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
                'employees' => $this->importEmployees($progress, $authLogin),
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
}
