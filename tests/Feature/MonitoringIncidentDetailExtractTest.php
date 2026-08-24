<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringIncidentDetailExtractTest extends TestCase
{
    use RefreshDatabase;

    private function systemUser(): User
    {
        Role::create([
            'id' => 'system',
            'name' => 'Hệ thống',
            'permissions' => [],
        ]);

        $user = User::factory()->create([
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        Employee::create([
            'id' => 'admin-employee',
            'user_id' => $user->id,
            'employee_id' => 'NTT-00001',
            'name' => $user->name,
            'nickname' => 'Admin',
            'email' => $user->email,
            'role_id' => 'system',
        ]);

        return $user;
    }

    public function test_requires_photo_payload(): void
    {
        $user = $this->systemUser();

        $this->actingAs($user)
            ->postJson(route('monitoring.schedules.extract-incident-detail', ['module' => 'homeroom']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['photo']);
    }

    public function test_returns_failure_message_when_ai_key_missing(): void
    {
        $user = $this->systemUser();

        $response = $this->actingAs($user)->postJson(
            route('monitoring.schedules.extract-incident-detail', ['module' => 'homeroom']),
            ['photo' => 'data:image/jpeg;base64,'.base64_encode('fake-image')],
        );

        $response->assertOk();
        $this->assertFalse($response->json('success'));
        $this->assertNotSame('', (string) $response->json('message'));
    }

    public function test_guest_cannot_extract(): void
    {
        $this->postJson(route('monitoring.schedules.extract-incident-detail', ['module' => 'homeroom']), [
            'photo' => 'data:image/jpeg;base64,abc',
        ])->assertUnauthorized();
    }
}
