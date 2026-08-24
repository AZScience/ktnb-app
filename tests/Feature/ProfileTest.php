<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithEmployee(array $userAttributes = [], array $employeeAttributes = []): User
    {
        $user = User::factory()->create($userAttributes);

        Employee::query()->create(array_merge([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'employee_id' => 'EMP-'.Str::upper(Str::random(6)),
            'name' => $user->name,
            'email' => $user->email,
        ], $employeeAttributes));

        return $user;
    }

    public function test_profile_page_is_displayed(): void
    {
        $user = $this->createUserWithEmployee();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = $this->createUserWithEmployee();
        $employee = Employee::query()->where('user_id', $user->id)->firstOrFail();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'nickname' => 'CB Test',
                'phone' => '0901234567',
                'address' => '123 Test Street',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $employee->refresh();

        $this->assertSame('CB Test', $employee->nickname);
        $this->assertSame('0901234567', $employee->phone);
        $this->assertSame('123 Test Street', $employee->address);
    }

    public function test_profile_update_requires_linked_employee(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'nickname' => 'Missing Employee',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile')
            ->assertSessionHas('error');
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
