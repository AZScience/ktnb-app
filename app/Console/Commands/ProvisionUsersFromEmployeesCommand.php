<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\AuthLoginService;
use Illuminate\Console\Command;

class ProvisionUsersFromEmployeesCommand extends Command
{
    protected $signature = 'users:provision-from-employees
                            {--force : Tạo lại tài khoản cho nhân viên đã có user nhưng chưa liên kết user_id}
                            {--reset-password : Đặt lại mật khẩu = mã nhân viên và bắt buộc đổi pass}';

    protected $description = 'Tạo tài khoản users từ bảng employees (mật khẩu mặc định = mã nhân viên)';

    public function handle(AuthLoginService $authLogin): int
    {
        $this->call('users:sync-bootstrap-admins');

        $this->ensureSuperAdminUsers($authLogin);

        $employees = Employee::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('name')
            ->get();

        if ($employees->isEmpty()) {
            $this->warn('Không có nhân viên nào trong hệ thống.');

            return self::SUCCESS;
        }

        $created = 0;
        $linked = 0;
        $skipped = 0;
        $reset = 0;

        foreach ($employees as $employee) {
            if ($authLogin->isSuperAdminEmail($employee->email)) {
                $this->line("  Bỏ qua super admin: {$employee->email}");
                $skipped++;

                continue;
            }

            $existingUser = \App\Models\User::query()
                ->whereRaw('LOWER(email) = ?', [strtolower(trim($employee->email))])
                ->first();

            if ($existingUser) {
                if (! $employee->user_id) {
                    $employee->update(['user_id' => $existingUser->id]);
                    $linked++;
                    $this->line("  Liên kết: {$employee->name} ({$employee->email})");
                } elseif ($this->option('reset-password')) {
                    $existingUser->forceFill([
                        'password' => $employee->employee_id,
                        'must_change_password' => true,
                        'password_changed_at' => null,
                    ])->save();
                    $reset++;
                    $this->line("  Đặt lại MK: {$employee->name} ({$employee->employee_id})");
                } else {
                    $skipped++;
                }

                continue;
            }

            $authLogin->provisionUserFromEmployee($employee);
            $created++;
            $this->line("  Tạo mới: {$employee->name} ({$employee->email}) — MK: {$employee->employee_id}");
        }

        $this->newLine();
        $this->info("Hoàn tất: {$created} tạo mới, {$linked} liên kết, {$reset} đặt lại MK, {$skipped} bỏ qua.");

        return self::SUCCESS;
    }

    private function ensureSuperAdminUsers(AuthLoginService $authLogin): void
    {
        $defaultPassword = (string) config('nttu.super_admin_default_password', '');
        if ($defaultPassword === '') {
            $this->warn('SUPER_ADMIN_DEFAULT_PASSWORD chưa cấu hình — bỏ qua tạo super admin mới không có nhân viên.');

            return;
        }

        foreach (config('nttu.super_admin_emails', []) as $email) {
            $email = strtolower(trim($email));

            if (\App\Models\User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
                continue;
            }

            $employee = Employee::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if ($employee) {
                $authLogin->provisionUserFromEmployee($employee, mustChangePassword: false);
                $this->line("  Super admin (từ NV): {$email}");

                continue;
            }

            \App\Models\User::create([
                'name' => 'Administrator',
                'email' => $email,
                'password' => $defaultPassword,
                'must_change_password' => false,
                'email_verified_at' => now(),
            ]);
            $this->line("  Super admin (tạo mới): {$email}");
        }
    }
}
