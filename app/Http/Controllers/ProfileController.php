<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileEmployeeUpdateRequest;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function edit(Request $request): View
    {
        $user = $request->user();
        $employee = $this->resolveEmployee($user);

        $positionName = null;
        $roleName = null;

        if ($employee?->position) {
            $positionName = Position::query()->find($employee->position)?->name ?? $employee->position;
        }
        if ($employee?->role_id) {
            $roleName = Role::query()->find($employee->role_id)?->name ?? $employee->role_id;
        }

        return view('profile.edit', [
            'user' => $user,
            'employee' => $employee,
            'positionName' => $positionName,
            'roleName' => $roleName ?: 'Nhân viên',
        ]);
    }

    public function update(ProfileEmployeeUpdateRequest $request): RedirectResponse|JsonResponse
    {
        $employee = $this->resolveEmployee($request->user());

        if (! $employee) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Không tìm thấy hồ sơ nhân viên.'], 404);
            }

            return back()->with('error', 'Không tìm thấy hồ sơ nhân viên liên kết với tài khoản.');
        }

        $employee->update($request->validated());

        $this->activityLog->log('Cập nhật hồ sơ cá nhân', 'Employee', $employee->name);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Đã cập nhật hồ sơ thành công.');
    }

    private function resolveEmployee($user): ?Employee
    {
        return Employee::query()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        auth()->logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
