<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForcedPasswordChangeController extends Controller
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user?->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.force-password-change');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user?->must_change_password) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        Auth::setUser($user->fresh());

        $this->activityLog->log('Đổi mật khẩu lần đầu', 'Auth', 'Người dùng đã thiết lập mật khẩu mới');

        return redirect()
            ->route('dashboard')
            ->with('success', 'Mật khẩu đã được cập nhật. Chào mừng bạn quay trở lại hệ thống!');
    }
}
