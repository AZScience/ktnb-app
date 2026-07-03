<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\User;
use App\Services\AuthLoginService;
use Illuminate\Console\Command;

class SyncBootstrapSuperAdminsCommand extends Command
{
    protected $signature = 'users:sync-bootstrap-admins';

    protected $description = 'Đồng bộ mật khẩu bootstrap cho tài khoản admin tự động (ngviphuc@gmail.com)';

    public function handle(AuthLoginService $authLogin): int
    {
        $password = $authLogin->bootstrapSuperAdminPassword();
        if ($password === null) {
            $this->error('Chưa cấu hình SUPER_ADMIN_BOOTSTRAP_PASSWORD.');

            return self::FAILURE;
        }

        $emails = config('nttu.super_admin_bootstrap_emails', []);
        if ($emails === []) {
            $this->warn('Không có email bootstrap nào được cấu hình.');

            return self::SUCCESS;
        }

        foreach ($emails as $email) {
            $email = strtolower(trim($email));
            $employee = Employee::query()->whereRaw('LOWER(email) = ?', [$email])->first();

            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

            if (! $user) {
                $user = User::create([
                    'name' => $employee?->name ?? 'Administrator',
                    'email' => $email,
                    'password' => $password,
                    'must_change_password' => false,
                    'email_verified_at' => now(),
                ]);
                $this->line("  Tạo mới: {$email}");
            } else {
                $user->forceFill([
                    'password' => $password,
                    'must_change_password' => false,
                    'password_changed_at' => now(),
                ])->save();
                $this->line("  Cập nhật MK: {$email}");
            }

            if ($employee && ! $employee->user_id) {
                $employee->update(['user_id' => $user->id]);
                $this->line("  Liên kết NV: {$email}");
            }
        }

        $this->info('Hoàn tất đồng bộ admin bootstrap.');

        return self::SUCCESS;
    }
}
