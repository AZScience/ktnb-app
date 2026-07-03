<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    public function __construct(private ActivityLogService $activityLog) {}

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $validated = $request->validateWithBag('updatePassword', [
                'current_password' => ['required', 'current_password'],
                'password' => ['required', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $exception) {
            if ($request->wantsJson()) {
                $errors = $exception->errors();
                $flat = $errors['updatePassword'] ?? $errors;

                return response()->json([
                    'message' => 'Vui lòng kiểm tra lại thông tin mật khẩu.',
                    'errors' => $flat,
                ], 422);
            }

            throw $exception;
        }

        $user = $request->user();
        $user->update([
            'password' => $validated['password'],
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);

        Auth::setUser($user->fresh());

        $this->activityLog->log('Đổi mật khẩu tài khoản', 'Auth', 'Mật khẩu đã được cập nhật');

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Đã đổi mật khẩu thành công.',
            ]);
        }

        return back()->with('status', 'password-updated');
    }
}
