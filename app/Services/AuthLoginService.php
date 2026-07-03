<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthLoginService
{
    public function isBootstrapSuperAdminEmail(string $email): bool
    {
        $email = strtolower(trim($email));
        $emails = array_map('strtolower', config('nttu.super_admin_bootstrap_emails', []));

        return in_array($email, $emails, true);
    }

    public function bootstrapSuperAdminPassword(): ?string
    {
        $password = config('nttu.super_admin_bootstrap_password');

        return is_string($password) && trim($password) !== '' ? $password : null;
    }

    public function passwordMatchesBootstrapSuperAdmin(string $email, string $password): bool
    {
        if (! $this->isBootstrapSuperAdminEmail($email)) {
            return false;
        }

        $bootstrap = $this->bootstrapSuperAdminPassword();
        if ($bootstrap === null) {
            return false;
        }

        return hash_equals($bootstrap, $password);
    }

    public function isSuperAdminEmail(string $email): bool
    {
        $email = strtolower(trim($email));
        $emails = array_map('strtolower', config('nttu.super_admin_emails', []));

        return in_array($email, $emails, true);
    }

    public function passwordMatchesEmployeeId(string $password, ?string $employeeId): bool
    {
        if ($employeeId === null || trim($employeeId) === '') {
            return false;
        }

        $password = trim($password);
        $employeeId = trim($employeeId);

        if (strcasecmp($password, $employeeId) === 0) {
            return true;
        }

        $passwordCore = $this->normalizeEmployeeIdToken($password);
        $employeeCore = $this->normalizeEmployeeIdToken($employeeId);

        return $passwordCore !== '' && $employeeCore !== '' && strcasecmp($passwordCore, $employeeCore) === 0;
    }

    public function findEmployeeByEmail(string $email): ?Employee
    {
        $email = strtolower(trim($email));

        return Employee::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first();
    }

    public function findUserByEmail(string $email): ?User
    {
        $email = strtolower(trim($email));

        return User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first();
    }

    /**
     * @throws ValidationException
     */
    public function attempt(string $email, string $password, bool $remember = false): User
    {
        $email = strtolower(trim($email));

        if ($this->isSuperAdminEmail($email)) {
            return $this->attemptSuperAdmin($email, $password, $remember);
        }

        $employee = $this->findEmployeeByEmail($email);

        if (! $employee) {
            throw ValidationException::withMessages([
                'email' => $this->failedLoginMessage(),
            ]);
        }

        $user = $this->findUserByEmail($email);

        if (! $user) {
            if (! $this->passwordMatchesEmployeeId($password, $employee->employee_id)) {
                throw ValidationException::withMessages([
                    'email' => $this->failedLoginMessage(),
                ]);
            }

            $user = $this->provisionUserFromEmployee($employee);

            Auth::login($user, $remember);

            return $user;
        }

        if (Hash::check($password, $user->password)) {
            Auth::login($user, $remember);

            return $user;
        }

        if ($this->attemptLegacyMd5($user, $password, $remember)) {
            return $user;
        }

        if ($this->passwordMatchesEmployeeId($password, $employee->employee_id)) {
            return $this->loginWithEmployeeIdPassword($user, $employee, $password, $remember);
        }

        throw ValidationException::withMessages([
            'email' => $this->failedLoginMessage(),
        ]);
    }

    /**
     * Super admin: không bắt buộc có employee, nhưng vẫn hỗ trợ đăng nhập lần đầu bằng mã NV.
     *
     * @throws ValidationException
     */
    private function attemptSuperAdmin(string $email, string $password, bool $remember): User
    {
        $employee = $this->findEmployeeByEmail($email);

        $user = $this->findUserByEmail($email);

        if (! $user && $employee && $this->passwordMatchesEmployeeId($password, $employee->employee_id)) {
            $user = $this->provisionUserFromEmployee($employee);
            Auth::login($user, $remember);

            return $user;
        }

        if (! $user && $this->isBootstrapSuperAdminEmail($email) && $this->passwordMatchesBootstrapSuperAdmin($email, $password)) {
            $user = User::create([
                'name' => $employee?->name ?? 'Administrator',
                'email' => $email,
                'password' => $password,
                'must_change_password' => false,
                'email_verified_at' => now(),
            ]);

            if ($employee && ! $employee->user_id) {
                $employee->update(['user_id' => $user->id]);
            }

            Auth::login($user, $remember);

            return $user;
        }

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => $this->failedLoginMessage(),
            ]);
        }

        if (Hash::check($password, $user->password)) {
            Auth::login($user, $remember);

            return $user;
        }

        if ($this->attemptLegacyMd5($user, $password, $remember)) {
            return $user;
        }

        if ($employee && $this->passwordMatchesEmployeeId($password, $employee->employee_id)) {
            return $this->loginWithEmployeeIdPassword($user, $employee, $password, $remember, forcePasswordChange: false);
        }

        if ($this->passwordMatchesBootstrapSuperAdmin($email, $password)) {
            if (! Hash::check($password, $user->password)) {
                $user->forceFill([
                    'password' => $password,
                    'must_change_password' => false,
                    'password_changed_at' => now(),
                ])->save();
            } elseif ($user->must_change_password) {
                $user->forceFill([
                    'must_change_password' => false,
                    'password_changed_at' => now(),
                ])->save();
            }

            Auth::login($user, $remember);

            return $user;
        }

        throw ValidationException::withMessages([
            'email' => $this->failedLoginMessage(),
        ]);
    }

    private function loginWithEmployeeIdPassword(
        User $user,
        Employee $employee,
        string $password,
        bool $remember,
        bool $forcePasswordChange = true,
    ): User {
        if (! $user->must_change_password && $user->password_changed_at !== null) {
            throw ValidationException::withMessages([
                'email' => $this->failedLoginMessage(),
            ]);
        }

        if ($user->must_change_password || ! Hash::check($password, $user->password)) {
            $user->forceFill([
                'password' => $password,
                'must_change_password' => true,
                'password_changed_at' => null,
            ])->save();
        }

        if (! $employee->user_id) {
            $employee->update(['user_id' => $user->id]);
        }

        Auth::login($user, $remember);

        return $user;
    }

    private function normalizeEmployeeIdToken(string $value): string
    {
        $value = trim($value);

        return preg_replace('/^NTT[\s\-_]*/i', '', $value) ?? $value;
    }

    private function failedLoginMessage(): string
    {
        return 'Email hoặc mật khẩu không đúng. Lần đầu đăng nhập: dùng email công ty và mã nhân viên làm mật khẩu (vd. NTT-02715).';
    }

    private function attemptLegacyMd5(User $user, string $password, bool $remember): bool
    {
        if (! $this->isLegacyPasswordHash($user->password)) {
            return false;
        }

        if (! hash_equals((string) $user->password, md5($password))) {
            return false;
        }

        $user->forceFill(['password' => $password])->save();
        Auth::login($user, $remember);

        return true;
    }

    private function isLegacyPasswordHash(?string $hash): bool
    {
        if (! $hash) {
            return false;
        }

        return ! str_starts_with($hash, '$2y$')
            && ! str_starts_with($hash, '$2a$')
            && ! str_starts_with($hash, '$2b$');
    }

    public function provisionUserFromEmployee(Employee $employee, bool $mustChangePassword = true): User
    {
        $email = strtolower(trim((string) $employee->email));

        $user = $this->findUserByEmail($email);

        if ($user) {
            if (! $employee->user_id) {
                $employee->update(['user_id' => $user->id]);
            }

            return $user;
        }

        $user = User::create([
            'name' => $employee->name,
            'email' => $email,
            'password' => $employee->employee_id,
            'must_change_password' => $mustChangePassword,
            'email_verified_at' => now(),
        ]);

        $employee->update(['user_id' => $user->id]);

        return $user;
    }
}
