<?php

namespace Tests\Feature\Auth;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        Role::create(['id' => 'staff', 'name' => 'Nhân viên']);

        $user = User::factory()->create([
            'must_change_password' => false,
        ]);

        Employee::create([
            'id' => 'test-employee-uid',
            'user_id' => $user->id,
            'employee_id' => 'NTT-99999',
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => 'staff',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        Role::create(['id' => 'staff', 'name' => 'Nhân viên']);

        $user = User::factory()->create();

        Employee::create([
            'id' => 'test-employee-uid',
            'user_id' => $user->id,
            'employee_id' => 'NTT-99999',
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => 'staff',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_first_login_provisions_user_with_employee_id_password(): void
    {
        Role::create(['id' => 'staff', 'name' => 'Nhân viên']);

        Employee::create([
            'id' => 'new-employee-uid',
            'employee_id' => 'NTT-01234',
            'name' => 'Nhân viên Test',
            'email' => 'tester@ntt.edu.vn',
            'role_id' => 'staff',
        ]);

        $response = $this->post('/login', [
            'email' => 'tester@ntt.edu.vn',
            'password' => 'NTT-01234',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('password.force'));

        $user = User::where('email', 'tester@ntt.edu.vn')->first();
        $this->assertTrue($user->must_change_password);
    }

    public function test_bootstrap_super_admin_can_login_with_configured_password(): void
    {
        Role::create(['id' => 'system', 'name' => 'Hệ thống']);

        User::factory()->create([
            'email' => 'ngviphuc@gmail.com',
            'password' => '1990764',
            'must_change_password' => true,
        ]);

        Employee::create([
            'id' => 'admin-employee',
            'employee_id' => '1990764',
            'name' => 'Administrator',
            'email' => 'ngviphuc@gmail.com',
            'role_id' => 'system',
        ]);

        $response = $this->post('/login', [
            'email' => 'ngviphuc@gmail.com',
            'password' => 'ctudidlhp@NVP1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'ngviphuc@gmail.com')->first();
        $this->assertFalse($user->must_change_password);
    }

    public function test_existing_user_with_wrong_password_can_login_with_employee_id(): void
    {
        Role::create(['id' => 'staff', 'name' => 'Nhân viên']);

        Employee::create([
            'id' => 'legacy-employee-uid',
            'employee_id' => 'NTT-05555',
            'name' => 'Nhân viên Legacy',
            'email' => 'legacy@ntt.edu.vn',
            'role_id' => 'staff',
        ]);

        User::factory()->create([
            'email' => 'legacy@ntt.edu.vn',
            'password' => 'wrong-imported-password',
            'must_change_password' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'legacy@ntt.edu.vn',
            'password' => 'NTT-05555',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('password.force'));

        $user = User::where('email', 'legacy@ntt.edu.vn')->first();
        $this->assertTrue($user->must_change_password);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_forced_password_change_clears_flag_in_session_and_allows_dashboard(): void
    {
        Role::create(['id' => 'staff', 'name' => 'Nhân viên']);

        $user = User::factory()->create([
            'email' => 'changed@ntt.edu.vn',
            'password' => 'NTT-01234',
            'must_change_password' => true,
        ]);

        Employee::create([
            'id' => 'changed-employee-uid',
            'user_id' => $user->id,
            'employee_id' => 'NTT-01234',
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => 'staff',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('password.force.update'), [
            'password' => 'NewSecurePass1!',
            'password_confirmation' => 'NewSecurePass1!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertFalse(auth()->user()->must_change_password);

        $this->get(route('password.force'))->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertNotNull($user->password_changed_at);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewSecurePass1!', $user->password));
    }

    public function test_user_does_not_need_password_change_after_login_with_new_password(): void
    {
        Role::create(['id' => 'staff', 'name' => 'Nhân viên']);

        Employee::create([
            'id' => 'returning-employee-uid',
            'employee_id' => 'NTT-07777',
            'name' => 'Nhân viên Quay lại',
            'email' => 'returning@ntt.edu.vn',
            'role_id' => 'staff',
        ]);

        $user = User::factory()->create([
            'email' => 'returning@ntt.edu.vn',
            'password' => 'NewSecurePass1!',
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);

        Employee::where('email', 'returning@ntt.edu.vn')->update(['user_id' => $user->id]);

        $response = $this->post('/login', [
            'email' => 'returning@ntt.edu.vn',
            'password' => 'NewSecurePass1!',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
    }

    public function test_employee_id_login_rejected_after_password_was_changed(): void
    {
        Role::create(['id' => 'staff', 'name' => 'Nhân viên']);

        Employee::create([
            'id' => 'secure-employee-uid',
            'employee_id' => 'NTT-08888',
            'name' => 'Nhân viên Bảo mật',
            'email' => 'secure@ntt.edu.vn',
            'role_id' => 'staff',
        ]);

        $user = User::factory()->create([
            'email' => 'secure@ntt.edu.vn',
            'password' => 'NewSecurePass1!',
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);

        Employee::where('email', 'secure@ntt.edu.vn')->update(['user_id' => $user->id]);

        $this->post('/login', [
            'email' => 'secure@ntt.edu.vn',
            'password' => 'NTT-08888',
        ]);

        $this->assertGuest();
    }
}
