<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private function limitedUser(): User
    {
        Role::create([
            'id' => 'limited',
            'name' => 'Hạn chế',
            'permissions' => [
                '/dashboard' => ['access' => true, 'view' => true],
                '/personnel/positions' => ['access' => true, 'view' => true],
            ],
        ]);

        $user = User::factory()->create([
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        Employee::create([
            'id' => 'limited-employee',
            'user_id' => $user->id,
            'employee_id' => 'NTT-10001',
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => 'limited',
        ]);

        return $user;
    }

    public function test_user_without_module_access_gets_403_on_route(): void
    {
        $user = $this->limitedUser();

        $this->actingAs($user)
            ->get(route('employees.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('positions.index'))
            ->assertOk();
    }

    public function test_user_without_export_permission_gets_403_on_export_route(): void
    {
        $user = $this->limitedUser();

        $this->actingAs($user)
            ->get(route('positions.export'))
            ->assertForbidden();
    }

    public function test_profile_and_settings_remain_accessible(): void
    {
        $user = $this->limitedUser();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk();
    }
}
